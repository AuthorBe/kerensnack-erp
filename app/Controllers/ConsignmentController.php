<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\Format;
use App\Helpers\ActivityLog;
use App\Helpers\PdfExport;
use App\Helpers\PrintDocumentHelper;
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
            error_log("ConsignmentController portal error: " . $e->getMessage());
            $this->flashError("Gagal memuat portal konsinyasi: " . $e->getMessage());
            $this->redirect('/');
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
                       p.sales_driver_id, p.tipe_konsinyasi, k.nama_karyawan as nama_sales,
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
            $shelfSql = "
                SELECT skt.id, skt.pelanggan_id, skt.item_id, skt.stok_titip_saat_ini, skt.terakhir_opname_pada,
                       i.nama_item, i.kode_sku, i.satuan_dasar, i.harga_pokok_pembelian as hpp,
                       p.nama_toko
                FROM public.stok_konsinyasi_toko skt
                JOIN public.item i ON skt.item_id = i.id
                JOIN public.pelanggan p ON skt.pelanggan_id = p.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
            ";
            $shelfParams = [];
            if ($isSales && $driverId) {
                $shelfSql .= " AND p.sales_driver_id = :driver_id";
                $shelfParams['driver_id'] = $driverId;
            }
            $shelfSql .= " ORDER BY p.nama_toko ASC, i.nama_item ASC";
            $shelfItems = Database::fetchAll($shelfSql, $shelfParams);

            $itemsByStore = [];
            foreach ($shelfItems as $item) {
                $itemsByStore[$item['pelanggan_id']][] = $item;
            }

            // Ambil daftar PO/Nota kiriman konsinyasi yang belum ada nilai tagihannya (is_tagihan = FALSE)
            $unbilledPosSql = "
                SELECT 
                    pes.id as pesanan_id,
                    pes.nomor_nota,
                    pes.pelanggan_id,
                    pes.tanggal_pesanan,
                    pes.status_pemrosesan,
                    pes.status_pembayaran,
                    pes.catatan,
                    COALESCE(SUM(ip.kuantitas_satuan_dasar), 0) as total_qty_kirim,
                    COUNT(DISTINCT ip.item_id) as total_sku,
                    string_agg(DISTINCT CONCAT(COALESCE(gp.nama_grup, i.nama_item), ' (', ip.kuantitas_satuan_dasar, ' ', COALESCE(i.satuan_dasar, 'pcs'), ')'), ', ') as rincian_barang
                FROM public.pesanan pes
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.item_pesanan ip ON ip.pesanan_id = pes.id
                LEFT JOIN public.item i ON ip.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE pes.tipe_pembayaran = 'konsinyasi'
                  AND pes.is_tagihan = FALSE
                  AND pes.status_pembayaran != 'dibatalkan'
                  AND p.is_konsinyasi = TRUE
            ";
            $unbilledPosParams = [];
            if ($isSales && $driverId) {
                $unbilledPosSql .= " AND p.sales_driver_id = :driver_id";
                $unbilledPosParams['driver_id'] = $driverId;
            }
            $unbilledPosSql .= " GROUP BY pes.id, pes.nomor_nota, pes.pelanggan_id, pes.tanggal_pesanan, pes.status_pemrosesan, pes.status_pembayaran, pes.catatan";
            $unbilledPosSql .= " ORDER BY pes.tanggal_pesanan ASC, pes.dibuat_pada ASC";
            $unbilledPos = Database::fetchAll($unbilledPosSql, $unbilledPosParams);

            $unbilledOrdersByStore = [];
            foreach ($unbilledPos as $upo) {
                $unbilledOrdersByStore[$upo['pelanggan_id']][] = $upo;
            }

            $this->view('consignment.stok_rak', [
                'pageTitle' => 'Stok Rak per Toko',
                'pageSubtitle' => 'Monitoring Saldo Titipan Rak di Setiap Mitra',
                'stores' => $stores,
                'itemsByStore' => $itemsByStore,
                'unbilledOrdersByStore' => $unbilledOrdersByStore,
                'isAdminOrOwner' => $isAdminOrOwner,
            ]);
        } catch (Throwable $e) {
            error_log("ConsignmentController stokRak error: " . $e->getMessage());
            $this->flashError("Gagal memuat data stok rak: " . $e->getMessage());
            $this->redirect('/consignment');
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

            $tipeKonsinyasi = $customer['tipe_konsinyasi'] ?? 'rolling_nota';

            // Ambil semua PO konsinyasi belum tertagih milik toko ini untuk pemilih PO di layar opname
            $storeUnbilledOrders = Database::fetchAll("
                SELECT pes.id, pes.nomor_nota, pes.tanggal_pesanan, pes.status_pemrosesan,
                       COALESCE(SUM(ip.kuantitas_satuan_dasar), 0) as total_qty_kirim,
                       COUNT(DISTINCT ip.item_id) as total_sku
                FROM public.pesanan pes
                LEFT JOIN public.item_pesanan ip ON ip.pesanan_id = pes.id
                WHERE pes.pelanggan_id = :pelanggan_id
                  AND pes.tipe_pembayaran = 'konsinyasi'
                  AND pes.is_tagihan = FALSE
                  AND pes.status_pembayaran != 'dibatalkan'
                GROUP BY pes.id, pes.nomor_nota, pes.tanggal_pesanan, pes.status_pemrosesan
                ORDER BY pes.tanggal_pesanan ASC, pes.dibuat_pada ASC
            ", ['pelanggan_id' => $storeId]);

            $pesananId = trim((string)$this->input('pesanan_id', ''));

            // Untuk Toko Tipe 2 (Rolling Nota): Wajib mengunci ke PO kiriman tertentu
            if ($tipeKonsinyasi === 'rolling_nota' && empty($pesananId)) {
                if (!empty($storeUnbilledOrders)) {
                    // Otomatis pilih PO unbilled terlama yang belum ditagih
                    $pesananId = $storeUnbilledOrders[0]['id'];
                } else {
                    $this->flashError('Toko konsinyasi Tipe 2 (Saldo Berjalan) belum memiliki PO kiriman baru yang menunggu opname. Buat pesanan kiriman terlebih dahulu.');
                    $this->redirect('/consignment/stok-rak');
                    return;
                }
            }

            $selectedOrder = null;
            $orderQtyByGroup = [];
            $orderQtyByItem = [];
            $orderItems = [];

            if (!empty($pesananId) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $pesananId)) {
                $selectedOrder = Database::fetchOne("
                    SELECT pes.id, pes.nomor_nota, pes.tanggal_pesanan, pes.total_netto, pes.status_pemrosesan, pes.is_tagihan,
                           COALESCE(sj.sales_driver_id, pes.sales_driver_id) as sales_driver_id,
                           COALESCE(k_sj.nama_karyawan, k.nama_karyawan) as nama_driver
                    FROM public.pesanan pes
                    LEFT JOIN public.v_karyawan_info k ON pes.sales_driver_id = k.id
                    LEFT JOIN public.surat_jalan sj ON sj.pesanan_id = pes.id
                    LEFT JOIN public.v_karyawan_info k_sj ON sj.sales_driver_id = k_sj.id
                    WHERE pes.id = :id AND pes.pelanggan_id = :pelanggan_id AND pes.tipe_pembayaran = 'konsinyasi'
                ", ['id' => $pesananId, 'pelanggan_id' => $storeId]);

                if ($selectedOrder) {
                    $orderItems = Database::fetchAll("
                        SELECT ip.item_id, ip.kuantitas_satuan_dasar, i.nama_item, i.grup_id
                        FROM public.item_pesanan ip
                        JOIN public.item i ON ip.item_id = i.id
                        WHERE ip.pesanan_id = :pesanan_id
                    ", ['pesanan_id' => $pesananId]);

                    foreach ($orderItems as $oi) {
                        $orderQtyByItem[$oi['item_id']] = (int)$oi['kuantitas_satuan_dasar'];
                        if (!empty($oi['grup_id'])) {
                            $orderQtyByGroup[$oi['grup_id']] = ($orderQtyByGroup[$oi['grup_id']] ?? 0) + (int)$oi['kuantitas_satuan_dasar'];
                        }
                    }
                }
            }

            // Ambil semua grup produk yang ada saldo atau pernah memiliki riwayat titip di toko ini
            $shelfGroups = Database::fetchAll("
                SELECT gp.id as grup_id, gp.nama_grup, gp.barcode_universal, gp.satuan_dasar,
                       COALESCE(gphl.harga_jual_pcs, 15000) as harga_jual_satuan,
                       COALESCE(SUM(skt.stok_titip_saat_ini), 0) as stok_titip_saat_ini,
                       COALESCE(SUM(skt.stok_hilang_pending), 0) as stok_hilang_pending,
                       MAX(skt.terakhir_opname_pada) as terakhir_opname_pada,
                       array_to_string(array_agg(DISTINCT i.nama_item), ', ') as varian_text,
                       MIN(i.id::text)::uuid as primary_item_id
                FROM public.stok_konsinyasi_toko skt
                JOIN public.item i ON skt.item_id = i.id
                JOIN public.grup_produk gp ON i.grup_id = gp.id
                LEFT JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = gp.id AND gphl.level_harga = 5
                WHERE skt.pelanggan_id = :pelanggan_id
                GROUP BY gp.id, gp.nama_grup, gp.barcode_universal, gp.satuan_dasar, gphl.harga_jual_pcs
                HAVING COALESCE(SUM(skt.stok_titip_saat_ini), 0) > 0 
                   OR EXISTS (
                       SELECT 1 
                       FROM public.rincian_kunjungan_konsinyasi rkk
                       JOIN public.kunjungan_konsinyasi kk ON rkk.kunjungan_id = kk.id
                       JOIN public.item i2 ON rkk.item_id = i2.id
                       WHERE kk.pelanggan_id = :pelanggan_id AND i2.grup_id = gp.id
                   )
                ORDER BY gp.nama_grup ASC
            ", ['pelanggan_id' => $storeId]);

            $savedInput = $_SESSION['_old_opname_input'][$storeId] ?? null;

            $items = [];
            foreach ($shelfGroups as $sg) {
                $priceRow = Database::fetchOne("
                    SELECT public.fn_hitung_harga_jual_item(:item_id, :store_id) AS json_res
                ", ['item_id' => $sg['primary_item_id'], 'store_id' => $storeId]);
                $priceJson = json_decode($priceRow['json_res'] ?? '{}', true);
                $dealPrice = (float)($priceJson['harga_pcs_netto'] ?? $sg['harga_jual_satuan']);

                $sisaFisik = 0;
                $rBagus = 0;
                $rRusak = 0;
                $laku = 0;
                $selisih = 0;
                $tambahTitip = 0;
                $isTouched = false;

                if ($selectedOrder) {
                    // Nilai kiriman baru otomatis mengikuti PO/NOTA yang dipilih
                    $tambahTitip = (int)($orderQtyByGroup[$sg['grup_id']] ?? $orderQtyByItem[$sg['primary_item_id']] ?? 0);
                    // PENTING: Sisa stok lalu adalah sisa kiriman lalu (baseline).
                    // Sisa di rak tergantung fisik di toko yang dihitung saat kiriman, bukan ditambahkan otomatis!
                    $sisaFisik = 0;
                    $laku = (int)$sg['stok_titip_saat_ini'] + $tambahTitip;
                    $isTouched = false;
                } else {
                    $laku = (int)$sg['stok_titip_saat_ini'];
                }

                if ($savedInput && isset($savedInput[$sg['primary_item_id']])) {
                    $sisaFisik = (int)($savedInput[$sg['primary_item_id']]['sisa_fisik_di_rak'] ?? $sisaFisik);
                    $laku = (int)($savedInput[$sg['primary_item_id']]['jumlah_laku'] ?? 0);
                    $rBagus = (int)($savedInput[$sg['primary_item_id']]['retur_bagus'] ?? 0);
                    $rRusak = (int)($savedInput[$sg['primary_item_id']]['retur_rusak'] ?? 0);
                    $selisih = (int)($savedInput[$sg['primary_item_id']]['selisih_qty'] ?? $savedInput[$sg['primary_item_id']]['selisih'] ?? 0);
                    if (!$selectedOrder) {
                        $tambahTitip = (int)($savedInput[$sg['primary_item_id']]['tambah_titip_baru'] ?? $savedInput[$sg['primary_item_id']]['kiriman_hari_ini'] ?? 0);
                    }
                    $isTouched = true;
                }

                $items[] = [
                    'grup_id' => $sg['grup_id'],
                    'item_id' => $sg['primary_item_id'],
                    'nama_grup' => $sg['nama_grup'],
                    'nama_item' => $sg['nama_grup'],
                    'kode_sku' => $sg['barcode_universal'] ?: 'SKU',
                    'barcode_universal' => $sg['barcode_universal'] ?: '-',
                    'varian_text' => $sg['varian_text'] ?: '',
                    'satuan_dasar' => $sg['satuan_dasar'] ?? 'pcs',
                    'stok_titip_saat_ini' => (int)$sg['stok_titip_saat_ini'],
                    'stok_hilang_pending' => (int)$sg['stok_hilang_pending'],
                    'harga_deal' => $dealPrice,
                    'sisa_fisik_di_rak' => $sisaFisik,
                    'tambah_titip_baru' => $tambahTitip,
                    'jumlah_laku' => $laku,
                    'retur_bagus' => $rBagus,
                    'retur_rusak' => $rRusak,
                    'selisih_qty' => $selisih,
                    'is_touched' => $isTouched
                ];
            }

            // Pastikan jika ada item dalam PO yang belum ada di shelfGroups (kiriman pertama), ditambahkan ke list
            if ($selectedOrder && !empty($orderItems)) {
                $existingGrupIds = array_column($shelfGroups, 'grup_id');
                foreach ($orderItems as $oi) {
                    if (!empty($oi['grup_id']) && !in_array($oi['grup_id'], $existingGrupIds)) {
                        $newGrp = Database::fetchOne("
                            SELECT gp.id as grup_id, gp.nama_grup, gp.barcode_universal, gp.satuan_dasar,
                                   COALESCE(gphl.harga_jual_pcs, 15000) as harga_jual_satuan,
                                   array_to_string(array_agg(DISTINCT i.nama_item), ', ') as varian_text,
                                   MIN(i.id::text)::uuid as primary_item_id
                            FROM public.grup_produk gp
                            JOIN public.item i ON i.grup_id = gp.id AND i.status_aktif = TRUE
                            LEFT JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = gp.id AND gphl.level_harga = 5
                            WHERE gp.id = :gid
                            GROUP BY gp.id, gp.nama_grup, gp.barcode_universal, gp.satuan_dasar, gphl.harga_jual_pcs
                        ", ['gid' => $oi['grup_id']]);

                        if ($newGrp) {
                            $dropQty = (int)$oi['kuantitas_satuan_dasar'];
                            $items[] = [
                                'grup_id' => $newGrp['grup_id'],
                                'item_id' => $newGrp['primary_item_id'],
                                'nama_grup' => $newGrp['nama_grup'],
                                'nama_item' => $newGrp['nama_grup'],
                                'kode_sku' => $newGrp['barcode_universal'] ?: 'SKU',
                                'barcode_universal' => $newGrp['barcode_universal'] ?: '-',
                                'varian_text' => $newGrp['varian_text'] ?: '',
                                'satuan_dasar' => $newGrp['satuan_dasar'] ?? 'pcs',
                                'stok_titip_saat_ini' => 0,
                                'stok_hilang_pending' => 0,
                                'harga_deal' => (float)$newGrp['harga_jual_satuan'],
                                'sisa_fisik_di_rak' => 0,
                                'tambah_titip_baru' => $dropQty,
                                'jumlah_laku' => $dropQty,
                                'retur_bagus' => 0,
                                'retur_rusak' => 0,
                                'selisih_qty' => 0,
                                'is_touched' => false
                            ];
                            $existingGrupIds[] = $newGrp['grup_id'];
                        }
                    }
                }
            }

            // Ambil daftar karyawan berposisi Driver untuk pilihan Admin
            $drivers = Database::fetchAll("
                SELECT k.id, k.nama_karyawan, k.posisi 
                FROM public.v_karyawan_info k 
                WHERE (k.posisi ILIKE '%driver%' OR k.posisi ILIKE '%supir%' OR k.posisi ILIKE '%pengemudi%')
                  AND k.status_aktif = TRUE
                ORDER BY k.nama_karyawan ASC
            ");
            if (empty($drivers)) {
                $drivers = Database::fetchAll("
                    SELECT k.id, k.nama_karyawan, k.posisi 
                    FROM public.v_karyawan_info k 
                    WHERE k.status_aktif = TRUE 
                    ORDER BY k.nama_karyawan ASC
                ");
            }

            // Ambil akun kas aktif untuk pembayaran kasir langsung di layar opname
            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, tipe_akun 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE 
                ORDER BY (tipe_akun = 'kas') DESC, nama_akun ASC
            ");

            // Ambil katalog grup produk barang jadi aktif untuk fitur '+ Tambah Produk Baru' ke rak
            $catalogGroups = Database::fetchAll("
                SELECT gp.id as grup_id, gp.nama_grup, gp.barcode_universal, gp.satuan_dasar,
                       COALESCE(gphl.harga_jual_pcs, 15000) as harga_jual_satuan,
                       array_to_string(array_agg(DISTINCT i.nama_item), ', ') as varian_text,
                       MIN(i.id::text)::uuid as primary_item_id,
                       MIN(i.id::text)::uuid as id,
                       gp.nama_grup as nama_item,
                       COALESCE(gp.barcode_universal, 'SKU') as kode_sku
                FROM public.grup_produk gp
                JOIN public.item i ON i.grup_id = gp.id AND i.status_aktif = TRUE AND i.tipe_item = 'barang_jadi'
                LEFT JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = gp.id AND gphl.level_harga = 5
                WHERE gp.status_aktif = TRUE
                GROUP BY gp.id, gp.nama_grup, gp.barcode_universal, gp.satuan_dasar, gphl.harga_jual_pcs
                ORDER BY gp.nama_grup ASC
            ");

            $this->view('consignment.opname', [
                'pageTitle' => 'Kunjungan & Nota Konsinyasi',
                'pageSubtitle' => 'Audit Sisa, Drop Baru, & Penagihan: ' . $customer['nama_toko'],
                'step' => 2,
                'customer' => $customer,
                'items' => $items,
                'drivers' => $drivers,
                'cashAccounts' => $cashAccounts,
                'catalogGroups' => $catalogGroups,
                'catalogItems' => $catalogGroups,
                'selectedOrder' => $selectedOrder,
                'storeUnbilledOrders' => $storeUnbilledOrders,
            ]);

        } catch (Throwable $e) {
            error_log("ConsignmentController opname error: " . $e->getMessage());
            $this->flashError("Gagal memuat form opname: " . $e->getMessage());
            $this->redirect('/consignment');
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
        $targetPesananId = trim((string)$this->input('pesanan_id', ''));
        $itemsJson = (string)$this->input('items_json');
        $catatan = trim((string)$this->input('catatan', 'Kunjungan & Nota Konsinyasi'));
        $rawNominalBayar = $this->input('nominal_bayar') ?? $this->input('nominal', '0');
        $nominalBayar = (float)preg_replace('/[^0-9]/', '', (string)$rawNominalBayar);
        $akunKasId = trim((string)$this->input('akun_kas_id', ''));
        $catatanBayar = trim((string)$this->input('catatan_bayar', ''));

        $currentUserId = Auth::id();
        $currentUser = Auth::user();
        $driverId = $currentUser['karyawan_id'] ?? null;
        if (empty($driverId) && !empty($customerId)) {
            $custRow = Database::fetchOne("SELECT sales_driver_id FROM public.pelanggan WHERE id = :id", ['id' => $customerId]);
            $driverId = $custRow['sales_driver_id'] ?? null;
        }
        $rawDriverPengirim = trim((string)$this->input('driver_pengirim_id', ''));
        $driverPengirimId = (!empty($rawDriverPengirim) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $rawDriverPengirim))
            ? $rawDriverPengirim
            : null;

        // Jika toko rolling nota atau terikat pesanan_id dan driver kosong, otomatis ambil dari nota/PO (atau surat jalan)
        if (empty($driverPengirimId) && !empty($targetPesananId) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $targetPesananId)) {
            $poDriverRow = Database::fetchOne("
                SELECT COALESCE(sj.sales_driver_id, pes.sales_driver_id) as po_driver_id
                FROM public.pesanan pes
                LEFT JOIN public.surat_jalan sj ON sj.pesanan_id = pes.id
                WHERE pes.id = :id
            ", ['id' => $targetPesananId]);
            if (!empty($poDriverRow['po_driver_id'])) {
                $driverPengirimId = $poDriverRow['po_driver_id'];
            }
        }

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

                $sisaFisik = max(0, (int)($it['sisa_fisik_di_rak'] ?? $it['sisa_fisik'] ?? 0));
                $tambahTitip = max(0, (int)($it['tambah_titip_baru'] ?? $it['kiriman_hari_ini'] ?? 0));
                $laku = isset($it['jumlah_laku']) ? max(0, (int)$it['jumlah_laku']) : null;
                $returBagus = max(0, (int)($it['retur_bagus'] ?? 0));
                $returRusak = max(0, (int)($it['retur_rusak'] ?? 0));
                $selisih = (int)($it['selisih_qty'] ?? $it['selisih'] ?? 0);

                $rincianFormatted[] = [
                    'item_id' => $itemId,
                    'sisa_fisik_di_rak' => $sisaFisik,
                    'tambah_titip_baru' => $tambahTitip,
                    'kiriman_hari_ini' => $tambahTitip,
                    'jumlah_laku' => $laku,
                    'retur_bagus' => $returBagus,
                    'retur_rusak' => $returRusak,
                    'selisih_qty' => $selisih,
                ];
            }

            $res = Database::fetchOne("
                SELECT public.fn_proses_kunjungan_konsinyasi(:cust_id, :driver_id, :rincian::jsonb, :catatan, :foto, :user_id, :driver_pengirim_id) AS json_res
            ", [
                'cust_id' => $customerId,
                'driver_id' => $driverId,
                'rincian' => json_encode($rincianFormatted),
                'catatan' => $catatan,
                'foto' => $fotoUrl,
                'user_id' => $currentUserId,
                'driver_pengirim_id' => $driverPengirimId
            ]);

            $jsonResult = json_decode($res['json_res'] ?? '{}', true);

            if (empty($jsonResult['success'])) {
                throw new \Exception('Proses opname ditolak oleh database.');
            }

            // Hapus old input sesi karena sudah sukses
            unset($_SESSION['_old_opname_input'][$customerId]);

            $kunjunganId = $jsonResult['kunjungan_id'] ?? null;
            $totalLakuNetto = (float)($jsonResult['total_laku_netto'] ?? 0);
            $custName = Database::fetchOne("SELECT nama_toko FROM public.pelanggan WHERE id = :id", ['id' => $customerId])['nama_toko'] ?? 'Toko';

            // Simpan catatan jika ada
            if (!empty($catatan) && !empty($kunjunganId)) {
                Database::execute("
                    UPDATE public.kunjungan_konsinyasi 
                    SET catatan = :catatan 
                    WHERE id = :id
                ", ['catatan' => $catatan, 'id' => $kunjunganId]);
            }

            ActivityLog::log(
                'logistik',
                'INSERT',
                "Sales menyelesaikan kunjungan konsinyasi di {$custName} ({$jsonResult['nomor_kunjungan']}).",
                'kunjungan_konsinyasi',
                $kunjunganId
            );

            // Ambil tipe konsinyasi pelanggan
            $custRow = Database::fetchOne("SELECT nama_toko, tipe_konsinyasi FROM public.pelanggan WHERE id = :id", ['id' => $customerId]);
            $tipeKonsinyasi = $custRow['tipe_konsinyasi'] ?? 'rolling_nota';

            // Auto-Generate / Update Tagihan Konsinyasi
            $pesananId = null;
            $nomorNota = null;

            // Jika mode Rolling Nota dan opname dilakukan atas dasar PO / Nota kiriman spesifik, konversi PO tersebut menjadi tagihan riil
            if ($tipeKonsinyasi === 'rolling_nota' && !empty($targetPesananId) && !empty($kunjunganId)) {
                $targetOrder = Database::fetchOne("
                    SELECT id, nomor_nota, pelanggan_id, is_tagihan 
                    FROM public.pesanan 
                    WHERE id = :id AND pelanggan_id = :pid
                ", ['id' => $targetPesananId, 'pid' => $customerId]);

                if ($targetOrder) {
                    $pesananId = $targetOrder['id'];
                    $nomorNota = $targetOrder['nomor_nota'];

                    Database::execute("
                        UPDATE public.pesanan
                        SET is_tagihan = TRUE,
                            total_bruto = :total_bruto,
                            total_netto = :total_netto,
                            total_dibayar = 0.00,
                            sisa_tagihan = :sisa_tagihan,
                            status_pembayaran = 'belum_lunas',
                            status_pemrosesan = 'selesai',
                            diubah_pada = NOW()
                        WHERE id = :id
                    ", [
                        'id' => $pesananId,
                        'total_bruto' => $totalLakuNetto,
                        'total_netto' => $totalLakuNetto,
                        'sisa_tagihan' => $totalLakuNetto
                    ]);

                    Database::execute("
                        UPDATE public.kunjungan_konsinyasi
                        SET pesanan_id = :pesanan_id
                        WHERE id = :kunjungan_id
                    ", [
                        'pesanan_id' => $pesananId,
                        'kunjungan_id' => $kunjunganId
                    ]);

                    Database::execute("
                        INSERT INTO public.tagihan_kunjungan (pesanan_id, kunjungan_id, dibuat_pada)
                        VALUES (:pesanan_id, :kunjungan_id, NOW())
                        ON CONFLICT DO NOTHING
                    ", [
                        'pesanan_id' => $pesananId,
                        'kunjungan_id' => $kunjunganId
                    ]);

                    if ($totalLakuNetto > 0) {
                        Database::execute("
                            UPDATE public.pelanggan
                            SET total_piutang_berjalan = COALESCE(total_piutang_berjalan, 0) + :tagihan,
                                diubah_pada = NOW()
                            WHERE id = :pid
                        ", [
                            'tagihan' => $totalLakuNetto,
                            'pid' => $customerId
                        ]);
                    }
                }
            }

            // Auto-Payment Kasir Langsung di Toko (khusus mode rolling nota jika ada pembayaran & pesanan sudah terbit)
            if ($tipeKonsinyasi === 'rolling_nota' && $nominalBayar > 0 && !empty($pesananId) && !empty($akunKasId)) {
                if ($nominalBayar > $totalLakuNetto) {
                    $nominalBayar = $totalLakuNetto;
                }
                try {
                    $keteranganBayar = !empty($catatanBayar) 
                        ? $catatanBayar 
                        : "Pembayaran kasir saat kunjungan di {$custName}";

                    $payRes = Database::fetchOne("
                        SELECT public.fn_catat_pembayaran_konsinyasi(:p, :a, :nom, :user_id, :ket, :tgl) as json_res
                    ", [
                        'p'       => $pesananId,
                        'a'       => $akunKasId,
                        'nom'     => $nominalBayar,
                        'user_id' => $currentUserId,
                        'ket'     => $keteranganBayar,
                        'tgl'     => date('Y-m-d'),
                    ]);
                    $payJson = json_decode($payRes['json_res'] ?? '{}', true);
                    if (!empty($payJson['success'])) {
                        $stBayar = ($payJson['status_pembayaran'] ?? '') === 'lunas' ? 'LUNAS' : 'SEBAGIAN';
                        ActivityLog::log(
                            'keuangan',
                            'INSERT',
                            "Pembayaran langsung di toko: " . Format::rupiah($nominalBayar) . " dicatat untuk faktur {$nomorNota} ({$custName}). Status: {$stBayar}.",
                            'pesanan',
                            $pesananId
                        );
                    }
                } catch (Throwable $ePay) {
                    error_log("Auto payment konsinyasi error: " . $ePay->getMessage());
                }
            }

            if ($tipeKonsinyasi === 'kolektif_tagihan') {
                $this->flashSuccess("Opname fisik rak di {$custName} berhasil disimpan. Rincian stok rak telah diperbarui.");
                $this->redirect('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)$kunjunganId));
            } else {
                $this->flashSuccess("Kunjungan konsinyasi di {$custName} berhasil diproses! Nota siap dicetak.");
                $redirectTarget = !empty($pesananId)
                    ? '/consignment/nota-print?pesanan_id=' . urlencode((string)$pesananId) . '&kunjungan_id=' . urlencode((string)$kunjunganId) . '&ref=hasil'
                    : '/consignment/nota-print?kunjungan_id=' . urlencode((string)$kunjunganId) . '&ref=hasil';

                $this->redirect($redirectTarget);
            }

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
                SELECT kk.*, p.nama_toko, p.kode_pelanggan, p.alamat_lengkap, p.nomor_whatsapp, p.nomor_whatsapp as nomor_telepon, p.nama_pemilik,
                       COALESCE(k.nama_karyawan, peng.nama_lengkap, 'Sales Lapangan') as sales_name,
                       COALESCE(k.posisi, 'Sales Lapangan') as sales_role,
                       COALESCE(k_drv.nama_karyawan, k_sj.nama_karyawan, k_pes.nama_karyawan, '-') as driver_name,
                       peng.nama_lengkap as auditor_name,
                       COALESCE(peng.posisi, 'Auditor') as auditor_role,
                       COALESCE(pes.id, tk.pesanan_id) as pesanan_id,
                       pes.nomor_nota, pes.total_netto, pes.total_dibayar, pes.status_pembayaran, pes.sisa_tagihan, pes.tanggal_pesanan
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                LEFT JOIN public.v_karyawan_info k ON COALESCE(kk.sales_driver_id, p.sales_driver_id) = k.id
                LEFT JOIN public.v_karyawan_info k_drv ON kk.driver_pengirim_id = k_drv.id
                LEFT JOIN public.tagihan_kunjungan tk ON tk.kunjungan_id = kk.id
                LEFT JOIN public.pesanan pes ON (kk.pesanan_id = pes.id OR tk.pesanan_id = pes.id)
                LEFT JOIN public.surat_jalan sj ON sj.pesanan_id = pes.id
                LEFT JOIN public.v_karyawan_info k_sj ON sj.sales_driver_id = k_sj.id
                LEFT JOIN public.v_karyawan_info k_pes ON pes.sales_driver_id = k_pes.id
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

            $canManageTagihan = Auth::can('consignment.piutang');
            $canSpotBill = Auth::can(['consignment.opname_all', 'consignment.opname_assigned', 'consignment.piutang']);

            $this->view('consignment.opname_hasil', [
                'pageTitle' => 'Hasil Kunjungan Konsinyasi',
                'pageSubtitle' => 'Rincian Stok & Hasil Opname Fisik Rak',
                'visit' => $visit,
                'details' => $details,
                'canManageTagihan' => $canManageTagihan,
                'canSpotBill' => $canSpotBill,
            ]);

        } catch (Throwable $e) {
            error_log("ConsignmentController hasil error: " . $e->getMessage());
            $this->flashError("Gagal memuat hasil kunjungan: " . $e->getMessage());
            $this->redirect('/consignment');
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

        $uuidRegex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        if (empty($kunjunganId) || !preg_match($uuidRegex, $kunjunganId) || empty($accountId) || $nominal <= 0) {
            $this->flashError('Pilih rekening kas penerima, pastikan ID kunjungan valid, dan masukkan nominal pembayaran lebih dari Rp 0.');
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
     * 5c. Cetak Lembar Berita Acara Opname / Faktur Tagihan Konsinyasi (HTML Preview & Switcher)
     * (GET /consignment/nota-print?kunjungan_id=... atau ?pesanan_id=...)
     */
    public function printNota(): void
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
                           p.nomor_whatsapp, p.nomor_whatsapp as nomor_telepon, p.nama_pemilik,
                           p.tipe_konsinyasi,
                           COALESCE(k.nama_karyawan, peng.nama_lengkap, 'Petugas ERP') as sales_name,
                           COALESCE(k.posisi, 'Sales Lapangan') as sales_role,
                           COALESCE(k_driver.nama_karyawan, '-') as driver_name,
                           peng.nama_lengkap as auditor_name,
                           COALESCE(peng.posisi, 'Auditor') as auditor_role,
                           (SELECT kk.nomor_kunjungan FROM public.tagihan_kunjungan tk JOIN public.kunjungan_konsinyasi kk ON tk.kunjungan_id = kk.id WHERE tk.pesanan_id = pes.id ORDER BY kk.tanggal_kunjungan DESC LIMIT 1) as nomor_kunjungan,
                           pes.tanggal_pesanan as tanggal_kunjungan
                    FROM public.pesanan pes
                    JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                    LEFT JOIN public.v_karyawan_info k ON COALESCE(pes.sales_driver_id, p.sales_driver_id) = k.id
                    LEFT JOIN LATERAL (
                        SELECT kk2.driver_pengirim_id 
                        FROM public.tagihan_kunjungan tk2 
                        JOIN public.kunjungan_konsinyasi kk2 ON tk2.kunjungan_id = kk2.id 
                        WHERE tk2.pesanan_id = pes.id AND kk2.driver_pengirim_id IS NOT NULL 
                        LIMIT 1
                    ) l_driver ON TRUE
                    LEFT JOIN public.v_karyawan_info k_driver ON l_driver.driver_pengirim_id = k_driver.id
                    LEFT JOIN public.pengguna peng ON pes.dibuat_oleh = peng.id
                    WHERE pes.id = :id
                ", ['id' => $pesananId]);

                if (!$visit) {
                    throw new \Exception('Data faktur tagihan tidak ditemukan.');
                }

                $isInvoiced = true;

                $details = Database::fetchAll("
                    SELECT COALESCE(gp.id, i.id) as grup_id,
                           COALESCE(gp.nama_grup, i.nama_item) as nama_item,
                           COALESCE(gp.nama_grup, i.nama_item) as nama_grup,
                           COALESCE(gp.barcode_universal, i.kode_sku) as kode_sku,
                           COALESCE(gp.barcode_universal, i.kode_sku) as barcode_universal,
                           COALESCE(gp.satuan_dasar, i.satuan_dasar, 'pcs') as satuan_dasar,
                           array_to_string(array_agg(DISTINCT i.nama_item), ', ') as varian_list,
                           SUM(rkk.stok_titip_awal) as stok_titip_awal,
                           SUM(rkk.tambah_titip_baru) as tambah_titip_baru,
                           SUM(rkk.sisa_fisik_di_rak) as sisa_fisik_di_rak,
                           SUM(rkk.jumlah_laku_terjual) as jumlah_laku_terjual,
                           SUM(rkk.retur_rusak) as retur_rusak,
                           SUM(rkk.retur_bagus) as retur_bagus,
                           SUM(rkk.selisih_qty) as selisih_qty,
                           MAX(rkk.harga_satuan_deal) as harga_satuan_deal,
                           SUM(rkk.subtotal_laku) as subtotal_laku
                    FROM public.rincian_kunjungan_konsinyasi rkk
                    JOIN public.tagihan_kunjungan tk ON rkk.kunjungan_id = tk.kunjungan_id
                    JOIN public.item i ON rkk.item_id = i.id
                    LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                    WHERE tk.pesanan_id = :id
                    GROUP BY COALESCE(gp.id, i.id), COALESCE(gp.nama_grup, i.nama_item), COALESCE(gp.barcode_universal, i.kode_sku), COALESCE(gp.satuan_dasar, i.satuan_dasar, 'pcs')
                    HAVING (
                        SUM(rkk.stok_titip_awal) > 0 
                        OR SUM(rkk.tambah_titip_baru) > 0 
                        OR SUM(rkk.sisa_fisik_di_rak) > 0 
                        OR SUM(rkk.jumlah_laku_terjual) > 0 
                        OR SUM(rkk.retur_rusak) > 0 
                        OR SUM(rkk.retur_bagus) > 0
                    )
                    ORDER BY COALESCE(gp.nama_grup, i.nama_item) ASC
                ", ['id' => $pesananId]);

                if (empty($details)) {
                    $details = Database::fetchAll("
                        SELECT COALESCE(gp.id, i.id) as grup_id,
                               COALESCE(gp.nama_grup, i.nama_item) as nama_item,
                               COALESCE(gp.nama_grup, i.nama_item) as nama_grup,
                               COALESCE(gp.barcode_universal, i.kode_sku) as kode_sku,
                               COALESCE(gp.barcode_universal, i.kode_sku) as barcode_universal,
                               COALESCE(gp.satuan_dasar, i.satuan_dasar, 'pcs') as satuan_dasar,
                               array_to_string(array_agg(DISTINCT i.nama_item), ', ') as varian_list,
                               0 as stok_titip_awal,
                               0 as tambah_titip_baru,
                               0 as sisa_fisik_di_rak,
                               SUM(ip.kuantitas_satuan_dasar) as jumlah_laku_terjual,
                               0 as retur_rusak,
                               0 as retur_bagus,
                               0 as selisih_qty,
                               MAX(ip.harga_satuan_deal) as harga_satuan_deal,
                               SUM(ip.subtotal) as subtotal_laku
                        FROM public.item_pesanan ip
                        JOIN public.item i ON ip.item_id = i.id
                        LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                        WHERE ip.pesanan_id = :id
                        GROUP BY COALESCE(gp.id, i.id), COALESCE(gp.nama_grup, i.nama_item), COALESCE(gp.barcode_universal, i.kode_sku), COALESCE(gp.satuan_dasar, i.satuan_dasar, 'pcs')
                        ORDER BY COALESCE(gp.nama_grup, i.nama_item) ASC
                    ", ['id' => $pesananId]);
                }

            } else {
                // Kasus 2: Diberikan kunjungan_id (Dari Halaman Hasil Opname)
                $sql = "
                    SELECT kk.*, p.nama_toko, p.kode_pelanggan, p.alamat_lengkap, p.nomor_whatsapp, p.nomor_whatsapp as nomor_telepon, p.nama_pemilik,
                           p.tipe_konsinyasi,
                           COALESCE(k.nama_karyawan, peng.nama_lengkap, 'Sales Lapangan') as sales_name,
                           COALESCE(k.posisi, 'Sales Lapangan') as sales_role,
                           COALESCE(k_driver.nama_karyawan, '-') as driver_name,
                           peng.nama_lengkap as auditor_name,
                           COALESCE(peng.posisi, 'Auditor') as auditor_role,
                           COALESCE(pes.id, tk.pesanan_id) as pesanan_id,
                           pes.nomor_nota, pes.total_netto, pes.total_dibayar, pes.status_pembayaran, pes.sisa_tagihan, pes.tanggal_pesanan
                    FROM public.kunjungan_konsinyasi kk
                    JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                    LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                    LEFT JOIN public.v_karyawan_info k ON COALESCE(kk.sales_driver_id, p.sales_driver_id) = k.id
                    LEFT JOIN public.v_karyawan_info k_driver ON kk.driver_pengirim_id = k_driver.id
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
                    SELECT COALESCE(gp.id, i.id) as grup_id,
                           COALESCE(gp.nama_grup, i.nama_item) as nama_item,
                           COALESCE(gp.nama_grup, i.nama_item) as nama_grup,
                           COALESCE(gp.barcode_universal, i.kode_sku) as kode_sku,
                           COALESCE(gp.barcode_universal, i.kode_sku) as barcode_universal,
                           COALESCE(gp.satuan_dasar, i.satuan_dasar, 'pcs') as satuan_dasar,
                           array_to_string(array_agg(DISTINCT i.nama_item), ', ') as varian_list,
                           SUM(rkk.stok_titip_awal) as stok_titip_awal,
                           SUM(rkk.tambah_titip_baru) as tambah_titip_baru,
                           SUM(rkk.sisa_fisik_di_rak) as sisa_fisik_di_rak,
                           SUM(rkk.jumlah_laku_terjual) as jumlah_laku_terjual,
                           SUM(rkk.retur_rusak) as retur_rusak,
                           SUM(rkk.retur_bagus) as retur_bagus,
                           SUM(rkk.selisih_qty) as selisih_qty,
                           MAX(rkk.harga_satuan_deal) as harga_satuan_deal,
                           SUM(rkk.subtotal_laku) as subtotal_laku
                    FROM public.rincian_kunjungan_konsinyasi rkk
                    JOIN public.item i ON rkk.item_id = i.id
                    LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                    WHERE rkk.kunjungan_id = :id
                    GROUP BY COALESCE(gp.id, i.id), COALESCE(gp.nama_grup, i.nama_item), COALESCE(gp.barcode_universal, i.kode_sku), COALESCE(gp.satuan_dasar, i.satuan_dasar, 'pcs')
                    HAVING (
                        SUM(rkk.stok_titip_awal) > 0 
                        OR SUM(rkk.tambah_titip_baru) > 0 
                        OR SUM(rkk.sisa_fisik_di_rak) > 0 
                        OR SUM(rkk.jumlah_laku_terjual) > 0 
                        OR SUM(rkk.retur_rusak) > 0 
                        OR SUM(rkk.retur_bagus) > 0
                    )
                    ORDER BY COALESCE(gp.nama_grup, i.nama_item) ASC
                ", ['id' => $visit['id']]);
            }

            if (!Auth::can(['consignment.opname_all', 'consignment.view_all', 'consignment.piutang']) && !Auth::isAssignedStore($visit['pelanggan_id'])) {
                $this->flashError('Akses Ditolak: Dokumen ini bukan dari toko binaan Anda.');
                $this->redirect('/consignment/riwayat-kunjungan');
                return;
            }

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

            $this->view('consignment.nota_konsinyasi', [
                'visit' => $visit,
                'details' => $details,
                'bankAccount' => $bankAccount,
                'isInvoiced' => $isInvoiced,
                'isPdf' => false
            ]);

        } catch (Throwable $e) {
            $this->flashError('Gagal membuka dokumen cetak: ' . $e->getMessage());
            $this->redirect('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)($kunjunganId ?: '')));
        }
    }

    /**
     * 5d. Unduh Berita Acara Opname / Faktur Tagihan Resmi Konsinyasi dalam format PDF
     * (GET /consignment/opname/hasil/pdf?kunjungan_id=... atau ?pesanan_id=...)
     */
    public function notaPdf(): void
    {
        Auth::requirePermission(['consignment.opname_all', 'consignment.opname_assigned', 'consignment.view_all', 'consignment.view_assigned']);

        $kunjunganId = (string)$this->input('kunjungan_id');
        $pesananId = (string)$this->input('pesanan_id');
        $format = PrintDocumentHelper::resolveFormat($this->input('format', 'standard'));

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
                           p.nomor_whatsapp, p.nomor_whatsapp as nomor_telepon, p.nama_pemilik,
                           p.tipe_konsinyasi,
                           COALESCE(k.nama_karyawan, peng.nama_lengkap, 'Petugas ERP') as sales_name,
                           COALESCE(k.posisi, 'Sales Lapangan') as sales_role,
                           COALESCE(k_driver.nama_karyawan, '-') as driver_name,
                           peng.nama_lengkap as auditor_name,
                           COALESCE(peng.posisi, 'Auditor') as auditor_role,
                           (SELECT kk.nomor_kunjungan FROM public.tagihan_kunjungan tk JOIN public.kunjungan_konsinyasi kk ON tk.kunjungan_id = kk.id WHERE tk.pesanan_id = pes.id ORDER BY kk.tanggal_kunjungan DESC LIMIT 1) as nomor_kunjungan,
                           pes.tanggal_pesanan as tanggal_kunjungan
                    FROM public.pesanan pes
                    JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                    LEFT JOIN public.v_karyawan_info k ON COALESCE(pes.sales_driver_id, p.sales_driver_id) = k.id
                    LEFT JOIN LATERAL (
                        SELECT kk2.driver_pengirim_id 
                        FROM public.tagihan_kunjungan tk2 
                        JOIN public.kunjungan_konsinyasi kk2 ON tk2.kunjungan_id = kk2.id 
                        WHERE tk2.pesanan_id = pes.id AND kk2.driver_pengirim_id IS NOT NULL 
                        LIMIT 1
                    ) l_driver ON TRUE
                    LEFT JOIN public.v_karyawan_info k_driver ON l_driver.driver_pengirim_id = k_driver.id
                    LEFT JOIN public.pengguna peng ON pes.dibuat_oleh = peng.id
                    WHERE pes.id = :id
                ", ['id' => $pesananId]);

                if (!$visit) {
                    throw new \Exception('Data faktur tagihan tidak ditemukan.');
                }

                $isInvoiced = true;

                // Ambil agregasi grup produk resmi
                $details = Database::fetchAll("
                    SELECT COALESCE(gp.id, i.id) as grup_id,
                           COALESCE(gp.nama_grup, i.nama_item) as nama_item,
                           COALESCE(gp.nama_grup, i.nama_item) as nama_grup,
                           COALESCE(gp.barcode_universal, i.kode_sku) as kode_sku,
                           COALESCE(gp.barcode_universal, i.kode_sku) as barcode_universal,
                           COALESCE(gp.satuan_dasar, i.satuan_dasar, 'pcs') as satuan_dasar,
                           array_to_string(array_agg(DISTINCT i.nama_item), ', ') as varian_list,
                           SUM(COALESCE(rkk.stok_titip_awal, 0)) as stok_titip_awal,
                           SUM(COALESCE(rkk.tambah_titip_baru, 0)) as tambah_titip_baru,
                           SUM(COALESCE(rkk.sisa_fisik_di_rak, 0)) as sisa_fisik_di_rak,
                           SUM(ip.kuantitas_satuan_dasar) as jumlah_laku_terjual,
                           SUM(COALESCE(rkk.retur_rusak, 0)) as retur_rusak,
                           SUM(COALESCE(rkk.retur_bagus, 0)) as retur_bagus,
                           SUM(COALESCE(rkk.selisih_qty, 0)) as selisih_qty,
                           MAX(ip.harga_satuan_deal) as harga_satuan_deal,
                           SUM(ip.subtotal) as subtotal_laku
                    FROM public.item_pesanan ip
                    JOIN public.item i ON ip.item_id = i.id
                    LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                    LEFT JOIN (
                        SELECT r.item_id,
                               SUM(r.stok_titip_awal) as stok_titip_awal,
                               SUM(r.tambah_titip_baru) as tambah_titip_baru,
                               SUM(r.sisa_fisik_di_rak) as sisa_fisik_di_rak,
                               SUM(r.retur_rusak) as retur_rusak,
                               SUM(r.retur_bagus) as retur_bagus,
                               SUM(r.selisih_qty) as selisih_qty
                        FROM public.rincian_kunjungan_konsinyasi r
                        JOIN public.tagihan_kunjungan tk ON r.kunjungan_id = tk.kunjungan_id
                        WHERE tk.pesanan_id = :id
                        GROUP BY r.item_id
                    ) rkk ON rkk.item_id = ip.item_id
                    WHERE ip.pesanan_id = :id
                    GROUP BY COALESCE(gp.id, i.id), COALESCE(gp.nama_grup, i.nama_item), COALESCE(gp.barcode_universal, i.kode_sku), COALESCE(gp.satuan_dasar, i.satuan_dasar, 'pcs')
                    ORDER BY COALESCE(gp.nama_grup, i.nama_item) ASC
                ", ['id' => $pesananId]);

            } else {
                // Kasus 2: Diberikan kunjungan_id (Dari Halaman Hasil Opname)
                $sql = "
                    SELECT kk.*, p.nama_toko, p.kode_pelanggan, p.alamat_lengkap, p.nomor_whatsapp, p.nomor_whatsapp as nomor_telepon, p.nama_pemilik,
                           p.tipe_konsinyasi,
                           COALESCE(k.nama_karyawan, peng.nama_lengkap, 'Sales Lapangan') as sales_name,
                           COALESCE(k.posisi, 'Sales Lapangan') as sales_role,
                           COALESCE(k_driver.nama_karyawan, '-') as driver_name,
                           peng.nama_lengkap as auditor_name,
                           COALESCE(peng.posisi, 'Auditor') as auditor_role,
                           COALESCE(pes.id, tk.pesanan_id) as pesanan_id,
                           pes.nomor_nota, pes.total_netto, pes.total_dibayar, pes.status_pembayaran, pes.sisa_tagihan, pes.tanggal_pesanan
                    FROM public.kunjungan_konsinyasi kk
                    JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                    LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                    LEFT JOIN public.v_karyawan_info k ON COALESCE(kk.sales_driver_id, p.sales_driver_id) = k.id
                    LEFT JOIN public.v_karyawan_info k_driver ON kk.driver_pengirim_id = k_driver.id
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
                    SELECT COALESCE(gp.id, i.id) as grup_id,
                           COALESCE(gp.nama_grup, i.nama_item) as nama_item,
                           COALESCE(gp.nama_grup, i.nama_item) as nama_grup,
                           COALESCE(gp.barcode_universal, i.kode_sku) as kode_sku,
                           COALESCE(gp.barcode_universal, i.kode_sku) as barcode_universal,
                           COALESCE(gp.satuan_dasar, i.satuan_dasar, 'pcs') as satuan_dasar,
                           array_to_string(array_agg(DISTINCT i.nama_item), ', ') as varian_list,
                           SUM(rkk.stok_titip_awal) as stok_titip_awal,
                           SUM(rkk.tambah_titip_baru) as tambah_titip_baru,
                           SUM(rkk.sisa_fisik_di_rak) as sisa_fisik_di_rak,
                           SUM(rkk.jumlah_laku_terjual) as jumlah_laku_terjual,
                           SUM(rkk.retur_rusak) as retur_rusak,
                           SUM(rkk.retur_bagus) as retur_bagus,
                           SUM(rkk.selisih_qty) as selisih_qty,
                           MAX(rkk.harga_satuan_deal) as harga_satuan_deal,
                           SUM(rkk.subtotal_laku) as subtotal_laku
                    FROM public.rincian_kunjungan_konsinyasi rkk
                    JOIN public.item i ON rkk.item_id = i.id
                    LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                    WHERE rkk.kunjungan_id = :id
                    GROUP BY COALESCE(gp.id, i.id), COALESCE(gp.nama_grup, i.nama_item), COALESCE(gp.barcode_universal, i.kode_sku), COALESCE(gp.satuan_dasar, i.satuan_dasar, 'pcs')
                    HAVING (
                        SUM(rkk.stok_titip_awal) > 0 
                        OR SUM(rkk.tambah_titip_baru) > 0 
                        OR SUM(rkk.sisa_fisik_di_rak) > 0 
                        OR SUM(rkk.jumlah_laku_terjual) > 0 
                        OR SUM(rkk.retur_rusak) > 0 
                        OR SUM(rkk.retur_bagus) > 0
                    )
                    ORDER BY COALESCE(gp.nama_grup, i.nama_item) ASC
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
                'isPdf' => true,
                'formatMode' => $format
            ]);
            require ROOT_PATH . '/views/consignment/nota_konsinyasi.php';
            $html = ob_get_clean();

            if ($isInvoiced) {
                $cleanNota = !empty($visit['nomor_nota']) 
                    ? preg_replace('/[^A-Za-z0-9]/', ' ', (string)$visit['nomor_nota']) 
                    : 'FAKTUR ' . date('Ymd His');
                $filename = "Faktur Konsinyasi {$cleanNota}";
            } else {
                $cleanKunj = !empty($visit['nomor_kunjungan'])
                    ? preg_replace('/[^A-Za-z0-9]/', ' ', (string)$visit['nomor_kunjungan'])
                    : 'OPNAME ' . date('Ymd His');
                $filename = "Berita Acara Opname {$cleanKunj}";
            }

            PrintDocumentHelper::downloadPdf($html, $filename, $format);

        } catch (Throwable $e) {
            $this->flashError('Gagal membuat dokumen PDF: ' . $e->getMessage());
            $this->redirect('/consignment/nota-print?' . (!empty($pesananId) ? 'pesanan_id=' . urlencode((string)$pesananId) : 'kunjungan_id=' . urlencode((string)$kunjunganId)));
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
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $sj = Database::fetchOne("
                SELECT sj.id, sj.nomor_surat_jalan, sj.pesanan_id, sj.status_surat_jalan,
                       p.id as pelanggan_id, p.nama_toko, p.is_konsinyasi
                FROM public.surat_jalan sj
                JOIN public.pesanan pes ON sj.pesanan_id = pes.id
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                WHERE sj.id = :id FOR UPDATE
            ", ['id' => $suratJalanId]);

            if (!$sj) {
                $pdo->rollBack();
                throw new \Exception('Surat jalan tidak ditemukan.');
            }

            if ($sj['status_surat_jalan'] !== 'selesai_diterima') {
                Database::execute("
                    UPDATE public.surat_jalan
                    SET status_surat_jalan = 'selesai_diterima',
                        waktu_sampai = COALESCE(waktu_sampai, NOW()),
                        diubah_pada = NOW()
                    WHERE id = :id
                ", ['id' => $suratJalanId]);

                Database::execute("
                    UPDATE public.pesanan
                    SET status_pemrosesan = 'selesai_dikirim',
                        diubah_pada = NOW()
                    WHERE id = :id
                ", ['id' => $sj['pesanan_id']]);
            }

            $pdo->commit();

            ActivityLog::log(
                'logistik',
                'UPDATE',
                "Driver/Sales mengonfirmasi terima barang di {$sj['nama_toko']} ({$sj['nomor_surat_jalan']}).",
                'surat_jalan',
                $suratJalanId
            );

            $this->flashSuccess("Pengiriman {$sj['nomor_surat_jalan']} berhasil dikonfirmasi! Pengiriman toko {$sj['nama_toko']} sudah selesai.");
            $this->redirect($redirectUrl);

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
            error_log("ConsignmentController laporanPenjualan error: " . $e->getMessage());
            $this->flashError("Gagal memuat laporan penjualan: " . $e->getMessage());
            $this->redirect('/consignment');
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
            $isOwner   = Auth::isOwner();
            $isAdmin   = Auth::isAdmin();
            $activeTab = (string)$this->input('tab', 'buat');

            // Filter untuk tab Buat Tagihan
            $filterStoreId  = (string)$this->input('pelanggan_id', '');
            $filterStart    = (string)$this->input('start_date', date('Y-m-01'));
            $filterEnd      = (string)$this->input('end_date', date('Y-m-d'));

            // Filter untuk tab Daftar Tagihan
            $filterStatus           = (string)$this->input('status', '');
            $filterTipeKonsinyasi   = (string)$this->input('tipe_konsinyasi', '');

            // Daftar toko untuk filter dropdown
            $stores = Database::fetchAll("
                SELECT id, nama_toko, kode_pelanggan, tipe_konsinyasi 
                FROM public.pelanggan 
                WHERE is_konsinyasi = TRUE AND status_aktif = TRUE
                ORDER BY nama_toko ASC
            ");

            // TAB 1: Kunjungan yang BELUM ditagih (total_laku > 0 dan belum ada di tagihan_kunjungan)
            $sqlUnbilled = "
                SELECT 
                    kk.id, kk.nomor_kunjungan, kk.tanggal_kunjungan, kk.total_laku_nominal,
                    p.nama_toko, p.kode_pelanggan, p.id as pelanggan_id,
                    COALESCE(p.tipe_konsinyasi, 'kolektif_toko') as tipe_konsinyasi,
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

            // TAB 2: Semua tagihan konsinyasi (Rolling Nota + Kolektif Toko; aktif + lunas + history)
            $sqlTagihan = "
                SELECT 
                    pes.id as pesanan_id, pes.nomor_nota, pes.tanggal_pesanan, pes.total_netto,
                    pes.total_dibayar, pes.sisa_tagihan, pes.status_pembayaran, pes.catatan,
                    p.id as pelanggan_id, p.nama_toko, p.kode_pelanggan, p.nomor_whatsapp,
                    COALESCE(p.tipe_konsinyasi, 'kolektif_toko') as tipe_konsinyasi,
                    COALESCE(kar.nama_karyawan, 'N/A') as nama_sales,
                    COALESCE(k_driver.nama_karyawan, '-') as driver_name,
                    (SELECT COUNT(*) FROM public.tagihan_kunjungan tk WHERE tk.pesanan_id = pes.id) as jumlah_kunjungan
                FROM public.pesanan pes
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.v_karyawan_info kar ON COALESCE(pes.sales_driver_id, p.sales_driver_id) = kar.id
                LEFT JOIN LATERAL (
                    SELECT kk2.driver_pengirim_id 
                    FROM public.tagihan_kunjungan tk2 
                    JOIN public.kunjungan_konsinyasi kk2 ON tk2.kunjungan_id = kk2.id 
                    WHERE tk2.pesanan_id = pes.id AND kk2.driver_pengirim_id IS NOT NULL 
                    LIMIT 1
                ) l_driver ON TRUE
                LEFT JOIN public.v_karyawan_info k_driver ON l_driver.driver_pengirim_id = k_driver.id
                WHERE (
                    (pes.tipe_pembayaran = 'konsinyasi' AND pes.is_tagihan = TRUE)
                    OR (p.is_konsinyasi = TRUE AND pes.is_tagihan = TRUE)
                    OR EXISTS (SELECT 1 FROM public.tagihan_kunjungan tk WHERE tk.pesanan_id = pes.id)
                )
                AND pes.status_pembayaran != 'dibatalkan'
            ";
            $tagihanParams = [];

            if (!empty($filterStatus)) {
                $sqlTagihan .= " AND pes.status_pembayaran = :status";
                $tagihanParams['status'] = $filterStatus;
            }
            if (!empty($filterTipeKonsinyasi)) {
                $sqlTagihan .= " AND p.tipe_konsinyasi = :tipe_konsinyasi";
                $tagihanParams['tipe_konsinyasi'] = $filterTipeKonsinyasi;
            }
            if (!empty($filterStoreId) && $activeTab === 'daftar') {
                $sqlTagihan .= " AND p.id = :pelanggan_id";
                $tagihanParams['pelanggan_id'] = $filterStoreId;
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
                'pageTitle'           => 'Tagihan Konsinyasi',
                'pageSubtitle'        => 'Buat & Kelola Tagihan Penjualan Toko Konsinyasi (Rolling Nota & Kolektif)',
                'csrfToken'           => \App\Helpers\CSRF::token(),
                'unbilledVisits'      => $unbilledVisits,
                'tagihan'             => $tagihan,
                'stores'              => $stores,
                'cashAccounts'        => $cashAccounts,
                'totalOutstanding'    => $totalOutstanding,
                'filterStoreId'       => $filterStoreId,
                'filterStart'         => $filterStart,
                'filterEnd'           => $filterEnd,
                'filterStatus'        => $filterStatus,
                'filterTipeKonsinyasi'=> $filterTipeKonsinyasi,
                'activeTab'           => $activeTab,
                'isOwner'             => $isOwner,
                'isAdmin'             => $isAdmin,
            ]);

        } catch (Throwable $e) {
            error_log("ConsignmentController tagihan error: " . $e->getMessage());
            $this->flashError("Gagal memuat tagihan konsinyasi: " . $e->getMessage());
            $this->redirect('/consignment');
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

        $rawIds = (array)$this->input('kunjungan_ids', []);
        $uuidRegex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        $kunjunganIds = [];
        foreach ($rawIds as $kid) {
            $kidStr = trim((string)$kid);
            if (preg_match($uuidRegex, $kidStr)) {
                $kunjunganIds[] = $kidStr;
            }
        }
        $kunjunganIds = array_values(array_unique($kunjunganIds));

        if (empty($kunjunganIds)) {
            $this->flashError('Pilih minimal 1 sesi kunjungan yang valid untuk dibuatkan tagihan.');
            $this->redirect('/consignment/tagihan');
            return;
        }

        try {
            // Format array untuk PostgreSQL: {uuid1,uuid2,...}
            $pgArray = '{' . implode(',', $kunjunganIds) . '}';

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
            $filterTipeKonsinyasi = (string)$this->input('tipe_konsinyasi', '');

            $sql = "
                SELECT 
                    pes.nomor_nota, pes.tanggal_pesanan, pes.total_netto,
                    pes.total_dibayar, pes.sisa_tagihan, pes.status_pembayaran,
                    p.nama_toko, p.kode_pelanggan, p.nomor_whatsapp,
                    COALESCE(p.tipe_konsinyasi, 'kolektif_toko') as tipe_konsinyasi,
                    COALESCE(kar.nama_karyawan, 'N/A') as nama_sales,
                    (SELECT COUNT(*) FROM public.tagihan_kunjungan tk WHERE tk.pesanan_id = pes.id) as jumlah_kunjungan
                FROM public.pesanan pes
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.v_karyawan_info kar ON COALESCE(pes.sales_driver_id, p.sales_driver_id) = kar.id
                WHERE (
                    (pes.tipe_pembayaran = 'konsinyasi' AND pes.is_tagihan = TRUE)
                    OR (p.is_konsinyasi = TRUE AND pes.is_tagihan = TRUE)
                    OR EXISTS (SELECT 1 FROM public.tagihan_kunjungan tk WHERE tk.pesanan_id = pes.id)
                )
                AND pes.status_pembayaran != 'dibatalkan'
            ";
            $params = [];

            if (!empty($filterStatus)) {
                $sql .= " AND pes.status_pembayaran = :status";
                $params['status'] = $filterStatus;
            }
            if (!empty($filterTipeKonsinyasi)) {
                $sql .= " AND p.tipe_konsinyasi = :tipe_konsinyasi";
                $params['tipe_konsinyasi'] = $filterTipeKonsinyasi;
            }
            $sql .= " ORDER BY pes.tanggal_pesanan DESC";
            $rows_data = Database::fetchAll($sql, $params);

            $headers = ['No', 'No. Tagihan', 'Tanggal', 'Nama Toko', 'Kode Toko', 'Tipe Toko', 'Sales PIC', 'Jml Kunjungan', 'Total Tagihan (Rp)', 'Terbayar (Rp)', 'Sisa (Rp)', 'Status'];
            $rows    = [];
            $no      = 1;
            $totTotal = $totBayar = $totSisa = 0;

            foreach ($rows_data as $r) {
                $tot   = (float)$r['total_netto'];
                $bayar = (float)$r['total_dibayar'];
                $sisa  = (float)$r['sisa_tagihan'];
                $totTotal += $tot; $totBayar += $bayar; $totSisa += $sisa;

                $tipeLabel = ($r['tipe_konsinyasi'] ?? '') === 'rolling_nota' ? 'Rolling Nota' : 'Kolektif Toko';

                $rows[] = [
                    $no++,
                    $r['nomor_nota'] ?? '-',
                    date('d/m/Y', strtotime($r['tanggal_pesanan'])),
                    $r['nama_toko'],
                    $r['kode_pelanggan'] ?? '-',
                    $tipeLabel,
                    $r['nama_sales'],
                    (int)$r['jumlah_kunjungan'],
                    $tot, $bayar, $sisa,
                    strtoupper(str_replace('_', ' ', (string)($r['status_pembayaran'] ?? '-')))
                ];
            }

            $rows[] = ['', '', '', '', '', '', '', 'GRAND TOTAL:', $totTotal, $totBayar, $totSisa, ''];

            ExcelExport::download("Tagihan Konsinyasi " . date('Ymd') . ".xlsx", $headers, $rows, "Tagihan Konsinyasi");
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
            error_log("ConsignmentController assignment error: " . $e->getMessage());
            $this->flashError("Gagal memuat assignment sales: " . $e->getMessage());
            $this->redirect('/consignment');
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

            // 3. Query Master Skema Tier Komisi
            $allTiers = Database::fetchAll("
                SELECT id, urutan, nama_tier, omzet_min, omzet_maks, persentase, status_aktif
                FROM public.skema_komisi_sales
                WHERE status_aktif = TRUE
                ORDER BY urutan ASC, omzet_min ASC
            ");

            // 4. Query Personel Sales Lapangan
            $salesQuery = "
                SELECT k.id as sales_id, k.nama_karyawan, k.nomor_telepon, k.posisi
                FROM public.v_karyawan_info k
                WHERE k.posisi = 'sales' AND k.status_aktif = TRUE
            ";
            $salesParams = [];
            if (!empty($selectedSalesId)) {
                $salesQuery .= " AND k.id = :sales_id";
                $salesParams['sales_id'] = $selectedSalesId;
            } elseif ($isSalesLocked && $unlinkedAccount) {
                $salesQuery .= " AND 1=0";
            }
            $salesQuery .= " ORDER BY k.nama_karyawan ASC";
            $salesRows = Database::fetchAll($salesQuery, $salesParams);

            $commissions = [];
            $storeBreakdown = [];
            $breakdownKonsin = [];
            $breakdownB2b = [];
            $breakdownUnbilled = [];

            foreach ($salesRows as $sales) {
                $sid = $sales['sales_id'];

                // A. Toko Binaan Konsinyasi Tetap
                $tokoRow = Database::fetchOne("
                    SELECT COUNT(id) as total_toko
                    FROM public.pelanggan
                    WHERE sales_driver_id = :sales_id AND is_konsinyasi = TRUE AND status_aktif = TRUE
                ", ['sales_id' => $sid]);
                $totalTokoAssigned = (int)($tokoRow['total_toko'] ?? 0);

                // B. Omzet Konsinyasi: HANYA Tagihan yang SUDAH DIBAYAR (pesanan.total_dibayar)
                // Sisa hutang / piutang (pesanan.sisa_tagihan) TIDAK MASUK OMZET
                $konsinRow = Database::fetchOne("
                    SELECT 
                        COALESCE(SUM(pes.total_dibayar), 0) as omzet_terbayar,
                        COALESCE(SUM(pes.sisa_tagihan), 0) as sisa_hutang,
                        COALESCE(SUM(pes.total_netto), 0) as total_faktur,
                        COUNT(pes.id) as jumlah_faktur
                    FROM public.pesanan pes
                    JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                    WHERE p.sales_driver_id = :sales_id
                      AND pes.is_tagihan = TRUE
                      AND pes.tipe_pembayaran = 'konsinyasi'
                      AND pes.status_pemrosesan != 'dibatalkan'
                      AND pes.tanggal_pesanan >= :start_date AND pes.tanggal_pesanan <= :end_date
                ", [
                    'sales_id' => $sid,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);

                // C. Omzet Pesanan Grosir / Reguler B2B: HANYA yang SUDAH DIBAYAR (pesanan.total_dibayar)
                $b2bRow = Database::fetchOne("
                    SELECT 
                        COALESCE(SUM(pes.total_dibayar), 0) as omzet_terbayar,
                        COALESCE(SUM(pes.sisa_tagihan), 0) as sisa_hutang,
                        COALESCE(SUM(pes.total_netto), 0) as total_faktur,
                        COUNT(pes.id) as jumlah_faktur
                    FROM public.pesanan pes
                    LEFT JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                    WHERE (pes.sales_driver_id = :sales_id OR (pes.sales_driver_id IS NULL AND p.sales_driver_id = :sales_id))
                      AND pes.is_tagihan = TRUE
                      AND pes.tipe_pembayaran != 'konsinyasi'
                      AND pes.status_pemrosesan != 'dibatalkan'
                      AND pes.tanggal_pesanan >= :start_date AND pes.tanggal_pesanan <= :end_date
                ", [
                    'sales_id' => $sid,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);

                $omzetKonsin = (float)($konsinRow['omzet_terbayar'] ?? 0);
                $omzetB2b    = (float)($b2bRow['omzet_terbayar'] ?? 0);
                $totalOmzet  = $omzetKonsin + $omzetB2b;

                $sisaKonsin  = (float)($konsinRow['sisa_hutang'] ?? 0);
                $sisaB2b     = (float)($b2bRow['sisa_hutang'] ?? 0);
                $totalPiutangPending = $sisaKonsin + $sisaB2b;

                // D. Hitung Tier Komisi Otomatis via DB RPC fn_hitung_tier_komisi_sales
                $tierRpcRaw = Database::fetchOne("
                    SELECT public.fn_hitung_tier_komisi_sales(:omzet) as r
                ", ['omzet' => $totalOmzet])['r'] ?? '{}';
                $tierData = json_decode($tierRpcRaw, true) ?? [];

                $persenKomisi = (float)($tierData['persentase'] ?? 0);
                $nominalKomisi = (float)($tierData['nominal_komisi'] ?? 0);

                $commissions[] = [
                    'sales_id' => $sid,
                    'nama_karyawan' => $sales['nama_karyawan'],
                    'nomor_telepon' => $sales['nomor_telepon'],
                    'posisi' => $sales['posisi'],
                    'total_toko_assigned' => $totalTokoAssigned,
                    'omzet_konsinyasi_terbayar' => $omzetKonsin,
                    'omzet_b2b_terbayar' => $omzetB2b,
                    'total_omzet' => $totalOmzet,
                    'total_piutang_pending' => $totalPiutangPending,
                    'persentase_komisi' => $persenKomisi,
                    'nominal_komisi' => $nominalKomisi,
                    'tier_info' => $tierData,
                    'jumlah_faktur_konsin' => (int)($konsinRow['jumlah_faktur'] ?? 0),
                    'jumlah_faktur_b2b' => (int)($b2bRow['jumlah_faktur'] ?? 0),
                ];

                // E. Breakdown Toko Binaan Konsinyasi
                $storeBreakdown[$sid] = Database::fetchAll("
                    SELECT p.sales_driver_id as sales_id, p.id as store_id, p.kode_pelanggan, p.nama_toko, p.alamat_lengkap,
                           COALESCE(w.nama_wilayah, 'Tanpa Wilayah') as nama_wilayah,
                           COALESCE(v_vis.total_kunjungan, 0) as total_kunjungan,
                           v_vis.terakhir_kunjungan,
                           COALESCE(v_inv.omzet_terbayar, 0) as omzet_terbayar,
                           COALESCE(v_inv.sisa_hutang, 0) as sisa_hutang,
                           COALESCE(v_inv.total_faktur, 0) as total_faktur
                    FROM public.pelanggan p
                    LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                    LEFT JOIN (
                        SELECT pelanggan_id, COUNT(id) as total_kunjungan, MAX(tanggal_kunjungan) as terakhir_kunjungan
                        FROM public.kunjungan_konsinyasi
                        WHERE tanggal_kunjungan >= :start_date AND tanggal_kunjungan <= :end_date
                        GROUP BY pelanggan_id
                    ) v_vis ON v_vis.pelanggan_id = p.id
                    LEFT JOIN (
                        SELECT pelanggan_id, 
                                SUM(total_dibayar) as omzet_terbayar, 
                                SUM(sisa_tagihan) as sisa_hutang,
                                SUM(total_netto) as total_faktur
                        FROM public.pesanan
                        WHERE is_tagihan = TRUE 
                          AND tipe_pembayaran = 'konsinyasi'
                          AND status_pemrosesan != 'dibatalkan'
                          AND tanggal_pesanan >= :start_date AND tanggal_pesanan <= :end_date
                        GROUP BY pelanggan_id
                    ) v_inv ON v_inv.pelanggan_id = p.id
                    WHERE p.sales_driver_id = :sales_id AND p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                    ORDER BY omzet_terbayar DESC, p.nama_toko ASC
                ", ['sales_id' => $sid, 'start_date' => $startDate, 'end_date' => $endDate]);

                // F. Breakdown Tagihan Konsinyasi Faktur
                $breakdownKonsin[$sid] = Database::fetchAll("
                    SELECT pes.id, pes.nomor_nota, pes.tanggal_pesanan, pes.total_netto,
                           pes.total_dibayar, pes.sisa_tagihan, pes.status_pembayaran,
                           p.nama_toko, p.kode_pelanggan
                    FROM public.pesanan pes
                    JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                    WHERE p.sales_driver_id = :sales_id
                      AND pes.is_tagihan = TRUE
                      AND pes.tipe_pembayaran = 'konsinyasi'
                      AND pes.status_pemrosesan != 'dibatalkan'
                      AND pes.tanggal_pesanan >= :start_date AND pes.tanggal_pesanan <= :end_date
                    ORDER BY pes.tanggal_pesanan DESC, pes.nomor_nota DESC
                ", ['sales_id' => $sid, 'start_date' => $startDate, 'end_date' => $endDate]);

                // G. Breakdown Pesanan B2B Faktur
                $breakdownB2b[$sid] = Database::fetchAll("
                    SELECT pes.id, pes.nomor_nota, pes.tanggal_pesanan, pes.total_netto,
                           pes.total_dibayar, pes.sisa_tagihan, pes.status_pembayaran,
                           COALESCE(p.nama_toko, 'Toko Umum') as nama_toko, p.kode_pelanggan
                    FROM public.pesanan pes
                    LEFT JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                    WHERE (pes.sales_driver_id = :sales_id OR (pes.sales_driver_id IS NULL AND p.sales_driver_id = :sales_id))
                      AND pes.is_tagihan = TRUE
                      AND pes.tipe_pembayaran != 'konsinyasi'
                      AND pes.status_pemrosesan != 'dibatalkan'
                      AND pes.tanggal_pesanan >= :start_date AND pes.tanggal_pesanan <= :end_date
                    ORDER BY pes.tanggal_pesanan DESC, pes.nomor_nota DESC
                ", ['sales_id' => $sid, 'start_date' => $startDate, 'end_date' => $endDate]);

                // H. Breakdown Kunjungan Belum Ditagih (Pending Omzet)
                $breakdownUnbilled[$sid] = Database::fetchAll("
                    SELECT kk.id, kk.nomor_kunjungan, kk.tanggal_kunjungan, kk.total_laku_nominal,
                           p.nama_toko, p.kode_pelanggan
                    FROM public.kunjungan_konsinyasi kk
                    JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                    WHERE p.sales_driver_id = :sales_id
                      AND kk.tanggal_kunjungan >= :start_date AND kk.tanggal_kunjungan <= :end_date
                      AND NOT EXISTS (
                          SELECT 1 FROM public.tagihan_kunjungan tk WHERE tk.kunjungan_id = kk.id
                      )
                    ORDER BY kk.tanggal_kunjungan DESC
                ", ['sales_id' => $sid, 'start_date' => $startDate, 'end_date' => $endDate]);
            }

            // Urutkan rekap komisi: total omzet terbesar di atas
            usort($commissions, fn($a, $b) => $b['total_omzet'] <=> $a['total_omzet']);

            // 5. Kalkulasi Ringkasan KPI Global
            $grandOmzet = array_sum(array_column($commissions, 'total_omzet'));
            $grandKonsinTerbayar = array_sum(array_column($commissions, 'omzet_konsinyasi_terbayar'));
            $grandB2bTerbayar = array_sum(array_column($commissions, 'omzet_b2b_terbayar'));
            $grandPiutangPending = array_sum(array_column($commissions, 'total_piutang_pending'));
            $grandKomisi = array_sum(array_column($commissions, 'nominal_komisi'));
            $totalStoresInvolved = array_sum(array_column($commissions, 'total_toko_assigned'));

            $this->view('consignment.komisi_sales', [
                'pageTitle' => $isSalesLocked ? 'Komisi Penjualan Saya' : 'Rekap Komisi Sales',
                'pageSubtitle' => $isSalesLocked ? 'Perhitungan komisi bulanan toko binaan tetap Anda' : 'Insentif omzet bulanan toko konsinyasi binaan per sales',
                'commissions' => $commissions,
                'storeBreakdown' => $storeBreakdown,
                'breakdownKonsin' => $breakdownKonsin,
                'breakdownB2b' => $breakdownB2b,
                'breakdownUnbilled' => $breakdownUnbilled,
                'allTiers' => $allTiers,
                'month' => $month,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'grandOmzet' => $grandOmzet,
                'grandKonsinTerbayar' => $grandKonsinTerbayar,
                'grandB2bTerbayar' => $grandB2bTerbayar,
                'grandPiutangPending' => $grandPiutangPending,
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
            error_log("ConsignmentController komisiSales error: " . $e->getMessage());
            $this->flashError("Gagal memuat komisi sales: " . $e->getMessage());
            $this->redirect('/consignment');
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
                \App\Helpers\ExcelExport::download("Laporan Barang Hilang Gantung " . date('Ymd') . ".xlsx", $headers, $rows, "Barang Hilang");
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

            $cleanStart = str_replace('-', ' ', $startDate);
            $cleanEnd = str_replace('-', ' ', $endDate);
            \App\Helpers\ExcelExport::download("Laporan Kerugian Rusak {$cleanStart} sd {$cleanEnd}.xlsx", $headers, $rows, "Kerugian Rusak");

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
            error_log("ConsignmentController earlyWarning error: " . $e->getMessage());
            $this->flashError("Gagal memuat early warning toko: " . $e->getMessage());
            $this->redirect('/consignment');
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

            // Query Paginated Visits dengan 1 Aggregated Join Terindeks (idx_rkk_kunjungan_id)
            $sql = "
                SELECT kk.id, kk.nomor_kunjungan, kk.tanggal_kunjungan, kk.total_laku_nominal, kk.catatan,
                       p.nama_toko, p.kode_pelanggan,
                       COALESCE(k.nama_karyawan, peng.nama_lengkap, 'Petugas ERP') as nama_sales,
                       k_driver.nama_karyawan as nama_driver,
                       peng.nama_lengkap as auditor_name,
                       pes.id as pesanan_id, pes.nomor_nota, pes.status_pembayaran, pes.total_netto, pes.total_dibayar, pes.sisa_tagihan,
                       COALESCE(rkk_agg.total_sku, 0) as total_sku,
                       COALESCE(rkk_agg.total_laku, 0) as total_laku,
                       COALESCE(rkk_agg.total_retur_bagus, 0) as total_retur_bagus,
                       COALESCE(rkk_agg.total_retur_rusak, 0) as total_retur_rusak,
                       COALESCE(rkk_agg.total_loss, 0) as total_loss
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                LEFT JOIN public.v_karyawan_info k ON COALESCE(kk.sales_driver_id, p.sales_driver_id) = k.id
                LEFT JOIN public.v_karyawan_info k_driver ON kk.driver_pengirim_id = k_driver.id
                LEFT JOIN public.tagihan_kunjungan tk ON tk.kunjungan_id = kk.id
                LEFT JOIN public.pesanan pes ON (kk.pesanan_id = pes.id OR tk.pesanan_id = pes.id)
                LEFT JOIN (
                    SELECT kunjungan_id,
                           COUNT(*) as total_sku,
                           COALESCE(SUM(jumlah_laku_terjual), 0) as total_laku,
                           COALESCE(SUM(retur_bagus), 0) as total_retur_bagus,
                           COALESCE(SUM(retur_rusak), 0) as total_retur_rusak,
                           COALESCE(SUM(nilai_kerugian_rusak), 0) as total_loss
                    FROM public.rincian_kunjungan_konsinyasi
                    GROUP BY kunjungan_id
                ) rkk_agg ON rkk_agg.kunjungan_id = kk.id
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
            error_log("ConsignmentController riwayatKunjungan error: " . $e->getMessage());
            $this->flashError("Gagal memuat riwayat kunjungan: " . $e->getMessage());
            $this->redirect('/consignment');
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

            $cleanStart = str_replace('-', ' ', $startDate);
            $cleanEnd = str_replace('-', ' ', $endDate);
            ExcelExport::download("Laporan Penjualan KPI Toko {$cleanStart} sd {$cleanEnd}.xlsx", $headers, $rows, "KPI Penjualan Toko");
        } catch (Throwable $e) {
            $this->flashError('Gagal export laporan penjualan: ' . $e->getMessage());
            $this->redirect('/consignment/laporan-penjualan');
        }
    }
}

