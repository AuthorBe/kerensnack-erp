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
                    $laku = 0;
                    $isTouched = false;

                    if ($savedInput && isset($savedInput[$si['item_id']])) {
                        $sisaFisik = (int)($savedInput[$si['item_id']]['sisa_fisik_di_rak'] ?? $sisaFisik);
                        $laku = (int)($savedInput[$si['item_id']]['jumlah_laku'] ?? 0);
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
                        'jumlah_laku' => $laku,
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
                SELECT kk.*, p.nama_toko, p.kode_pelanggan, p.alamat_lengkap, p.nomor_whatsapp, p.nomor_telepon, p.nama_pemilik,
                       COALESCE(peng.nama_lengkap, k.nama_karyawan, 'Sales') as sales_name,
                       k.posisi as sales_role,
                       COALESCE(pes.id, tk.pesanan_id) as pesanan_id,
                       pes.nomor_nota, pes.total_netto, pes.total_dibayar, pes.status_pembayaran, pes.sisa_tagihan, pes.tanggal_pesanan
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                LEFT JOIN public.karyawan k ON kk.sales_driver_id = k.id
                LEFT JOIN public.tagihan_kunjungan tk ON tk.kunjungan_id = kk.id
                LEFT JOIN public.pesanan pes ON (kk.pesanan_id = pes.id OR tk.pesanan_id = pes.id)
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

            $this->view('consignment.opname_hasil', [
                'pageTitle' => 'Hasil Kunjungan Konsinyasi',
                'pageSubtitle' => 'Ringkasan Opname & Faktur Penjualan',
                'visit' => $visit,
                'details' => $details,
                'otherUnbilledVisits' => $otherUnbilledVisits,
                'cashAccounts' => $cashAccounts,
                'canManageTagihan' => $canManageTagihan,
            ]);

        } catch (Throwable $e) {
            echo "Error Hasil Kunjungan: " . $e->getMessage();
        }
    }

    /**
     * 5b. Unduh Nota Tagihan Resmi Konsinyasi dalam format PDF
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
            $sql = "
                SELECT kk.*, p.nama_toko, p.kode_pelanggan, p.alamat_lengkap, p.nomor_whatsapp, p.nomor_telepon, p.nama_pemilik,
                       COALESCE(peng.nama_lengkap, k.nama_karyawan, 'Sales') as sales_name,
                       COALESCE(pes.id, tk.pesanan_id) as pesanan_id,
                       pes.nomor_nota, pes.total_netto, pes.total_dibayar, pes.status_pembayaran, pes.sisa_tagihan, pes.tanggal_pesanan
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                LEFT JOIN public.karyawan k ON kk.sales_driver_id = k.id
                LEFT JOIN public.tagihan_kunjungan tk ON tk.kunjungan_id = kk.id
                LEFT JOIN public.pesanan pes ON (kk.pesanan_id = pes.id OR tk.pesanan_id = pes.id)
                WHERE " . (!empty($kunjunganId) ? "kk.id = :id" : "(kk.pesanan_id = :id OR pes.id = :id)");

            $visit = Database::fetchOne($sql, ['id' => !empty($kunjunganId) ? $kunjunganId : $pesananId]);

            if (!$visit) {
                $this->flashError('Data kunjungan / faktur konsinyasi tidak ditemukan.');
                $this->redirect('/consignment/opname');
                return;
            }

            $details = Database::fetchAll("
                SELECT rkk.*, i.nama_item, i.kode_sku, i.satuan_dasar
                FROM public.rincian_kunjungan_konsinyasi rkk
                JOIN public.item i ON rkk.item_id = i.id
                WHERE rkk.kunjungan_id = :id
                ORDER BY rkk.subtotal_laku DESC, i.nama_item ASC
            ", ['id' => $visit['id']]);

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
                'isPdf' => true
            ]);
            require ROOT_PATH . '/views/consignment/nota_pdf.php';
            $html = ob_get_clean();

            $cleanNota = !empty($visit['nomor_nota']) 
                ? preg_replace('/[^A-Za-z0-9\-]/', '_', (string)$visit['nomor_nota']) 
                : 'KONSIN_' . date('Ymd_His');

            PdfExport::download($html, "Nota-Tagihan-{$cleanNota}.pdf", 'A4', 'portrait');

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
                    FROM public.karyawan
                    WHERE posisi IN ('sales', 'sales_driver') AND status_aktif = TRUE
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
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
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
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
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
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
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
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
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
                    SELECT i.nama_item, rkk.sisa_fisik_di_rak, rkk.retur_bagus, rkk.retur_rusak
                    FROM public.rincian_kunjungan_konsinyasi rkk
                    JOIN public.item i ON rkk.item_id = i.id
                    WHERE rkk.kunjungan_id = :visit_id
                    ORDER BY rkk.sisa_fisik_di_rak DESC
                ", ['visit_id' => $lastVisit['id']]);
            }

            // 5. Outstanding Tagihan
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
                LEFT JOIN public.karyawan kar ON kk.sales_driver_id = kar.id
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
                LEFT JOIN public.karyawan kar ON pes.sales_driver_id = kar.id
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
                LEFT JOIN public.karyawan kar ON pes.sales_driver_id = kar.id
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
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
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

