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
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
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
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
                WHERE p.id = :id AND p.is_konsinyasi = TRUE
            ", ['id' => $storeId]);

            if (!$customer) {
                $this->flashError('Toko konsinyasi tidak ditemukan.');
                $this->redirect('/consignment/opname');
                return;
            }

            // Cek apakah ada kiriman masuk berstatus 'sedang_dikirim'
            $incomingDeliveries = Database::fetchAll("
                SELECT sj.id as surat_jalan_id, sj.nomor_surat_jalan, sj.waktu_berangkat,
                       pes.id as pesanan_id, pes.nomor_nota,
                       COUNT(ip.id) as total_sku,
                       COALESCE(SUM(ip.kuantitas_satuan_dasar), 0) as total_pcs
                FROM public.surat_jalan sj
                JOIN public.pesanan pes ON sj.pesanan_id = pes.id
                LEFT JOIN public.item_pesanan ip ON ip.pesanan_id = pes.id
                WHERE pes.pelanggan_id = :cust_id 
                  AND sj.status_surat_jalan = 'sedang_dikirim'
                  AND pes.tipe_pembayaran = 'konsinyasi'
                GROUP BY sj.id, sj.nomor_surat_jalan, sj.waktu_berangkat, pes.id, pes.nomor_nota
                ORDER BY sj.waktu_berangkat DESC
            ", ['cust_id' => $storeId]);

            // Ambil semua item yang ada di rak toko ini (termasuk yang 0 pcs)
            $shelfItems = Database::fetchAll("
                SELECT skt.item_id, skt.stok_titip_saat_ini, skt.terakhir_opname_pada,
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
                    $isTouched = false;

                    if ($savedInput && isset($savedInput[$si['item_id']])) {
                        $sisaFisik = (int)($savedInput[$si['item_id']]['sisa_fisik_di_rak'] ?? $sisaFisik);
                        $rBagus = (int)($savedInput[$si['item_id']]['retur_bagus'] ?? 0);
                        $rRusak = (int)($savedInput[$si['item_id']]['retur_rusak'] ?? 0);
                        $isTouched = true;
                    }

                    $items[] = [
                        'item_id' => $si['item_id'],
                        'nama_item' => $si['nama_item'],
                        'kode_sku' => $si['kode_sku'],
                        'satuan_dasar' => $si['satuan_dasar'] ?? 'pcs',
                        'stok_titip_saat_ini' => (int)$si['stok_titip_saat_ini'],
                        'harga_deal' => $dealPrice,
                        'sisa_fisik_di_rak' => $sisaFisik,
                        'retur_bagus' => $rBagus,
                        'retur_rusak' => $rRusak,
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
                'incomingDeliveries' => $incomingDeliveries,
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

        try {
            $rincianFormatted = [];
            foreach ($items as $it) {
                $itemId = $it['item_id'] ?? null;
                if (!$itemId) continue;

                $sisaFisik = max(0, (int)($it['sisa_fisik_di_rak'] ?? 0));
                $laku = isset($it['jumlah_laku']) ? max(0, (int)$it['jumlah_laku']) : null;
                $returBagus = max(0, (int)($it['retur_bagus'] ?? 0));
                $returRusak = max(0, (int)($it['retur_rusak'] ?? 0));

                $rincianFormatted[] = [
                    'item_id' => $itemId,
                    'sisa_fisik_di_rak' => $sisaFisik,
                    'jumlah_laku' => $laku,
                    'retur_bagus' => $returBagus,
                    'retur_rusak' => $returRusak,
                    'selisih_qty' => 0,
                ];
            }

            $res = Database::fetchOne("
                SELECT public.fn_proses_kunjungan_konsinyasi(:cust_id, :driver_id, :rincian::jsonb, :user_id) AS json_res
            ", [
                'cust_id' => $customerId,
                'driver_id' => $driverId,
                'rincian' => json_encode($rincianFormatted),
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
                SELECT kk.*, p.nama_toko, p.alamat_lengkap, p.nomor_whatsapp, p.nomor_telepon,
                       COALESCE(peng.nama_lengkap, k.nama_karyawan, 'Sales') as sales_name,
                       pes.nomor_nota, pes.total_netto, pes.status_pembayaran, pes.sisa_tagihan
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                LEFT JOIN public.karyawan k ON kk.sales_driver_id = k.id
                LEFT JOIN public.pesanan pes ON kk.pesanan_id = pes.id
                WHERE kk.id = :id
            ", ['id' => $kunjunganId]);

            if (!$visit) {
                $this->flashError('Data kunjungan tidak ditemukan.');
                $this->redirect('/consignment/opname');
                return;
            }

            $details = Database::fetchAll("
                SELECT rkk.*, i.nama_item, i.kode_sku, i.satuan_dasar
                FROM public.rincian_kunjungan_konsinyasi rkk
                JOIN public.item i ON rkk.item_id = i.id
                WHERE rkk.kunjungan_id = :id
                ORDER BY rkk.subtotal_laku DESC, i.nama_item ASC
            ", ['id' => $kunjunganId]);

            $this->view('consignment.opname_hasil', [
                'pageTitle' => 'Hasil Kunjungan Konsinyasi',
                'pageSubtitle' => 'Ringkasan Opname & Faktur Penjualan',
                'visit' => $visit,
                'details' => $details,
            ]);

        } catch (Throwable $e) {
            echo "Error Hasil Kunjungan: " . $e->getMessage();
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
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
            $storeId = (string)$this->input('pelanggan_id', '');
            $driverId = $this->getLoggedInDriverId();
            $isSales = $this->isSalesPersona();

            // Daftar filter toko
            $storeQuery = "SELECT id, nama_toko, kode_pelanggan FROM public.pelanggan WHERE is_konsinyasi = TRUE AND status_aktif = TRUE";
            $storeParams = [];
            if ($isSales && $driverId) {
                $storeQuery .= " AND sales_driver_id = :driver_id";
                $storeParams['driver_id'] = $driverId;
            }
            $storeQuery .= " ORDER BY nama_toko ASC";
            $stores = Database::fetchAll($storeQuery, $storeParams);

            // Data Laporan Penjualan
            $sql = "
                SELECT kk.id as kunjungan_id, kk.nomor_kunjungan, kk.tanggal_kunjungan, kk.total_laku_nominal,
                       p.nama_toko, p.kode_pelanggan,
                       k.nama_karyawan as nama_sales,
                       pes.id as pesanan_id, pes.nomor_nota, pes.status_pembayaran, pes.total_dibayar, pes.sisa_tagihan,
                       (SELECT COUNT(*) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_sku_laku,
                       (SELECT COALESCE(SUM(jumlah_laku_terjual), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_qty_laku
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.karyawan k ON kk.sales_driver_id = k.id
                LEFT JOIN public.pesanan pes ON kk.pesanan_id = pes.id
                WHERE kk.tanggal_kunjungan >= :start_date AND kk.tanggal_kunjungan <= :end_date
            ";
            $params = [
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            if ($isSales && $driverId) {
                $sql .= " AND p.sales_driver_id = :driver_id";
                $params['driver_id'] = $driverId;
            } elseif (!empty($storeId)) {
                $sql .= " AND p.id = :store_id";
                $params['store_id'] = $storeId;
            }

            $sql .= " ORDER BY kk.tanggal_kunjungan DESC, kk.dibuat_pada DESC";
            $reports = Database::fetchAll($sql, $params);

            // Agregasi
            $totalLaku = 0.0;
            $totalNotaCount = 0;
            $totalDibayar = 0.0;
            $totalPiutang = 0.0;

            foreach ($reports as $r) {
                $totalLaku += (float)$r['total_laku_nominal'];
                if (!empty($r['nomor_nota'])) {
                    $totalNotaCount++;
                    $totalDibayar += (float)($r['total_dibayar'] ?? 0);
                    $totalPiutang += (float)($r['sisa_tagihan'] ?? 0);
                }
            }

            $this->view('consignment.laporan_penjualan', [
                'pageTitle' => 'Laporan Penjualan Konsinyasi',
                'pageSubtitle' => 'Rekapitulasi Penjualan & Penerbitan Nota Hasil Opname',
                'reports' => $reports,
                'stores' => $stores,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'selectedStoreId' => $storeId,
                'totalLaku' => $totalLaku,
                'totalNotaCount' => $totalNotaCount,
                'totalDibayar' => $totalDibayar,
                'totalPiutang' => $totalPiutang,
                'isSales' => $isSales,
            ]);

        } catch (Throwable $e) {
            echo "Error Laporan Penjualan: " . $e->getMessage();
        }
    }

    /**
     * 8. Sub-halaman: Piutang Konsinyasi (GET /consignment/piutang)
     */
    public function piutang(): void
    {
        Auth::requirePermission('consignment.piutang');

        try {
            $isOwner = Auth::isOwner();
            $isAdmin = Auth::isAdmin() && !$isOwner;

            // Query seluruh piutang konsinyasi aktif
            $invoices = Database::fetchAll("
                SELECT pes.id as pesanan_id, pes.nomor_nota, pes.tanggal_pesanan, pes.total_netto, pes.total_dibayar, pes.sisa_tagihan,
                       pes.status_pembayaran, pes.catatan,
                       p.id as pelanggan_id, p.nama_toko, p.kode_pelanggan, p.nomor_whatsapp,
                       k.nama_karyawan as nama_sales
                FROM public.pesanan pes
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.karyawan k ON pes.sales_driver_id = k.id
                WHERE pes.tipe_pembayaran = 'konsinyasi'
                  AND pes.adalah_tagihan = TRUE
                  AND pes.status_pembayaran IN ('belum_lunas', 'sebagian')
                ORDER BY pes.tanggal_pesanan ASC, pes.dibuat_pada ASC
            ");

            // Master akun kas aktif untuk modal catat pembayaran (Admin only)
            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, tipe_akun, saldo_saat_ini 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE 
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            $totalPiutang = array_sum(array_column($invoices, 'sisa_tagihan'));

            $this->view('consignment.piutang', [
                'pageTitle' => 'Piutang Konsinyasi',
                'pageSubtitle' => 'Daftar Faktur Hasil Kunjungan yang Belum Dilunasi Toko',
                'invoices' => $invoices,
                'cashAccounts' => $cashAccounts,
                'totalPiutang' => $totalPiutang,
                'isOwner' => $isOwner,
                'isAdmin' => $isAdmin,
            ]);

        } catch (Throwable $e) {
            echo "Error Piutang Konsinyasi: " . $e->getMessage();
        }
    }

    /**
     * 9. Action: Catat Pembayaran Piutang Konsinyasi (POST /consignment/piutang/bayar)
     */
    public function catatPembayaran(): void
    {
        Auth::requirePermission('consignment.piutang');

        if (!$this->validateCsrf()) {
            $this->redirect('/consignment/piutang');
            return;
        }

        $pesananId = (string)$this->input('pesanan_id');
        $accountId = (string)$this->input('akun_kas_id');
        $nominal = (float)$this->input('nominal', 0);
        $keterangan = trim((string)$this->input('keterangan', ''));

        if (empty($pesananId) || empty($accountId) || $nominal <= 0) {
            $this->flashError('Pilih nota pesanan, rekening kas penerima, dan masukkan nominal pembayaran yang valid.');
            $this->redirect('/consignment/piutang');
            return;
        }

        try {
            $res = Database::fetchOne("
                SELECT public.fn_catat_pembayaran_konsinyasi(:p, :a, :nom, :user_id, :ket) as json_res
            ", [
                'p' => $pesananId,
                'a' => $accountId,
                'nom' => $nominal,
                'user_id' => Auth::id(),
                'ket' => !empty($keterangan) ? $keterangan : null
            ]);

            $jsonResult = json_decode($res['json_res'] ?? '{}', true);

            if (empty($jsonResult['success'])) {
                throw new \Exception('Pembayaran ditolak oleh database.');
            }

            $statusText = $jsonResult['status_pembayaran'] === 'lunas' ? 'LUNAS' : 'SEBAGIAN (Cicil)';
            $sisaRp = Format::rupiah((float)($jsonResult['sisa_tagihan'] ?? 0));

            ActivityLog::log(
                'keuangan',
                'INSERT',
                "Pencatatan pembayaran nota konsinyasi sebesar " . Format::rupiah($nominal) . " disetorkan ke kas. Status: {$statusText}.",
                'pesanan',
                $pesananId
            );

            $this->flashSuccess("Pembayaran sebesar " . Format::rupiah($nominal) . " berhasil dicatat! Status: {$statusText} (Sisa: {$sisaRp}).");
            $this->redirect('/consignment/piutang');

        } catch (Throwable $e) {
            $this->flashError('Gagal mencatat pembayaran: ' . $e->getMessage());
            $this->redirect('/consignment/piutang');
        }
    }

    /**
     * 10. Sub-halaman: Assignment Sales ↔ Toko (GET /consignment/assignment-sales)
     */
    public function assignmentSales(): void
    {
        Auth::requirePermission('consignment.assignment');

        try {
            $stores = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp, p.alamat_lengkap,
                       p.sales_driver_id,
                       k.nama_karyawan as nama_sales, k.nomor_telepon as sales_telepon
                FROM public.pelanggan p
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                ORDER BY (p.sales_driver_id IS NULL) DESC, p.nama_toko ASC
            ");

            $salesList = Database::fetchAll("
                SELECT id, nama_karyawan, nomor_telepon, posisi 
                FROM public.karyawan 
                WHERE posisi IN ('sales', 'sales_driver') AND status_aktif = TRUE 
                ORDER BY nama_karyawan ASC
            ");

            $this->view('consignment.assignment_sales', [
                'pageTitle' => 'Assignment Sales ↔ Toko',
                'pageSubtitle' => 'Penetapan Sales Penanggung Jawab Toko Konsinyasi Tetap',
                'stores' => $stores,
                'salesList' => $salesList,
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
            $this->redirect('/consignment/assignment-sales');
            return;
        }

        $storeIds = (array)$this->input('store_ids', []);
        $singleStoreId = (string)$this->input('pelanggan_id', '');
        $salesDriverId = (string)$this->input('sales_driver_id', '');

        if (!empty($singleStoreId)) {
            $storeIds[] = $singleStoreId;
        }

        $storeIds = array_filter(array_unique($storeIds));

        if (empty($storeIds)) {
            $this->flashError('Pilih minimal satu toko konsinyasi.');
            $this->redirect('/consignment/assignment-sales');
            return;
        }

        $salesUuid = !empty($salesDriverId) ? $salesDriverId : null;

        try {
            $count = 0;
            foreach ($storeIds as $sid) {
                Database::execute("
                    UPDATE public.pelanggan 
                    SET sales_driver_id = :d, diubah_pada = NOW() 
                    WHERE id = :c
                ", ['d' => $salesUuid, 'c' => $sid]);
                $count++;
            }

            $salesName = 'Tidak Ada (Unassigned)';
            if ($salesUuid) {
                $salesName = Database::fetchOne("SELECT nama_karyawan FROM public.karyawan WHERE id = :id", ['id' => $salesUuid])['nama_karyawan'] ?? 'Sales';
            }

            ActivityLog::log(
                'master_data',
                'UPDATE',
                "Admin memperbarui penugasan {$count} toko konsinyasi ke sales: {$salesName}.",
                'pelanggan',
                $storeIds[0] ?? null
            );

            $this->flashSuccess("Berhasil meng-assign {$count} toko konsinyasi ke {$salesName}!");
            $this->redirect('/consignment/assignment-sales');

        } catch (Throwable $e) {
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
            $month = (string)$this->input('month', date('Y-m'));
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            $sql = "
                SELECT k.id as sales_id, k.nama_karyawan, k.nomor_telepon,
                       COALESCE(k.persentase_komisi_sales, 5.0) as persentase_komisi,
                       COUNT(DISTINCT p.id) as total_toko_assigned,
                       COALESCE(SUM(kk.total_laku_nominal), 0) as total_omzet,
                       (COALESCE(SUM(kk.total_laku_nominal), 0) * COALESCE(k.persentase_komisi_sales, 5.0) / 100.0) as nominal_komisi
                FROM public.karyawan k
                JOIN public.pelanggan p ON p.sales_driver_id = k.id AND p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                LEFT JOIN public.kunjungan_konsinyasi kk ON kk.pelanggan_id = p.id 
                     AND kk.tanggal_kunjungan >= :start_date AND kk.tanggal_kunjungan <= :end_date
                WHERE k.posisi IN ('sales', 'sales_driver') AND k.status_aktif = TRUE
            ";

            $params = [
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            // Penyekatan scope: jika hanya punya hak lihat komisi sendiri
            if (!Auth::can('consignment.komisi_all')) {
                $myEmpId = Auth::employeeId();
                if ($myEmpId) {
                    $sql .= " AND k.id = :my_emp_id";
                    $params['my_emp_id'] = $myEmpId;
                } else {
                    $sql .= " AND 1=0";
                }
            }

            $sql .= " GROUP BY k.id, k.nama_karyawan, k.nomor_telepon, k.persentase_komisi_sales ORDER BY total_omzet DESC";
            $commissions = Database::fetchAll($sql, $params);

            $grandOmzet = array_sum(array_column($commissions, 'total_omzet'));
            $grandKomisi = array_sum(array_column($commissions, 'nominal_komisi'));

            $this->view('consignment.komisi_sales', [
                'pageTitle' => 'Rekap Komisi Sales',
                'pageSubtitle' => 'Komisi Bulanan Berdasarkan Toko Konsinyasi Binaan Tetap',
                'commissions' => $commissions,
                'month' => $month,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'grandOmzet' => $grandOmzet,
                'grandKomisi' => $grandKomisi,
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

            $stores = Database::fetchAll("
                SELECT id, nama_toko, kode_pelanggan 
                FROM public.pelanggan 
                WHERE is_konsinyasi = TRUE AND status_aktif = TRUE 
                ORDER BY nama_toko ASC
            ");

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
                LEFT JOIN public.karyawan k ON kk.sales_driver_id = k.id
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

            $this->view('consignment.kerugian_rusak', [
                'pageTitle' => 'Laporan Kerugian Barang Rusak',
                'pageSubtitle' => 'Valuasi HPP Resmi Barang Retur Rusak/Bocor Hasil Opname',
                'losses' => $losses,
                'stores' => $stores,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'selectedStoreId' => $storeId,
                'totalLossNominal' => $totalLossNominal,
                'totalPcsRusak' => $totalPcsRusak,
            ]);

        } catch (Throwable $e) {
            echo "Error Kerugian Rusak: " . $e->getMessage();
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
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
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

            $sql = "
                SELECT kk.id, kk.nomor_kunjungan, kk.tanggal_kunjungan, kk.total_laku_nominal, kk.catatan,
                       p.nama_toko, p.kode_pelanggan,
                       COALESCE(peng.nama_lengkap, k.nama_karyawan, 'Petugas ERP') as nama_sales,
                       pes.nomor_nota, pes.status_pembayaran,
                       (SELECT COUNT(*) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_sku,
                       (SELECT COALESCE(SUM(jumlah_laku_terjual), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_laku,
                       (SELECT COALESCE(SUM(retur_bagus), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_retur_bagus,
                       (SELECT COALESCE(SUM(retur_rusak), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_retur_rusak,
                       (SELECT COALESCE(SUM(nilai_kerugian_rusak), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_loss
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                LEFT JOIN public.karyawan k ON kk.sales_driver_id = k.id
                LEFT JOIN public.pesanan pes ON kk.pesanan_id = pes.id
                WHERE kk.tanggal_kunjungan >= :start_date AND kk.tanggal_kunjungan <= :end_date
            ";
            $params = [
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            if ($isSales && $driverId) {
                $sql .= " AND p.sales_driver_id = :driver_id";
                $params['driver_id'] = $driverId;
            } elseif (!empty($storeId)) {
                $sql .= " AND p.id = :store_id";
                $params['store_id'] = $storeId;
            }

            $sql .= " ORDER BY kk.tanggal_kunjungan DESC, kk.dibuat_pada DESC";
            $visits = Database::fetchAll($sql, $params);

            $this->view('consignment.riwayat_kunjungan', [
                'pageTitle' => 'Riwayat Kunjungan Toko',
                'pageSubtitle' => 'Audit Trail Kunjungan & Settlement Konsinyasi Lapangan',
                'visits' => $visits,
                'stores' => $stores,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'selectedStoreId' => $storeId,
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
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
            $storeId = (string)$this->input('pelanggan_id', '');
            $driverId = $this->getLoggedInDriverId();
            $isSales = $this->isSalesPersona();

            $sql = "
                SELECT kk.nomor_kunjungan, kk.tanggal_kunjungan, kk.total_laku_nominal,
                       p.nama_toko, p.kode_pelanggan,
                       k.nama_karyawan as nama_sales,
                       pes.nomor_nota, pes.status_pembayaran, pes.total_dibayar, pes.sisa_tagihan
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.karyawan k ON kk.sales_driver_id = k.id
                LEFT JOIN public.pesanan pes ON kk.pesanan_id = pes.id
                WHERE kk.tanggal_kunjungan >= :start_date AND kk.tanggal_kunjungan <= :end_date
            ";
            $params = ['start_date' => $startDate, 'end_date' => $endDate];

            if ($isSales && $driverId) {
                $sql .= " AND p.sales_driver_id = :driver_id";
                $params['driver_id'] = $driverId;
            } elseif (!empty($storeId)) {
                $sql .= " AND p.id = :store_id";
                $params['store_id'] = $storeId;
            }

            $sql .= " ORDER BY kk.tanggal_kunjungan DESC, kk.dibuat_pada DESC";
            $reports = Database::fetchAll($sql, $params);

            $headers = ['No', 'Tanggal Kunjungan', 'Nomor Kunjungan', 'Kode Toko', 'Nama Toko Konsinyasi', 'Sales / Driver', 'Nomor Faktur B2B', 'Total Terjual (Rp)', 'Total Terbayar (Rp)', 'Sisa Tagihan (Rp)', 'Status Bayar'];
            $rows = [];
            $no = 1;
            $totLaku = 0;
            $totBayar = 0;
            $totSisa = 0;

            foreach ($reports as $r) {
                $laku = (float)$r['total_laku_nominal'];
                $bayar = (float)($r['total_dibayar'] ?? 0);
                $sisa = (float)($r['sisa_tagihan'] ?? 0);
                $totLaku += $laku;
                $totBayar += $bayar;
                $totSisa += $sisa;

                $rows[] = [
                    $no++,
                    date('d/m/Y', strtotime($r['tanggal_kunjungan'])),
                    $r['nomor_kunjungan'],
                    $r['kode_pelanggan'] ?? '-',
                    $r['nama_toko'],
                    $r['nama_sales'] ?? '-',
                    $r['nomor_nota'] ?? '-',
                    $laku,
                    $bayar,
                    $sisa,
                    strtoupper(str_replace('_', ' ', (string)($r['status_pembayaran'] ?? 'BELUM BAYAR')))
                ];
            }

            $rows[] = ['', '', '', '', '', '', 'TOTAL PENJUALAN KONSINYASI:', $totLaku, $totBayar, $totSisa, ''];

            ExcelExport::download("Laporan-Penjualan-Konsinyasi-{$startDate}-sd-{$endDate}.xlsx", $headers, $rows, "Penjualan Konsinyasi");
        } catch (Throwable $e) {
            $this->flashError('Gagal export laporan penjualan konsinyasi: ' . $e->getMessage());
            $this->redirect('/consignment/laporan-penjualan');
        }
    }

    /**
     * Export Laporan Piutang Toko Konsinyasi ke File Excel (PhpSpreadsheet)
     */
    public function exportPiutangExcel(): void
    {
        Auth::requirePermission(['consignment.piutang_view_all', 'consignment.piutang_view_assigned']);

        try {
            $driverId = $this->getLoggedInDriverId();
            $isSales = $this->isSalesPersona();

            $sql = "
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp, p.alamat_lengkap,
                       k.nama_karyawan as nama_sales,
                       COUNT(pes.id) as total_nota_piutang,
                       COALESCE(SUM(pes.sisa_tagihan), 0) as total_piutang
                FROM public.pelanggan p
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
                JOIN public.pesanan pes ON pes.pelanggan_id = p.id
                WHERE p.is_konsinyasi = TRUE
                  AND pes.sisa_tagihan > 0
                  AND pes.status_pemrosesan != 'dibatalkan'
            ";
            $params = [];

            if ($isSales && $driverId) {
                $sql .= " AND p.sales_driver_id = :driver_id";
                $params['driver_id'] = $driverId;
            }

            $sql .= " GROUP BY p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp, p.alamat_lengkap, k.nama_karyawan";
            $sql .= " HAVING SUM(pes.sisa_tagihan) > 0";
            $sql .= " ORDER BY total_piutang DESC";

            $stores = Database::fetchAll($sql, $params);

            $headers = ['No', 'Kode Toko', 'Nama Toko Konsinyasi', 'Pemilik Toko', 'No. WhatsApp', 'Alamat Lengkap', 'Sales PIC', 'Jumlah Nota Belum Lunas', 'Total Piutang Berjalan (Rp)'];
            $rows = [];
            $no = 1;
            $grandPiutang = 0;

            foreach ($stores as $s) {
                $piutang = (float)$s['total_piutang'];
                $grandPiutang += $piutang;

                $rows[] = [
                    $no++,
                    $s['kode_pelanggan'] ?? '-',
                    $s['nama_toko'],
                    $s['nama_pemilik'] ?? '-',
                    $s['nomor_whatsapp'] ?? '-',
                    $s['alamat_lengkap'] ?? '-',
                    $s['nama_sales'] ?? '-',
                    (int)$s['total_nota_piutang'],
                    $piutang
                ];
            }

            $rows[] = ['', '', '', '', '', '', '', 'GRAND TOTAL PIUTANG:', $grandPiutang];

            ExcelExport::download("Laporan-Piutang-Konsinyasi-" . date('Ymd') . ".xlsx", $headers, $rows, "Piutang Konsinyasi");
        } catch (Throwable $e) {
            $this->flashError('Gagal export data piutang konsinyasi: ' . $e->getMessage());
            $this->redirect('/consignment/piutang');
        }
    }
}
