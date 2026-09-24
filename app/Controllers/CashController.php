<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\Format;
use App\Helpers\ExcelExport;
use App\Helpers\CashVoucher;
use Database;
use Throwable;
use PDO;

/**
 * app/Controllers/CashController.php
 * Pengendali Keuangan, Manajemen Akun Kas & Bank, Transaksi Kas & Transfer Dana,
 * serta Laporan Arus Kas (Cash Flow) & Valuasi Kekayaan Usaha (HPP Persediaan & Piutang).
 */
class CashController extends Controller
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    // =========================================================================
    // 1. HALAMAN /cash — FOKUS: MANAJEMEN AKUN KAS & REKENING BANK
    // =========================================================================

    /**
     * Dashboard Manajemen Master Akun Kas & Rekening Bank
     */
    public function index(): void
    {
        Auth::requirePermission(['cash.view_all', 'cash.manage_accounts']);
        try {
            $pdo = Database::getConnection();

            // 1. Ambil seluruh Akun Kas & Bank beserta total transaksinya
            $accounts = Database::fetchAll("
                SELECT ak.*,
                       COALESCE((SELECT COUNT(*) FROM public.arus_kas ark WHERE ark.akun_kas_id = ak.id), 0) as total_transaksi,
                       COALESCE((
                           SELECT SUM(CASE WHEN ark.jenis_kas IN ('masuk', 'transfer_masuk') THEN ark.nominal ELSE -ark.nominal END)
                           FROM public.arus_kas ark
                           WHERE ark.akun_kas_id = ak.id
                       ), 0) as kalkulasi_saldo_buku_besar
                FROM public.akun_kas ak
                ORDER BY ak.status_aktif DESC, ak.is_default_pos DESC, ak.nama_akun ASC
            ");

            // 2. Evaluasi status rekonsiliasi per akun kas
            $allReconciled = true;
            $liquidCashTotal = 0;
            $cashTunaiTotal = 0;
            $bankTotal = 0;
            $qrisDigitalTotal = 0;

            foreach ($accounts as &$acc) {
                $actualBal = (float)$acc['saldo_saat_ini'];
                $ledgerBal = (float)$acc['kalkulasi_saldo_buku_besar'];
                $diff = round($actualBal - $ledgerBal, 2);
                $acc['selisih_rekonsiliasi'] = $diff;
                $acc['is_reconciled'] = ($diff == 0.0);

                if (!$acc['is_reconciled']) {
                    $allReconciled = false;
                }

                // Hitung total saldo jika akun aktif
                if ($acc['status_aktif']) {
                    $liquidCashTotal += $actualBal;
                    if ($acc['tipe_akun'] === 'kas_tunai') {
                        $cashTunaiTotal += $actualBal;
                    } elseif ($acc['tipe_akun'] === 'bank') {
                        $bankTotal += $actualBal;
                    } elseif (in_array($acc['tipe_akun'], ['qris', 'kas_operasional', 'kas_kecil'])) {
                        $qrisDigitalTotal += $actualBal;
                    }
                }
            }
            unset($acc);

            $this->view('cash.index', [
                'pageTitle' => 'Manajemen Akun Kas & Rekening Bank',
                'pageSubtitle' => 'Kelola Master Rekening Bank, Laci Kasir, QRIS & Verifikasi Saldo Buku Kas',
                'accounts' => $accounts,
                'liquidCashTotal' => $liquidCashTotal,
                'cashTunaiTotal' => $cashTunaiTotal,
                'bankTotal' => $bankTotal,
                'qrisDigitalTotal' => $qrisDigitalTotal,
                'allReconciled' => $allReconciled
            ]);

        } catch (Throwable $e) {
            error_log("CashController index error: " . $e->getMessage());
            $this->flashError("Gagal memuat akun kas: " . $e->getMessage());
            $this->redirect('/');
        }
    }

    // =========================================================================
    // 2. HALAMAN /cash/transactions — FOKUS: TRANSAKSI KAS & TRANSFER DANA
    // =========================================================================

    /**
     * Halaman Seluruh Transaksi Kas Masuk, Kas Keluar & Transfer Dana
     */
    public function transactions(): void
    {
        Auth::requirePermission(['cash.view_all', 'cash.inflow', 'cash.outflow', 'cash.transfer']);

        try {
            $startDate = $this->input('start_date', date('Y-m-01'));
            $endDate = $this->input('end_date', date('Y-m-d'));
            $accountId = $this->input('account_id', 'all');
            $type = $this->input('type', 'all');
            $category = $this->input('category', 'all');
            $keyword = trim((string)$this->input('keyword', ''));
            $page = max(1, (int)$this->input('page', 1));
            $perPage = 25;

            $params = [
                'start' => $startDate,
                'end' => $endDate
            ];

            $whereSql = "WHERE ark.tanggal_transaksi >= :start AND ark.tanggal_transaksi <= :end";

            if ($accountId !== 'all' && !empty($accountId)) {
                $whereSql .= " AND ark.akun_kas_id = :acc";
                $params['acc'] = $accountId;
            }

            if ($type !== 'all' && !empty($type)) {
                if ($type === 'masuk') {
                    $whereSql .= " AND ark.jenis_kas = 'masuk'";
                } elseif ($type === 'keluar') {
                    $whereSql .= " AND ark.jenis_kas = 'keluar'";
                } elseif ($type === 'transfer') {
                    $whereSql .= " AND ark.jenis_kas IN ('transfer_masuk', 'transfer_keluar')";
                }
            }

            if ($category !== 'all' && !empty($category)) {
                $whereSql .= " AND ark.kategori = :cat";
                $params['cat'] = $category;
            }

            if (!empty($keyword)) {
                $whereSql .= " AND (ark.nomor_transaksi ILIKE :kw OR ark.keterangan ILIKE :kw)";
                $params['kw'] = "%{$keyword}%";
            }

            // 1. Rekap Ringkasan Periode (Inflow, Outflow, Transfer)
            $summaryData = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(CASE WHEN ark.jenis_kas = 'masuk' THEN ark.nominal ELSE 0 END), 0) as total_inflow,
                    COALESCE(SUM(CASE WHEN ark.jenis_kas = 'keluar' THEN ark.nominal ELSE 0 END), 0) as total_outflow,
                    COALESCE(SUM(CASE WHEN ark.jenis_kas = 'transfer_keluar' THEN ark.nominal ELSE 0 END), 0) as total_transfer
                FROM public.arus_kas ark
                {$whereSql}
            ", $params);

            $totalInflow = (float)($summaryData['total_inflow'] ?? 0);
            $totalOutflow = (float)($summaryData['total_outflow'] ?? 0);
            $totalTransfer = (float)($summaryData['total_transfer'] ?? 0);

            // 2. Hitung total baris untuk pagination
            $totalRows = (int)(Database::fetchOne("
                SELECT COUNT(*) as total
                FROM public.arus_kas ark
                {$whereSql}
            ", $params)['total'] ?? 0);

            $totalPages = max(1, (int)ceil($totalRows / $perPage));
            $offset = ($page - 1) * $perPage;

            // 3. Query baris data transaksi
            $transactions = Database::fetchAll("
                SELECT ark.*, ak.nama_akun, ak.tipe_akun, u.nama_lengkap as nama_user
                FROM public.arus_kas ark
                JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id
                LEFT JOIN public.pengguna u ON ark.dicatat_oleh = u.id
                {$whereSql}
                ORDER BY ark.tanggal_transaksi DESC, ark.dibuat_pada DESC
                LIMIT {$perPage} OFFSET {$offset}
            ", $params);

            // 4. Daftar Akun Kas Aktif & Master Kategori
            $accounts = Database::fetchAll("SELECT id, nama_akun, tipe_akun, saldo_saat_ini FROM public.akun_kas WHERE status_aktif = TRUE ORDER BY nama_akun ASC");
            $categories = Database::fetchAll("SELECT * FROM public.kategori_biaya WHERE status_aktif = TRUE ORDER BY nama_kategori ASC");

            $this->view('cash.transactions', [
                'pageTitle' => 'Transaksi Kas & Transfer Dana',
                'pageSubtitle' => 'Catat Pemasukan Kas, Beban Operasional & Mutasi Dana Antar Rekening',
                'transactions' => $transactions,
                'accounts' => $accounts,
                'categories' => $categories,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'account_id' => $accountId,
                    'type' => $type,
                    'category' => $category,
                    'keyword' => $keyword
                ],
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_rows' => $totalRows,
                    'total_pages' => $totalPages
                ],
                'summary' => [
                    'total_inflow' => $totalInflow,
                    'total_outflow' => $totalOutflow,
                    'total_transfer' => $totalTransfer,
                    'net' => $totalInflow - $totalOutflow
                ]
            ]);

        } catch (Throwable $e) {
            error_log("CashController transactions error: " . $e->getMessage());
            $this->flashError("Gagal memuat transaksi kas: " . $e->getMessage());
            $this->redirect('/cash');
        }
    }

    // =========================================================================
    // 3. HALAMAN /cash/reports — FOKUS: 100% LAPORAN ARUS KAS & VALUASI ASET
    // =========================================================================

    /**
     * Laporan Arus Kas Formal (Cash Flow Statement), Breakdown Beban & Valuasi Aset Bisnis
     */
    public function reports(): void
    {
        Auth::requirePermission('cash.reports');

        try {
            $startDate = $this->input('start_date', date('Y-m-01'));
            $endDate = $this->input('end_date', date('Y-m-d'));
            $accountId = $this->input('account_id', 'all');

            $params = [
                'start' => $startDate,
                'end' => $endDate
            ];

            $accFilterSql = "";
            $accFilterParams = [];
            if ($accountId !== 'all' && !empty($accountId)) {
                $accFilterSql = " AND ark.akun_kas_id = :acc";
                $params['acc'] = $accountId;
                $accFilterParams['acc'] = $accountId;
            }

            // 1. SALDO AWAL PERIODE (Beginning Balance sebelum start_date)
            $startParams = array_merge(['start' => $startDate], $accFilterParams);
            $beginningRow = Database::fetchOne("
                SELECT COALESCE(SUM(
                    CASE 
                        WHEN ark.jenis_kas IN ('masuk', 'transfer_masuk') THEN ark.nominal 
                        ELSE -ark.nominal 
                    END
                ), 0) as saldo_awal
                FROM public.arus_kas ark
                WHERE ark.tanggal_transaksi < :start {$accFilterSql}
            ", $startParams);
            $beginningBalance = (float)($beginningRow['saldo_awal'] ?? 0);

            // 2. Transaksi Arus Kas Periode (Untuk Agregasi Rekap Harian)
            $transactions = Database::fetchAll("
                SELECT ark.*, ak.nama_akun
                FROM public.arus_kas ark
                JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id
                WHERE ark.tanggal_transaksi >= :start AND ark.tanggal_transaksi <= :end {$accFilterSql}
                ORDER BY ark.tanggal_transaksi ASC, ark.dibuat_pada ASC
            ", $params);

            // 3. Breakdown per Kategori Pengeluaran Beban (Outflow)
            $expenseBreakdown = Database::fetchAll("
                SELECT ark.kategori, SUM(ark.nominal) as total_nominal, COUNT(*) as total_transaksi
                FROM public.arus_kas ark
                WHERE ark.tanggal_transaksi >= :start AND ark.tanggal_transaksi <= :end {$accFilterSql}
                  AND ark.jenis_kas = 'keluar'
                GROUP BY ark.kategori
                ORDER BY total_nominal DESC
            ", $params);

            // 4. Breakdown per Kategori Penerimaan Kas (Inflow)
            $incomeBreakdown = Database::fetchAll("
                SELECT ark.kategori, SUM(ark.nominal) as total_nominal, COUNT(*) as total_transaksi
                FROM public.arus_kas ark
                WHERE ark.tanggal_transaksi >= :start AND ark.tanggal_transaksi <= :end {$accFilterSql}
                  AND ark.jenis_kas = 'masuk'
                GROUP BY ark.kategori
                ORDER BY total_nominal DESC
            ", $params);

            // 5. Rekap Inflow vs Outflow vs Transfer
            $totalIn = 0;
            $totalOut = 0;
            $netTransfer = 0;
            foreach ($transactions as $t) {
                if ($t['jenis_kas'] === 'masuk') {
                    $totalIn += (float)$t['nominal'];
                } elseif ($t['jenis_kas'] === 'keluar') {
                    $totalOut += (float)$t['nominal'];
                } elseif ($t['jenis_kas'] === 'transfer_masuk') {
                    $netTransfer += (float)$t['nominal'];
                } elseif ($t['jenis_kas'] === 'transfer_keluar') {
                    $netTransfer -= (float)$t['nominal'];
                }
            }

            $netCashFlow = $totalIn - $totalOut;
            $endingBalance = $beginningBalance + $netCashFlow + ($accountId !== 'all' ? $netTransfer : 0);

            // 6. REKAPITULASI ARUS KAS HARIAN (DAILY CASH FLOW SUMMARY) — ZERO DUPLICATION
            $dailySummaryRaw = Database::fetchAll("
                SELECT 
                    ark.tanggal_transaksi,
                    COUNT(*) as total_transaksi,
                    COALESCE(SUM(CASE WHEN ark.jenis_kas = 'masuk' THEN ark.nominal ELSE 0 END), 0) as kas_masuk,
                    COALESCE(SUM(CASE WHEN ark.jenis_kas = 'keluar' THEN ark.nominal ELSE 0 END), 0) as kas_keluar,
                    COALESCE(SUM(CASE WHEN ark.jenis_kas = 'transfer_masuk' THEN ark.nominal WHEN ark.jenis_kas = 'transfer_keluar' THEN -ark.nominal ELSE 0 END), 0) as net_transfer
                FROM public.arus_kas ark
                WHERE ark.tanggal_transaksi >= :start AND ark.tanggal_transaksi <= :end {$accFilterSql}
                GROUP BY ark.tanggal_transaksi
                ORDER BY ark.tanggal_transaksi ASC
            ", $params);

            $runningDaily = $beginningBalance;
            $dailySummary = [];
            foreach ($dailySummaryRaw as $d) {
                $dIn = (float)$d['kas_masuk'];
                $dOut = (float)$d['kas_keluar'];
                $dTrf = ($accountId !== 'all') ? (float)$d['net_transfer'] : 0;
                $dNet = $dIn - $dOut + $dTrf;
                $runningDaily += $dNet;

                $dailySummary[] = [
                    'tanggal' => $d['tanggal_transaksi'],
                    'total_transaksi' => (int)$d['total_transaksi'],
                    'kas_masuk' => $dIn,
                    'kas_keluar' => $dOut,
                    'net_harian' => $dIn - $dOut,
                    'saldo_akhir_hari' => $runningDaily
                ];
            }

            // 7. NERACA LIKUIDITAS & LIVE VALUASI KEKAYAAN USAHA
            // A. Kas Cair
            $liquidCashTotal = (float)(Database::fetchOne("SELECT SUM(saldo_saat_ini) as total FROM public.akun_kas WHERE status_aktif = TRUE")['total'] ?? 0);

            // B. Live Valuasi Persediaan (Bahan Mentah, Kemasan, Barang Jadi)
            $rawItems = Database::fetchAll("
                SELECT id, kode_sku, nama_item, satuan_dasar, stok_fisik_saat_ini, harga_pokok_pembelian,
                       (stok_fisik_saat_ini * harga_pokok_pembelian) as subtotal
                FROM public.item
                WHERE tipe_item = 'bahan_mentah' AND status_aktif = TRUE
            ");
            $rawTotalValuation = array_sum(array_column($rawItems, 'subtotal'));
            $rawTotalQty = array_sum(array_column($rawItems, 'stok_fisik_saat_ini'));

            $packItems = Database::fetchAll("
                SELECT id, kode_sku, nama_item, satuan_dasar, stok_fisik_saat_ini, harga_pokok_pembelian,
                       (stok_fisik_saat_ini * harga_pokok_pembelian) as subtotal
                FROM public.item
                WHERE tipe_item = 'bahan_kemas' AND status_aktif = TRUE
            ");
            $packTotalValuation = array_sum(array_column($packItems, 'subtotal'));
            $packTotalQty = array_sum(array_column($packItems, 'stok_fisik_saat_ini'));

            $fgItems = Database::fetchAll("
                SELECT id, kode_sku, nama_item, satuan_dasar, stok_fisik_saat_ini, harga_pokok_pembelian,
                       (stok_fisik_saat_ini * harga_pokok_pembelian) as subtotal
                FROM public.item
                WHERE tipe_item = 'barang_jadi' AND status_aktif = TRUE
            ");
            $fgTotalValuation = array_sum(array_column($fgItems, 'subtotal'));
            $fgTotalQty = array_sum(array_column($fgItems, 'stok_fisik_saat_ini'));

            $inventoryTotal = $rawTotalValuation + $packTotalValuation + $fgTotalValuation;

            // C. Total Piutang Toko Berjalan
            $receivablesTotal = (float)(Database::fetchOne("SELECT SUM(total_piutang_berjalan) as total FROM public.pelanggan WHERE status_aktif = TRUE")['total'] ?? 0);

            // D. Total Kekayaan Usaha (Kas + Persediaan + Piutang)
            $totalWealth = $liquidCashTotal + $inventoryTotal + $receivablesTotal;

            // Daftar akun untuk filter
            $accounts = Database::fetchAll("SELECT id, nama_akun FROM public.akun_kas WHERE status_aktif = TRUE ORDER BY nama_akun ASC");

            $this->view('cash.reports', [
                'pageTitle' => 'Laporan Arus Kas & Valuasi Aset',
                'pageSubtitle' => 'Analisis Cash Flow Masuk-Keluar, Saldo Awal/Akhir, Serta Valuasi Persediaan HPP & Piutang',
                'startDate' => $startDate,
                'endDate' => $endDate,
                'accountId' => $accountId,
                'accounts' => $accounts,
                'beginningBalance' => $beginningBalance,
                'totalIn' => $totalIn,
                'totalOut' => $totalOut,
                'netCashFlow' => $netCashFlow,
                'netTransfer' => $netTransfer,
                'endingBalance' => $endingBalance,
                'dailySummary' => $dailySummary,
                'expenseBreakdown' => $expenseBreakdown,
                'incomeBreakdown' => $incomeBreakdown,
                'liquidCashTotal' => $liquidCashTotal,
                'inventoryTotal' => $inventoryTotal,
                'receivablesTotal' => $receivablesTotal,
                'totalWealth' => $totalWealth,
                'rawValuation' => [
                    'count' => count($rawItems),
                    'total_qty' => $rawTotalQty,
                    'subtotal' => $rawTotalValuation
                ],
                'packValuation' => [
                    'count' => count($packItems),
                    'total_qty' => $packTotalQty,
                    'subtotal' => $packTotalValuation
                ],
                'fgValuation' => [
                    'count' => count($fgItems),
                    'total_qty' => $fgTotalQty,
                    'subtotal' => $fgTotalValuation
                ]
            ]);

        } catch (Throwable $e) {
            error_log("CashController reports error: " . $e->getMessage());
            $this->flashError("Gagal memuat laporan arus kas: " . $e->getMessage());
            $this->redirect('/cash');
        }
    }

    // =========================================================================
    // POST ACTIONS: AKUN KAS, KAS MASUK, KAS KELUAR, TRANSFER DANA, DELETE AKUN
    // =========================================================================

    public function setDefaultPos(): void
    {
        Auth::requirePermission('cash.manage_accounts');

        $id = $this->input('id');
        if (!empty($id)) {
            try {
                $pdo = Database::getConnection();
                $pdo->beginTransaction();
                $pdo->exec("UPDATE public.akun_kas SET is_default_pos = FALSE");
                $pdo->prepare("UPDATE public.akun_kas SET is_default_pos = TRUE WHERE id = :id")->execute(['id' => $id]);
                $pdo->commit();
                
                $acc = Database::fetchOne("SELECT nama_akun FROM public.akun_kas WHERE id = :id", ['id' => $id]);
                $this->flashSuccess("Akun '{$acc['nama_akun']}' sekarang menjadi Default Kasir POS!");
            } catch (Throwable $e) {
                if (isset($pdo)) $pdo->rollBack();
                $this->flashError("Gagal mengubah default POS: " . $e->getMessage());
            }
        }
        $this->redirect('/cash');
    }

    public function storeAccount(): void
    {
        Auth::requirePermission('cash.manage_accounts');

        $namaAkun = trim((string)$this->input('nama_akun'));
        $tipeAkun = $this->input('tipe_akun', 'kas_tunai');
        $nomorRekening = trim((string)$this->input('nomor_rekening', '-'));
        $atasNama = trim((string)$this->input('atas_nama', '-'));
        $saldoAwal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('saldo_awal', '0'));
        $isDefaultPos = (bool)$this->input('is_default_pos', false);

        if (empty($namaAkun)) {
            $this->flashError('Nama akun kas / bank wajib diisi.');
            $this->redirect('/cash');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            if ($isDefaultPos) {
                $pdo->exec("UPDATE public.akun_kas SET is_default_pos = FALSE");
            } else {
                $existingCount = (int)($pdo->query("SELECT count(*) FROM public.akun_kas WHERE is_default_pos = TRUE")->fetchColumn() ?? 0);
                if ($existingCount === 0) {
                    $isDefaultPos = true;
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO public.akun_kas (
                    nama_akun, tipe_akun, nomor_rekening, atas_nama, saldo_saat_ini, is_default_pos, status_aktif, dibuat_pada, diubah_pada
                ) VALUES (
                    :nama, :tipe, :rek, :an, :saldo, :def_pos, TRUE, NOW(), NOW()
                ) RETURNING id
            ");
            $stmt->execute([
                'nama' => $namaAkun,
                'tipe' => $tipeAkun,
                'rek' => $nomorRekening ?: '-',
                'an' => $atasNama ?: '-',
                'saldo' => $saldoAwal,
                'def_pos' => $isDefaultPos ? 'true' : 'false'
            ]);
            $newAccountId = $stmt->fetchColumn();

            // Jika ada saldo awal, catat resmi di arus kas dengan nomor bukti kas
            if ($saldoAwal > 0) {
                $voucherNo = CashVoucher::generate('masuk', date('Y-m-d'), $pdo);
                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                        keterangan, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :nomor_tx, :acc_id, CURRENT_DATE, 'masuk', 'modal_awal', :nom,
                        'Saldo Awal Pembukaan Akun', :saldo, :user_id, NOW()
                    )
                ")->execute([
                    'nomor_tx' => $voucherNo,
                    'acc_id' => $newAccountId,
                    'nom' => $saldoAwal,
                    'saldo' => $saldoAwal,
                    'user_id' => Auth::id()
                ]);
            }

            $pdo->commit();
            $this->flashSuccess("Akun kas/bank '{$namaAkun}' berhasil ditambahkan!");
            $this->redirect('/cash');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal menambahkan akun kas: ' . $e->getMessage());
            $this->redirect('/cash');
        }
    }

    public function updateAccount(): void
    {
        Auth::requirePermission('cash.manage_accounts');

        $id = $this->input('id');
        $namaAkun = trim((string)$this->input('nama_akun'));
        $tipeAkun = $this->input('tipe_akun', 'kas_tunai');
        $nomorRekening = trim((string)$this->input('nomor_rekening', '-'));
        $atasNama = trim((string)$this->input('atas_nama', '-'));
        $isDefaultPos = (bool)$this->input('is_default_pos', false);
        $statusAktif = (bool)$this->input('status_aktif', true);

        if (empty($id) || empty($namaAkun)) {
            $this->flashError('Parameter akun kas tidak lengkap.');
            $this->redirect('/cash');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            if ($isDefaultPos) {
                $pdo->prepare("UPDATE public.akun_kas SET is_default_pos = FALSE WHERE id != :id")
                    ->execute(['id' => $id]);
            }

            $pdo->prepare("
                UPDATE public.akun_kas SET
                    nama_akun = :nama,
                    tipe_akun = :tipe,
                    nomor_rekening = :rek,
                    atas_nama = :an,
                    is_default_pos = :def_pos,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id
            ")->execute([
                'id' => $id,
                'nama' => $namaAkun,
                'tipe' => $tipeAkun,
                'rek' => $nomorRekening ?: '-',
                'an' => $atasNama ?: '-',
                'def_pos' => $isDefaultPos ? 'true' : 'false',
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            $pdo->commit();
            $this->flashSuccess("Akun kas '{$namaAkun}' berhasil diperbarui!");
            $this->redirect('/cash');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal memperbarui akun kas: ' . $e->getMessage());
            $this->redirect('/cash');
        }
    }

    public function deleteAccount(): void
    {
        Auth::requirePermission('cash.manage_accounts');

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID akun kas tidak valid.');
            $this->redirect('/cash');
            return;
        }

        try {
            $pdo = Database::getConnection();

            // Cek apakah akun memiliki transaksi di arus_kas
            $stmtTx = $pdo->prepare("SELECT COUNT(*) FROM public.arus_kas WHERE akun_kas_id = :id");
            $stmtTx->execute(['id' => $id]);
            $txCount = (int)$stmtTx->fetchColumn();

            if ($txCount > 0) {
                $this->flashError("Akun kas tidak dapat dihapus karena memiliki {$txCount} riwayat transaksi mutasi buku besar. Silakan ubah status akun menjadi Nonaktif melalui tombol Edit.");
                $this->redirect('/cash');
                return;
            }

            // Cek apakah akun merupakan default POS
            $stmtPos = $pdo->prepare("SELECT is_default_pos, nama_akun FROM public.akun_kas WHERE id = :id");
            $stmtPos->execute(['id' => $id]);
            $accRow = $stmtPos->fetch();

            if (!$accRow) {
                $this->flashError('Akun kas tidak ditemukan.');
                $this->redirect('/cash');
                return;
            }

            if ($accRow['is_default_pos']) {
                $this->flashError("Akun '{$accRow['nama_akun']}' adalah Default Kasir POS. Pindahkan status default POS ke akun lain terlebih dahulu.");
                $this->redirect('/cash');
                return;
            }

            $pdo->prepare("DELETE FROM public.akun_kas WHERE id = :id")->execute(['id' => $id]);

            ActivityLog::log(
                'keuangan',
                'DELETE',
                "Penghapusan Akun Kas kosong '{$accRow['nama_akun']}' (ID: {$id})",
                'akun_kas',
                $id
            );

            $this->flashSuccess("Akun kas '{$accRow['nama_akun']}' berhasil dihapus secara permanen.");
            $this->redirect('/cash');

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus akun kas: ' . $e->getMessage());
            $this->redirect('/cash');
        }
    }

    public function storeInflow(): void
    {
        Auth::requirePermission('cash.inflow');

        $accountId = $this->input('akun_kas_id');
        $tanggal = $this->input('tanggal_transaksi', date('Y-m-d'));
        $kategori = trim((string)$this->input('kategori', 'Pendapatan Lain'));
        $nominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('nominal', '0'));
        $keterangan = trim((string)$this->input('keterangan', 'Kas Masuk'));

        if (empty($accountId) || $nominal <= 0) {
            $this->flashError('Pilih akun kas dan masukkan nominal yang valid.');
            $this->redirect('/cash/transactions');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Tambah saldo akun
            $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + :nom, diubah_pada = NOW() WHERE id = :id")
                ->execute(['nom' => $nominal, 'id' => $accountId]);

            $stmtBal = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id");
            $stmtBal->execute(['id' => $accountId]);
            $newBalance = (float)($stmtBal->fetchColumn() ?? 0);

            // Generate nomor voucher kas
            $voucherNo = CashVoucher::generate('masuk', $tanggal, $pdo);

            // Catat arus kas
            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                    keterangan, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :nomor_tx, :acc_id, :tgl, 'masuk', :kat, :nom,
                    :ket, :saldo, :user_id, NOW()
                )
            ")->execute([
                'nomor_tx' => $voucherNo,
                'acc_id' => $accountId,
                'tgl' => $tanggal,
                'kat' => $kategori,
                'nom' => $nominal,
                'ket' => $keterangan,
                'saldo' => $newBalance,
                'user_id' => Auth::id()
            ]);

            ActivityLog::log(
                'keuangan',
                'INSERT',
                "Pencatatan Kas Masuk [{$voucherNo}] ({$kategori}) sebesar " . Format::rupiah($nominal) . ": {$keterangan}",
                'arus_kas',
                $accountId
            );

            $pdo->commit();
            $this->flashSuccess("Kas masuk [{$voucherNo}] sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil dicatat!");
            $this->redirect('/cash/transactions');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal mencatat kas masuk: ' . $e->getMessage());
            $this->redirect('/cash/transactions');
        }
    }

    public function storeOutflow(): void
    {
        Auth::requirePermission('cash.outflow');

        $accountId = $this->input('akun_kas_id');
        $tanggal = $this->input('tanggal_transaksi', date('Y-m-d'));
        $kategori = trim((string)$this->input('kategori', 'Operasional'));
        $nominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('nominal', '0'));
        $keterangan = trim((string)$this->input('keterangan', 'Kas Keluar'));

        if (empty($accountId) || $nominal <= 0) {
            $this->flashError('Pilih akun kas dan masukkan nominal pengeluaran yang valid.');
            $this->redirect('/cash/transactions');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $stmtBal = $pdo->prepare("SELECT id, nama_akun, saldo_saat_ini, tipe_akun FROM public.akun_kas WHERE id = :id FOR UPDATE");
            $stmtBal->execute(['id' => $accountId]);
            $acc = $stmtBal->fetch();

            if (!$acc) {
                throw new \Exception("Akun kas tidak ditemukan.");
            }

            $currentBal = (float)($acc['saldo_saat_ini'] ?? 0);
            if ($currentBal < $nominal) {
                throw new \Exception("Saldo akun kas '{$acc['nama_akun']}' tidak mencukupi untuk pengeluaran ini. Saldo saat ini: " . Format::rupiah($currentBal) . ", Nominal pengeluaran: " . Format::rupiah($nominal));
            }

            // Potong saldo akun
            $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom, diubah_pada = NOW() WHERE id = :id")
                ->execute(['nom' => $nominal, 'id' => $accountId]);

            $newBalance = $currentBal - $nominal;

            // Generate nomor voucher kas
            $voucherNo = CashVoucher::generate('keluar', $tanggal, $pdo);

            // Catat arus kas
            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                    keterangan, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :nomor_tx, :acc_id, :tgl, 'keluar', :kat, :nom,
                    :ket, :saldo, :user_id, NOW()
                )
            ")->execute([
                'nomor_tx' => $voucherNo,
                'acc_id' => $accountId,
                'tgl' => $tanggal,
                'kat' => $kategori,
                'nom' => $nominal,
                'ket' => $keterangan,
                'saldo' => $newBalance,
                'user_id' => Auth::id()
            ]);

            ActivityLog::log(
                'keuangan',
                'INSERT',
                "Pencatatan Kas Keluar [{$voucherNo}] ({$kategori}) sebesar " . Format::rupiah($nominal) . ": {$keterangan}",
                'arus_kas',
                $accountId
            );

            $pdo->commit();
            $this->flashSuccess("Kas keluar / beban [{$voucherNo}] sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil dicatat!");
            $this->redirect('/cash/transactions');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal mencatat kas keluar: ' . $e->getMessage());
            $this->redirect('/cash/transactions');
        }
    }

    public function storeTransfer(): void
    {
        Auth::requirePermission('cash.transfer');

        $sourceId = $this->input('source_account_id');
        $destId = $this->input('dest_account_id');
        $tanggal = $this->input('tanggal_transaksi', date('Y-m-d'));
        $nominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('nominal', '0'));
        $keterangan = trim((string)$this->input('keterangan', 'Transfer Antar Kas'));

        if (empty($sourceId) || empty($destId)) {
            $this->flashError('Akun kas sumber dan akun kas tujuan wajib dipilih.');
            $this->redirect('/cash/transactions');
            return;
        }

        if ($sourceId === $destId) {
            $this->flashError('Transfer ditolak: Akun kas sumber dan akun kas tujuan tidak boleh sama!');
            $this->redirect('/cash/transactions');
            return;
        }

        if ($nominal <= 0) {
            $this->flashError('Nominal transfer harus lebih besar dari Rp 0.');
            $this->redirect('/cash/transactions');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Kunci akun sumber dan tujuan secara deterministik
            $ids = [$sourceId, $destId];
            sort($ids);
            $stmtLock = $pdo->prepare("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE");
            $lockedAccounts = [];
            foreach ($ids as $lockId) {
                $stmtLock->execute(['id' => $lockId]);
                $accRow = $stmtLock->fetch();
                if ($accRow) {
                    $lockedAccounts[$accRow['id']] = $accRow;
                }
            }

            $sourceAcc = $lockedAccounts[$sourceId] ?? null;
            $destAcc = $lockedAccounts[$destId] ?? null;

            if (!$sourceAcc || !$destAcc) {
                throw new \Exception("Akun sumber atau tujuan tidak ditemukan.");
            }

            $sourceBal = (float)$sourceAcc['saldo_saat_ini'];
            if ($sourceBal < $nominal) {
                throw new \Exception("Saldo akun sumber '{$sourceAcc['nama_akun']}' tidak mencukupi untuk transfer ini. Saldo saat ini: " . Format::rupiah($sourceBal) . ", Nominal transfer: " . Format::rupiah($nominal));
            }

            // Generate nomor voucher transfer kembar (K untuk keluar sumber, M untuk masuk tujuan)
            $voucherBase = CashVoucher::generate('transfer', $tanggal, $pdo);
            $voucherOut = "{$voucherBase}-K";
            $voucherIn = "{$voucherBase}-M";

            // 1. Potong Akun Sumber
            $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom, diubah_pada = NOW() WHERE id = :id")
                ->execute(['nom' => $nominal, 'id' => $sourceId]);
            $newSourceBal = $sourceBal - $nominal;

            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                    keterangan, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :nomor_tx, :acc_id, :tgl, 'transfer_keluar', 'Transfer Antar Kas', :nom,
                    :ket, :saldo, :user_id, NOW()
                )
            ")->execute([
                'nomor_tx' => $voucherOut,
                'acc_id' => $sourceId,
                'tgl' => $tanggal,
                'nom' => $nominal,
                'ket' => "Transfer ke {$destAcc['nama_akun']} - {$keterangan}",
                'saldo' => $newSourceBal,
                'user_id' => Auth::id()
            ]);

            // 2. Tambah Akun Tujuan
            $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + :nom, diubah_pada = NOW() WHERE id = :id")
                ->execute(['nom' => $nominal, 'id' => $destId]);
            $newDestBal = (float)$destAcc['saldo_saat_ini'] + $nominal;

            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                    keterangan, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :nomor_tx, :acc_id, :tgl, 'transfer_masuk', 'Transfer Antar Kas', :nom,
                    :ket, :saldo, :user_id, NOW()
                )
            ")->execute([
                'nomor_tx' => $voucherIn,
                'acc_id' => $destId,
                'tgl' => $tanggal,
                'nom' => $nominal,
                'ket' => "Transfer dari {$sourceAcc['nama_akun']} - {$keterangan}",
                'saldo' => $newDestBal,
                'user_id' => Auth::id()
            ]);

            ActivityLog::log(
                'keuangan',
                'EXECUTE',
                "Transfer Kas [{$voucherBase}] sebesar " . Format::rupiah($nominal) . " dari '{$sourceAcc['nama_akun']}' ke '{$destAcc['nama_akun']}'",
                'arus_kas',
                $destId
            );

            $pdo->commit();
            $this->flashSuccess("Transfer [{$voucherBase}] sebesar Rp " . number_format($nominal, 0, ',', '.') . " dari '{$sourceAcc['nama_akun']}' ke '{$destAcc['nama_akun']}' berhasil!");
            $this->redirect('/cash/transactions');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal melakukan transfer kas: ' . $e->getMessage());
            $this->redirect('/cash/transactions');
        }
    }

    /**
     * Export Riwayat Transaksi Kas Masuk & Keluar ke Excel
     */
    public function exportTransactionsExcel(): void
    {
        Auth::requirePermission('cash.view_all');

        try {
            $startDate = $this->input('start_date', date('Y-m-01'));
            $endDate = $this->input('end_date', date('Y-m-d'));
            $accountId = $this->input('account_id');
            $type = $this->input('type');
            $category = $this->input('category');
            $keyword = trim((string)$this->input('keyword', ''));

            $sql = "
                SELECT ark.*, ak.nama_akun, u.nama_lengkap as nama_user
                FROM public.arus_kas ark
                JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id
                LEFT JOIN public.pengguna u ON ark.dicatat_oleh = u.id
                WHERE ark.tanggal_transaksi >= :start AND ark.tanggal_transaksi <= :end
            ";
            $params = ['start' => $startDate, 'end' => $endDate];

            if (!empty($accountId) && $accountId !== 'all') {
                $sql .= " AND ark.akun_kas_id = :account_id";
                $params['account_id'] = $accountId;
            }
            if (!empty($type) && $type !== 'all') {
                if ($type === 'masuk') {
                    $sql .= " AND ark.jenis_kas = 'masuk'";
                } elseif ($type === 'keluar') {
                    $sql .= " AND ark.jenis_kas = 'keluar'";
                } elseif ($type === 'transfer') {
                    $sql .= " AND ark.jenis_kas IN ('transfer_masuk', 'transfer_keluar')";
                }
            }
            if (!empty($category) && $category !== 'all') {
                $sql .= " AND ark.kategori = :category";
                $params['category'] = $category;
            }
            if (!empty($keyword)) {
                $sql .= " AND (ark.nomor_transaksi ILIKE :kw OR ark.keterangan ILIKE :kw)";
                $params['kw'] = "%{$keyword}%";
            }

            $sql .= " ORDER BY ark.tanggal_transaksi DESC, ark.dibuat_pada DESC";
            $transactions = Database::fetchAll($sql, $params);

            $headers = ['No', 'Tanggal', 'No Bukti Transaksi', 'Akun Kas / Bank', 'Jenis Kas', 'Kategori', 'Keterangan', 'Nominal (Rp)', 'Saldo Berjalan (Rp)', 'Dicatat Oleh'];
            $rows = [];
            $no = 1;
            $totalIn = 0;
            $totalOut = 0;

            foreach ($transactions as $t) {
                $isMasuk = in_array($t['jenis_kas'], ['masuk', 'transfer_masuk']);
                $nom = (float)$t['nominal'];
                if ($isMasuk) $totalIn += $nom;
                else $totalOut += $nom;

                $jenisLabel = match($t['jenis_kas']) {
                    'masuk' => 'KAS MASUK',
                    'keluar' => 'KAS KELUAR',
                    'transfer_masuk' => 'TRANSFER MASUK',
                    'transfer_keluar' => 'TRANSFER KELUAR',
                    default => strtoupper($t['jenis_kas'])
                };

                $rows[] = [
                    $no++,
                    date('d/m/Y', strtotime($t['tanggal_transaksi'])),
                    $t['nomor_transaksi'] ?: '-',
                    $t['nama_akun'],
                    $jenisLabel,
                    ucfirst(str_replace('_', ' ', (string)$t['kategori'])),
                    $t['keterangan'] ?? '-',
                    $nom,
                    (float)$t['saldo_berjalan'],
                    $t['nama_user'] ?? 'Sistem'
                ];
            }

            $rows[] = ['', '', '', '', '', '', 'TOTAL KAS MASUK (Rp):', $totalIn, '', ''];
            $rows[] = ['', '', '', '', '', '', 'TOTAL KAS KELUAR (Rp):', $totalOut, '', ''];
            $rows[] = ['', '', '', '', '', '', 'ARUS KAS BERSIH (NET) (Rp):', ($totalIn - $totalOut), '', ''];

            $cleanStart = str_replace('-', ' ', $startDate);
            $cleanEnd = str_replace('-', ' ', $endDate);
            ExcelExport::download("Mutasi Kas {$cleanStart} sd {$cleanEnd}.xlsx", $headers, $rows, "Mutasi Kas");
        } catch (Throwable $e) {
            $this->flashError('Gagal export data transaksi kas: ' . $e->getMessage());
            $this->redirect('/cash/transactions');
        }
    }

    /**
     * Export Laporan Arus Kas Periode ke Excel
     */
    public function exportReportsExcel(): void
    {
        Auth::requirePermission('cash.reports');

        try {
            $startDate = $this->input('start_date', date('Y-m-01'));
            $endDate = $this->input('end_date', date('Y-m-d'));
            $accountId = $this->input('account_id', 'all');

            $params = ['start' => $startDate, 'end' => $endDate];
            $accFilterSql = "";
            $accFilterParams = [];
            if ($accountId !== 'all' && !empty($accountId)) {
                $accFilterSql = " AND ark.akun_kas_id = :acc";
                $params['acc'] = $accountId;
                $accFilterParams['acc'] = $accountId;
            }

            // 1. Saldo Awal Periode
            $startParams = array_merge(['start' => $startDate], $accFilterParams);
            $beginningRow = Database::fetchOne("
                SELECT COALESCE(SUM(
                    CASE 
                        WHEN ark.jenis_kas IN ('masuk', 'transfer_masuk') THEN ark.nominal 
                        ELSE -ark.nominal 
                    END
                ), 0) as saldo_awal
                FROM public.arus_kas ark
                WHERE ark.tanggal_transaksi < :start {$accFilterSql}
            ", $startParams);
            $beginningBalance = (float)($beginningRow['saldo_awal'] ?? 0);

            // 2. Rekapitulasi Arus Kas Harian
            $dailySummaryRaw = Database::fetchAll("
                SELECT 
                    ark.tanggal_transaksi,
                    COUNT(*) as total_transaksi,
                    COALESCE(SUM(CASE WHEN ark.jenis_kas = 'masuk' THEN ark.nominal ELSE 0 END), 0) as kas_masuk,
                    COALESCE(SUM(CASE WHEN ark.jenis_kas = 'keluar' THEN ark.nominal ELSE 0 END), 0) as kas_keluar,
                    COALESCE(SUM(CASE WHEN ark.jenis_kas = 'transfer_masuk' THEN ark.nominal WHEN ark.jenis_kas = 'transfer_keluar' THEN -ark.nominal ELSE 0 END), 0) as net_transfer
                FROM public.arus_kas ark
                WHERE ark.tanggal_transaksi >= :start AND ark.tanggal_transaksi <= :end {$accFilterSql}
                GROUP BY ark.tanggal_transaksi
                ORDER BY ark.tanggal_transaksi ASC
            ", $params);

            $headers = ['No', 'Tanggal', 'Frekuensi Transaksi', 'Total Kas Masuk (Rp)', 'Total Kas Keluar (Rp)', 'Net Harian (Rp)', 'Saldo Akhir Hari (Rp)'];
            $rows = [];
            $no = 1;
            $totalIn = 0;
            $totalOut = 0;
            $runningDaily = $beginningBalance;

            // Baris Pembuka: Saldo Awal Periode
            $rows[] = [
                '-',
                date('d/m/Y', strtotime($startDate)),
                '-',
                0,
                0,
                0,
                $beginningBalance
            ];

            foreach ($dailySummaryRaw as $d) {
                $dIn = (float)$d['kas_masuk'];
                $dOut = (float)$d['kas_keluar'];
                $dTrf = ($accountId !== 'all') ? (float)$d['net_transfer'] : 0;
                $dNet = $dIn - $dOut + $dTrf;
                $runningDaily += $dNet;

                $totalIn += $dIn;
                $totalOut += $dOut;

                $rows[] = [
                    $no++,
                    date('d/m/Y', strtotime($d['tanggal_transaksi'])),
                    (int)$d['total_transaksi'] . ' Transaksi',
                    $dIn,
                    $dOut,
                    $dIn - $dOut,
                    $runningDaily
                ];
            }

            $endingBalance = $runningDaily;

            $rows[] = ['', '', 'TOTAL MUTASI PERIODE (Rp):', $totalIn, $totalOut, ($totalIn - $totalOut), ''];
            $rows[] = ['', '', 'SALDO KAS AKHIR PERIODE (Rp):', '', '', '', $endingBalance];

            $cleanStart = str_replace('-', ' ', $startDate);
            $cleanEnd = str_replace('-', ' ', $endDate);
            ExcelExport::download("Laporan Arus Kas {$cleanStart} sd {$cleanEnd}.xlsx", $headers, $rows, "Laporan Arus Kas");
        } catch (Throwable $e) {
            $this->flashError('Gagal export laporan kas: ' . $e->getMessage());
            $this->redirect('/cash/reports');
        }
    }
}
