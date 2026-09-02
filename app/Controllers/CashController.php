<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\Format;
use Database;
use Throwable;

/**
 * app/Controllers/CashController.php
 * Pengendali Keuangan, Buku Kas & Bank, Kas Masuk/Keluar, Transfer Dana,
 * dan Live Valuasi Kas Persediaan Gudang (HPP).
 */
class CashController extends Controller
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Dashboard Buku Kas & Live Valuasi Persediaan
     */
    public function index(): void
    {
        Auth::requirePermission('cash.view_all');
        try {
            // 1. Ambil daftar Akun Kas & Bank
            $accounts = Database::fetchAll("
                SELECT ak.*,
                       (SELECT COUNT(*) FROM public.arus_kas ark WHERE ark.akun_kas_id = ak.id) as total_transaksi
                FROM public.akun_kas ak
                WHERE ak.status_aktif = TRUE
                ORDER BY ak.is_default_pos DESC, ak.nama_akun ASC
            ");

            // 2. Hitung Total Kas & Bank Cair
            $liquidCashTotal = array_sum(array_column($accounts, 'saldo_saat_ini'));

            // 3. Hitung Live Valuasi Kas Persediaan (Berdasarkan HPP Murni)
            // A. Bahan Mentah Curah (Bal / Kg)
            $rawItems = Database::fetchAll("
                SELECT id, kode_sku, nama_item, satuan_dasar, stok_fisik_saat_ini, harga_pokok_pembelian,
                       (stok_fisik_saat_ini * harga_pokok_pembelian) as subtotal
                FROM public.item
                WHERE tipe_item = 'bahan_mentah' AND status_aktif = TRUE
            ");
            $rawTotalValuation = array_sum(array_column($rawItems, 'subtotal'));
            $rawTotalQty = array_sum(array_column($rawItems, 'stok_fisik_saat_ini'));

            // B. Bahan Kemasan (Plastik, Label, Cup)
            $packItems = Database::fetchAll("
                SELECT id, kode_sku, nama_item, satuan_dasar, stok_fisik_saat_ini, harga_pokok_pembelian,
                       (stok_fisik_saat_ini * harga_pokok_pembelian) as subtotal
                FROM public.item
                WHERE tipe_item = 'bahan_kemas' AND status_aktif = TRUE
            ");
            $packTotalValuation = array_sum(array_column($packItems, 'subtotal'));
            $packTotalQty = array_sum(array_column($packItems, 'stok_fisik_saat_ini'));

            // C. Barang Jadi Siap Jual (Bungkus)
            $fgItems = Database::fetchAll("
                SELECT id, kode_sku, nama_item, satuan_dasar, stok_fisik_saat_ini, harga_pokok_pembelian,
                       (stok_fisik_saat_ini * harga_pokok_pembelian) as subtotal
                FROM public.item
                WHERE tipe_item = 'barang_jadi' AND status_aktif = TRUE
            ");
            $fgTotalValuation = array_sum(array_column($fgItems, 'subtotal'));
            $fgTotalQty = array_sum(array_column($fgItems, 'stok_fisik_saat_ini'));

            // Grand Total Kas Persediaan
            $totalInventoryValuation = $rawTotalValuation + $packTotalValuation + $fgTotalValuation;

            // 4. Hitung Total Piutang Toko Berjalan
            $receivablesTotal = (float)(Database::fetchOne("
                SELECT SUM(total_piutang_berjalan) as total FROM public.pelanggan WHERE status_aktif = TRUE
            ")['total'] ?? 0);

            // 5. Total Kekayaan Usaha (Kas Cair + Persediaan + Piutang)
            $totalBusinessWealth = $liquidCashTotal + $totalInventoryValuation + $receivablesTotal;

            // 6. Mutasi Terakhir (10 Transaksi)
            $recentMovements = Database::fetchAll("
                SELECT ark.*, ak.nama_akun
                FROM public.arus_kas ark
                JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id
                ORDER BY ark.tanggal_transaksi DESC, ark.dibuat_pada DESC
                LIMIT 10
            ");

            // 7. Master Kategori Biaya
            $categories = Database::fetchAll("SELECT * FROM public.kategori_biaya WHERE status_aktif = TRUE ORDER BY nama_kategori ASC");

            $this->view('cash.index', [
                'pageTitle' => 'Buku Kas & Valuasi Persediaan',
                'pageSubtitle' => 'Kelola Saldo Kas, Rekening Bank, Transfer & Nilai Aset Stok',
                'accounts' => $accounts,
                'liquidCashTotal' => $liquidCashTotal,
                'totalInventoryValuation' => $totalInventoryValuation,
                'receivablesTotal' => $receivablesTotal,
                'totalBusinessWealth' => $totalBusinessWealth,
                'rawValuation' => [
                    'count' => count($rawItems),
                    'total_qty' => $rawTotalQty,
                    'subtotal' => $rawTotalValuation,
                    'items' => $rawItems
                ],
                'packValuation' => [
                    'count' => count($packItems),
                    'total_qty' => $packTotalQty,
                    'subtotal' => $packTotalValuation,
                    'items' => $packItems
                ],
                'fgValuation' => [
                    'count' => count($fgItems),
                    'total_qty' => $fgTotalQty,
                    'subtotal' => $fgTotalValuation,
                    'items' => $fgItems
                ],
                'recentMovements' => $recentMovements,
                'categories' => $categories
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    /**
     * Halaman Transaksi Kas Masuk & Kas Keluar (Beban)
     */
    public function transactions(): void
    {
        Auth::requirePermission(['cash.view_all', 'cash.inflow', 'cash.outflow']);

        try {
            $startDate = $this->input('start_date', date('Y-m-01'));
            $endDate = $this->input('end_date', date('Y-m-d'));
            $accountId = $this->input('account_id', 'all');
            $type = $this->input('type', 'all');
            $category = $this->input('category', 'all');

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
                    $whereSql .= " AND ark.jenis_kas IN ('masuk', 'transfer_masuk')";
                } elseif ($type === 'keluar') {
                    $whereSql .= " AND ark.jenis_kas IN ('keluar', 'transfer_keluar')";
                } elseif ($type === 'transfer') {
                    $whereSql .= " AND ark.jenis_kas LIKE 'transfer_%'";
                }
            }

            if ($category !== 'all' && !empty($category)) {
                $whereSql .= " AND ark.kategori = :cat";
                $params['cat'] = $category;
            }

            $transactions = Database::fetchAll("
                SELECT ark.*, ak.nama_akun, ak.tipe_akun
                FROM public.arus_kas ark
                JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id
                {$whereSql}
                ORDER BY ark.tanggal_transaksi DESC, ark.dibuat_pada DESC
            ", $params);

            $accounts = Database::fetchAll("SELECT id, nama_akun, tipe_akun, saldo_saat_ini FROM public.akun_kas WHERE status_aktif = TRUE ORDER BY nama_akun ASC");
            $categories = Database::fetchAll("SELECT * FROM public.kategori_biaya WHERE status_aktif = TRUE ORDER BY nama_kategori ASC");

            // Rekap periode
            $totalInflow = 0;
            $totalOutflow = 0;
            foreach ($transactions as $t) {
                if ($t['jenis_kas'] === 'masuk' || $t['jenis_kas'] === 'transfer_masuk') {
                    $totalInflow += (float)$t['nominal'];
                } else {
                    $totalOutflow += (float)$t['nominal'];
                }
            }

            $this->view('cash.transactions', [
                'pageTitle' => 'Kas Masuk & Kas Keluar',
                'pageSubtitle' => 'Catat Pengeluaran Beban Operasional & Pendapatan Kas Lain',
                'transactions' => $transactions,
                'accounts' => $accounts,
                'categories' => $categories,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'account_id' => $accountId,
                    'type' => $type,
                    'category' => $category
                ],
                'summary' => [
                    'total_inflow' => $totalInflow,
                    'total_outflow' => $totalOutflow,
                    'net' => $totalInflow - $totalOutflow
                ]
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    /**
     * Laporan Arus Kas (Cash Flow) & Analisis Beban
     */
    public function reports(): void
    {
        Auth::requirePermission('cash.reports');

        try {
            $startDate = $this->input('start_date', date('Y-m-01'));
            $endDate = $this->input('end_date', date('Y-m-d'));

            $params = [
                'start' => $startDate,
                'end' => $endDate
            ];

            // 1. Seluruh transaksi arus kas pada periode
            $transactions = Database::fetchAll("
                SELECT ark.*, ak.nama_akun
                FROM public.arus_kas ark
                JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id
                WHERE ark.tanggal_transaksi >= :start AND ark.tanggal_transaksi <= :end
                ORDER BY ark.tanggal_transaksi ASC, ark.dibuat_pada ASC
            ", $params);

            // 2. Breakdown per Kategori Pengeluaran Beban
            $expenseBreakdown = Database::fetchAll("
                SELECT ark.kategori, SUM(ark.nominal) as total_nominal, COUNT(*) as total_transaksi
                FROM public.arus_kas ark
                WHERE ark.tanggal_transaksi >= :start AND ark.tanggal_transaksi <= :end
                  AND ark.jenis_kas = 'keluar'
                GROUP BY ark.kategori
                ORDER BY total_nominal DESC
            ", $params);

            // 3. Rekap Inflow vs Outflow
            $totalIn = 0;
            $totalOut = 0;
            foreach ($transactions as $t) {
                if ($t['jenis_kas'] === 'masuk') {
                    $totalIn += (float)$t['nominal'];
                } elseif ($t['jenis_kas'] === 'keluar') {
                    $totalOut += (float)$t['nominal'];
                }
            }

            // 4. Saldo Kas & Persediaan Saat Ini
            $liquidCashTotal = (float)(Database::fetchOne("SELECT SUM(saldo_saat_ini) as total FROM public.akun_kas WHERE status_aktif = TRUE")['total'] ?? 0);
            
            $rawTotal = (float)(Database::fetchOne("SELECT SUM(stok_fisik_saat_ini * harga_pokok_pembelian) as total FROM public.item WHERE tipe_item = 'bahan_mentah' AND status_aktif = TRUE")['total'] ?? 0);
            $packTotal = (float)(Database::fetchOne("SELECT SUM(stok_fisik_saat_ini * harga_pokok_pembelian) as total FROM public.item WHERE tipe_item = 'bahan_kemas' AND status_aktif = TRUE")['total'] ?? 0);
            $fgTotal = (float)(Database::fetchOne("SELECT SUM(stok_fisik_saat_ini * harga_pokok_pembelian) as total FROM public.item WHERE tipe_item = 'barang_jadi' AND status_aktif = TRUE")['total'] ?? 0);
            $inventoryTotal = $rawTotal + $packTotal + $fgTotal;

            $receivablesTotal = (float)(Database::fetchOne("SELECT SUM(total_piutang_berjalan) as total FROM public.pelanggan WHERE status_aktif = TRUE")['total'] ?? 0);

            $this->view('cash.reports', [
                'pageTitle' => 'Laporan Arus Kas & Analisis Beban',
                'pageSubtitle' => 'Laporan Pemasukan, Pengeluaran & Arus Keuangan Usaha',
                'startDate' => $startDate,
                'endDate' => $endDate,
                'transactions' => $transactions,
                'expenseBreakdown' => $expenseBreakdown,
                'totalIn' => $totalIn,
                'totalOut' => $totalOut,
                'netCashFlow' => $totalIn - $totalOut,
                'liquidCashTotal' => $liquidCashTotal,
                'inventoryTotal' => $inventoryTotal,
                'receivablesTotal' => $receivablesTotal,
                'totalWealth' => $liquidCashTotal + $inventoryTotal + $receivablesTotal
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    // =========================================================================
    // POST ACTIONS: AKUN KAS, KAS MASUK, KAS KELUAR, TRANSFER DANA
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

            // Jika akun baru diset default POS, nonaktifkan default di semua akun lain
            if ($isDefaultPos) {
                $pdo->exec("UPDATE public.akun_kas SET is_default_pos = FALSE");
            } else {
                // Pastikan jika ini adalah akun pertama, otomatis jadikan default POS
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

            // Jika ada saldo awal, catat di arus kas
            if ($saldoAwal > 0) {
                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                        keterangan, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :acc_id, CURRENT_DATE, 'masuk', 'modal_awal', :nom,
                        'Saldo Awal Pembukaan Akun', :saldo, :user_id, NOW()
                    )
                ")->execute([
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

            // Catat arus kas
            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                    keterangan, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :acc_id, :tgl, 'masuk', :kat, :nom,
                    :ket, :saldo, :user_id, NOW()
                )
            ")->execute([
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
                "Pencatatan Kas Masuk ({$kategori}) sebesar " . Format::rupiah($nominal) . ": {$keterangan}",
                'arus_kas',
                $accountId
            );

            $pdo->commit();
            $this->flashSuccess("Kas masuk sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil dicatat!");
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

            $stmtBal = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id");
            $stmtBal->execute(['id' => $accountId]);
            $currentBal = (float)($stmtBal->fetchColumn() ?? 0);

            // Potong saldo akun
            $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom, diubah_pada = NOW() WHERE id = :id")
                ->execute(['nom' => $nominal, 'id' => $accountId]);

            $newBalance = $currentBal - $nominal;

            // Catat arus kas
            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                    keterangan, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :acc_id, :tgl, 'keluar', :kat, :nom,
                    :ket, :saldo, :user_id, NOW()
                )
            ")->execute([
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
                "Pencatatan Kas Keluar ({$kategori}) sebesar " . Format::rupiah($nominal) . ": {$keterangan}",
                'arus_kas',
                $accountId
            );

            $pdo->commit();
            $this->flashSuccess("Kas keluar / beban sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil dicatat!");
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

        if (empty($sourceId) || empty($destId) || $sourceId === $destId || $nominal <= 0) {
            $this->flashError('Pilih akun sumber dan akun tujuan yang berbeda dengan nominal valid.');
            $this->redirect('/cash');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $sourceAcc = Database::fetchOne("SELECT nama_akun, saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $sourceId]);
            $destAcc = Database::fetchOne("SELECT nama_akun, saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $destId]);

            if (!$sourceAcc || !$destAcc) {
                throw new \Exception("Akun sumber atau tujuan tidak ditemukan.");
            }

            // 1. Potong Akun Sumber
            $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom, diubah_pada = NOW() WHERE id = :id")
                ->execute(['nom' => $nominal, 'id' => $sourceId]);
            $newSourceBal = (float)$sourceAcc['saldo_saat_ini'] - $nominal;

            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                    keterangan, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :acc_id, :tgl, 'transfer_keluar', 'Transfer Antar Kas', :nom,
                    :ket, :saldo, :user_id, NOW()
                )
            ")->execute([
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
                    akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                    keterangan, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :acc_id, :tgl, 'transfer_masuk', 'Transfer Antar Kas', :nom,
                    :ket, :saldo, :user_id, NOW()
                )
            ")->execute([
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
                "Transfer Kas sebesar " . Format::rupiah($nominal) . " dari '{$sourceAcc['nama_akun']}' ke '{$destAcc['nama_akun']}'",
                'arus_kas',
                $destId
            );

            $pdo->commit();
            $this->flashSuccess("Transfer Rp " . number_format($nominal, 0, ',', '.') . " dari '{$sourceAcc['nama_akun']}' ke '{$destAcc['nama_akun']}' berhasil!");
            $this->redirect('/cash');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal melakukan transfer kas: ' . $e->getMessage());
            $this->redirect('/cash');
        }
    }
}
