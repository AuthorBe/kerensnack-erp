<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/DeliveryController.php
 * Pengendali Pengiriman Logistik, Manifest Rute Sales-Driver & Surat Jalan.
 */
class DeliveryController extends Controller
{
    public function __construct()
    {
        Auth::requireRole(['owner', 'admin', 'developer', 'driver', 'sales', 'sales_driver']);
    }

    public function index(): void
    {
        try {
            $currentUser = Auth::user();
            $driverId = $currentUser['karyawan_id'] ?? null;
            $isAdmin = Auth::isAdmin() || Auth::isOwner() || Auth::isDeveloper();

            $sqlDeliveries = "
                SELECT sj.id, sj.nomor_surat_jalan, sj.status_surat_jalan, sj.bukti_terima_foto,
                       sj.nama_penerima_toko, sj.waktu_berangkat, sj.waktu_sampai, sj.dibuat_pada,
                       p.nomor_nota, p.tanggal_pesanan, p.total_netto, p.tipe_pembayaran,
                       cust.nama_toko, cust.alamat_lengkap as alamat_toko, cust.nomor_whatsapp,
                       COALESCE(driver_sj.nama_karyawan, driver_p.nama_karyawan) as nama_driver,
                       COALESCE(driver_sj.nomor_telepon, driver_p.nomor_telepon) as telp_driver,
                       COALESCE(driver_sj.nomor_polisi_kendaraan, driver_p.nomor_polisi_kendaraan) as nopol_driver,
                       w.nama_wilayah, w.kode_rute
                FROM public.surat_jalan sj
                JOIN public.pesanan p ON sj.pesanan_id = p.id
                JOIN public.pelanggan cust ON p.pelanggan_id = cust.id
                LEFT JOIN public.karyawan driver_sj ON sj.sales_driver_id = driver_sj.id
                LEFT JOIN public.karyawan driver_p ON p.sales_driver_id = driver_p.id
                LEFT JOIN public.wilayah w ON COALESCE(sj.rute_wilayah_id, cust.wilayah_id) = w.id
            ";

            $paramsDeliv = [];
            if (!$isAdmin && $driverId) {
                $sqlDeliveries .= " WHERE sj.sales_driver_id = :driver_id";
                $paramsDeliv['driver_id'] = $driverId;
            }

            $sqlDeliveries .= " ORDER BY sj.dibuat_pada DESC";
            $deliveries = Database::fetchAll($sqlDeliveries, $paramsDeliv);

            // Ambil pesanan yang belum dibuatkan surat jalan
            $pendingOrders = Database::fetchAll("
                SELECT p.id, p.nomor_nota, p.tanggal_pesanan, p.total_netto, p.tipe_pembayaran,
                       cust.nama_toko, cust.wilayah_id, w.nama_wilayah
                FROM public.pesanan p
                JOIN public.pelanggan cust ON p.pelanggan_id = cust.id
                LEFT JOIN public.wilayah w ON cust.wilayah_id = w.id
                LEFT JOIN public.surat_jalan sj ON p.id = sj.pesanan_id
                WHERE sj.id IS NULL AND p.status_pembayaran != 'dibatalkan'
                ORDER BY p.tanggal_pesanan DESC
            ");

            // Karyawan yang bisa ditugaskan sebagai pengemudi: Posisi Driver atau Sales
            $drivers = Database::fetchAll("
                SELECT id, nama_karyawan, nomor_telepon, nomor_polisi_kendaraan, posisi 
                FROM public.karyawan 
                WHERE posisi IN ('driver', 'sales', 'sales_driver') AND status_aktif = TRUE
                ORDER BY (posisi = 'driver') DESC, nama_karyawan ASC
            ");
            $territories = Database::fetchAll("SELECT id, kode_rute, nama_wilayah FROM public.wilayah WHERE status_aktif = TRUE ORDER BY nama_wilayah ASC");

            $this->view('deliveries.index', [
                'pageTitle' => 'Status Pengiriman',
                'pageSubtitle' => 'Manifest Rute Sales-Driver & Status Pengiriman Toko',
                'deliveries' => $deliveries,
                'pendingOrders' => $pendingOrders,
                'drivers' => $drivers,
                'territories' => $territories
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    public function store(): void
    {
        $pesananId = $this->input('pesanan_id');
        $driverId = $this->input('sales_driver_id') ?: null;
        $wilayahId = $this->input('rute_wilayah_id') ?: null;
        $status = $this->input('status_surat_jalan', 'disetujui_owner');

        if (empty($pesananId)) {
            $this->flashError('Pilih pesanan nota toko.');
            $this->redirect('/deliveries');
            return;
        }

        try {
            $count = Database::fetchOne("SELECT count(*) as total FROM public.surat_jalan")['total'] ?? 0;
            $nomorSj = 'SJ-' . date('Ymd') . '-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
            $userId = Auth::id() ?: null;

            Database::execute("
                INSERT INTO public.surat_jalan (
                    nomor_surat_jalan, pesanan_id, sales_driver_id, rute_wilayah_id,
                    status_surat_jalan, disetujui_oleh, dibuat_pada
                ) VALUES (
                    :no_sj, :pesanan, :driver, :wilayah,
                    :status, :user_id, NOW()
                )
            ", [
                'no_sj' => $nomorSj,
                'pesanan' => $pesananId,
                'driver' => $driverId,
                'wilayah' => $wilayahId,
                'status' => $status,
                'user_id' => $userId
            ]);

            $this->flashSuccess("Surat jalan {$nomorSj} berhasil diterbitkan!");
            $this->redirect('/deliveries');

        } catch (Throwable $e) {
            $this->flashError('Gagal menerbitkan surat jalan: ' . $e->getMessage());
            $this->redirect('/deliveries');
        }
    }

    public function updateStatus(): void
    {
        $id = $this->input('id');
        $status = $this->input('status_surat_jalan');
        $penerima = trim((string)$this->input('nama_penerima_toko'));

        if (empty($id) || empty($status)) {
            $this->flashError('Parameter tidak lengkap.');
            $this->redirect('/deliveries');
            return;
        }

        try {
            $sql = "UPDATE public.surat_jalan SET status_surat_jalan = :status, diubah_pada = NOW()";
            $params = [
                'id' => $id,
                'status' => $status
            ];

            if (!empty($penerima)) {
                $sql .= ", nama_penerima_toko = :penerima";
                $params['penerima'] = $penerima;
            }

            if ($status === 'sedang_dikirim') {
                $sql .= ", waktu_berangkat = COALESCE(waktu_berangkat, NOW())";
            } elseif ($status === 'selesai_diterima') {
                $sql .= ", waktu_sampai = NOW()";
            }

            $sql .= " WHERE id = :id";

            Database::execute($sql, $params);

            // Sync stok rak konsinyasi jika pesanan ini adalah titip konsinyasi dan status selesai_diterima
            if ($status === 'selesai_diterima') {
                $sjData = Database::fetchOne("SELECT pesanan_id FROM public.surat_jalan WHERE id = :id", ['id' => $id]);
                if ($sjData && !empty($sjData['pesanan_id'])) {
                    $orderData = Database::fetchOne("
                        SELECT p.pelanggan_id, pel.is_konsinyasi, p.tipe_pembayaran 
                        FROM public.pesanan p 
                        JOIN public.pelanggan pel ON p.pelanggan_id = pel.id 
                        WHERE p.id = :id
                    ", ['id' => $sjData['pesanan_id']]);

                    if ($orderData && ($orderData['is_konsinyasi'] || $orderData['tipe_pembayaran'] === 'konsinyasi')) {
                        $orderedItems = Database::fetchAll("
                            SELECT item_id, kuantitas_satuan_dasar 
                            FROM public.item_pesanan 
                            WHERE pesanan_id = :id
                        ", ['id' => $sjData['pesanan_id']]);

                        foreach ($orderedItems as $oit) {
                            Database::execute("
                                INSERT INTO public.stok_konsinyasi_toko (
                                    pelanggan_id, item_id, stok_titip_saat_ini, terakhir_opname_pada, dibuat_pada, diubah_pada
                                ) VALUES (
                                    :pelanggan_id, :item_id, :qty, NOW(), NOW(), NOW()
                                )
                                ON CONFLICT (pelanggan_id, item_id) DO UPDATE SET
                                    stok_titip_saat_ini = public.stok_konsinyasi_toko.stok_titip_saat_ini + EXCLUDED.stok_titip_saat_ini,
                                    diubah_pada = NOW()
                            ", [
                                'pelanggan_id' => $orderData['pelanggan_id'],
                                'item_id' => $oit['item_id'],
                                'qty' => (int)$oit['kuantitas_satuan_dasar']
                            ]);
                        }
                    }
                }
            }

            $this->flashSuccess("Status pengiriman berhasil diperbarui!");
            $this->redirect('/deliveries');

        } catch (Throwable $e) {
            $this->flashError('Gagal update status: ' . $e->getMessage());
            $this->redirect('/deliveries');
        }
    }

    /**
     * Cetak Lembar Surat Jalan Pengiriman (Print Delivery Order / Manifest)
     */
    public function print(): void
    {
        $id = $this->input('id');
        $orderId = $this->input('order_id');

        if (empty($id) && empty($orderId)) {
            $this->redirect('/deliveries');
            return;
        }

        try {
            if (!empty($orderId) && empty($id)) {
                $sj = Database::fetchOne("SELECT id FROM public.surat_jalan WHERE pesanan_id = :order_id", ['order_id' => $orderId]);
                if (!$sj) {
                    // Auto create surat_jalan for this order if not existing
                    $pesanan = Database::fetchOne("
                        SELECT p.*, pel.wilayah_id 
                        FROM public.pesanan p 
                        JOIN public.pelanggan pel ON p.pelanggan_id = pel.id 
                        WHERE p.id = :id
                    ", ['id' => $orderId]);

                    if ($pesanan) {
                        $count = Database::fetchOne("SELECT count(*) as total FROM public.surat_jalan")['total'] ?? 0;
                        $nomorSj = 'SJ-' . date('Ymd') . '-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
                        Database::execute("
                            INSERT INTO public.surat_jalan (
                                nomor_surat_jalan, pesanan_id, sales_driver_id, rute_wilayah_id,
                                status_surat_jalan, disetujui_oleh, dibuat_pada
                            ) VALUES (
                                :no_sj, :pesanan_id, :driver_id, :wilayah_id,
                                'sedang_dikirim', :user_id, NOW()
                            )
                        ", [
                            'no_sj' => $nomorSj,
                            'pesanan_id' => $orderId,
                            'driver_id' => $pesanan['sales_driver_id'] ?: null,
                            'wilayah_id' => $pesanan['wilayah_id'] ?? null,
                            'user_id' => Auth::id() ?: null,
                        ]);
                        $sj = Database::fetchOne("SELECT id FROM public.surat_jalan WHERE pesanan_id = :order_id", ['order_id' => $orderId]);
                    }
                }
                $id = $sj['id'] ?? null;
            }

            if (empty($id)) {
                $this->flashError('Surat jalan tidak ditemukan.');
                $this->redirect('/deliveries');
                return;
            }

            $delivery = Database::fetchOne("
                SELECT sj.*, 
                       p.nomor_nota, p.tanggal_pesanan, p.total_netto, p.tipe_pembayaran, p.catatan as catatan_pesanan,
                       cust.nama_toko, cust.alamat_lengkap as alamat_toko, cust.nomor_whatsapp, cust.nama_pemilik,
                       COALESCE(driver_sj.nama_karyawan, driver_p.nama_karyawan) as nama_driver,
                       COALESCE(driver_sj.nomor_telepon, driver_p.nomor_telepon) as telp_driver,
                       COALESCE(driver_sj.nomor_polisi_kendaraan, driver_p.nomor_polisi_kendaraan) as nopol_driver,
                       w.nama_wilayah, w.kode_rute
                FROM public.surat_jalan sj
                JOIN public.pesanan p ON sj.pesanan_id = p.id
                JOIN public.pelanggan cust ON p.pelanggan_id = cust.id
                LEFT JOIN public.karyawan driver_sj ON sj.sales_driver_id = driver_sj.id
                LEFT JOIN public.karyawan driver_p ON p.sales_driver_id = driver_p.id
                LEFT JOIN public.wilayah w ON COALESCE(sj.rute_wilayah_id, cust.wilayah_id) = w.id
                WHERE sj.id = :id
            ", ['id' => $id]);

            if (!$delivery) {
                $this->flashError('Surat jalan tidak ditemukan.');
                $this->redirect('/deliveries');
                return;
            }

            $items = Database::fetchAll("
                SELECT ip.*, i.nama_item, i.kode_sku, i.varian_rasa, i.satuan_dasar
                FROM public.item_pesanan ip
                JOIN public.item i ON ip.item_id = i.id
                WHERE ip.pesanan_id = :pesanan_id
                ORDER BY i.nama_item ASC
            ", ['pesanan_id' => $delivery['pesanan_id']]);

            $this->view('deliveries.print', [
                'delivery' => $delivery,
                'items' => $items,
            ]);

        } catch (Throwable $e) {
            $this->flashError('Gagal memuat dokumen surat jalan: ' . $e->getMessage());
            $this->redirect('/deliveries');
        }
    }
}
