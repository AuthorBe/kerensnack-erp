<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/ConsignmentController.php
 * Pengendali Modul Titip Jual (Konsinyasi) Rak Toko & Opname Mingguan Sales-Driver.
 */

class ConsignmentController extends Controller
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    public function index(): void
    {
        try {
            // 1. Ambil toko konsinyasi
            $consignmentStores = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.alamat_lengkap,
                       w.nama_wilayah as rute
                FROM public.pelanggan p
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                ORDER BY p.nama_toko ASC
            ");

            // 2. Ambil saldo stok di rak toko konsinyasi
            $shelfStocks = Database::fetchAll("
                SELECT skt.id, skt.pelanggan_id, skt.item_id, skt.stok_titip_saat_ini, skt.terakhir_opname_pada,
                       i.nama_item, i.kode_sku, p.nama_toko
                FROM public.stok_konsinyasi_toko skt
                JOIN public.item i ON skt.item_id = i.id
                JOIN public.pelanggan p ON skt.pelanggan_id = p.id
                ORDER BY p.nama_toko ASC, i.nama_item ASC
            ");

            // 3. Ambil riwayat kunjungan konsinyasi
            $recentVisits = Database::fetchAll("
                SELECT kk.id, kk.nomor_kunjungan, kk.tanggal_kunjungan, kk.total_laku_nominal,
                       p.nama_toko, k.nama_karyawan as sales_driver, kk.catatan
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.karyawan k ON kk.sales_driver_id = k.id
                ORDER BY kk.tanggal_kunjungan DESC
                LIMIT 15
            ");

            $items = Database::fetchAll("SELECT id, kode_sku, nama_item FROM public.item WHERE status_aktif = TRUE AND tipe_item = 'barang_jadi' LIMIT 50");
            $drivers = Database::fetchAll("SELECT id, nama_karyawan FROM public.karyawan WHERE posisi = 'sales_driver' OR status_aktif = TRUE");

            $this->view('consignment.index', [
                'pageTitle' => 'Modul Konsinyasi (Titip Jual Rak)',
                'pageSubtitle' => 'Monitoring Saldo Rak Toko & Opname Kunjungan Mingguan',
                'consignmentStores' => $consignmentStores,
                'shelfStocks' => $shelfStocks,
                'recentVisits' => $recentVisits,
                'items' => $items,
                'drivers' => $drivers
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    public function processOpname(): void
    {
        $customerId = $this->input('pelanggan_id');
        $driverId = $this->input('sales_driver_id') ?: '00000000-0000-0000-0000-000000000000';
        $itemId = $this->input('item_id');
        $sisaRak = (int)$this->input('sisa_fisik_rak', 0);
        $tambahBaru = (int)$this->input('tambah_baru', 0);
        $returRusak = (int)$this->input('retur_rusak', 0);

        try {
            $rincianJson = json_encode([[
                'item_id' => $itemId,
                'sisa_fisik_di_rak' => $sisaRak,
                'tambah_titip_baru' => $tambahBaru,
                'retur_rusak' => $returRusak
            ]]);

            $res = Database::fetchOne("
                SELECT public.fn_proses_kunjungan_konsinyasi(:cust_id, :driver_id, :rincian::jsonb) AS json_res
            ", [
                'cust_id' => $customerId,
                'driver_id' => $driverId,
                'rincian' => $rincianJson
            ]);

            $this->flashSuccess('Kunjungan opname konsinyasi berhasil diproses & saldo rak tersinkronisasi!');
            $this->redirect('/consignment');

        } catch (Throwable $e) {
            $this->flashError('Gagal memproses opname konsinyasi: ' . $e->getMessage());
            $this->redirect('/consignment');
        }
    }
}
