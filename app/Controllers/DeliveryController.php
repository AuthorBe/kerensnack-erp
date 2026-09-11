<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\PdfExport;
use App\Helpers\ExcelExport;
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
        Auth::requireLogin();
    }

    public function index(): void
    {
        Auth::requirePermission(['deliveries.view_all', 'deliveries.view_assigned']);

        try {
            $currentUser = Auth::user();
            $driverId = $currentUser['karyawan_id'] ?? null;
            $canViewAll = Auth::can('deliveries.view_all');

            $sqlDeliveries = "
                SELECT sj.id, sj.nomor_surat_jalan, sj.status_surat_jalan, sj.bukti_terima_foto,
                       sj.nama_penerima_toko, sj.waktu_berangkat, sj.waktu_sampai, sj.dibuat_pada,
                       p.nomor_nota, p.tanggal_pesanan, p.total_netto, p.tipe_pembayaran,
                       cust.nama_toko, cust.alamat_lengkap as alamat_toko, cust.nomor_whatsapp, cust.is_konsinyasi,
                       COALESCE(driver_sj.nama_karyawan, driver_p.nama_karyawan) as nama_driver,
                       COALESCE(driver_sj.nomor_telepon, driver_p.nomor_telepon) as telp_driver,
                       COALESCE(driver_sj.nomor_polisi_kendaraan, driver_p.nomor_polisi_kendaraan) as nopol_driver,
                       w.nama_wilayah, w.kode_rute
                FROM public.surat_jalan sj
                JOIN public.pesanan p ON sj.pesanan_id = p.id
                JOIN public.pelanggan cust ON p.pelanggan_id = cust.id
                LEFT JOIN public.v_karyawan_info driver_sj ON sj.sales_driver_id = driver_sj.id
                LEFT JOIN public.v_karyawan_info driver_p ON p.sales_driver_id = driver_p.id
                LEFT JOIN public.wilayah w ON COALESCE(sj.rute_wilayah_id, cust.wilayah_id) = w.id
            ";

            $paramsDeliv = [];
            if (!$canViewAll && $driverId) {
                $sqlDeliveries .= " WHERE (sj.sales_driver_id = :driver_id OR p.sales_driver_id = :driver_id)";
                $paramsDeliv['driver_id'] = $driverId;
            } elseif (!$canViewAll && !$driverId) {
                $sqlDeliveries .= " WHERE 1=0";
            }

            $sqlDeliveries .= " ORDER BY sj.dibuat_pada DESC";
            $deliveries = Database::fetchAll($sqlDeliveries, $paramsDeliv);

            // Ambil pesanan yang berstatus 'siap_dikirim' dan belum dibuatkan surat jalan aktif
            $pendingOrders = Database::fetchAll("
                SELECT p.id, p.nomor_nota, p.tanggal_pesanan, p.total_netto, p.tipe_pembayaran,
                       cust.nama_toko, cust.wilayah_id, cust.is_konsinyasi, w.nama_wilayah
                FROM public.pesanan p
                JOIN public.pelanggan cust ON p.pelanggan_id = cust.id
                LEFT JOIN public.wilayah w ON cust.wilayah_id = w.id
                LEFT JOIN public.surat_jalan sj ON (p.id = sj.pesanan_id AND sj.status_surat_jalan NOT IN ('gagal_kirim', 'dibatalkan'))
                WHERE sj.id IS NULL 
                  AND p.status_pemrosesan = 'siap_dikirim'
                  AND p.status_pembayaran != 'dibatalkan'
                ORDER BY p.tanggal_pesanan DESC, p.dibuat_pada DESC
            ");

            // Karyawan yang bisa ditugaskan sebagai pengemudi: Posisi Driver atau Sales
            $drivers = Database::fetchAll("
                SELECT id, nama_karyawan, nomor_telepon, nomor_polisi_kendaraan, posisi 
                FROM public.v_karyawan_info 
                WHERE posisi IN ('driver', 'sales') AND status_aktif = TRUE
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
        Auth::requirePermission('deliveries.create');

        $pesananId = $this->input('pesanan_id');
        $driverId = $this->input('sales_driver_id') ?: null;
        $wilayahId = $this->input('rute_wilayah_id') ?: null;
        $status = $this->input('status_surat_jalan', 'disetujui_owner');

        if (empty($pesananId)) {
            $this->flashError('Pilih pesanan nota toko.');
            $this->redirect('/deliveries');
            return;
        }

        if (empty($driverId)) {
            $this->flashError('Pilih sales-driver penanggung jawab pengiriman.');
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

            // Sync driver ke pesanan jika ada driver yang ditugaskan
            if (!empty($driverId)) {
                Database::execute("UPDATE public.pesanan SET sales_driver_id = :driver WHERE id = :pesanan", [
                    'driver' => $driverId,
                    'pesanan' => $pesananId
                ]);
            }

            ActivityLog::log('Logistik', 'CREATE', "Menerbitkan Surat Jalan #{$nomorSj} (Status: {$status})", 'surat_jalan');

            $this->flashSuccess("Surat Jalan <strong>{$nomorSj}</strong> berhasil dibuat!");
            $this->redirect('/deliveries');

        } catch (Throwable $e) {
            $this->flashError('Gagal menerbitkan surat jalan: ' . $e->getMessage());
            $this->redirect('/deliveries');
        }
    }

    /**
     * Otorisasi Persetujuan Pengiriman oleh Owner / Pimpinan
     */
    public function approve(): void
    {
        Auth::requirePermission('owner.approval_delivery');

        $id = (string)$this->input('id');
        if (empty($id)) {
            $this->flashError('ID Surat Jalan tidak valid.');
            $this->redirect('/deliveries');
            return;
        }

        try {
            $sj = Database::fetchOne("
                SELECT sj.id, sj.nomor_surat_jalan, sj.status_surat_jalan, cust.nama_toko
                FROM public.surat_jalan sj
                JOIN public.pesanan p ON sj.pesanan_id = p.id
                JOIN public.pelanggan cust ON p.pelanggan_id = cust.id
                WHERE sj.id = :id
            ", ['id' => $id]);

            if (!$sj) {
                $this->flashError('Surat Jalan tidak ditemukan.');
                $this->redirect('/deliveries');
                return;
            }

            Database::execute("
                UPDATE public.surat_jalan 
                SET status_surat_jalan = 'disetujui_owner', 
                    disetujui_oleh = :uid, 
                    diubah_pada = NOW() 
                WHERE id = :id
            ", [
                'uid' => Auth::id() ?: null,
                'id' => $id,
            ]);

            ActivityLog::log(
                'Logistik',
                'APPROVE',
                "Menyetujui Surat Jalan #{$sj['nomor_surat_jalan']} tujuan toko {$sj['nama_toko']}",
                'surat_jalan',
                $id
            );

            $this->flashSuccess("Surat Jalan #{$sj['nomor_surat_jalan']} berhasil disetujui (Approved)! Armada/Driver dapat memulai pengiriman.");
            $this->redirect('/deliveries');

        } catch (Throwable $e) {
            $this->flashError('Gagal menyetujui surat jalan: ' . $e->getMessage());
            $this->redirect('/deliveries');
        }
    }

    public function updateStatus(): void
    {
        Auth::requirePermission(['deliveries.update_all', 'deliveries.update_assigned']);

        $id = $this->input('id');
        $status = $this->input('status_surat_jalan');
        $penerima = trim((string)$this->input('nama_penerima_toko'));

        if (empty($id) || empty($status)) {
            $this->flashError('Parameter tidak lengkap.');
            $this->redirect('/deliveries');
            return;
        }

        // Scope check untuk driver: pastikan surat jalan ini miliknya jika tidak punya deliveries.update_all
        if (!Auth::can('deliveries.update_all') && !Auth::isAssignedDelivery((string)$id)) {
            $this->flashError('Akses Ditolak: Tugas pengiriman ini bukan dialokasikan ke Anda.');
            $this->redirect('/deliveries');
            return;
        }

        // Cek status persetujuan saat ini
        $currentSj = Database::fetchOne("SELECT status_surat_jalan, nomor_surat_jalan FROM public.surat_jalan WHERE id = :id", ['id' => $id]);
        if (!$currentSj) {
            $this->flashError('Surat Jalan tidak ditemukan.');
            $this->redirect('/deliveries');
            return;
        }

        if ($currentSj['status_surat_jalan'] === 'selesai_diterima') {
            $this->flashError("Surat Jalan #{$currentSj['nomor_surat_jalan']} sudah selesai diterima oleh toko mitra dan tidak dapat diubah lagi.");
            $this->redirect('/deliveries');
            return;
        }

        if ($currentSj['status_surat_jalan'] === 'menunggu_persetujuan' && !Auth::can('owner.approval_delivery')) {
            $this->flashError("Surat Jalan #{$currentSj['nomor_surat_jalan']} belum disetujui oleh Owner/Pimpinan. Armada belum dapat diberangkatkan.");
            $this->redirect('/deliveries');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

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
            $stmtSj = $pdo->prepare($sql);
            $stmtSj->execute($params);

            // Ambil data pesanan terkait
            $sjData = Database::fetchOne("SELECT pesanan_id FROM public.surat_jalan WHERE id = :id", ['id' => $id]);
            $orderId = $sjData['pesanan_id'] ?? null;

            if ($orderId) {
                $orderData = Database::fetchOne("
                    SELECT p.*, pel.nama_toko, pel.is_konsinyasi 
                    FROM public.pesanan p 
                    JOIN public.pelanggan pel ON p.pelanggan_id = pel.id 
                    WHERE p.id = :id
                ", ['id' => $orderId]);

                if ($orderData) {
                    $isKonsinyasi = (bool)$orderData['is_konsinyasi'] || ($orderData['tipe_pembayaran'] === 'konsinyasi');

                    if ($status === 'sedang_dikirim') {
                        $pdo->prepare("UPDATE public.pesanan SET status_pemrosesan = 'sedang_dikirim', diubah_pada = NOW() WHERE id = :id")->execute(['id' => $orderId]);
                    } elseif ($status === 'gagal_kembali' || $status === 'gagal_kirim') {
                        // Kembalikan stok fisik ke gudang jika pesanan sebelumnya sudah dipotong stok
                        if (in_array($orderData['status_pemrosesan'] ?? '', ['siap_dikirim', 'siap_kirim', 'sedang_dikirim'], true)) {
                            $orderedItems = Database::fetchAll("SELECT item_id, kuantitas_satuan_dasar FROM public.item_pesanan WHERE pesanan_id = :id", ['id' => $orderId]);
                            $stmtStok = $pdo->prepare("UPDATE public.item SET stok_fisik_saat_ini = stok_fisik_saat_ini + :qty, diubah_pada = NOW() WHERE id = :item_id");
                            $stmtRiwayat = $pdo->prepare("
                                INSERT INTO public.riwayat_stok (
                                    item_id, tipe_mutasi, jumlah_perubahan,
                                    stok_sebelum, stok_sesudah, referensi_tabel, referensi_id,
                                    keterangan, dibuat_oleh, dibuat_pada
                                ) VALUES (
                                    :item_id, 'penyesuaian_opname_tambah', :qty,
                                    :stok_sebelum, :stok_sesudah, 'pesanan', :ref_id,
                                    :ket, :user_id, NOW()
                                )
                            ");
                            $userId = Auth::id() ?: null;
                            foreach ($orderedItems as $oit) {
                                $qtyPcs = (int)$oit['kuantitas_satuan_dasar'];
                                if ($qtyPcs <= 0) continue;
                                $itemData = Database::fetchOne("SELECT stok_fisik_saat_ini FROM public.item WHERE id = :id FOR UPDATE", ['id' => $oit['item_id']]);
                                $stokSebelum = $itemData ? (float)$itemData['stok_fisik_saat_ini'] : 0;
                                $stokSesudah = $stokSebelum + $qtyPcs;

                                $stmtStok->execute(['qty' => $qtyPcs, 'item_id' => $oit['item_id']]);
                                $stmtRiwayat->execute([
                                    'item_id' => $oit['item_id'],
                                    'qty' => $qtyPcs,
                                    'stok_sebelum' => $stokSebelum,
                                    'stok_sesudah' => $stokSesudah,
                                    'ref_id' => $orderId,
                                    'ket' => "Pengembalian Barang Gagal Kirim #{$orderData['nomor_nota']}",
                                    'user_id' => $userId
                                ]);
                            }
                        }

                        $pdo->prepare("UPDATE public.pesanan SET status_pemrosesan = 'gagal_dikirim', waktu_gagal_kirim = NOW(), diubah_pada = NOW() WHERE id = :id")->execute(['id' => $orderId]);
                        
                        ActivityLog::log(
                            'Delivery',
                            'UPDATE',
                            "Pengiriman pesanan #{$orderData['nomor_nota']} ({$orderData['nama_toko']}) ditandai Gagal Kirim. Stok fisik produk otomatis dikembalikan ke rak gudang.",
                            'pesanan',
                            (string)$orderId
                        );
                    } elseif ($status === 'selesai_diterima') {
                        if ($isKonsinyasi) {
                            // Konsinyasi: Pemotongan stok gudang, riwayat mutasi stok konsinyasi keluar,
                            // dan penambahan saldo rak toko diproses secara atomik oleh trigger DB: trg_proses_pengiriman_konsinyasi.
                        } else {
                            $pdo->prepare("UPDATE public.pesanan SET status_pemrosesan = 'selesai_dikirim', diubah_pada = NOW() WHERE id = :id")->execute(['id' => $orderId]);

                            // 2. Reguler: Catat Pengakuan Piutang & Arus Kas Masuk
                            $sisaTagihan = (float)$orderData['sisa_tagihan'];
                            $totalDibayar = (float)$orderData['total_dibayar'];
                            $akunKasId = $orderData['akun_kas_id'];
                            $userId = Auth::id() ?: null;

                            if ($sisaTagihan > 0) {
                                $pdo->prepare("
                                    UPDATE public.pelanggan
                                    SET total_piutang_berjalan = COALESCE(total_piutang_berjalan, 0) + :sisa,
                                        diubah_pada = NOW()
                                    WHERE id = :pelanggan_id
                                ")->execute([
                                    'sisa' => $sisaTagihan,
                                    'pelanggan_id' => $orderData['pelanggan_id']
                                ]);
                            }

                            if ($totalDibayar > 0 && !empty($akunKasId)) {
                                $akunKas = Database::fetchOne("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $akunKasId]);
                                $saldoLama = (float)($akunKas['saldo_saat_ini'] ?? 0);
                                $saldoBaru = $saldoLama + $totalDibayar;

                                $pdo->prepare("
                                    UPDATE public.akun_kas
                                    SET saldo_saat_ini = :saldo,
                                        diubah_pada = NOW()
                                    WHERE id = :akun_kas
                                ")->execute([
                                    'saldo' => $saldoBaru,
                                    'akun_kas' => $akunKasId,
                                ]);

                                $keteranganKas = ($orderData['status_pembayaran'] === 'lunas')
                                    ? "Penerimaan Tunai Lunas Pesanan Toko #{$orderData['nomor_nota']} ({$orderData['nama_toko']})"
                                    : "Penerimaan DP/Sebagian Pesanan Toko #{$orderData['nomor_nota']} ({$orderData['nama_toko']})";

                                $pdo->prepare("
                                    INSERT INTO public.arus_kas (
                                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                                        keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                                    ) VALUES (
                                        :akun_kas, CURRENT_DATE, 'masuk', 'penjualan', :nominal,
                                        :ket, 'pesanan', :ref_id, :saldo_berjalan, :user_id, NOW()
                                    )
                                ")->execute([
                                    'akun_kas' => $akunKasId,
                                    'nominal' => $totalDibayar,
                                    'ket' => $keteranganKas,
                                    'ref_id' => $orderId,
                                    'saldo_berjalan' => $saldoBaru,
                                    'user_id' => $userId,
                                ]);
                            }
                        }
                    }
                }
            }

            $pdo->commit();
            $this->flashSuccess("Status pengiriman berhasil diperbarui!");
            $this->redirect('/deliveries');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal update status: ' . $e->getMessage());
            $this->redirect('/deliveries');
        }
    }

    /**
     * Cetak Lembar Surat Jalan Pengiriman (Print Delivery Order / Manifest)
     */
    public function print(): void
    {
        Auth::requirePermission('deliveries.print');

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
                       cust.nama_toko, cust.kode_pelanggan, cust.alamat_lengkap as alamat_toko, cust.nomor_whatsapp, cust.nama_pemilik, cust.is_konsinyasi,
                       COALESCE(driver_sj.nama_karyawan, driver_p.nama_karyawan) as nama_driver,
                       COALESCE(driver_sj.nomor_telepon, driver_p.nomor_telepon) as telp_driver,
                       COALESCE(driver_sj.nomor_polisi_kendaraan, driver_p.nomor_polisi_kendaraan) as nopol_driver,
                       w.nama_wilayah, w.kode_rute
                FROM public.surat_jalan sj
                JOIN public.pesanan p ON sj.pesanan_id = p.id
                JOIN public.pelanggan cust ON p.pelanggan_id = cust.id
                LEFT JOIN public.v_karyawan_info driver_sj ON sj.sales_driver_id = driver_sj.id
                LEFT JOIN public.v_karyawan_info driver_p ON p.sales_driver_id = driver_p.id
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

    /**
     * Portal Khusus Driver: Rute Pengiriman Hari Ini & Manajemen Tugas Armada
     */
    public function driverRoute(): void
    {
        Auth::requirePermission(['deliveries.view_all', 'deliveries.view_assigned']);

        try {
            $selectedDate = $this->input('date', date('Y-m-d'));
            $filterDriver = $this->input('driver_id', '');
            $statusFilter = $this->input('status', 'semua');

            $myEmpId = Auth::employeeId();
            $userRole = Auth::role();
            // Role sales / driver terkunci ke ID karyawan miliknya
            $isRestricted = in_array($userRole, ['sales', 'driver'], true) || !Auth::can('deliveries.view_all');
            $isManager = !$isRestricted;

            if ($isRestricted) {
                // Driver / Sales hanya bisa melihat tugas miliknya
                $driverId = $myEmpId;
                $filterDriver = $myEmpId;
            } else {
                $driverId = !empty($filterDriver) ? $filterDriver : null;
            }

            // Query Surat Jalan & Pesanan Aktif untuk Rute Pengiriman
            $sql = "
                SELECT sj.id as surat_jalan_id, sj.nomor_surat_jalan, sj.status_surat_jalan,
                       sj.waktu_berangkat, sj.waktu_sampai, sj.nama_penerima_toko, sj.bukti_terima_foto,
                       sj.foto_bukti_gagal, sj.alasan_gagal, sj.catatan_gagal,
                       sj.dibuat_pada as waktu_terbit_sj,
                       p.id as pesanan_id, p.nomor_nota, p.tanggal_pesanan, p.total_bruto, p.total_netto,
                       p.total_dibayar, p.sisa_tagihan, p.tipe_pembayaran, p.status_pembayaran, p.status_pemrosesan,
                       p.catatan as catatan_pesanan, p.waktu_gagal_kirim,
                       pel.id as pelanggan_id, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik,
                       pel.nomor_whatsapp, pel.alamat_lengkap, pel.link_google_maps, pel.is_konsinyasi,
                       w.nama_wilayah, w.kode_rute,
                       k.id as driver_id, k.nama_karyawan as nama_driver, k.nomor_polisi_kendaraan as nopol_driver,
                       k.nomor_telepon as telp_driver,
                       (SELECT COUNT(*) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_sku,
                       (SELECT COALESCE(SUM(kuantitas_satuan_dasar), 0) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_pcs
                FROM public.surat_jalan sj
                JOIN public.pesanan p ON sj.pesanan_id = p.id
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.wilayah w ON COALESCE(sj.rute_wilayah_id, pel.wilayah_id) = w.id
                LEFT JOIN public.v_karyawan_info k ON COALESCE(sj.sales_driver_id, p.sales_driver_id) = k.id
                WHERE p.status_pembayaran != 'dibatalkan'
            ";

            $params = [];

            // Filter Tanggal Surat Jalan / Tanggal Pesanan jika dipilih
            if (!empty($selectedDate)) {
                $sql .= " AND (DATE(sj.dibuat_pada) = :sel_date OR p.tanggal_pesanan = :sel_date)";
                $params['sel_date'] = $selectedDate;
            }

            // Scope Filter Driver
            if (!empty($driverId)) {
                $sql .= " AND (sj.sales_driver_id = :driver_id OR p.sales_driver_id = :driver_id)";
                $params['driver_id'] = $driverId;
            }

            $sql .= " ORDER BY 
                CASE 
                    WHEN sj.status_surat_jalan = 'sedang_dikirim' THEN 1
                    WHEN sj.status_surat_jalan IN ('siap_kirim', 'disetujui_owner', 'menunggu_persetujuan', 'draf_n8n') THEN 2
                    WHEN sj.status_surat_jalan = 'selesai_diterima' THEN 3
                    WHEN sj.status_surat_jalan = 'gagal_kirim' THEN 4
                    ELSE 5
                END,
                sj.dibuat_pada ASC
            ";

            // 1. Ambil seluruh data untuk tanggal & armada ini guna kalkulasi Metrik Global yang presisi
            $allDeliveries = Database::fetchAll($sql, $params);

            $countTotal = count($allDeliveries);
            $countPending = 0;
            $countInTransit = 0;
            $countCompleted = 0;
            $countFailed = 0;
            $totalPcs = 0;

            foreach ($allDeliveries as $d) {
                $st = $d['status_surat_jalan'];
                if (in_array($st, ['menunggu_persetujuan', 'draf_n8n', 'siap_kirim', 'disetujui_owner'], true)) {
                    $countPending++;
                } elseif ($st === 'sedang_dikirim') {
                    $countInTransit++;
                } elseif ($st === 'selesai_diterima') {
                    $countCompleted++;
                } elseif ($st === 'gagal_kirim') {
                    $countFailed++;
                }
                $totalPcs += (int)$d['total_pcs'];
            }

            // 2. Terapkan Filter Status Tab hanya pada daftar data yang dirender (tanpa mengacaukan metrik tab & kartu)
            $deliveries = $allDeliveries;
            if (!empty($statusFilter) && $statusFilter !== 'semua') {
                $deliveries = array_values(array_filter($allDeliveries, function($d) use ($statusFilter) {
                    $st = $d['status_surat_jalan'];
                    if ($statusFilter === 'pending') {
                        return in_array($st, ['menunggu_persetujuan', 'draf_n8n', 'siap_kirim', 'disetujui_owner'], true);
                    } elseif ($statusFilter === 'in_transit') {
                        return ($st === 'sedang_dikirim');
                    } elseif ($statusFilter === 'completed') {
                        return ($st === 'selesai_diterima');
                    } elseif ($statusFilter === 'failed') {
                        return ($st === 'gagal_kirim');
                    }
                    return true;
                }));
            }

            // Ambil item produk untuk setiap rute pengiriman yang ditampilkan
            if (!empty($deliveries)) {
                $orderIds = array_values(array_filter(array_unique(array_column($deliveries, 'pesanan_id'))));
                if (!empty($orderIds)) {
                    $itemParams = [];
                    $itemPlaceholders = [];
                    foreach ($orderIds as $idx => $oid) {
                        $key = 'ord_id_' . $idx;
                        $itemPlaceholders[] = ':' . $key;
                        $itemParams[$key] = (string)$oid;
                    }
                    $rawItems = Database::fetchAll("
                        SELECT ip.pesanan_id, ip.item_id, ip.kuantitas_satuan_dasar, ip.harga_satuan_deal, ip.subtotal,
                               it.nama_item, it.kode_sku, it.varian_rasa, it.satuan_dasar
                        FROM public.item_pesanan ip
                        JOIN public.item it ON ip.item_id = it.id
                        WHERE ip.pesanan_id IN (" . implode(', ', $itemPlaceholders) . ")
                        ORDER BY it.nama_item ASC
                    ", $itemParams);

                    $itemsByOrder = [];
                    foreach ($rawItems as $rit) {
                        $itemsByOrder[$rit['pesanan_id']][] = $rit;
                    }

                    foreach ($deliveries as &$d) {
                        $d['items'] = $itemsByOrder[$d['pesanan_id']] ?? [];
                    }
                    unset($d);
                }
            }

            // Master data drivers untuk filter admin
            $drivers = Database::fetchAll("
                SELECT id, nama_karyawan, nomor_polisi_kendaraan, nomor_telepon, posisi 
                FROM public.v_karyawan_info 
                WHERE posisi IN ('driver', 'sales') AND status_aktif = TRUE
                ORDER BY (posisi = 'driver') DESC, nama_karyawan ASC
            ");

            // Master akun kas untuk setor tunai
            $cashAccounts = Database::fetchAll("SELECT id, nama_akun, saldo_saat_ini, is_default_pos FROM public.akun_kas WHERE status_aktif = TRUE ORDER BY is_default_pos DESC, nama_akun ASC");

            // Driver name for restricted user
            $myDriverName = null;
            if (!empty($myEmpId)) {
                foreach ($drivers as $dr) {
                    if ($dr['id'] === $myEmpId) {
                        $myDriverName = $dr['nama_karyawan'] . (!empty($dr['nomor_polisi_kendaraan']) ? ' (' . $dr['nomor_polisi_kendaraan'] . ')' : '');
                        break;
                    }
                }
                if (!$myDriverName) {
                    $emp = Database::fetchOne("SELECT nama_karyawan, nomor_polisi_kendaraan FROM public.v_karyawan_info WHERE id = :id", ['id' => $myEmpId]);
                    if ($emp) {
                        $myDriverName = $emp['nama_karyawan'] . (!empty($emp['nomor_polisi_kendaraan']) ? ' (' . $emp['nomor_polisi_kendaraan'] . ')' : '');
                    }
                }
            }
            if (!$myDriverName) {
                $myDriverName = Auth::user()['nama_lengkap'] ?? Auth::username();
            }

            $this->view('deliveries.driver_route', [
                'pageTitle' => 'Pengiriman Driver',
                'pageSubtitle' => 'Rute Distribusi & Konfirmasi Serah Terima Toko',
                'deliveries' => $deliveries,
                'selectedDate' => $selectedDate,
                'filterDriver' => $filterDriver,
                'statusFilter' => $statusFilter,
                'drivers' => $drivers,
                'cashAccounts' => $cashAccounts,
                'isManager' => $isManager,
                'isRestricted' => $isRestricted,
                'myDriverName' => $myDriverName,
                'currentEmployeeId' => $myEmpId,
                'metrics' => [
                    'count_total' => $countTotal,
                    'count_pending' => $countPending,
                    'count_in_transit' => $countInTransit,
                    'count_completed' => $countCompleted,
                    'count_failed' => $countFailed,
                    'total_pcs' => $totalPcs,
                ]
            ]);

        } catch (Throwable $e) {
            $this->flashError('Gagal memuat rute pengiriman: ' . $e->getMessage());
            $this->redirect('/deliveries');
        }
    }

    /**
     * Driver Memulai Pengiriman (Berangkat / In Transit)
     */
    public function startTrip(): void
    {
        Auth::requirePermission(['deliveries.update_all', 'deliveries.update_assigned']);

        $sjId = $this->input('surat_jalan_id');
        if (empty($sjId)) {
            $this->flashError('Parameter surat jalan tidak valid.');
            $this->redirect('/driver-deliveries');
            return;
        }

        try {
            $sj = Database::fetchOne("SELECT * FROM public.surat_jalan WHERE id = :id", ['id' => $sjId]);
            if (!$sj) {
                $this->flashError('Surat jalan tidak ditemukan.');
                $this->redirect('/driver-deliveries');
                return;
            }

            // Scope check jika bukan admin/manajer
            if (!Auth::can('deliveries.update_all') && !Auth::isAssignedDelivery((string)$sjId)) {
                $this->flashError('Akses Ditolak: Surat jalan ini tidak ditugaskan ke Anda.');
                $this->redirect('/driver-deliveries');
                return;
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $pdo->prepare("
                UPDATE public.surat_jalan 
                SET status_surat_jalan = 'sedang_dikirim', 
                    waktu_berangkat = COALESCE(waktu_berangkat, NOW()),
                    diubah_pada = NOW() 
                WHERE id = :id
            ")->execute(['id' => $sjId]);

            if (!empty($sj['pesanan_id'])) {
                $pdo->prepare("
                    UPDATE public.pesanan 
                    SET status_pemrosesan = 'sedang_dikirim', 
                        diubah_pada = NOW() 
                    WHERE id = :id
                ")->execute(['id' => $sj['pesanan_id']]);
            }

            $pdo->commit();

            ActivityLog::log(
                'Delivery',
                'UPDATE',
                "Driver memulai pengiriman Surat Jalan #{$sj['nomor_surat_jalan']} (Status: Sedang Dikirim)",
                'surat_jalan',
                (string)$sjId
            );

            $this->flashSuccess("Pengiriman Surat Jalan #{$sj['nomor_surat_jalan']} dimulai! Hati-hati di jalan.");
            $this->redirect('/driver-deliveries');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal memulai pengiriman: ' . $e->getMessage());
            $this->redirect('/driver-deliveries');
        }
    }

    /**
     * Konfirmasi Selesai Pengiriman (Serah Terima Toko)
     */
    public function completeDelivery(): void
    {
        Auth::requirePermission(['deliveries.update_all', 'deliveries.update_assigned']);

        $sjId = $this->input('surat_jalan_id');
        $penerima = trim((string)$this->input('nama_penerima_toko'));
        $nominalTunai = (float)str_replace(['.', ','], '', (string)$this->input('nominal_tunai_diterima', 0));
        $akunKasId = $this->input('akun_kas_id');
        $catatanDriver = trim((string)$this->input('catatan_driver', ''));

        if (empty($sjId) || empty($penerima)) {
            $this->flashError('Nama penerima toko wajib diisi.');
            $this->redirect('/driver-deliveries');
            return;
        }

        try {
            $sj = Database::fetchOne("
                SELECT sj.*, p.nomor_nota, p.total_netto, p.total_dibayar, p.tipe_pembayaran, p.pelanggan_id,
                       pel.nama_toko, pel.is_konsinyasi
                FROM public.surat_jalan sj
                JOIN public.pesanan p ON sj.pesanan_id = p.id
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                WHERE sj.id = :id
            ", ['id' => $sjId]);

            if (!$sj) {
                $this->flashError('Surat jalan tidak ditemukan.');
                $this->redirect('/driver-deliveries');
                return;
            }

            if (!Auth::can('deliveries.update_all') && !Auth::isAssignedDelivery((string)$sjId)) {
                $this->flashError('Akses Ditolak: Surat jalan ini tidak ditugaskan ke Anda.');
                $this->redirect('/driver-deliveries');
                return;
            }

            // Handle Upload Foto Bukti Serah Terima via Upload Helper (Anti Dobel Folder & Validasi Gambar)
            $fotoPath = null;
            if (isset($_FILES['bukti_foto']) && $_FILES['bukti_foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadRes = \App\Helpers\Upload::storeImage($_FILES['bukti_foto'], 'delivery_proofs', 'PROOF');
                if (!$uploadRes['success']) {
                    $this->flashError($uploadRes['error']);
                    $this->redirect('/driver-deliveries');
                    return;
                }
                $fotoPath = $uploadRes['path'];
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // 1. Update Surat Jalan
            $sqlSj = "
                UPDATE public.surat_jalan 
                SET status_surat_jalan = 'selesai_diterima',
                    nama_penerima_toko = :penerima,
                    waktu_sampai = NOW(),
                    diubah_pada = NOW()
            ";
            $paramsSj = [
                'id' => $sjId,
                'penerima' => $penerima
            ];

            if (!empty($fotoPath)) {
                $sqlSj .= ", bukti_terima_foto = :foto";
                $paramsSj['foto'] = $fotoPath;
            }

            $sqlSj .= " WHERE id = :id";
            $pdo->prepare($sqlSj)->execute($paramsSj);

            // 2. Update Pesanan
            $orderId = $sj['pesanan_id'];
            $isKonsinyasi = (bool)$sj['is_konsinyasi'] || ($sj['tipe_pembayaran'] === 'konsinyasi');

            // Hitung Pembayaran Tunai (Jika Penjualan Tunai dan ada nominal diterima)
            $newTotalDibayar = (float)$sj['total_dibayar'];
            $newStatusBayar = null;

            if ($nominalTunai > 0) {
                $newTotalDibayar += $nominalTunai;
                $sisa = max(0, (float)$sj['total_netto'] - $newTotalDibayar);
                $newStatusBayar = ($sisa <= 0) ? 'lunas' : 'sebagian';

                // Catat transaksi kas jika akun kas dipilih atau default kasir
                $kasId = $akunKasId;
                if (empty($kasId)) {
                    $defaultKas = Database::fetchOne("SELECT id FROM public.akun_kas WHERE is_default_pos = TRUE AND status_aktif = TRUE LIMIT 1");
                    $kasId = $defaultKas['id'] ?? null;
                }

                if (!empty($kasId)) {
                    // Update Saldo Kas
                    $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + :nom, diubah_pada = NOW() WHERE id = :id")->execute([
                        'nom' => $nominalTunai,
                        'id' => $kasId
                    ]);

                    $currentSaldo = (float)Database::fetchOne("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $kasId])['saldo_saat_ini'];

                    // Insert Arus Kas
                    $pdo->prepare("
                        INSERT INTO public.arus_kas (
                            akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                            keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                        ) VALUES (
                            :kas_id, CURRENT_DATE, 'masuk', 'penjualan', :nominal,
                            :ket, 'pesanan', :order_id, :saldo_berjalan, :user_id, NOW()
                        )
                    ")->execute([
                        'kas_id' => $kasId,
                        'nominal' => $nominalTunai,
                        'ket' => "Penerimaan Tunai Driver #{$sj['nomor_surat_jalan']} ({$sj['nama_toko']}) - Nota #{$sj['nomor_nota']}",
                        'order_id' => $orderId,
                        'saldo_berjalan' => $currentSaldo,
                        'user_id' => Auth::id() ?: null
                    ]);
                }
            }

            $sqlOrder = "
                UPDATE public.pesanan 
                SET status_pemrosesan = :st_proc,
                    diubah_pada = NOW()
            ";
            $paramsOrder = [
                'id' => $orderId,
                'st_proc' => $isKonsinyasi ? 'selesai' : 'selesai_dikirim'
            ];

            if ($nominalTunai > 0 && $newStatusBayar !== null) {
                $sqlOrder .= ", total_dibayar = :tot_bayar, sisa_tagihan = GREATEST(0, total_netto - :tot_bayar), status_pembayaran = :st_bayar";
                if (!empty($kasId)) {
                    $sqlOrder .= ", akun_kas_id = :kas_id";
                    $paramsOrder['kas_id'] = $kasId;
                }
                $paramsOrder['tot_bayar'] = $newTotalDibayar;
                $paramsOrder['st_bayar'] = $newStatusBayar;
            }

            if (!empty($catatanDriver)) {
                $sqlOrder .= ", catatan = COALESCE(catatan, '') || E'\n[Catatan Driver: ' || :catatan || ']'";
                $paramsOrder['catatan'] = $catatanDriver;
            }

            $sqlOrder .= " WHERE id = :id";
            $pdo->prepare($sqlOrder)->execute($paramsOrder);

            // Catatan: Jika pesanan adalah konsinyasi, pemotongan stok gudang (item.stok_fisik_saat_ini),
            // pencatatan kartu stok (riwayat_stok: konsinyasi_keluar), dan penambahan saldo stok rak toko (stok_konsinyasi_toko)
            // sudah dieksekusi secara otomatis dan atomik oleh database trigger: trg_proses_pengiriman_konsinyasi.

            $pdo->commit();

            ActivityLog::log(
                'Delivery',
                'UPDATE',
                "Driver menyelesaikan serah terima pengiriman ke {$sj['nama_toko']} (Penerima: {$penerima})",
                'surat_jalan',
                (string)$sjId
            );

            $this->flashSuccess("Pengiriman ke {$sj['nama_toko']} berhasil diselesaikan (Penerima: {$penerima})!");
            $this->redirect('/driver-deliveries');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal konfirmasi selesai pengiriman: ' . $e->getMessage());
            $this->redirect('/driver-deliveries');
        }
    }

    /**
     * Driver Melaporkan Gagal Kirim
     */
    public function failDelivery(): void
    {
        Auth::requirePermission(['deliveries.update_all', 'deliveries.update_assigned']);

        $sjId = $this->input('surat_jalan_id');
        $alasan = trim((string)$this->input('alasan_gagal', 'Kendala Lapangan'));
        $catatan = trim((string)$this->input('catatan_gagal', ''));

        if (empty($sjId)) {
            $this->flashError('Parameter surat jalan tidak valid.');
            $this->redirect('/driver-deliveries');
            return;
        }

        try {
            $sj = Database::fetchOne("
                SELECT sj.*, p.nomor_nota, pel.nama_toko 
                FROM public.surat_jalan sj
                JOIN public.pesanan p ON sj.pesanan_id = p.id
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                WHERE sj.id = :id
            ", ['id' => $sjId]);

            if (!$sj) {
                $this->flashError('Surat jalan tidak ditemukan.');
                $this->redirect('/driver-deliveries');
                return;
            }

            if (!Auth::can('deliveries.update_all') && !Auth::isAssignedDelivery((string)$sjId)) {
                $this->flashError('Akses Ditolak: Surat jalan ini tidak ditugaskan ke Anda.');
                $this->redirect('/driver-deliveries');
                return;
            }

            // Handle Upload Foto Bukti Gagal Kirim via Upload Helper (Anti Dobel Folder, Kompresi WebP & Acak Hash)
            $fotoGagalPath = null;
            $fileInput = $_FILES['foto_bukti_gagal'] ?? $_FILES['bukti_foto_gagal'] ?? null;
            if ($fileInput && ($fileInput['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploadRes = \App\Helpers\Upload::storeImage($fileInput, 'delivery_proofs', 'FAIL');
                if (!$uploadRes['success']) {
                    $this->flashError($uploadRes['error']);
                    $this->redirect('/driver-deliveries');
                    return;
                }
                $fotoGagalPath = $uploadRes['path'];
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // 1. Update Surat Jalan
            $sqlSj = "
                UPDATE public.surat_jalan 
                SET status_surat_jalan = 'gagal_kirim',
                    alasan_gagal = :alasan,
                    catatan_gagal = :catatan,
                    diubah_pada = NOW()
            ";
            $paramsSj = [
                'id' => $sjId,
                'alasan' => $alasan,
                'catatan' => $catatan
            ];
            if (!empty($fotoGagalPath)) {
                $sqlSj .= ", foto_bukti_gagal = :foto";
                $paramsSj['foto'] = $fotoGagalPath;
            }
            $sqlSj .= " WHERE id = :id";
            $pdo->prepare($sqlSj)->execute($paramsSj);

            // 2. Kembalikan stok fisik ke gudang jika pesanan sebelumnya sudah dipotong stok
            $orderBefore = Database::fetchOne("SELECT id, nomor_nota, status_pemrosesan, catatan FROM public.pesanan WHERE id = :id FOR UPDATE", ['id' => $sj['pesanan_id']]);
            $isPhysicalStockCut = $orderBefore && in_array($orderBefore['status_pemrosesan'] ?? '', ['siap_dikirim', 'siap_kirim', 'sedang_dikirim'], true);

            if ($isPhysicalStockCut) {
                $orderedItems = Database::fetchAll("SELECT item_id, kuantitas_satuan_dasar FROM public.item_pesanan WHERE pesanan_id = :id", ['id' => $sj['pesanan_id']]);
                $stmtStok = $pdo->prepare("UPDATE public.item SET stok_fisik_saat_ini = stok_fisik_saat_ini + :qty, diubah_pada = NOW() WHERE id = :item_id");
                $stmtRiwayat = $pdo->prepare("
                    INSERT INTO public.riwayat_stok (
                        item_id, tipe_mutasi, jumlah_perubahan,
                        stok_sebelum, stok_sesudah, referensi_tabel, referensi_id,
                        keterangan, dibuat_oleh, dibuat_pada
                    ) VALUES (
                        :item_id, 'penyesuaian_opname_tambah', :qty,
                        :stok_sebelum, :stok_sesudah, 'pesanan', :ref_id,
                        :ket, :user_id, NOW()
                    )
                ");
                $userId = Auth::id() ?: null;
                foreach ($orderedItems as $oit) {
                    $qtyPcs = (int)$oit['kuantitas_satuan_dasar'];
                    if ($qtyPcs <= 0) continue;
                    $itemData = Database::fetchOne("SELECT stok_fisik_saat_ini FROM public.item WHERE id = :id FOR UPDATE", ['id' => $oit['item_id']]);
                    $stokSebelum = $itemData ? (float)$itemData['stok_fisik_saat_ini'] : 0;
                    $stokSesudah = $stokSebelum + $qtyPcs;

                    $stmtStok->execute(['qty' => $qtyPcs, 'item_id' => $oit['item_id']]);
                    $stmtRiwayat->execute([
                        'item_id' => $oit['item_id'],
                        'qty' => $qtyPcs,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'ref_id' => $sj['pesanan_id'],
                        'ket' => "Pengembalian Barang Gagal Kirim #{$sj['nomor_nota']} ({$alasan})",
                        'user_id' => $userId
                    ]);
                }
            }

            // 3. Update Pesanan
            $keteranganGagal = "[Gagal Kirim: {$alasan}]" . (!empty($catatan) ? " Catatan: {$catatan}" : "");
            $pdo->prepare("
                UPDATE public.pesanan 
                SET status_pemrosesan = 'gagal_dikirim',
                    waktu_gagal_kirim = NOW(),
                    catatan = COALESCE(catatan, '') || E'\n' || :ket_gagal,
                    diubah_pada = NOW()
                WHERE id = :id
            ")->execute([
                'ket_gagal' => $keteranganGagal,
                'id' => $sj['pesanan_id']
            ]);

            $pdo->commit();

            ActivityLog::log(
                'Delivery',
                'UPDATE',
                "Driver melaporkan gagal kirim ke {$sj['nama_toko']} - Alasan: {$alasan}" . ($isPhysicalStockCut ? " (Stok produk otomatis dikembalikan ke rak gudang)" : ""),
                'surat_jalan',
                (string)$sjId
            );
            ActivityLog::log(
                'Delivery',
                'UPDATE',
                "Pengiriman pesanan #{$sj['nomor_nota']} ({$sj['nama_toko']}) GAGAL KIRIM. Alasan: {$alasan}." . (!empty($catatan) ? " Catatan: {$catatan}." : "") . ($isPhysicalStockCut ? " Seluruh stok fisik produk otomatis dikembalikan ke rak gudang." : ""),
                'pesanan',
                (string)$sj['pesanan_id']
            );

            $this->flashWarning("Pengiriman ke {$sj['nama_toko']} ditandai Gagal Kirim ({$alasan}). Stok fisik telah dikembalikan ke rak gudang.");
            $this->redirect('/driver-deliveries');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal melaporkan pengiriman: ' . $e->getMessage());
            $this->redirect('/driver-deliveries');
        }
    }

    /**
     * Unduh Dokumen Surat Jalan dalam Format PDF (Dompdf Library)
     */
    public function pdf(): void
    {
        Auth::requirePermission('deliveries.print');

        $id = $this->input('id');
        $orderId = $this->input('order_id');

        if (empty($id) && empty($orderId)) {
            $this->redirect('/deliveries');
            return;
        }

        try {
            if (!empty($orderId) && empty($id)) {
                $sj = Database::fetchOne("SELECT id FROM public.surat_jalan WHERE pesanan_id = :order_id", ['order_id' => $orderId]);
                $id = $sj['id'] ?? null;
            }

            $delivery = Database::fetchOne("
                SELECT sj.*,
                       p.nomor_nota, p.tanggal_pesanan, p.total_netto, p.tipe_pembayaran, p.catatan as catatan_pesanan,
                       pel.nama_toko, pel.kode_pelanggan, pel.nama_pemilik, pel.nomor_whatsapp, 
                       pel.alamat_lengkap, pel.alamat_lengkap as alamat_toko, pel.is_konsinyasi,
                       COALESCE(k.nama_karyawan, driver_p.nama_karyawan) as nama_driver,
                       COALESCE(k.nomor_telepon, driver_p.nomor_telepon) as telp_driver,
                       COALESCE(k.nomor_polisi_kendaraan, driver_p.nomor_polisi_kendaraan) as nopol_driver,
                       w.nama_wilayah, w.kode_rute
                FROM public.surat_jalan sj
                JOIN public.pesanan p ON sj.pesanan_id = p.id
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.v_karyawan_info k ON sj.sales_driver_id = k.id
                LEFT JOIN public.v_karyawan_info driver_p ON p.sales_driver_id = driver_p.id
                LEFT JOIN public.wilayah w ON COALESCE(sj.rute_wilayah_id, pel.wilayah_id) = w.id
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

            ob_start();
            extract(['delivery' => $delivery, 'items' => $items, 'isPdf' => true]);
            require ROOT_PATH . '/views/deliveries/print.php';
            $html = ob_get_clean();

            $cleanSj = preg_replace('/[^A-Za-z0-9\-]/', '_', (string)$delivery['nomor_surat_jalan']);
            PdfExport::download($html, "SuratJalan-{$cleanSj}.pdf", 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->flashError('Gagal membuat PDF Surat Jalan: ' . $e->getMessage());
            $this->redirect('/deliveries/print?id=' . urlencode((string)$id));
        }
    }

    /**
     * Export Riwayat Surat Jalan / Logistik ke File Excel (PhpSpreadsheet)
     */
    public function exportExcel(): void
    {
        Auth::requirePermission(['deliveries.view_all', 'deliveries.view_assigned']);

        try {
            $startDate = $this->input('start_date', date('Y-m-01'));
            $endDate = $this->input('end_date', date('Y-m-d'));
            $driverId = $this->input('driver_id');
            $status = $this->input('status');

            $sql = "
                SELECT sj.*, p.nomor_nota, p.tanggal_pesanan,
                       pel.nama_toko, pel.kode_pelanggan,
                       k.nama_karyawan as nama_driver, k.nomor_polisi_kendaraan,
                       w.nama_wilayah
                FROM public.surat_jalan sj
                JOIN public.pesanan p ON sj.pesanan_id = p.id
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.v_karyawan_info k ON sj.sales_driver_id = k.id
                LEFT JOIN public.wilayah w ON sj.rute_wilayah_id = w.id
                WHERE sj.dibuat_pada >= :start AND sj.dibuat_pada <= :end
            ";
            $params = ['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59'];

            if (!empty($driverId)) {
                $sql .= " AND sj.sales_driver_id = :driver_id";
                $params['driver_id'] = $driverId;
            }
            if (!empty($status)) {
                $sql .= " AND sj.status_surat_jalan = :status";
                $params['status'] = $status;
            }

            $sql .= " ORDER BY sj.dibuat_pada DESC";
            $deliveries = Database::fetchAll($sql, $params);

            $headers = ['No', 'Nomor Surat Jalan', 'Nomor Nota B2B', 'Tanggal Diterbitkan', 'Nama Toko Tujuan', 'Wilayah / Rute', 'Driver / Armada', 'Nomor Polisi', 'Status Pengiriman', 'Penerima Toko'];
            $rows = [];
            $no = 1;
            foreach ($deliveries as $d) {
                $rows[] = [
                    $no++,
                    $d['nomor_surat_jalan'],
                    $d['nomor_nota'],
                    date('d/m/Y H:i', strtotime($d['dibuat_pada'])),
                    $d['nama_toko'],
                    $d['nama_wilayah'] ?? '-',
                    $d['nama_driver'] ?? 'Armada Toko',
                    $d['nomor_polisi_kendaraan'] ?? '-',
                    strtoupper(str_replace('_', ' ', (string)$d['status_surat_jalan'])),
                    $d['nama_penerima_toko'] ?? '-'
                ];
            }

            ExcelExport::download("Daftar-Surat-Jalan-{$startDate}-sd-{$endDate}.xlsx", $headers, $rows, "Surat Jalan");
        } catch (Throwable $e) {
            $this->flashError('Gagal export data surat jalan: ' . $e->getMessage());
            $this->redirect('/deliveries');
        }
    }
}
