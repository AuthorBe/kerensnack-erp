<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\Format;
use App\Helpers\ActivityLog;
use App\Helpers\PdfExport;
use App\Helpers\ExcelExport;
use App\Core\Router;
use Database;
use Throwable;

/**
 * app/Controllers/ConsignmentController.php
 * Pengendali Portal Konsinyasi Terpadu & 9 Sub-Halaman Sesuai PRD Final.
 */
class ConsignmentController extends Controller
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Helper: Cek apakah user adalah Sales (hanya punya akses toko binaan)
     */
    private function isSalesPersona(): bool
    {
        return !Auth::can('consignment.view_all');
    }

    /**
     * Helper: Ambil Karyawan ID sales yang sedang login (jika ada)
     */
    private function getLoggedInDriverId(): ?string
    {
        return Auth::employeeId();
    }

    /**
     * Helper: Proteksi akses Admin/Owner only
     */
    private function requireAdminOrOwner(): void
    {
        if (!Auth::can('consignment.view_all')) {
            $this->flashError('Kamu tidak memiliki izin mengakses halaman ini.');
            $this->redirect('/consignment');
            exit;
        }
    }

    /**
     * 1. Portal Konsinyasi Hub (GET /consignment)
     */
    public function portal(): void
    {
        Auth::requirePermission(['consignment.view_all', 'consignment.view_assigned']);
        try {
            $currentUser = Auth::user();
            $role = Auth::role();
            $driverId = $this->getLoggedInDriverId();
            $isSales = $this->isSalesPersona();
            $isOwner = Auth::isOwner();
            $isAdmin = Auth::isAdmin() && !$isOwner;

            $this->view('consignment.index', [
                'pageTitle' => 'Konsinyasi',
                'pageSubtitle' => 'Portal Terpadu Titip Jual Rak Toko',
                'currentUser' => $currentUser,
                'role' => $role,
                'isSales' => $isSales,
                'isAdmin' => $isAdmin,
                'isOwner' => $isOwner,
            ]);
        } catch (Throwable $e) {
            echo "Error Portal Konsinyasi: " . $e->getMessage();
        }
    }

    /**
     * 2. Sub-halaman: Stok Rak per Toko (GET /consignment/stok-rak)
     */
    public function stokRak(): void
    {
        Auth::requirePermission(['consignment.view_all', 'consignment.view_assigned']);

        try {
            $driverId = $this->getLoggedInDriverId();
            $isSales = $this->isSalesPersona();
            $isAdminOrOwner = Auth::can('consignment.view_all');

            // Query daftar toko konsinyasi
            $queryStores = "
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.alamat_lengkap,
                       p.sales_driver_id, k.nama_karyawan as nama_sales,
                       (SELECT MAX(skt.terakhir_opname_pada) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) as terakhir_opname,
                       (SELECT COUNT(*) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id AND skt.stok_titip_saat_ini > 0) as total_sku_titip,
                       (SELECT COALESCE(SUM(skt.stok_titip_saat_ini), 0) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) as total_pcs_titip
                FROM public.pelanggan p
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
            ";

            $params = [];
            if ($isSales && $driverId) {
                $queryStores .= " AND p.sales_driver_id = :driver_id";
                $params['driver_id'] = $driverId;
            }
            $queryStores .= " ORDER BY p.nama_toko ASC";
            $stores = Database::fetchAll($queryStores, $params);

            // Ambil rincian seluruh item rak untuk drill-down
            $shelfItems = Database::fetchAll("
                SELECT skt.id, skt.pelanggan_id, skt.item_id, skt.stok_titip_saat_ini, skt.terakhir_opname_pada,
                       i.nama_item, i.kode_sku, i.satuan_dasar, i.harga_pokok_pembelian as hpp,
                       p.nama_toko
                FROM public.stok_konsinyasi_toko skt
                JOIN public.item i ON skt.item_id = i.id
                JOIN public.pelanggan p ON skt.pelanggan_id = p.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                ORDER BY p.nama_toko ASC, i.nama_item ASC
            ");

            $itemsByStore = [];
            foreach ($shelfItems as $item) {
                $itemsByStore[$item['pelanggan_id']][] = $item;
            }

            $this->view('consignment.stok_rak', [
                'pageTitle' => 'Stok Rak per Toko',
                'pageSubtitle' => 'Monitoring Saldo Titipan Rak di Setiap Mitra',
                'stores' => $stores,
                'itemsByStore' => $itemsByStore,
                'isAdminOrOwner' => $isAdminOrOwner,
            ]);
        } catch (Throwable $e) {
            echo "Error Stok Rak: " . $e->getMessage();
        }
    }

    /**
     * 3. Sub-halaman: Opname / Kunjungan (GET /consignment/opname)
     * Step 1 (Pilih Toko) jika pelanggan_id kosong, Step 2 (Form Opname) jika ada pelanggan_id.
     */
    public function opname(): void
    {
        Auth::requirePermission(['consignment.opname_all', 'consignment.opname_assigned']);

        try {
            $storeId = (string)$this->input('pelanggan_id', '');
            $driverId = $this->getLoggedInDriverId();
            $isSales = $this->isSalesPersona();

            // STEP 1: Jika belum memilih toko, redirect ke Stok Rak per Toko
            if (empty($storeId)) {
                $this->redirect('/consignment/stok-rak');
                return;
            }

            // Scope Check: Jika hanya punya hak opname toko binaan
            if (!Auth::can('consignment.opname_all') && !Auth::isAssignedStore($storeId)) {
                $this->flashError('Akses Ditolak: Toko ini bukan merupakan toko binaan Anda.');
                $this->redirect('/consignment/stok-rak');
                return;
            }

            // STEP 2: Form Opname Toko Spesifik
            $customer = Database::fetchOne("
                SELECT p.*, k.nama_karyawan as nama_sales 
                FROM public.pelanggan p
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.id = :id AND p.is_konsinyasi = TRUE
            ", ['id' => $storeId]);

            if (!$customer) {
                $this->flashError('Toko konsinyasi tidak ditemukan.');
                $this->redirect('/consignment/opname');
                return;
            }

            // Ambil semua item yang ada di rak toko ini (termasuk yang 0 pcs)
            $shelfItems = Database::fetchAll("
                SELECT skt.item_id, skt.stok_titip_saat_ini, skt.stok_hilang_pending, skt.terakhir_opname_pada,
                       i.nama_item, i.kode_sku, i.satuan_dasar, COALESCE(gphl.harga_jual_pcs, 15000) as harga_jual_satuan
                FROM public.stok_konsinyasi_toko skt
                JOIN public.item i ON skt.item_id = i.id
                LEFT JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = i.grup_id AND gphl.level_harga = 1
                WHERE skt.pelanggan_id = :pelanggan_id
                ORDER BY (skt.stok_titip_saat_ini > 0) DESC, i.nama_item ASC
            ", ['pelanggan_id' => $storeId]);

            $savedInput = $_SESSION['_old_opname_input'][$storeId] ?? null;

            $items = [];
            if (!empty($shelfItems)) {
                $itemIds = array_column($shelfItems, 'item_id');
                $inClause = implode(',', array_fill(0, count($itemIds), '?'));
                $params = array_merge([$storeId], $itemIds);
                
                $priceRows = Database::fetchAll("
                    SELECT id as item_id, public.fn_hitung_harga_jual_item(id, ?) AS json_res
                    FROM public.item
                    WHERE id IN ($inClause)
                ", $params);
                
                $priceMap = [];
                foreach ($priceRows as $row) {
                    $priceMap[$row['item_id']] = json_decode($row['json_res'] ?? '{}', true);
                }

                foreach ($shelfItems as $si) {
                    $priceJson = $priceMap[$si['item_id']] ?? [];
                    $dealPrice = (float)($priceJson['harga_pcs_netto'] ?? $si['harga_jual_satuan']);

                    $sisaFisik = (int)$si['stok_titip_saat_ini'];
                    $rBagus = 0;
                    $rRusak = 0;
                    $laku = 0;
                    $selisih = 0;
                    $isTouched = false;

                    if ($savedInput && isset($savedInput[$si['item_id']])) {
                        $sisaFisik = (int)($savedInput[$si['item_id']]['sisa_fisik_di_rak'] ?? $sisaFisik);
                        $laku = (int)($savedInput[$si['item_id']]['jumlah_laku'] ?? 0);
                        $rBagus = (int)($savedInput[$si['item_id']]['retur_bagus'] ?? 0);
                        $rRusak = (int)($savedInput[$si['item_id']]['retur_rusak'] ?? 0);
                        $selisih = (int)($savedInput[$si['item_id']]['selisih_qty'] ?? $savedInput[$si['item_id']]['selisih'] ?? 0);
                        $isTouched = true;
                    }

                    $items[] = [
                        'item_id' => $si['item_id'],
                        'nama_item' => $si['nama_item'],
                        'kode_sku' => $si['kode_sku'],
                        'satuan_dasar' => $si['satuan_dasar'] ?? 'pcs',
                        'stok_titip_saat_ini' => (int)$si['stok_titip_saat_ini'],
                        'stok_hilang_pending' => (int)($si['stok_hilang_pending'] ?? 0),
                        'harga_deal' => $dealPrice,
                        'sisa_fisik_di_rak' => $sisaFisik,
                        'jumlah_laku' => $laku,
                        'retur_bagus' => $rBagus,
                        'retur_rusak' => $rRusak,
                        'selisih_qty' => $selisih,
                        'is_touched' => $isTouched
                    ];
                }
            }

            $this->view('consignment.opname', [
                'pageTitle' => 'Form Opname Rak Toko',
                'pageSubtitle' => 'Hitung Sisa Fisik & Retur Kunjungan: ' . $customer['nama_toko'],
                'step' => 2,
                'customer' => $customer,
                'items' => $items,
            ]);

        } catch (Throwable $e) {
            echo "Error Opname: " . $e->getMessage();
        }
    }

    /**
     * 4. Action: Proses Form Opname (POST /consignment/opname/proses)
     */
    public function opnameProses(): void
    {
        Auth::requirePermission(['consignment.opname_all', 'consignment.opname_assigned']);

        if (!$this->validateCsrf()) {
            $this->redirect('/consignment/opname');
            return;
        }

        $customerId = (string)$this->input('pelanggan_id');
        $itemsJson = (string)$this->input('items_json');
        $catatan = trim((string)$this->input('catatan', 'Opname Kunjungan Sales'));

        $currentUserId = Auth::id();
        $currentUser = Auth::user();
        $driverId = $currentUser['karyawan_id'] ?? null;

        if (empty($customerId)) {
            $this->flashError('Pilih toko konsinyasi terlebih dahulu.');
            $this->redirect('/consignment/opname');
            return;
        }

        // Scope check toko binaan
        if (!Auth::can('consignment.opname_all') && !Auth::isAssignedStore($customerId)) {
            $this->flashError('Akses Ditolak: Toko ini bukan merupakan toko binaan Anda.');
            $this->redirect('/consignment/stok-rak');
            return;
        }

        $items = json_decode($itemsJson, true);
        if (empty($items) || !is_array($items)) {
            $this->flashError('Mohon periksa dan masukkan minimal 1 rincian produk yang di-opname.');
            $this->redirect('/consignment/opname?pelanggan_id=' . urlencode($customerId));
            return;
        }

        // Simpan sementara input ke sesi untuk recovery jika gagal
        $_SESSION['_old_opname_input'][$customerId] = [];
        foreach ($items as $it) {
            if (!empty($it['item_id'])) {
                $_SESSION['_old_opname_input'][$customerId][$it['item_id']] = $it;
            }
        }

        // Simpan foto bukti kunjungan/retur jika diunggah (opsional)
        $fotoUrl = null;
        if (!empty($_FILES['foto_kunjungan']) && ($_FILES['foto_kunjungan']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $uploadRes = \App\Helpers\Upload::storeImage($_FILES['foto_kunjungan'], 'consignment_returns');
            if ($uploadRes['success']) {
                $fotoUrl = $uploadRes['path'];
            }
        }

        try {
            $rincianFormatted = [];
            foreach ($items as $it) {
                $itemId = $it['item_id'] ?? null;
                if (!$itemId) continue;

                $sisaFisik = max(0, (int)($it['sisa_fisik_di_rak'] ?? 0));
                $laku = isset($it['jumlah_laku']) ? max(0, (int)$it['jumlah_laku']) : null;
                $returBagus = max(0, (int)($it['retur_bagus'] ?? 0));
                $returRusak = max(0, (int)($it['retur_rusak'] ?? 0));
                $selisih = (int)($it['selisih_qty'] ?? $it['selisih'] ?? 0);

                $rincianFormatted[] = [
                    'item_id' => $itemId,
                    'sisa_fisik_di_rak' => $sisaFisik,
                    'jumlah_laku' => $laku,
                    'retur_bagus' => $returBagus,
                    'retur_rusak' => $returRusak,
                    'selisih_qty' => $selisih,
                ];
            }

            $res = Database::fetchOne("
                SELECT public.fn_proses_kunjungan_konsinyasi(:cust_id, :driver_id, :rincian::jsonb, :catatan, :foto, :user_id) AS json_res
            ", [
                'cust_id' => $customerId,
                'driver_id' => $driverId,
                'rincian' => json_encode($rincianFormatted),
                'catatan' => $catatan,
                'foto' => $fotoUrl,
                'user_id' => $currentUserId
            ]);

            $jsonResult = json_decode($res['json_res'] ?? '{}', true);

            if (empty($jsonResult['success'])) {
                throw new \Exception('Proses opname ditolak oleh database.');
            }

            // Hapus old input sesi karena sudah sukses
            unset($_SESSION['_old_opname_input'][$customerId]);

            $kunjunganId = $jsonResult['kunjungan_id'] ?? null;

            // Simpan catatan jika ada
            if (!empty($catatan) && !empty($kunjunganId)) {
                Database::execute("
                    UPDATE public.kunjungan_konsinyasi 
                    SET catatan = :catatan 
                    WHERE id = :id
                ", ['catatan' => $catatan, 'id' => $kunjunganId]);
            }

            $custName = Database::fetchOne("SELECT nama_toko FROM public.pelanggan WHERE id = :id", ['id' => $customerId])['nama_toko'] ?? 'Toko';
            ActivityLog::log(
                'logistik',
                'INSERT',
                "Sales menyelesaikan kunjungan opname di {$custName} ({$jsonResult['nomor_kunjungan']}).",
                'kunjungan_konsinyasi',
                $kunjunganId
            );

            $this->redirect('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)$kunjunganId));

        } catch (Throwable $e) {
            $this->flashError('Gagal memproses opname: ' . $e->getMessage() . ' (Data formulir Anda tetap tersimpan)');
            $this->redirect('/consignment/opname?pelanggan_id=' . urlencode($customerId));
        }
    }

    /**
     * 5. Sub-halaman: Hasil Kunjungan Opname (GET /consignment/opname/hasil)
     */
    public function hasilKunjungan(): void
    {
        Auth::requirePermission(['consignment.opname_all', 'consignment.opname_assigned', 'consignment.view_all', 'consignment.view_assigned']);

        $kunjunganId = (string)$this->input('kunjungan_id');

        if (empty($kunjunganId)) {
            $this->flashError('ID kunjungan tidak ditemukan.');
            $this->redirect('/consignment/opname');
            return;
        }

        try {
            $visit = Database::fetchOne("
                SELECT kk.*, p.nama_toko, p.kode_pelanggan, p.alamat_lengkap, p.nomor_whatsapp, p.nomor_telepon, p.nama_pemilik,
                       COALESCE(k.nama_karyawan, peng.nama_lengkap, 'Sales Lapangan') as sales_name,
                       COALESCE(k.posisi, 'Sales Lapangan') as sales_role,
                       peng.nama_lengkap as auditor_name,
                       COALESCE(peng.posisi, 'Auditor') as auditor_role,
                       COALESCE(pes.id, tk.pesanan_id) as pesanan_id,
                       pes.nomor_nota, pes.total_netto, pes.total_dibayar, pes.status_pembayaran, pes.sisa_tagihan, pes.tanggal_pesanan
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                LEFT JOIN public.v_karyawan_info k ON COALESCE(kk.sales_driver_id, p.sales_driver_id) = k.id
                LEFT JOIN public.tagihan_kunjungan tk ON tk.kunjungan_id = kk.id
                LEFT JOIN public.pesanan pes ON (kk.pesanan_id = pes.id OR tk.pesanan_id = pes.id)
                WHERE kk.id = :id
            ", ['id' => $kunjunganId]);

            if (!$visit) {
                $this->flashError('Data kunjungan tidak ditemukan.');
                $this->redirect('/consignment/opname');
                return;
            }

            // Scope Check: Jika user restricted sales persona, batasi hanya untuk toko binaannya
            if (!Auth::can(['consignment.opname_all', 'consignment.view_all']) && !Auth::isAssignedStore($visit['pelanggan_id'])) {
                $this->flashError('Akses Ditolak: Toko ini bukan merupakan toko binaan Anda.');
                $this->redirect('/consignment/riwayat-kunjungan');
                return;
            }

            $details = Database::fetchAll("
                SELECT rkk.*, i.nama_item, i.kode_sku, i.satuan_dasar
                FROM public.rincian_kunjungan_konsinyasi rkk
                JOIN public.item i ON rkk.item_id = i.id
                WHERE rkk.kunjungan_id = :id
                ORDER BY rkk.subtotal_laku DESC, i.nama_item ASC
            ", ['id' => $kunjunganId]);

            // Cek kunjungan lain dari toko ini yang belum ditagih
            $otherUnbilledVisits = Database::fetchAll("
                SELECT kk.id, kk.nomor_kunjungan, kk.tanggal_kunjungan, kk.total_laku_nominal
                FROM public.kunjungan_konsinyasi kk
                WHERE kk.pelanggan_id = :pelanggan_id
                  AND kk.total_laku_nominal > 0
                  AND NOT EXISTS (
                      SELECT 1 FROM public.tagihan_kunjungan tk WHERE tk.kunjungan_id = kk.id
                  )
                ORDER BY kk.tanggal_kunjungan DESC
            ", ['pelanggan_id' => $visit['pelanggan_id']]);

            // Akun kas untuk modal bayar (jika sudah ada nota dan belum lunas)
            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, tipe_akun, saldo_saat_ini 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE 
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            $canManageTagihan = Auth::can('consignment.piutang') && (Auth::isAdmin() || Auth::isOwner());
            $canSpotBill = Auth::can(['consignment.opname_all', 'consignment.opname_assigned', 'consignment.piutang']);

            $this->view('consignment.opname_hasil', [
                'pageTitle' => 'Hasil Kunjungan Konsinyasi',
                'pageSubtitle' => 'Rincian Stok & Hasil Opname Fisik Rak',
                'visit' => $visit,
                'details' => $details,
                'otherUnbilledVisits' => $otherUnbilledVisits,
                'cashAccounts' => $cashAccounts,
                'canManageTagihan' => $canManageTagihan,
                'canSpotBill' => $canSpotBill,
            ]);

        } catch (Throwable $e) {
            echo "Error Hasil Kunjungan: " . $e->getMessage();
        }
    }

    /**
     * 5b. Action: Bayar Langsung / Spot-Billing Tunai di Toko (POST /consignment/opname/bayar-langsung)
     * Sales/Driver atau Admin dapat langsung menerbitkan faktur untuk kunjungan ini dan mencatat pembayaran (lunas / cicil)
     */
    public function bayarLangsungKunjungan(): void
    {
        Auth::requirePermission(['consignment.opname_all', 'consignment.opname_assigned', 'consignment.piutang']);

        $kunjunganId = trim((string)$this->input('kunjungan_id', ''));
        $redirectUrl = '/consignment/opname/hasil?kunjungan_id=' . urlencode($kunjunganId);

        if (!$this->validateCsrf()) {
            $this->flashError('Sesi kedaluwarsa (CSRF token invalid). Silakan coba lagi.');
            $this->redirect($redirectUrl);
            return;
        }

        $rawNominal   = $this->input('nominal') ?? $this->input('nominal_bayar', '0');
        $nominal      = (float)preg_replace('/[^0-9]/', '', (string)$rawNominal);
        $accountId    = trim((string)$this->input('akun_kas_id', ''));
        $catatan      = trim((string)$this->input('catatan', ''));
        $tanggalBayar = trim((string)$this->input('tanggal_bayar', ''));
        if (empty($tanggalBayar)) {
            $tanggalBayar = date('Y-m-d');
        }

        if (empty($kunjunganId) || empty($accountId) || $nominal <= 0) {
            $this->flashError('Pilih rekening kas penerima dan masukkan nominal pembayaran yang valid (lebih dari Rp 0).');
            $this->redirect($redirectUrl);
            return;
        }

        try {
            // 1. Dapatkan info kunjungan beserta pesanan jika sudah ada
            $visit = Database::fetchOne("
                SELECT kk.id, kk.nomor_kunjungan, kk.pelanggan_id, kk.total_laku_nominal,
                       p.nama_toko,
                       COALESCE(pes.id, tk.pesanan_id) as pesanan_id,
                       pes.nomor_nota, pes.status_pembayaran, pes.sisa_tagihan
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.tagihan_kunjungan tk ON tk.kunjungan_id = kk.id
                LEFT JOIN public.pesanan pes ON (kk.pesanan_id = pes.id OR tk.pesanan_id = pes.id)
                WHERE kk.id = :id
            ", ['id' => $kunjunganId]);

            if (!$visit) {
                throw new \Exception('Data kunjungan konsinyasi tidak ditemukan.');
            }

            // Scope check: Jika user restricted sales persona, batasi hanya untuk toko binaannya
            if (!Auth::can(['consignment.opname_all', 'consignment.piutang']) && !Auth::isAssignedStore($visit['pelanggan_id'])) {
                throw new \Exception('Akses Ditolak: Toko ini bukan merupakan toko binaan Anda.');
            }

            if ((float)$visit['total_laku_nominal'] <= 0) {
                throw new \Exception('Kunjungan ini tidak memiliki nominal laku (nihil penjualan), tidak dapat ditagihkan.');
            }

            $pesananId = $visit['pesanan_id'];
            $nomorNota = $visit['nomor_nota'];

            // 2. Jika kunjungan belum memiliki faktur, terbitkan faktur otomatis untuk kunjungan ini
            if (empty($pesananId)) {
                $pgArray = '{' . $kunjunganId . '}';
                $genRes = Database::fetchOne("
                    SELECT public.fn_buat_tagihan_konsinyasi(:kunjungan_ids::uuid[], :user_id) AS json_res
                ", [
                    'kunjungan_ids' => $pgArray,
                    'user_id'       => Auth::id(),
                ]);

                $genJson = json_decode($genRes['json_res'] ?? '{}', true);
                if (empty($genJson['success'])) {
                    throw new \Exception('Gagal menerbitkan faktur tagihan untuk kunjungan ini.');
                }

                $pesananId = $genJson['pesanan_id'] ?? null;
                $nomorNota = $genJson['nomor_nota'] ?? null;
            }

            if (empty($pesananId)) {
                throw new \Exception('ID Faktur tidak valid setelah pemrosesan tagihan.');
            }

            // 3. Catat pembayaran
            $keterangan = !empty($catatan) 
                ? $catatan 
                : "Spot-billing tunai di toko oleh sales ({$visit['nama_toko']})";

            $payRes = Database::fetchOne("
                SELECT public.fn_catat_pembayaran_konsinyasi(:p, :a, :nom, :user_id, :ket, :tgl) as json_res
            ", [
                'p'       => $pesananId,
                'a'       => $accountId,
                'nom'     => $nominal,
                'user_id' => Auth::id(),
                'ket'     => $keterangan,
                'tgl'     => $tanggalBayar,
            ]);

            $payJson = json_decode($payRes['json_res'] ?? '{}', true);
            if (empty($payJson['success'])) {
                throw new \Exception('Pembayaran ditolak: ' . ($payJson['message'] ?? 'Silakan cek sisa tagihan'));
            }

            $stBayar = ($payJson['status_pembayaran'] ?? '') === 'lunas' ? 'LUNAS' : 'SEBAGIAN (Cicil)';
            $nominalFormatted = Format::rupiah($nominal);

            ActivityLog::log(
                'keuangan',
                'INSERT',
                "Spot-billing di toko: Pembayaran {$nominalFormatted} dicatat untuk faktur {$nomorNota} ({$visit['nama_toko']}). Status: {$stBayar}.",
                'pesanan',
                $pesananId
            );

            $this->flashSuccess("Pembayaran {$nominalFormatted} berhasil diterima di {$visit['nama_toko']}! Status: {$stBayar} (Faktur: {$nomorNota})");
            $this->redirect($redirectUrl);

        } catch (Throwable $e) {
            $this->flashError('Gagal memproses pembayaran di toko: ' . $e->getMessage());
            $this->redirect($redirectUrl);
        }
    }

    /**
     * 5c. Unduh Berita Acara Opname / Faktur Tagihan Resmi Konsinyasi dalam format PDF
     * (GET /consignment/opname/hasil/pdf?kunjungan_id=... atau ?pesanan_id=...)
     */
    public function notaPdf(): void
    {
        Auth::requirePermission(['consignment.opname_all', 'consignment.opname_assigned', 'consignment.view_all', 'consignment.view_assigned']);

        $kunjunganId = (string)$this->input('kunjungan_id');
        $pesananId = (string)$this->input('pesanan_id');

        if (empty($kunjunganId) && empty($pesananId)) {
            $this->flashError('Parameter ID kunjungan atau nota tidak ditemukan.');
            $this->redirect('/consignment/opname');
            return;
        }

        try {
            $visit = null;
            $details = [];
            $isInvoiced = false;

            // Kasus 1: Diberikan pesanan_id (Faktur Konsolidasi dari Menu Tagihan)
            if (!empty($pesananId)) {
                $visit = Database::fetchOne("
                    SELECT pes.id as pesanan_id, pes.nomor_nota, pes.total_netto, pes.total_dibayar, 
                           pes.status_pembayaran, pes.sisa_tagihan, pes.tanggal_pesanan,
                           pes.catatan,
                           p.id as pelanggan_id, p.nama_toko, p.kode_pelanggan, p.alamat_lengkap, 
                           p.nomor_whatsapp, p.nomor_telepon, p.nama_pemilik,
                           COALESCE(k.nama_karyawan, peng.nama_lengkap, 'Petugas ERP') as sales_name,
                           COALESCE(k.posisi, 'Sales Lapangan') as sales_role,
                           peng.nama_lengkap as auditor_name,
                           COALESCE(peng.posisi, 'Auditor') as auditor_role,
                           (SELECT kk.nomor_kunjungan FROM public.tagihan_kunjungan tk JOIN public.kunjungan_konsinyasi kk ON tk.kunjungan_id = kk.id WHERE tk.pesanan_id = pes.id ORDER BY kk.tanggal_kunjungan DESC LIMIT 1) as nomor_kunjungan,
                           pes.tanggal_pesanan as tanggal_kunjungan
                    FROM public.pesanan pes
                    JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                    LEFT JOIN public.v_karyawan_info k ON COALESCE(pes.sales_driver_id, p.sales_driver_id) = k.id
                    LEFT JOIN public.pengguna peng ON pes.dibuat_oleh = peng.id
                    WHERE pes.id = :id
                ", ['id' => $pesananId]);

                if (!$visit) {
                    throw new \Exception('Data faktur tagihan tidak ditemukan.');
                }

                $isInvoiced = true;

                // Ambil agregasi item pesanan resmi
                $details = Database::fetchAll("
                    SELECT ip.id, ip.item_id, ip.kuantitas_satuan_dasar as jumlah_laku_terjual,
                           ip.harga_satuan_deal, ip.subtotal as subtotal_laku,
                           i.nama_item, i.kode_sku, i.satuan_dasar,
                           0 as retur_rusak, 0 as retur_bagus, 0 as sisa_fisik_di_rak, 0 as stok_titip_awal, 0 as selisih_qty
                    FROM public.item_pesanan ip
                    JOIN public.item i ON ip.item_id = i.id
                    WHERE ip.pesanan_id = :id
                    ORDER BY ip.subtotal DESC, i.nama_item ASC
                ", ['id' => $pesananId]);

            } else {
                // Kasus 2: Diberikan kunjungan_id (Dari Halaman Hasil Opname)
                $sql = "
                    SELECT kk.*, p.nama_toko, p.kode_pelanggan, p.alamat_lengkap, p.nomor_whatsapp, p.nomor_telepon, p.nama_pemilik,
                           COALESCE(k.nama_karyawan, peng.nama_lengkap, 'Sales Lapangan') as sales_name,
                           COALESCE(k.posisi, 'Sales Lapangan') as sales_role,
                           peng.nama_lengkap as auditor_name,
                           COALESCE(peng.posisi, 'Auditor') as auditor_role,
                           COALESCE(pes.id, tk.pesanan_id) as pesanan_id,
                           pes.nomor_nota, pes.total_netto, pes.total_dibayar, pes.status_pembayaran, pes.sisa_tagihan, pes.tanggal_pesanan
                    FROM public.kunjungan_konsinyasi kk
                    JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                    LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                    LEFT JOIN public.v_karyawan_info k ON COALESCE(kk.sales_driver_id, p.sales_driver_id) = k.id
                    LEFT JOIN public.tagihan_kunjungan tk ON tk.kunjungan_id = kk.id
                    LEFT JOIN public.pesanan pes ON (kk.pesanan_id = pes.id OR tk.pesanan_id = pes.id)
                    WHERE kk.id = :id
                ";
                $visit = Database::fetchOne($sql, ['id' => $kunjunganId]);

                if (!$visit) {
                    throw new \Exception('Data kunjungan konsinyasi tidak ditemukan.');
                }

                $isInvoiced = !empty($visit['pesanan_id']) && !empty($visit['nomor_nota']);

                $details = Database::fetchAll("
                    SELECT rkk.*, i.nama_item, i.kode_sku, i.satuan_dasar
                    FROM public.rincian_kunjungan_konsinyasi rkk
                    JOIN public.item i ON rkk.item_id = i.id
                    WHERE rkk.kunjungan_id = :id
                    ORDER BY rkk.subtotal_laku DESC, i.nama_item ASC
                ", ['id' => $visit['id']]);
            }

            // Scope Check: Jika user restricted sales persona, pastikan dokumen adalah milik toko binaannya
            if (!Auth::can(['consignment.opname_all', 'consignment.view_all', 'consignment.piutang']) && !Auth::isAssignedStore($visit['pelanggan_id'])) {
                $this->flashError('Akses Ditolak: Dokumen ini bukan dari toko binaan Anda.');
                $this->redirect('/consignment/riwayat-kunjungan');
                return;
            }

            // Ambil data rekening kas utama/BCA jika ada
            $bankAccount = Database::fetchOne("
                SELECT nama_akun, nomor_rekening, atas_nama
                FROM public.akun_kas
                WHERE status_aktif = TRUE 
                  AND tipe_akun = 'bank'
                  AND nomor_rekening IS NOT NULL 
                  AND nomor_rekening != '' 
                  AND nomor_rekening != '-'
                ORDER BY (nama_akun ILIKE '%BCA%') DESC, id ASC
                LIMIT 1
            ");

            ob_start();
            extract([
                'visit' => $visit,
                'details' => $details,
                'bankAccount' => $bankAccount,
                'isInvoiced' => $isInvoiced,
                'isPdf' => true
            ]);
            require ROOT_PATH . '/views/consignment/nota_pdf.php';
            $html = ob_get_clean();

            if ($isInvoiced) {
                $cleanNota = !empty($visit['nomor_nota']) 
                    ? preg_replace('/[^A-Za-z0-9\-]/', '_', (string)$visit['nomor_nota']) 
                    : 'FAKTUR_' . date('Ymd_His');
                $filename = "Faktur-Konsinyasi-{$cleanNota}.pdf";
            } else {
                $cleanKunj = !empty($visit['nomor_kunjungan'])
                    ? preg_replace('/[^A-Za-z0-9\-]/', '_', (string)$visit['nomor_kunjungan'])
                    : 'OPNAME_' . date('Ymd_His');
                $filename = "Berita-Acara-Opname-{$cleanKunj}.pdf";
            }

            PdfExport::download($html, $filename, 'A4', 'portrait');

        } catch (Throwable $e) {
            $this->flashError('Gagal membuat dokumen PDF: ' . $e->getMessage());
            $this->redirect('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)($kunjunganId ?: '')));
        }
    }

    /**
     * 6. Action: Konfirmasi Terima Barang Kiriman di Toko (POST /consignment/konfirmasi-terima)
     */
    public function konfirmasiTerima(): void
    {
        Auth::requirePermission(['consignment.opname_all', 'consignment.opname_assigned', 'deliveries.update_all', 'deliveries.update_assigned']);

        if (!$this->validateCsrf()) {
            $this->redirect('/consignment/opname');
            return;
        }

        $suratJalanId = (string)$this->input('surat_jalan_id');
        $redirectUrl = (string)$this->input('redirect_url', '/consignment/opname');

        if (empty($suratJalanId)) {
            $this->flashError('Surat jalan tidak ditemukan.');
            $this->redirect($redirectUrl);
            return;
        }

        try {
            $res = Database::fetchOne("
                SELECT public.fn_konfirmasi_terima_pengiriman(:sj_id, :user_id) AS json_res
            ", [
                'sj_id' => $suratJalanId,
                'user_id' => Auth::id()
            ]);

            $jsonResult = json_decode($res['json_res'] ?? '{}', true);

            if (empty($jsonResult['success'])) {
                throw new \Exception($jsonResult['message'] ?? 'Konfirmasi pengiriman ditolak database.');
            }

            $sj = Database::fetchOne("
                SELECT sj.nomor_surat_jalan, p.nama_toko 
                FROM public.surat_jalan sj
                JOIN public.pesanan pes ON sj.pesanan_id = pes.id
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                WHERE sj.id = :id
            ", ['id' => $suratJalanId]);

            ActivityLog::log(
                'logistik',
                'UPDATE',
                "Driver/Sales mengonfirmasi terima barang di {$sj['nama_toko']} ({$sj['nomor_surat_jalan']}).",
                'surat_jalan',
                $suratJalanId
            );

            $this->flashSuccess("Pengiriman {$sj['nomor_surat_jalan']} berhasil dikonfirmasi! Stok rak toko {$sj['nama_toko']} sudah diperbarui.");
            $this->redirect($redirectUrl);

        } catch (Throwable $e) {
            $this->flashError('Gagal mengonfirmasi pengiriman: ' . $e->getMessage());
            $this->redirect($redirectUrl);
        }
    }

    /**
     * 7. Sub-halaman: Laporan Penjualan Konsinyasi (GET /consignment/laporan-penjualan)
     */
    public function laporanPenjualan(): void
    {
        Auth::requirePermission(['consignment.reports_all', 'consignment.reports_assigned']);

        try {
            $startDate  = (string)$this->input('start_date', date('Y-m-01'));
            $endDate    = (string)$this->input('end_date', date('Y-m-d'));
            $storeId    = (string)$this->input('pelanggan_id', '');
            $salesId    = (string)$this->input('sales_id', '');
            $driverId   = $this->getLoggedInDriverId();
            $isSales    = $this->isSalesPersona();

            // Daftar toko (untuk filter dropdown)
            $storeQuery  = "SELECT id, nama_toko, kode_pelanggan FROM public.pelanggan WHERE is_konsinyasi = TRUE AND status_aktif = TRUE";
            $storeParams = [];
            if ($isSales && $driverId) {
                $storeQuery .= " AND sales_driver_id = :driver_id";
                $storeParams['driver_id'] = $driverId;
            }
            $storeQuery .= " ORDER BY nama_toko ASC";
            $stores = Database::fetchAll($storeQuery, $storeParams);

            // Daftar sales (untuk filter dropdown, admin/owner saja)
            $salesList = [];
            if (!$isSales) {
                $salesList = Database::fetchAll("
                    SELECT id, nama_karyawan
                    FROM public.v_karyawan_info
                    WHERE posisi = 'sales' AND status_aktif = TRUE
                    ORDER BY nama_karyawan ASC
                ");
            }

            // Build WHERE clause shared params
            $whereExtra  = '';
            $queryParams = ['start_date' => $startDate, 'end_date' => $endDate];

            if ($isSales && $driverId) {
                $whereExtra .= " AND p.sales_driver_id = :driver_id";
                $queryParams['driver_id'] = $driverId;
            } else {
                if (!empty($storeId)) {
                    $whereExtra .= " AND p.id = :store_id";
                    $queryParams['store_id'] = $storeId;
                }
                if (!empty($salesId)) {
                    $whereExtra .= " AND k.id = :sales_id";
                    $queryParams['sales_id'] = $salesId;
                }
            }

            // Hitung MoM (Bulan Lalu)
            $prevStartDate = date('Y-m-d', strtotime($startDate . ' -1 month'));
            $prevEndDate = date('Y-m-d', strtotime($endDate . ' -1 month'));
            $prevQueryParams = ['start_date' => $prevStartDate, 'end_date' => $prevEndDate];
            if ($isSales && $driverId) {
                $prevQueryParams['driver_id'] = $driverId;
            } else {
                if (!empty($storeId)) $prevQueryParams['store_id'] = $storeId;
                if (!empty($salesId)) $prevQueryParams['sales_id'] = $salesId;
            }

            $sqlPrevMonth = "
                SELECT COALESCE(SUM(kk.total_laku_nominal), 0) as total_omzet_prev
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE kk.tanggal_kunjungan >= :start_date
                  AND kk.tanggal_kunjungan <= :end_date
                  AND p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                {$whereExtra}
            ";
            $prevTotalRow = Database::fetchOne($sqlPrevMonth, $prevQueryParams);
            $prevTotalOmzet = (float)($prevTotalRow['total_omzet_prev'] ?? 0);

            // Query 1: KPI per toko (untuk bar chart ranking + donut + tabel)
            $sqlPerStore = "
                SELECT
                    p.id as pelanggan_id,
                    p.nama_toko,
                    p.kode_pelanggan,
                    k.nama_karyawan as nama_sales,
                    COUNT(kk.id) as total_kunjungan,
                    COALESCE(SUM(kk.total_laku_nominal), 0) as total_omzet,
                    COALESCE(AVG(kk.total_laku_nominal), 0) as avg_per_kunjungan,
                    MAX(kk.tanggal_kunjungan) as last_visit_period,
                    (SELECT MAX(tanggal_kunjungan) FROM public.kunjungan_konsinyasi WHERE pelanggan_id = p.id) as last_visit
                FROM public.pelanggan p
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                LEFT JOIN public.kunjungan_konsinyasi kk
                    ON kk.pelanggan_id = p.id
                    AND kk.tanggal_kunjungan >= :start_date
                    AND kk.tanggal_kunjungan <= :end_date
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                {$whereExtra}
                GROUP BY p.id, p.nama_toko, p.kode_pelanggan, k.nama_karyawan
                ORDER BY total_omzet DESC, p.nama_toko ASC
            ";
            $storeStats = Database::fetchAll($sqlPerStore, $queryParams);

            // Hitung % kontribusi per toko di PHP
            $grandTotal = array_sum(array_column($storeStats, 'total_omzet'));
            foreach ($storeStats as &$s) {
                $s['persen_kontribusi'] = $grandTotal > 0
                    ? round((float)$s['total_omzet'] / $grandTotal * 100, 1)
                    : 0;
                $s['avg_per_kunjungan'] = round((float)$s['avg_per_kunjungan'], 0);
                
                // Cek Idle Status (>14 hari belum dikunjungi)
                $lastVisitDate = $s['last_visit'];
                $s['is_idle'] = false;
                $s['idle_days'] = 0;
                if ($lastVisitDate) {
                    $diff = date_diff(date_create($lastVisitDate), date_create(date('Y-m-d')));
                    $s['idle_days'] = $diff->days;
                    if ($diff->days > 14) {
                        $s['is_idle'] = true;
                    }
                } else {
                    $s['is_idle'] = true; // Belum pernah dikunjungi
                }
            }
            unset($s);

            // Query 2: Tren harian (untuk line chart)
            $sqlTrend = "
                SELECT
                    kk.tanggal_kunjungan,
                    COALESCE(SUM(kk.total_laku_nominal), 0) as total_omzet_hari,
                    COUNT(kk.id) as jumlah_kunjungan
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE kk.tanggal_kunjungan >= :start_date
                  AND kk.tanggal_kunjungan <= :end_date
                  AND p.is_konsinyasi = TRUE
                {$whereExtra}
                GROUP BY kk.tanggal_kunjungan
                ORDER BY kk.tanggal_kunjungan ASC
            ";
            $trendData = Database::fetchAll($sqlTrend, $queryParams);

            // KPI summary keseluruhan
            $totalOmzet      = (float)$grandTotal;
            $totalKunjungan  = (int)array_sum(array_column($storeStats, 'total_kunjungan'));
            $tokoAktif       = count(array_filter($storeStats, fn($s) => (int)$s['total_kunjungan'] > 0));
            $avgPerKunjungan = $totalKunjungan > 0 ? round($totalOmzet / $totalKunjungan, 0) : 0;

            // Kalkulasi MoM Growth %
            $momGrowth = 0;
            if ($prevTotalOmzet > 0) {
                $momGrowth = round((($totalOmzet - $prevTotalOmzet) / $prevTotalOmzet) * 100, 1);
            } elseif ($totalOmzet > 0) {
                $momGrowth = 100; // Jika bulan lalu 0 dan bulan ini ada omzet
            }

            $this->view('consignment.laporan_penjualan', [
                'pageTitle'       => 'Laporan Penjualan Konsinyasi',
                'pageSubtitle'    => 'Dashboard Performa Penjualan Semua Toko Konsinyasi',
                'storeStats'      => $storeStats,
                'trendData'       => $trendData,
                'stores'          => $stores,
                'salesList'       => $salesList,
                'startDate'       => $startDate,
                'endDate'         => $endDate,
                'selectedStoreId' => $storeId,
                'selectedSalesId' => $salesId,
                'totalOmzet'      => $totalOmzet,
                'totalKunjungan'  => $totalKunjungan,
                'tokoAktif'       => $tokoAktif,
                'avgPerKunjungan' => $avgPerKunjungan,
                'isSales'         => $isSales,
                'prevTotalOmzet'  => $prevTotalOmzet,
                'momGrowth'       => $momGrowth
            ]);

        } catch (Throwable $e) {
            echo "Error Laporan Penjualan: " . $e->getMessage();
        }
    }

    /**
     * AJAX endpoint: Mengambil data detail toko untuk pop-up modal
     */
    public function detailTokoAjax(): void
    {
        Auth::requirePermission(['consignment.reports_all', 'consignment.reports_assigned']);
        header('Content-Type: application/json');

        try {
            $pelangganId = (string)$this->input('pelanggan_id');
            $startDate   = (string)$this->input('start_date', date('Y-m-01'));
            $endDate     = (string)$this->input('end_date', date('Y-m-d'));
            
            if (!$pelangganId) {
                echo json_encode(['error' => 'ID Pelanggan tidak valid']);
                return;
            }

            // 1. Profil Toko
            $tokoInfo = Database::fetchOne("
                SELECT p.nama_toko, p.kode_pelanggan, p.alamat_lengkap, p.nomor_whatsapp, 
                       k.nama_karyawan as nama_sales
                FROM public.pelanggan p
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.id = :id
            ", ['id' => $pelangganId]);

            // 2. Trend Omzet
            $trendOmzet = Database::fetchAll("
                SELECT tanggal_kunjungan, total_laku_nominal as omzet
                FROM public.kunjungan_konsinyasi
                WHERE pelanggan_id = :id AND tanggal_kunjungan >= :sd AND tanggal_kunjungan <= :ed
                ORDER BY tanggal_kunjungan ASC
            ", ['id' => $pelangganId, 'sd' => $startDate, 'ed' => $endDate]);

            // 3. Top Items (dari kunjungan di periode tersebut)
            $topItems = Database::fetchAll("
                SELECT i.nama_item, SUM(rkk.jumlah_laku_terjual) as total_qty, SUM(rkk.subtotal_laku) as total_omzet
                FROM public.rincian_kunjungan_konsinyasi rkk
                JOIN public.kunjungan_konsinyasi kk ON rkk.kunjungan_id = kk.id
                JOIN public.item i ON rkk.item_id = i.id
                WHERE kk.pelanggan_id = :id AND kk.tanggal_kunjungan >= :sd AND kk.tanggal_kunjungan <= :ed
                GROUP BY i.nama_item
                ORDER BY total_omzet DESC
                LIMIT 5
            ", ['id' => $pelangganId, 'sd' => $startDate, 'ed' => $endDate]);

            // 4. Stok Rak Terakhir (dari 1 kunjungan paling akhir, terlepas dari filter tanggal)
            $lastVisit = Database::fetchOne("
                SELECT id, tanggal_kunjungan, nomor_kunjungan
                FROM public.kunjungan_konsinyasi 
                WHERE pelanggan_id = :id 
                ORDER BY tanggal_kunjungan DESC LIMIT 1
            ", ['id' => $pelangganId]);

            $stokRak = [];
            if ($lastVisit) {
                $stokRak = Database::fetchAll("
                    SELECT i.nama_item, rkk.sisa_fisik_di_rak, rkk.retur_bagus, rkk.retur_rusak, COALESCE(rkk.selisih_qty, 0) as selisih_qty
                    FROM public.rincian_kunjungan_konsinyasi rkk
                    JOIN public.item i ON rkk.item_id = i.id
                    WHERE rkk.kunjungan_id = :visit_id
                    ORDER BY rkk.sisa_fisik_di_rak DESC
                ", ['visit_id' => $lastVisit['id']]);
            }

            // 5. Monitoring Barang Hilang (Gantung / Pending Loss)
            $pendingLostItems = Database::fetchAll("
                SELECT i.nama_item, i.kode_sku, i.satuan_dasar, skt.stok_hilang_pending,
                       COALESCE(i.harga_pokok_pembelian, 10000) as hpp,
                       (skt.stok_hilang_pending * COALESCE(i.harga_pokok_pembelian, 10000)) as subtotal_hpp_hilang
                FROM public.stok_konsinyasi_toko skt
                JOIN public.item i ON skt.item_id = i.id
                WHERE skt.pelanggan_id = :id AND skt.stok_hilang_pending > 0
                ORDER BY skt.stok_hilang_pending DESC, i.nama_item ASC
            ", ['id' => $pelangganId]);

            $totalPcsHilang = (int)array_sum(array_column($pendingLostItems, 'stok_hilang_pending'));
            $totalNilaiHppHilang = (float)array_sum(array_column($pendingLostItems, 'subtotal_hpp_hilang'));

            // 6. Outstanding Tagihan
            $tagihan = Database::fetchAll("
                SELECT nomor_nota, tanggal_pesanan, sisa_tagihan
                FROM public.pesanan
                WHERE pelanggan_id = :id 
                  AND tipe_pembayaran = 'konsinyasi' 
                  AND sisa_tagihan > 0
                  AND status_pembayaran != 'dibatalkan'
                ORDER BY tanggal_pesanan ASC
            ", ['id' => $pelangganId]);

            echo json_encode([
                'toko_info' => $tokoInfo,
                'trend_omzet' => $trendOmzet,
                'top_items' => $topItems,
                'stok_rak' => $stokRak,
                'last_visit' => $lastVisit,
                'pending_lost' => [
                    'items' => $pendingLostItems,
                    'total_pcs' => $totalPcsHilang,
                    'total_nilai_hpp' => $totalNilaiHppHilang
                ],
                'tagihan' => $tagihan
            ]);

        } catch (Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * 8. Sub-halaman: Tagihan Konsinyasi (GET /consignment/tagihan)
     * 2 Tab: Tab 1 = Kunjungan belum ditagih (buat tagihan), Tab 2 = Daftar semua tagihan
     */
    public function tagihanIndex(): void
    {
        Auth::requirePermission('consignment.piutang');

        try {
            $isOwner = Auth::isOwner();
            $isAdmin = Auth::isAdmin() && !$isOwner;
            $activeTab = (string)$this->input('tab', 'buat');

            // Filter untuk tab Buat Tagihan
            $filterStoreId  = (string)$this->input('pelanggan_id', '');
            $filterStart    = (string)$this->input('start_date', date('Y-m-01'));
            $filterEnd      = (string)$this->input('end_date', date('Y-m-d'));

            // Filter untuk tab Daftar Tagihan
            $filterStatus   = (string)$this->input('status', '');

            // Daftar toko untuk filter dropdown
            $stores = Database::fetchAll("
                SELECT id, nama_toko, kode_pelanggan 
                FROM public.pelanggan 
                WHERE is_konsinyasi = TRUE AND status_aktif = TRUE
                ORDER BY nama_toko ASC
            ");

            // TAB 1: Kunjungan yang BELUM ditagih (total_laku > 0 dan belum ada di tagihan_kunjungan)
            $sqlUnbilled = "
                SELECT 
                    kk.id, kk.nomor_kunjungan, kk.tanggal_kunjungan, kk.total_laku_nominal,
                    p.nama_toko, p.kode_pelanggan, p.id as pelanggan_id,
                    COALESCE(kar.nama_karyawan, 'N/A') as nama_sales,
                    (SELECT COUNT(*) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_sku
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.v_karyawan_info kar ON kk.sales_driver_id = kar.id
                WHERE kk.total_laku_nominal > 0
                  AND NOT EXISTS (
                      SELECT 1 FROM public.tagihan_kunjungan tk WHERE tk.kunjungan_id = kk.id
                  )
                  AND kk.tanggal_kunjungan >= :start_date
                  AND kk.tanggal_kunjungan <= :end_date
            ";
            $unbilledParams = ['start_date' => $filterStart, 'end_date' => $filterEnd];

            if (!empty($filterStoreId)) {
                $sqlUnbilled .= " AND p.id = :pelanggan_id";
                $unbilledParams['pelanggan_id'] = $filterStoreId;
            }
            $sqlUnbilled .= " ORDER BY kk.tanggal_kunjungan DESC, p.nama_toko ASC";
            $unbilledVisits = Database::fetchAll($sqlUnbilled, $unbilledParams);

            // TAB 2: Semua tagihan konsinyasi (aktif + lunas + history)
            $sqlTagihan = "
                SELECT 
                    pes.id as pesanan_id, pes.nomor_nota, pes.tanggal_pesanan, pes.total_netto,
                    pes.total_dibayar, pes.sisa_tagihan, pes.status_pembayaran, pes.catatan,
                    p.id as pelanggan_id, p.nama_toko, p.kode_pelanggan, p.nomor_whatsapp,
                    COALESCE(kar.nama_karyawan, 'N/A') as nama_sales,
                    (SELECT COUNT(*) FROM public.tagihan_kunjungan tk WHERE tk.pesanan_id = pes.id) as jumlah_kunjungan
                FROM public.pesanan pes
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.v_karyawan_info kar ON pes.sales_driver_id = kar.id
                WHERE pes.tipe_pembayaran = 'konsinyasi'
                  AND pes.adalah_tagihan = TRUE
                  AND pes.status_pembayaran != 'dibatalkan'
            ";
            $tagihanParams = [];

            if (!empty($filterStatus)) {
                $sqlTagihan .= " AND pes.status_pembayaran = :status";
                $tagihanParams['status'] = $filterStatus;
            }
            $sqlTagihan .= " ORDER BY pes.tanggal_pesanan DESC, pes.dibuat_pada DESC";
            $tagihan = Database::fetchAll($sqlTagihan, $tagihanParams);

            // Akun kas untuk modal bayar
            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, tipe_akun, saldo_saat_ini, is_default_pos 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE 
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            $totalOutstanding = array_sum(array_column(
                array_filter($tagihan, fn($t) => in_array($t['status_pembayaran'], ['belum_lunas', 'sebagian'])),
                'sisa_tagihan'
            ));

            $this->view('consignment.tagihan', [
                'pageTitle'       => 'Tagihan Konsinyasi',
                'pageSubtitle'    => 'Buat & Kelola Tagihan Penjualan Toko Konsinyasi',
                'csrfToken'       => \App\Helpers\CSRF::token(),
                'unbilledVisits'  => $unbilledVisits,
                'tagihan'         => $tagihan,
                'stores'          => $stores,
                'cashAccounts'    => $cashAccounts,
                'totalOutstanding'=> $totalOutstanding,
                'filterStoreId'   => $filterStoreId,
                'filterStart'     => $filterStart,
                'filterEnd'       => $filterEnd,
                'filterStatus'    => $filterStatus,
                'activeTab'       => $activeTab,
                'isOwner'         => $isOwner,
                'isAdmin'         => $isAdmin,
            ]);

        } catch (Throwable $e) {
            echo "Error Tagihan Konsinyasi: " . $e->getMessage();
        }
    }

    /**
     * 8b. Action: Generate Tagihan Manual dari kunjungan terpilih (POST /consignment/tagihan/generate)
     */
    public function tagihanGenerate(): void
    {
        Auth::requirePermission('consignment.piutang');
        $this->requireAdminOrOwner();

        if (!$this->validateCsrf()) {
            $this->redirect('/consignment/tagihan');
            return;
        }

        $kunjunganIds = (array)$this->input('kunjungan_ids', []);
        $kunjunganIds = array_values(array_filter(array_unique($kunjunganIds)));

        if (empty($kunjunganIds)) {
            $this->flashError('Pilih minimal 1 kunjungan untuk dibuatkan tagihan.');
            $this->redirect('/consignment/tagihan');
            return;
        }

        try {
            // Format array untuk PostgreSQL: {uuid1,uuid2,...}
            $pgArray = '{' . implode(',', array_map('strval', $kunjunganIds)) . '}';

            $res = Database::fetchOne("
                SELECT public.fn_buat_tagihan_konsinyasi(:kunjungan_ids::uuid[], :user_id) AS json_res
            ", [
                'kunjungan_ids' => $pgArray,
                'user_id'       => Auth::id(),
            ]);

            $jsonResult = json_decode($res['json_res'] ?? '{}', true);

            if (empty($jsonResult['success'])) {
                throw new \Exception('Pembuatan tagihan ditolak oleh database.');
            }

            $nomor     = $jsonResult['nomor_nota'] ?? '-';
            $total     = Format::rupiah((float)($jsonResult['total_tagihan'] ?? 0));
            $jmlKunj   = (int)($jsonResult['jumlah_kunjungan'] ?? count($kunjunganIds));

            ActivityLog::log(
                'keuangan',
                'INSERT',
                "Tagihan konsinyasi {$nomor} dibuat manual dari {$jmlKunj} kunjungan. Total: {$total}.",
                'pesanan',
                $jsonResult['pesanan_id'] ?? null
            );

            $redirectUrl = (string)$this->input('redirect_url', '/consignment/tagihan?tab=daftar');
            $this->flashSuccess("Tagihan {$nomor} berhasil dibuat! Total: {$total} ({$jmlKunj} kunjungan).");
            $this->redirect($redirectUrl);

        } catch (Throwable $e) {
            $this->flashError('Gagal membuat tagihan: ' . $e->getMessage());
            $redirectUrl = (string)$this->input('redirect_url', '/consignment/tagihan');
            $this->redirect($redirectUrl);
        }
    }

    /**
     * 8c. Action: Catat Pembayaran Tagihan (POST /consignment/tagihan/bayar)
     */
    public function tagihanBayar(): void
    {
        Auth::requirePermission('consignment.piutang');

        $redirectUrl = (string)$this->input('redirect_url', '/consignment/tagihan?tab=daftar');

        if (!$this->validateCsrf()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Sesi kedaluwarsa (CSRF token invalid). Silakan refresh halaman.'], 403);
                return;
            }
            $this->flashError('Sesi kedaluwarsa. Silakan muat ulang halaman dan coba lagi.');
            $this->redirect($redirectUrl);
            return;
        }

        $pesananId    = trim((string)$this->input('pesanan_id', ''));
        $accountId    = trim((string)$this->input('akun_kas_id', ''));
        $rawNominal   = $this->input('nominal') ?? $this->input('nominal_bayar', '0');
        $nominal      = (float)preg_replace('/[^0-9]/', '', (string)$rawNominal);
        $tanggalBayar = trim((string)$this->input('tanggal_bayar', ''));
        if (empty($tanggalBayar)) {
            $tanggalBayar = date('Y-m-d');
        }

        $keterangan   = trim((string)($this->input('keterangan') ?? $this->input('catatan', '')));

        if (empty($pesananId) || empty($accountId) || $nominal <= 0) {
            $msg = 'Pilih faktur tagihan, rekening kas penerima, dan masukkan nominal pembayaran yang valid (lebih dari Rp 0).';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg], 400);
                return;
            }
            $this->flashError($msg);
            $this->redirect($redirectUrl);
            return;
        }

        try {
            $res = Database::fetchOne("
                SELECT public.fn_catat_pembayaran_konsinyasi(:p, :a, :nom, :user_id, :ket, :tgl) as json_res
            ", [
                'p'       => $pesananId,
                'a'       => $accountId,
                'nom'     => $nominal,
                'user_id' => Auth::id(),
                'ket'     => !empty($keterangan) ? $keterangan : null,
                'tgl'     => $tanggalBayar,
            ]);

            $jsonResult = json_decode($res['json_res'] ?? '{}', true);

            if (empty($jsonResult['success'])) {
                throw new \Exception('Pembayaran ditolak oleh sistem database.');
            }

            $statusText = ($jsonResult['status_pembayaran'] ?? '') === 'lunas' ? 'LUNAS' : 'SEBAGIAN (Cicil)';
            $sisaRp     = Format::rupiah((float)($jsonResult['sisa_tagihan'] ?? 0));
            $notaNum    = htmlspecialchars($jsonResult['nomor_nota'] ?? '');

            ActivityLog::log(
                'keuangan',
                'INSERT',
                "Pembayaran tagihan konsinyasi {$notaNum} sebesar " . Format::rupiah($nominal) . " dicatat ({$statusText}) pada tanggal {$tanggalBayar}.",
                'pesanan',
                $pesananId
            );

            $successMsg = "Pembayaran faktur {$notaNum} sebesar " . Format::rupiah($nominal) . " berhasil dicatat! Status: {$statusText} (Sisa Piutang: {$sisaRp}).";

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => $successMsg,
                    'data'    => $jsonResult
                ]);
                return;
            }

            $this->flashSuccess($successMsg);
            $this->redirect($redirectUrl);

        } catch (Throwable $e) {
            $errMsg = 'Gagal mencatat pembayaran: ' . $e->getMessage();
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $errMsg], 500);
                return;
            }
            $this->flashError($errMsg);
            $this->redirect($redirectUrl);
        }
    }

    /**
     * 8d. Export Daftar Tagihan ke Excel (GET /consignment/tagihan/export-excel)
     */
    public function tagihanExportExcel(): void
    {
        Auth::requirePermission('consignment.piutang');

        try {
            $filterStatus = (string)$this->input('status', '');

            $sql = "
                SELECT 
                    pes.nomor_nota, pes.tanggal_pesanan, pes.total_netto,
                    pes.total_dibayar, pes.sisa_tagihan, pes.status_pembayaran,
                    p.nama_toko, p.kode_pelanggan, p.nomor_whatsapp,
                    COALESCE(kar.nama_karyawan, 'N/A') as nama_sales,
                    (SELECT COUNT(*) FROM public.tagihan_kunjungan tk WHERE tk.pesanan_id = pes.id) as jumlah_kunjungan
                FROM public.pesanan pes
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.v_karyawan_info kar ON pes.sales_driver_id = kar.id
                WHERE pes.tipe_pembayaran = 'konsinyasi'
                  AND pes.adalah_tagihan = TRUE
                  AND pes.status_pembayaran != 'dibatalkan'
            ";
            $params = [];

            if (!empty($filterStatus)) {
                $sql .= " AND pes.status_pembayaran = :status";
                $params['status'] = $filterStatus;
            }
            $sql .= " ORDER BY pes.tanggal_pesanan DESC";
            $rows_data = Database::fetchAll($sql, $params);

            $headers = ['No', 'No. Tagihan', 'Tanggal', 'Nama Toko', 'Kode Toko', 'Sales PIC', 'Jml Kunjungan', 'Total Tagihan (Rp)', 'Terbayar (Rp)', 'Sisa (Rp)', 'Status'];
            $rows    = [];
            $no      = 1;
            $totTotal = $totBayar = $totSisa = 0;

            foreach ($rows_data as $r) {
                $tot   = (float)$r['total_netto'];
                $bayar = (float)$r['total_dibayar'];
                $sisa  = (float)$r['sisa_tagihan'];
                $totTotal += $tot; $totBayar += $bayar; $totSisa += $sisa;

                $rows[] = [
                    $no++,
                    $r['nomor_nota'] ?? '-',
                    date('d/m/Y', strtotime($r['tanggal_pesanan'])),
                    $r['nama_toko'],
                    $r['kode_pelanggan'] ?? '-',
                    $r['nama_sales'],
                    (int)$r['jumlah_kunjungan'],
                    $tot, $bayar, $sisa,
                    strtoupper(str_replace('_', ' ', (string)($r['status_pembayaran'] ?? '-')))
                ];
            }

            $rows[] = ['', '', '', '', '', '', 'GRAND TOTAL:', $totTotal, $totBayar, $totSisa, ''];

            ExcelExport::download("Tagihan-Konsinyasi-" . date('Ymd') . ".xlsx", $headers, $rows, "Tagihan Konsinyasi");
        } catch (Throwable $e) {
            $this->flashError('Gagal export tagihan konsinyasi: ' . $e->getMessage());
            $this->redirect('/consignment/tagihan');
        }
    }



    /**
     * 10. Sub-halaman: Assignment Sales ↔ Toko (GET /consignment/assignment-sales)
     */
    public function assignmentSales(): void
    {
        Auth::requirePermission('consignment.assignment');

        try {
            // 1. Ambil daftar Karyawan dengan posisi 'sales'
            $salesList = Database::fetchAll("
                SELECT k.id, k.nama_karyawan, k.nomor_telepon, k.posisi,
                       COUNT(p.id) as total_toko,
                       STRING_AGG(DISTINCT CASE WHEN p.id IS NOT NULL THEN COALESCE(w.nama_wilayah, 'Tanpa Wilayah') END, ', ') as wilayah_tercover
                FROM public.v_karyawan_info k
                LEFT JOIN public.pelanggan p ON p.sales_driver_id = k.id AND p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                WHERE k.posisi = 'sales' AND k.status_aktif = TRUE
                GROUP BY k.id, k.nama_karyawan, k.nomor_telepon, k.posisi
                ORDER BY k.nama_karyawan ASC
            ");

            // 2. Ambil seluruh Toko Konsinyasi Aktif
            $stores = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp, p.alamat_lengkap,
                       p.sales_driver_id, p.wilayah_id,
                       COALESCE(w.nama_wilayah, 'Tanpa Wilayah') as nama_wilayah,
                       k.nama_karyawan as nama_sales
                FROM public.pelanggan p
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                ORDER BY p.nama_toko ASC
            ");

            // 3. Ambil daftar Wilayah / Rute aktif untuk filter modal
            $territories = Database::fetchAll("
                SELECT id, nama_wilayah, kode_rute 
                FROM public.wilayah 
                WHERE status_aktif = TRUE 
                ORDER BY nama_wilayah ASC
            ");

            // 4. Kalkulasi Statistik & Toko Unassigned
            $totalSales = count($salesList);
            $assignedStores = count(array_filter($stores, fn($s) => !empty($s['sales_driver_id'])));
            $unassignedStores = array_values(array_filter($stores, fn($s) => empty($s['sales_driver_id'])));
            $totalUnassigned = count($unassignedStores);

            $this->view('consignment.assignment_sales', [
                'pageTitle' => 'Assignment Sales ↔ Toko',
                'pageSubtitle' => 'Penetapan Toko Konsinyasi Binaan per Sales Lapangan',
                'salesList' => $salesList,
                'stores' => $stores,
                'territories' => $territories,
                'totalSales' => $totalSales,
                'assignedStores' => $assignedStores,
                'unassignedStores' => $unassignedStores,
                'totalUnassigned' => $totalUnassigned,
            ]);

        } catch (Throwable $e) {
            echo "Error Assignment Sales: " . $e->getMessage();
        }
    }

    /**
     * 11. Action: Simpan Assignment Sales ↔ Toko (POST /consignment/assignment-sales/save)
     */
    public function saveAssignment(): void
    {
        Auth::requirePermission('consignment.assignment');

        if (!$this->validateCsrf()) {
            $this->flashError('Sesi kedaluwarsa (CSRF token invalid). Silakan coba lagi.');
            $this->redirect('/consignment/assignment-sales');
            return;
        }

        $salesId = trim((string)$this->input('sales_id', ''));
        $storeIds = (array)$this->input('store_ids', []);
        $storeIds = array_values(array_filter(array_unique(array_map('trim', $storeIds))));

        if (empty($salesId)) {
            $this->flashError('Pilih sales penanggung jawab terlebih dahulu.');
            $this->redirect('/consignment/assignment-sales');
            return;
        }

        $sales = Database::fetchOne("
            SELECT id, nama_karyawan 
            FROM public.v_karyawan_info 
            WHERE id = :id AND posisi = 'sales' AND status_aktif = TRUE
        ", ['id' => $salesId]);

        if (!$sales) {
            $this->flashError('Data sales tidak ditemukan atau sudah tidak aktif.');
            $this->redirect('/consignment/assignment-sales');
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            if (!empty($storeIds)) {
                // 1. Unassign toko milik sales ini yang di-uncheck (tidak ada di storeIds baru)
                $inClause = implode(',', array_fill(0, count($storeIds), '?'));
                $stmtUnassign = $pdo->prepare("
                    UPDATE public.pelanggan
                    SET sales_driver_id = NULL, diubah_pada = NOW()
                    WHERE sales_driver_id = ?
                      AND is_konsinyasi = TRUE
                      AND id NOT IN ($inClause)
                ");
                $stmtUnassign->execute(array_merge([$salesId], $storeIds));

                // 2. Assign / Reassign seluruh toko yang dicentang ke sales ini (1 toko = 1 sales)
                $stmtAssign = $pdo->prepare("
                    UPDATE public.pelanggan
                    SET sales_driver_id = ?, diubah_pada = NOW()
                    WHERE id IN ($inClause)
                      AND is_konsinyasi = TRUE
                ");
                $stmtAssign->execute(array_merge([$salesId], $storeIds));
            } else {
                // Jika semua toko di-uncheck / dilepas untuk sales ini
                $stmtClear = $pdo->prepare("
                    UPDATE public.pelanggan
                    SET sales_driver_id = NULL, diubah_pada = NOW()
                    WHERE sales_driver_id = ?
                      AND is_konsinyasi = TRUE
                ");
                $stmtClear->execute([$salesId]);
            }

            $pdo->commit();

            $count = count($storeIds);
            $salesName = $sales['nama_karyawan'];

            ActivityLog::log(
                'master_data',
                'UPDATE',
                "Admin memperbarui penugasan toko konsinyasi untuk sales {$salesName}: {$count} toko ditugaskan.",
                'karyawan',
                $salesId
            );

            $this->flashSuccess("Berhasil memperbarui toko binaan {$salesName}! ({$count} toko aktif ditugaskan)");
            $this->redirect('/consignment/assignment-sales');

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menyimpan assignment sales: ' . $e->getMessage());
            $this->redirect('/consignment/assignment-sales');
        }
    }


    /**
     * 12. Sub-halaman: Rekap Komisi Sales (GET /consignment/komisi-sales)
     */
    public function komisiSales(): void
    {
        Auth::requirePermission(['consignment.komisi_all', 'consignment.komisi_self']);

        try {
            // 1. Validasi Input Rentang Tanggal Fleksibel (Default: Bulan Saat Ini)
            $inputStart = trim((string)$this->input('start_date', ''));
            $inputEnd   = trim((string)$this->input('end_date', ''));
            $inputMonth = trim((string)$this->input('month', ''));

            if (!empty($inputStart) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $inputStart)) {
                $startDate = $inputStart;
            } elseif (!empty($inputMonth) && preg_match('/^\d{4}-\d{2}$/', $inputMonth)) {
                $startDate = $inputMonth . '-01';
            } else {
                $startDate = date('Y-m-01');
            }

            if (!empty($inputEnd) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $inputEnd)) {
                $endDate = $inputEnd;
            } elseif (!empty($inputMonth) && preg_match('/^\d{4}-\d{2}$/', $inputMonth)) {
                $endDate = date('Y-m-t', strtotime($startDate));
            } else {
                $endDate = date('Y-m-t', strtotime($startDate));
            }

            // Keamanan: pastikan tanggal mulai tidak melebihi tanggal akhir
            if ($startDate > $endDate) {
                $tmp = $startDate;
                $startDate = $endDate;
                $endDate = $tmp;
            }

            $month = date('Y-m', strtotime($startDate));

            // 2. Evaluasi Hak Akses & Penguncian Filter Sales
            $canViewAll = Auth::can('consignment.komisi_all');
            $myEmpId = Auth::employeeId();
            $isSalesLocked = !$canViewAll;
            $unlinkedAccount = false;
            $selectedSalesId = '';
            $currentSalesName = '';
            $salesOptions = [];

            if ($canViewAll) {
                // Admin / Owner: Bebas memilih sales tertentu atau melihat semua
                $selectedSalesId = trim((string)$this->input('sales_id', ''));
                $salesOptions = Database::fetchAll("
                    SELECT id, nama_karyawan 
                    FROM public.v_karyawan_info 
                    WHERE posisi = 'sales' AND status_aktif = TRUE 
                    ORDER BY nama_karyawan ASC
                ");
            } else {
                // Karyawan Sales: Filter otomatis terkunci ke dirinya sendiri
                if (!empty($myEmpId)) {
                    $selectedSalesId = $myEmpId; // Anti-tampering: paksa employee_id sesi
                    $salesData = Database::fetchOne("
                        SELECT nama_karyawan FROM public.v_karyawan_info WHERE id = :id
                    ", ['id' => $myEmpId]);
                    $currentSalesName = $salesData['nama_karyawan'] ?? 'Sales Saya';
                    $salesOptions = [['id' => $myEmpId, 'nama_karyawan' => $currentSalesName]];
                } else {
                    $unlinkedAccount = true;
                    $selectedSalesId = '';
                    $currentSalesName = 'Akun Belum Ditautkan';
                }
            }

            // 3. Query Rekapitulasi Komisi Utama (LEFT JOIN pelanggan, Default Rate 2.50%)
            $sql = "
                SELECT k.id as sales_id, k.nama_karyawan, k.nomor_telepon, k.posisi,
                       COALESCE(NULLIF(k.persentase_komisi_sales, 0), 2.50) as persentase_komisi,
                       COUNT(DISTINCT p.id) as total_toko_assigned,
                       COALESCE(SUM(kk.total_laku_nominal), 0) as total_omzet,
                       (COALESCE(SUM(kk.total_laku_nominal), 0) * COALESCE(NULLIF(k.persentase_komisi_sales, 0), 2.50) / 100.0) as nominal_komisi
                FROM public.v_karyawan_info k
                LEFT JOIN public.pelanggan p ON p.sales_driver_id = k.id AND p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                LEFT JOIN public.kunjungan_konsinyasi kk ON kk.pelanggan_id = p.id 
                     AND kk.tanggal_kunjungan >= :start_date AND kk.tanggal_kunjungan <= :end_date
                WHERE k.posisi = 'sales' AND k.status_aktif = TRUE
            ";

            $params = [
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            if (!empty($selectedSalesId)) {
                $sql .= " AND k.id = :selected_sales_id";
                $params['selected_sales_id'] = $selectedSalesId;
            } elseif ($isSalesLocked && $unlinkedAccount) {
                $sql .= " AND 1=0";
            }

            $sql .= " GROUP BY k.id, k.nama_karyawan, k.nomor_telepon, k.posisi, k.persentase_komisi_sales ORDER BY total_omzet DESC, k.nama_karyawan ASC";
            $commissions = Database::fetchAll($sql, $params);

            // 4. Query Breakdown Rincian Toko Binaan per Sales untuk Modal Detail
            $breakdownSql = "
                SELECT p.sales_driver_id as sales_id, p.id as store_id, p.kode_pelanggan, p.nama_toko, p.alamat_lengkap,
                       COALESCE(w.nama_wilayah, 'Tanpa Wilayah') as nama_wilayah,
                       COUNT(kk.id) as total_kunjungan,
                       MAX(kk.tanggal_kunjungan) as terakhir_kunjungan,
                       COALESCE(SUM(kk.total_laku_nominal), 0) as omzet_toko
                FROM public.pelanggan p
                JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id AND k.status_aktif = TRUE
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                LEFT JOIN public.kunjungan_konsinyasi kk ON kk.pelanggan_id = p.id 
                     AND kk.tanggal_kunjungan >= :start_date AND kk.tanggal_kunjungan <= :end_date
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
            ";
            $bParams = [
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
            if (!empty($selectedSalesId)) {
                $breakdownSql .= " AND p.sales_driver_id = :b_sales_id";
                $bParams['b_sales_id'] = $selectedSalesId;
            } elseif ($isSalesLocked && $unlinkedAccount) {
                $breakdownSql .= " AND 1=0";
            }
            $breakdownSql .= " GROUP BY p.sales_driver_id, p.id, p.kode_pelanggan, p.nama_toko, p.alamat_lengkap, w.nama_wilayah ORDER BY omzet_toko DESC, p.nama_toko ASC";
            $breakdownRaw = Database::fetchAll($breakdownSql, $bParams);

            // Kelompokkan breakdown per sales_id
            $storeBreakdown = [];
            foreach ($breakdownRaw as $b) {
                $storeBreakdown[$b['sales_id']][] = $b;
            }

            // 5. Kalkulasi Ringkasan KPI
            $grandOmzet = array_sum(array_column($commissions, 'total_omzet'));
            $grandKomisi = array_sum(array_column($commissions, 'nominal_komisi'));
            $totalStoresInvolved = array_sum(array_column($commissions, 'total_toko_assigned'));

            $this->view('consignment.komisi_sales', [
                'pageTitle' => $isSalesLocked ? 'Komisi Penjualan Saya' : 'Rekap Komisi Sales',
                'pageSubtitle' => $isSalesLocked ? 'Perhitungan komisi bulanan toko binaan tetap Anda' : 'Insentif omzet bulanan toko konsinyasi binaan per sales',
                'commissions' => $commissions,
                'storeBreakdown' => $storeBreakdown,
                'month' => $month,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'grandOmzet' => $grandOmzet,
                'grandKomisi' => $grandKomisi,
                'totalStoresInvolved' => $totalStoresInvolved,
                'canViewAll' => $canViewAll,
                'isSalesLocked' => $isSalesLocked,
                'selectedSalesId' => $selectedSalesId,
                'salesOptions' => $salesOptions,
                'currentSalesName' => $currentSalesName,
                'unlinkedAccount' => $unlinkedAccount,
            ]);

        } catch (Throwable $e) {
            echo "Error Komisi Sales: " . $e->getMessage();
        }
    }

    /**
     * 13. Sub-halaman: Laporan Kerugian Barang Rusak (GET /consignment/kerugian-rusak)
     */
    public function kerugianRusak(): void
    {
        Auth::requirePermission('consignment.kerugian');

        try {
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
            $storeId = (string)$this->input('pelanggan_id', '');

            // Proteksi sanitasi UUID dan rentang tanggal
            if (!empty($storeId) && !preg_match('/^[0-9a-fA-F-]{36}$/', $storeId)) {
                $storeId = '';
            }
            if ($startDate > $endDate) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }

            $stores = Database::fetchAll("
                SELECT id, nama_toko, kode_pelanggan 
                FROM public.pelanggan 
                WHERE is_konsinyasi = TRUE AND status_aktif = TRUE 
                ORDER BY nama_toko ASC
            ");

            $sql = "
                SELECT rkk.id, rkk.kunjungan_id, rkk.retur_rusak, rkk.harga_pokok_satuan, rkk.nilai_kerugian_rusak,
                       kk.tanggal_kunjungan, kk.nomor_kunjungan,
                       p.id as pelanggan_id, p.nama_toko, p.kode_pelanggan,
                       i.nama_item, i.kode_sku, i.satuan_dasar,
                       k.nama_karyawan as nama_sales
                FROM public.rincian_kunjungan_konsinyasi rkk
                JOIN public.kunjungan_konsinyasi kk ON rkk.kunjungan_id = kk.id
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                JOIN public.item i ON rkk.item_id = i.id
                LEFT JOIN public.v_karyawan_info k ON kk.sales_driver_id = k.id
                WHERE rkk.retur_rusak > 0
                  AND kk.tanggal_kunjungan >= :start_date
                  AND kk.tanggal_kunjungan <= :end_date
            ";
            $params = [
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            if (!empty($storeId)) {
                $sql .= " AND p.id = :store_id";
                $params['store_id'] = $storeId;
            }

            $sql .= " ORDER BY kk.tanggal_kunjungan DESC, rkk.nilai_kerugian_rusak DESC";
            $losses = Database::fetchAll($sql, $params);

            $totalLossNominal = array_sum(array_column($losses, 'nilai_kerugian_rusak'));
            $totalPcsRusak = array_sum(array_column($losses, 'retur_rusak'));

            // Deteksi jika ada SKU ber-HPP 0 di laporan
            $hasZeroHpp = false;
            foreach ($losses as $l) {
                if ((float)$l['harga_pokok_satuan'] <= 0) {
                    $hasZeroHpp = true;
                    break;
                }
            }

            // Top 3 Produk Rusak / BS (Operational Intelligence)
            $topBsMap = [];
            foreach ($losses as $l) {
                $sku = $l['kode_sku'] ?: $l['nama_item'];
                if (!isset($topBsMap[$sku])) {
                    $topBsMap[$sku] = [
                        'nama_item' => $l['nama_item'],
                        'kode_sku' => $l['kode_sku'],
                        'satuan_dasar' => $l['satuan_dasar'],
                        'total_pcs' => 0,
                        'total_rp' => 0
                    ];
                }
                $topBsMap[$sku]['total_pcs'] += (int)$l['retur_rusak'];
                $topBsMap[$sku]['total_rp'] += (float)$l['nilai_kerugian_rusak'];
            }
            usort($topBsMap, fn($a, $b) => $b['total_rp'] <=> $a['total_rp']);
            $topBsProducts = array_slice($topBsMap, 0, 3);

            // Query 2: Rekapitulasi Potensi Kerugian Barang Hilang / Selisih Rak (Status Gantung / Pending)
            $pendingLossSql = "
                SELECT 
                    p.id as pelanggan_id,
                    p.nama_toko,
                    p.kode_pelanggan,
                    k.nama_karyawan as nama_sales,
                    i.id as item_id,
                    i.nama_item,
                    i.kode_sku,
                    i.satuan_dasar,
                    skt.stok_titip_saat_ini,
                    skt.stok_hilang_pending,
                    COALESCE(i.harga_pokok_pembelian, 10000) as hpp,
                    (skt.stok_hilang_pending * COALESCE(i.harga_pokok_pembelian, 10000)) as nilai_hpp_hilang
                FROM public.stok_konsinyasi_toko skt
                JOIN public.pelanggan p ON skt.pelanggan_id = p.id
                JOIN public.item i ON skt.item_id = i.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.is_konsinyasi = TRUE 
                  AND skt.stok_hilang_pending > 0
            ";
            $pendingLossParams = [];
            if (!empty($storeId)) {
                $pendingLossSql .= " AND p.id = :store_id";
                $pendingLossParams['store_id'] = $storeId;
            }
            $pendingLossSql .= " ORDER BY nilai_hpp_hilang DESC, p.nama_toko ASC";
            $pendingLosses = Database::fetchAll($pendingLossSql, $pendingLossParams);

            $totalPcsPendingLoss = array_sum(array_column($pendingLosses, 'stok_hilang_pending'));
            $totalNominalPendingLoss = array_sum(array_column($pendingLosses, 'nilai_hpp_hilang'));
            $totalStoresPendingLoss = count(array_unique(array_column($pendingLosses, 'pelanggan_id')));

            // Grouping Barang Hilang per Toko (Ranking Toko Paling Rawan Hilang)
            $pendingLossByStore = [];
            foreach ($pendingLosses as $pl) {
                $pid = $pl['pelanggan_id'];
                if (!isset($pendingLossByStore[$pid])) {
                    $pendingLossByStore[$pid] = [
                        'pelanggan_id' => $pid,
                        'nama_toko' => $pl['nama_toko'],
                        'kode_pelanggan' => $pl['kode_pelanggan'],
                        'nama_sales' => $pl['nama_sales'],
                        'total_sku_hilang' => 0,
                        'total_pcs_hilang' => 0,
                        'total_nilai_hpp' => 0,
                        'total_titip_rak' => 0,
                        'items' => []
                    ];
                }
                $pendingLossByStore[$pid]['total_sku_hilang']++;
                $pendingLossByStore[$pid]['total_pcs_hilang'] += (int)$pl['stok_hilang_pending'];
                $pendingLossByStore[$pid]['total_nilai_hpp'] += (float)$pl['nilai_hpp_hilang'];
                $pendingLossByStore[$pid]['total_titip_rak'] += (int)$pl['stok_titip_saat_ini'];
                $pendingLossByStore[$pid]['items'][] = $pl;
            }
            usort($pendingLossByStore, fn($a, $b) => $b['total_nilai_hpp'] <=> $a['total_nilai_hpp']);

            $this->view('consignment.kerugian_rusak', [
                'pageTitle' => 'Laporan Kerugian & Barang Hilang',
                'pageSubtitle' => 'Audit Kerugian HPP Retur Rusak (BS) & Monitoring Stok Hilang Gantung di Toko Mitra',
                'losses' => $losses,
                'stores' => $stores,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'selectedStoreId' => $storeId,
                'totalLossNominal' => $totalLossNominal,
                'totalPcsRusak' => $totalPcsRusak,
                'hasZeroHpp' => $hasZeroHpp,
                'topBsProducts' => $topBsProducts,
                'pendingLosses' => $pendingLosses,
                'pendingLossByStore' => $pendingLossByStore,
                'totalPcsPendingLoss' => $totalPcsPendingLoss,
                'totalNominalPendingLoss' => $totalNominalPendingLoss,
                'totalStoresPendingLoss' => $totalStoresPendingLoss,
            ]);

        } catch (Throwable $e) {
            $this->flashError('Gagal memuat laporan kerugian rusak: ' . $e->getMessage());
            $this->redirect('/consignment');
        }
    }

    /**
     * Export Laporan Kerugian Barang Rusak / Hilang ke File Excel (.xlsx)
     */
    public function exportKerugianExcel(): void
    {
        Auth::requirePermission('consignment.kerugian');

        try {
            $type = (string)$this->input('type', 'rusak');
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
            $storeId = (string)$this->input('pelanggan_id', '');

            if (!empty($storeId) && !preg_match('/^[0-9a-fA-F-]{36}$/', $storeId)) {
                $storeId = '';
            }
            if ($startDate > $endDate) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }

            // Export Barang Hilang Gantung
            if ($type === 'hilang') {
                $sql = "
                    SELECT 
                        p.nama_toko, p.kode_pelanggan,
                        k.nama_karyawan as nama_sales,
                        i.kode_sku, i.nama_item, i.satuan_dasar,
                        skt.stok_titip_saat_ini, skt.stok_hilang_pending,
                        COALESCE(i.harga_pokok_pembelian, 10000) as hpp,
                        (skt.stok_hilang_pending * COALESCE(i.harga_pokok_pembelian, 10000)) as nilai_hpp_hilang
                    FROM public.stok_konsinyasi_toko skt
                    JOIN public.pelanggan p ON skt.pelanggan_id = p.id
                    JOIN public.item i ON skt.item_id = i.id
                    LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                    WHERE p.is_konsinyasi = TRUE AND skt.stok_hilang_pending > 0
                ";
                $params = [];
                if (!empty($storeId)) {
                    $sql .= " AND p.id = :store_id";
                    $params['store_id'] = $storeId;
                }
                $sql .= " ORDER BY nilai_hpp_hilang DESC, p.nama_toko ASC";
                $rowsData = Database::fetchAll($sql, $params);

                $headers = ['No', 'Toko Mitra', 'Kode Pelanggan', 'Sales PIC', 'SKU', 'Nama Produk', 'Saldo Titip Rak', 'Qty Hilang (Pcs)', 'Satuan', 'Estimasi HPP (Rp)', 'Nilai HPP Gantung (Rp)', 'Status'];
                $rows = [];
                $no = 1;
                $totalPcs = 0;
                $totalRp = 0;
                foreach ($rowsData as $r) {
                    $pcs = (int)$r['stok_hilang_pending'];
                    $rp = (float)$r['nilai_hpp_hilang'];
                    $totalPcs += $pcs;
                    $totalRp += $rp;
                    $rows[] = [
                        $no++,
                        $r['nama_toko'],
                        $r['kode_pelanggan'] ?? '-',
                        $r['nama_sales'] ?? 'Sales',
                        $r['kode_sku'] ?? '-',
                        $r['nama_item'],
                        (int)$r['stok_titip_saat_ini'],
                        $pcs,
                        $r['satuan_dasar'] ?? 'pcs',
                        (float)$r['hpp'],
                        $rp,
                        'Ditangguhkan (Gantung)'
                    ];
                }
                $rows[] = ['', '', '', '', '', '', 'TOTAL BARANG HILANG GANTUNG:', $totalPcs, 'pcs', 'TOTAL ESTIMASI HPP:', $totalRp, ''];
                \App\Helpers\ExcelExport::download("Laporan-Barang-Hilang-Gantung-" . date('Ymd') . ".xlsx", $headers, $rows, "Barang Hilang");
                return;
            }

            // Default: Export Retur Rusak (BS)
            $sql = "
                SELECT rkk.id, rkk.retur_rusak, rkk.harga_pokok_satuan, rkk.nilai_kerugian_rusak,
                       kk.tanggal_kunjungan, kk.nomor_kunjungan,
                       p.nama_toko, p.kode_pelanggan,
                       i.nama_item, i.kode_sku, i.satuan_dasar,
                       k.nama_karyawan as nama_sales
                FROM public.rincian_kunjungan_konsinyasi rkk
                JOIN public.kunjungan_konsinyasi kk ON rkk.kunjungan_id = kk.id
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                JOIN public.item i ON rkk.item_id = i.id
                LEFT JOIN public.v_karyawan_info k ON kk.sales_driver_id = k.id
                WHERE rkk.retur_rusak > 0
                  AND kk.tanggal_kunjungan >= :start_date
                  AND kk.tanggal_kunjungan <= :end_date
            ";
            $params = ['start_date' => $startDate, 'end_date' => $endDate];
            if (!empty($storeId)) {
                $sql .= " AND p.id = :store_id";
                $params['store_id'] = $storeId;
            }
            $sql .= " ORDER BY kk.tanggal_kunjungan DESC, rkk.nilai_kerugian_rusak DESC";
            $losses = Database::fetchAll($sql, $params);

            $headers = ['No', 'Tanggal Kunjungan', 'No. Kunjungan', 'Toko Mitra', 'Kode Pelanggan', 'Sales / Driver', 'SKU', 'Nama Produk', 'Qty Rusak', 'Satuan', 'HPP Satuan (Rp)', 'Total Kerugian HPP (Rp)'];
            $rows = [];
            $no = 1;
            $totalPcs = 0;
            $totalRp = 0;

            foreach ($losses as $l) {
                $qty = (int)$l['retur_rusak'];
                $hpp = (float)$l['harga_pokok_satuan'];
                $loss = (float)$l['nilai_kerugian_rusak'];
                $totalPcs += $qty;
                $totalRp += $loss;

                $rows[] = [
                    $no++,
                    date('d/m/Y', strtotime($l['tanggal_kunjungan'])),
                    $l['nomor_kunjungan'],
                    $l['nama_toko'],
                    $l['kode_pelanggan'] ?? '-',
                    $l['nama_sales'] ?? 'Sales',
                    $l['kode_sku'] ?? '-',
                    $l['nama_item'],
                    $qty,
                    $l['satuan_dasar'] ?? 'pcs',
                    $hpp,
                    $loss
                ];
            }

            $rows[] = ['', '', '', '', '', '', '', 'TOTAL RETUR RUSAK:', $totalPcs, 'pcs', 'TOTAL VALUASI KERUGIAN:', $totalRp];

            \App\Helpers\ExcelExport::download("Laporan-Kerugian-Rusak-{$startDate}-sd-{$endDate}.xlsx", $headers, $rows, "Kerugian Rusak");

        } catch (Throwable $e) {
            $this->flashError('Gagal export laporan kerugian: ' . $e->getMessage());
            $this->redirect('/consignment/kerugian-rusak');
        }
    }

    /**
     * 14. Sub-halaman: Early Warning Toko (GET /consignment/early-warning)
     */
    public function earlyWarning(): void
    {
        Auth::requirePermission('consignment.early_warning');

        try {
            $thresholdDays = 14;

            $stores = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp, p.alamat_lengkap,
                       k.nama_karyawan as nama_sales,
                       (SELECT MAX(skt.terakhir_opname_pada) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) as terakhir_opname,
                       (SELECT COALESCE(SUM(skt.stok_titip_saat_ini), 0) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) as total_pcs_titip,
                       CASE 
                            WHEN (SELECT MAX(skt.terakhir_opname_pada) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) IS NULL THEN 999
                            ELSE EXTRACT(DAY FROM NOW() - (SELECT MAX(skt.terakhir_opname_pada) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id))::int
                       END as hari_sejak_opname
                FROM public.pelanggan p
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                  AND (
                      (SELECT MAX(skt.terakhir_opname_pada) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) IS NULL
                      OR (SELECT MAX(skt.terakhir_opname_pada) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) < NOW() - INTERVAL '14 days'
                  )
                ORDER BY hari_sejak_opname DESC, p.nama_toko ASC
            ");

            $this->view('consignment.early_warning', [
                'pageTitle' => 'Early Warning Toko',
                'pageSubtitle' => 'Daftar Toko Konsinyasi yang Belum Diopname Lebih dari 14 Hari',
                'stores' => $stores,
                'thresholdDays' => $thresholdDays,
            ]);

        } catch (Throwable $e) {
            echo "Error Early Warning: " . $e->getMessage();
        }
    }

    /**
     * 15. Sub-halaman: Riwayat Kunjungan per Toko (GET /consignment/riwayat-kunjungan)
     */
    public function riwayatKunjungan(): void
    {
        Auth::requirePermission(['consignment.view_all', 'consignment.view_assigned']);

        try {
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
            $storeId = (string)$this->input('pelanggan_id', '');
            $statusTagihan = (string)$this->input('status_tagihan', 'semua');
            $driverId = $this->getLoggedInDriverId();
            $isSales = $this->isSalesPersona();

            $storeSql = "SELECT id, nama_toko, kode_pelanggan FROM public.pelanggan WHERE is_konsinyasi = TRUE AND status_aktif = TRUE";
            $storeParams = [];
            if ($isSales && $driverId) {
                $storeSql .= " AND sales_driver_id = :driver_id";
                $storeParams['driver_id'] = $driverId;
            }
            $storeSql .= " ORDER BY nama_toko ASC";
            $stores = Database::fetchAll($storeSql, $storeParams);

            // Parameter Pagination
            $page = max(1, (int)$this->input('page', 1));
            $perPage = max(10, min(100, (int)$this->input('per_page', 25)));
            $offset = ($page - 1) * $perPage;

            // Kondisi Filter
            $where = ["kk.tanggal_kunjungan >= :start_date AND kk.tanggal_kunjungan <= :end_date"];
            $params = [
                'start_date' => $startDate,
                'end_date'   => $endDate
            ];

            if ($isSales && $driverId) {
                $where[] = "p.sales_driver_id = :driver_id";
                $params['driver_id'] = $driverId;
            } elseif (!empty($storeId)) {
                $where[] = "p.id = :store_id";
                $params['store_id'] = $storeId;
            }

            if ($statusTagihan === 'menunggu_tagihan') {
                $where[] = "kk.total_laku_nominal > 0 AND pes.id IS NULL";
            } elseif ($statusTagihan === 'lunas') {
                $where[] = "pes.status_pembayaran = 'lunas'";
            } elseif ($statusTagihan === 'belum_lunas') {
                $where[] = "(pes.status_pembayaran = 'belum_lunas' OR pes.status_pembayaran = 'sebagian')";
            } elseif ($statusTagihan === 'nihil') {
                $where[] = "kk.total_laku_nominal = 0";
            } elseif ($statusTagihan === 'sudah_ditagih') {
                $where[] = "pes.id IS NOT NULL";
            }

            $whereSql = implode(" AND ", $where);

            // Ringkasan KPI dan Total Baris untuk Seluruh Data Terfilter (Database Aggregation)
            $kpiSql = "
                SELECT 
                    COUNT(kk.id) as total_kunjungan,
                    COUNT(DISTINCT kk.pelanggan_id) as count_unique_stores,
                    COALESCE(SUM(kk.total_laku_nominal), 0) as total_nominal_laku,
                    COALESCE(SUM(CASE WHEN kk.total_laku_nominal > 0 AND pes.id IS NULL THEN 1 ELSE 0 END), 0) as count_menunggu_tagihan,
                    COALESCE(SUM(CASE WHEN kk.total_laku_nominal > 0 AND pes.id IS NULL THEN kk.total_laku_nominal ELSE 0 END), 0) as nominal_menunggu_tagihan,
                    COALESCE(SUM(rkk_agg.sum_laku), 0) as total_pcs_laku,
                    COALESCE(SUM(rkk_agg.sum_retur_rusak), 0) as total_retur_rusak,
                    COALESCE(SUM(rkk_agg.sum_retur_bagus), 0) as total_retur_bagus
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.tagihan_kunjungan tk ON tk.kunjungan_id = kk.id
                LEFT JOIN public.pesanan pes ON (kk.pesanan_id = pes.id OR tk.pesanan_id = pes.id)
                LEFT JOIN (
                    SELECT kunjungan_id, 
                           SUM(jumlah_laku_terjual) as sum_laku, 
                           SUM(retur_rusak) as sum_retur_rusak,
                           SUM(retur_bagus) as sum_retur_bagus
                    FROM public.rincian_kunjungan_konsinyasi 
                    GROUP BY kunjungan_id
                ) rkk_agg ON rkk_agg.kunjungan_id = kk.id
                WHERE {$whereSql}
            ";
            $kpiData = Database::fetchOne($kpiSql, $params);

            $totalVisits = (int)($kpiData['total_kunjungan'] ?? 0);
            $totalPages  = max(1, (int)ceil($totalVisits / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
                $offset = ($page - 1) * $perPage;
            }

            $kpiSummary = [
                'total_kunjungan'          => $totalVisits,
                'count_unique_stores'      => (int)($kpiData['count_unique_stores'] ?? 0),
                'total_nominal_laku'       => (float)($kpiData['total_nominal_laku'] ?? 0),
                'count_menunggu_tagihan'   => (int)($kpiData['count_menunggu_tagihan'] ?? 0),
                'nominal_menunggu_tagihan' => (float)($kpiData['nominal_menunggu_tagihan'] ?? 0),
                'total_pcs_laku'           => (int)($kpiData['total_pcs_laku'] ?? 0),
                'total_retur_rusak'        => (int)($kpiData['total_retur_rusak'] ?? 0),
                'total_retur_bagus'        => (int)($kpiData['total_retur_bagus'] ?? 0),
            ];

            // Query Paginated Visits
            $sql = "
                SELECT kk.id, kk.nomor_kunjungan, kk.tanggal_kunjungan, kk.total_laku_nominal, kk.catatan,
                       p.nama_toko, p.kode_pelanggan,
                       COALESCE(k.nama_karyawan, peng.nama_lengkap, 'Petugas ERP') as nama_sales,
                       peng.nama_lengkap as auditor_name,
                       pes.id as pesanan_id, pes.nomor_nota, pes.status_pembayaran, pes.total_netto, pes.total_dibayar, pes.sisa_tagihan,
                       (SELECT COUNT(*) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_sku,
                       (SELECT COALESCE(SUM(jumlah_laku_terjual), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_laku,
                       (SELECT COALESCE(SUM(retur_bagus), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_retur_bagus,
                       (SELECT COALESCE(SUM(retur_rusak), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_retur_rusak,
                       (SELECT COALESCE(SUM(nilai_kerugian_rusak), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_loss
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                LEFT JOIN public.v_karyawan_info k ON COALESCE(kk.sales_driver_id, p.sales_driver_id) = k.id
                LEFT JOIN public.tagihan_kunjungan tk ON tk.kunjungan_id = kk.id
                LEFT JOIN public.pesanan pes ON (kk.pesanan_id = pes.id OR tk.pesanan_id = pes.id)
                WHERE {$whereSql}
                ORDER BY kk.tanggal_kunjungan DESC, kk.dibuat_pada DESC
                LIMIT {$perPage} OFFSET {$offset}
            ";
            $visits = Database::fetchAll($sql, $params);

            $this->view('consignment.riwayat_kunjungan', [
                'pageTitle'             => 'Riwayat Kunjungan Toko',
                'pageSubtitle'          => 'Audit Trail Kunjungan & Settlement Konsinyasi Lapangan',
                'visits'                => $visits,
                'stores'                => $stores,
                'startDate'             => $startDate,
                'endDate'               => $endDate,
                'selectedStoreId'       => $storeId,
                'selectedStatusTagihan' => $statusTagihan,
                'kpiSummary'            => $kpiSummary,
                'totalVisits'           => $totalVisits,
                'totalPages'            => $totalPages,
                'currentPage'           => $page,
                'perPage'               => $perPage,
            ]);

        } catch (Throwable $e) {
            echo "Error Riwayat Kunjungan: " . $e->getMessage();
        }
    }

    /**
     * Export Laporan Penjualan Konsinyasi ke File Excel (PhpSpreadsheet)
     */
    public function exportSalesExcel(): void
    {
        Auth::requirePermission(['consignment.reports_all', 'consignment.reports_assigned']);

        try {
            $startDate  = (string)$this->input('start_date', date('Y-m-01'));
            $endDate    = (string)$this->input('end_date', date('Y-m-d'));
            $storeId    = (string)$this->input('pelanggan_id', '');
            $salesId    = (string)$this->input('sales_id', '');
            $driverId   = $this->getLoggedInDriverId();
            $isSales    = $this->isSalesPersona();

            $whereExtra  = '';
            $queryParams = ['start_date' => $startDate, 'end_date' => $endDate];

            if ($isSales && $driverId) {
                $whereExtra .= " AND p.sales_driver_id = :driver_id";
                $queryParams['driver_id'] = $driverId;
            } else {
                if (!empty($storeId)) {
                    $whereExtra .= " AND p.id = :store_id";
                    $queryParams['store_id'] = $storeId;
                }
                if (!empty($salesId)) {
                    $whereExtra .= " AND k.id = :sales_id";
                    $queryParams['sales_id'] = $salesId;
                }
            }

            $sql = "
                SELECT
                    p.kode_pelanggan, p.nama_toko,
                    COALESCE(k.nama_karyawan, '-') as nama_sales,
                    COUNT(kk.id) as total_kunjungan,
                    COALESCE(SUM(kk.total_laku_nominal), 0) as total_omzet,
                    COALESCE(AVG(kk.total_laku_nominal), 0) as avg_per_kunjungan,
                    MAX(kk.tanggal_kunjungan) as last_visit
                FROM public.pelanggan p
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                LEFT JOIN public.kunjungan_konsinyasi kk
                    ON kk.pelanggan_id = p.id
                    AND kk.tanggal_kunjungan >= :start_date
                    AND kk.tanggal_kunjungan <= :end_date
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                {$whereExtra}
                GROUP BY p.id, p.kode_pelanggan, p.nama_toko, k.nama_karyawan
                ORDER BY total_omzet DESC, p.nama_toko ASC
            ";
            $storeStats = Database::fetchAll($sql, $queryParams);

            $grandTotal = array_sum(array_column($storeStats, 'total_omzet'));

            $headers = ['No', 'Kode Toko', 'Nama Toko Konsinyasi', 'Sales PIC', 'Total Kunjungan', 'Total Omzet (Rp)', 'Rata-rata / Kunjungan (Rp)', 'Kontribusi (%)', 'Terakhir Kunjungan'];
            $rows    = [];
            $no      = 1;

            foreach ($storeStats as $s) {
                $omzet = (float)$s['total_omzet'];
                $pct   = $grandTotal > 0 ? round($omzet / $grandTotal * 100, 1) : 0;
                $rows[] = [
                    $no++,
                    $s['kode_pelanggan'] ?? '-',
                    $s['nama_toko'],
                    $s['nama_sales'],
                    (int)$s['total_kunjungan'],
                    $omzet,
                    round((float)$s['avg_per_kunjungan'], 0),
                    $pct . '%',
                    $s['last_visit'] ? date('d/m/Y', strtotime($s['last_visit'])) : '-',
                ];
            }

            $rows[] = ['', '', '', 'GRAND TOTAL:', '', $grandTotal, '', '100%', ''];

            ExcelExport::download("Laporan-Penjualan-KPI-Toko-{$startDate}-sd-{$endDate}.xlsx", $headers, $rows, "KPI Penjualan Toko");
        } catch (Throwable $e) {
            $this->flashError('Gagal export laporan penjualan: ' . $e->getMessage());
            $this->redirect('/consignment/laporan-penjualan');
        }
    }
}

