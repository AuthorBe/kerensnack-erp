<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\PdfExport;
use App\Helpers\PrintDocumentHelper;
use App\Helpers\ExcelExport;
use App\Helpers\StockHelper;
use App\Helpers\PaymentHelper;
use App\Helpers\DocumentNumber;
use App\Helpers\CashVoucher;
use Database;
use Throwable;

/**
 * app/Controllers/DeliveryController.php
 * Pengendali Pengiriman Logistik, Manifest Rute Armada & Surat Jalan.
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
                SELECT sj.id, sj.nomor_surat_jalan, sj.tanggal_surat_jalan, sj.sales_driver_id, sj.status_surat_jalan, sj.bukti_terima_foto,
                       sj.nama_penerima_toko, sj.waktu_berangkat, sj.waktu_sampai, sj.dibuat_pada,
                       p.nomor_nota, p.tanggal_pesanan, p.total_netto, p.tipe_pembayaran,
                       cust.nama_toko, cust.alamat_lengkap as alamat_toko, cust.nomor_whatsapp, cust.is_konsinyasi,
                       COALESCE(driver_sj.nama_karyawan, driver_p.nama_karyawan) as nama_driver,
                       COALESCE(driver_sj.nomor_telepon, driver_p.nomor_telepon) as telp_driver,
                       COALESCE(driver_sj.nomor_polisi_kendaraan, driver_p.nomor_polisi_kendaraan) as nopol_driver,
                       COALESCE(sj.nama_wilayah_snapshot, w.nama_wilayah, '-') as nama_wilayah,
                       COALESCE(sj.kode_rute_snapshot, w.kode_rute, '-') as kode_rute
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

            $sqlDeliveries .= " ORDER BY COALESCE(sj.tanggal_surat_jalan, sj.dibuat_pada::date) DESC, sj.dibuat_pada DESC";
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
                  AND p.status_pemrosesan IN ('siap_dikirim', 'siap_kirim')
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

            // Hitung default tanggal kirim: Pagi (< 12:00) -> Hari ini, Siang/Sore (>= 12:00) -> Besok
            $nowHour = (int)date('H');
            $isAfternoon = ($nowHour >= 12);
            $defaultDeliveryDate = $isAfternoon ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');

            // Injeksi Cloudflare R2 Presigned URLs (10 Menit)
            foreach ($deliveries as &$deliv) {
                if (!empty($deliv['bukti_terima_foto'])) {
                    $deliv['bukti_terima_foto'] = \App\Helpers\Upload::presignedUrl($deliv['bukti_terima_foto'], 10);
                }
            }
            unset($deliv);

            $this->view('deliveries.index', [
                'pageTitle' => 'Status Pengiriman',
                'pageSubtitle' => 'Manifest Rute Pengiriman & Status Antar Toko',
                'deliveries' => $deliveries,
                'pendingOrders' => $pendingOrders,
                'drivers' => $drivers,
                'territories' => $territories,
                'defaultDeliveryDate' => $defaultDeliveryDate,
                'isAfternoon' => $isAfternoon
            ]);

        } catch (Throwable $e) {
            $this->flashError("Gagal memuat daftar pengiriman: " . $e->getMessage());
            $this->redirect('/dashboard');
        }
    }

    public function store(): void
    {
        Auth::requirePermission('deliveries.create');

        $pesananId = $this->input('pesanan_id');
        $driverId = $this->input('sales_driver_id') ?: null;
        $wilayahId = $this->input('rute_wilayah_id') ?: null;
        $status = $this->input('status_surat_jalan', 'siap_kirim');
        $validStatuses = ['siap_kirim', 'sedang_dikirim', 'selesai_diterima', 'gagal_kembali', 'gagal_kirim'];
        if (!in_array($status, $validStatuses, true)) {
            $status = 'siap_kirim';
        }

        $nowHour = (int)date('H');
        $defaultDate = ($nowHour >= 12) ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');
        $tanggalSj = trim((string)$this->input('tanggal_surat_jalan', $defaultDate));
        if (empty($tanggalSj) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSj)) {
            $tanggalSj = $defaultDate;
        }

        if (empty($pesananId)) {
            $this->flashError('Pilih pesanan nota toko.');
            $this->redirect('/deliveries');
            return;
        }

        if (empty($driverId)) {
            $this->flashError('Pilih driver / petugas penanggung jawab pengiriman.');
            $this->redirect('/deliveries');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $nomorSj = DocumentNumber::nextDeliveryNumber($pdo);
            $userId = Auth::id() ?: null;

            // Tarik snapshot wilayah & rute agar arsip historis surat jalan terkunci permanen
            $wilayahInfo = null;
            if (!empty($wilayahId)) {
                $wilayahInfo = Database::fetchOne("SELECT nama_wilayah, kode_rute FROM public.wilayah WHERE id = :id", ['id' => $wilayahId]);
            } elseif (!empty($pesananId)) {
                $wilayahInfo = Database::fetchOne("
                    SELECT w.id, w.nama_wilayah, w.kode_rute 
                    FROM public.pesanan p 
                    JOIN public.pelanggan pel ON p.pelanggan_id = pel.id 
                    JOIN public.wilayah w ON pel.wilayah_id = w.id 
                    WHERE p.id = :pid
                ", ['pid' => $pesananId]);
                if ($wilayahInfo) {
                    $wilayahId = $wilayahInfo['id'];
                }
            }

            $stmtSj = $pdo->prepare("
                INSERT INTO public.surat_jalan (
                    nomor_surat_jalan, pesanan_id, sales_driver_id, rute_wilayah_id,
                    nama_wilayah_snapshot, kode_rute_snapshot,
                    status_surat_jalan, tanggal_surat_jalan, disetujui_oleh, dibuat_pada
                ) VALUES (
                    :no_sj, :pesanan, :driver, :wilayah,
                    :wilayah_snap, :rute_snap,
                    :status, :tanggal_sj, :user_id, NOW()
                )
            ");
            $stmtSj->execute([
                'no_sj' => $nomorSj,
                'pesanan' => $pesananId,
                'driver' => $driverId,
                'wilayah' => $wilayahId,
                'wilayah_snap' => $wilayahInfo['nama_wilayah'] ?? null,
                'rute_snap' => $wilayahInfo['kode_rute'] ?? null,
                'status' => $status,
                'tanggal_sj' => $tanggalSj,
                'user_id' => $userId
            ]);

            // Sync driver ke pesanan jika ada driver yang ditugaskan
            if (!empty($driverId)) {
                $stmtOrder = $pdo->prepare("UPDATE public.pesanan SET sales_driver_id = :driver WHERE id = :pesanan");
                $stmtOrder->execute([
                    'driver' => $driverId,
                    'pesanan' => $pesananId
                ]);
            }

            $pdo->commit();

            ActivityLog::log('Logistik', 'CREATE', "Menerbitkan Surat Jalan #{$nomorSj} (Status: {$status}, Tgl Kirim: {$tanggalSj})", 'surat_jalan');

            $this->flashSuccess("Surat Jalan <strong>{$nomorSj}</strong> berhasil dibuat untuk tanggal " . date('d/m/Y', strtotime($tanggalSj)) . "!");
            $this->redirect('/deliveries');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menerbitkan surat jalan: ' . $e->getMessage());
            $this->redirect('/deliveries');
        }
    }

    /**
     * Memperbarui Data Surat Jalan (Pengemudi & Tanggal Pengiriman)
     */
    public function update(): void
    {
        Auth::requirePermission(['deliveries.create', 'deliveries.update_all']);

        $id = trim((string)$this->input('id'));
        $driverId = $this->input('sales_driver_id') ?: null;
        $tanggalSj = trim((string)$this->input('tanggal_surat_jalan'));

        if (empty($id)) {
            $this->flashError('ID Surat Jalan tidak valid.');
            $this->redirect('/deliveries');
            return;
        }

        if (empty($driverId)) {
            $this->flashError('Pilih driver / petugas penanggung jawab pengiriman.');
            $this->redirect('/deliveries');
            return;
        }

        if (empty($tanggalSj) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSj)) {
            $this->flashError('Format tanggal pengiriman tidak valid.');
            $this->redirect('/deliveries');
            return;
        }

        try {
            $sj = Database::fetchOne("
                SELECT sj.*, p.nomor_nota, cust.nama_toko
                FROM public.surat_jalan sj
                JOIN public.pesanan p ON sj.pesanan_id = p.id
                JOIN public.pelanggan cust ON p.pelanggan_id = cust.id
                WHERE sj.id = :id
            ", ['id' => $id]);

            if (!$sj) {
                $this->flashError('Data Surat Jalan tidak ditemukan.');
                $this->redirect('/deliveries');
                return;
            }

            if (in_array($sj['status_surat_jalan'] ?? '', ['selesai_diterima', 'gagal_kirim', 'gagal_kembali', 'dibatalkan'], true)) {
                $this->flashError('Surat Jalan ini berstatus arsip/selesai dan tidak dapat diubah lagi.');
                $this->redirect('/deliveries');
                return;
            }

            $driver = Database::fetchOne("SELECT nama_karyawan FROM public.v_karyawan_info WHERE id = :id", ['id' => $driverId]);
            $namaDriver = $driver['nama_karyawan'] ?? 'Driver';

            // 1. Update Surat Jalan
            Database::execute("
                UPDATE public.surat_jalan
                SET sales_driver_id = :driver_id,
                    tanggal_surat_jalan = :tanggal,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $id,
                'driver_id' => $driverId,
                'tanggal' => $tanggalSj,
            ]);

            // 2. Sinkronkan sales_driver_id ke tabel pesanan
            if (!empty($sj['pesanan_id'])) {
                Database::execute("
                    UPDATE public.pesanan
                    SET sales_driver_id = :driver_id,
                        diubah_pada = NOW()
                    WHERE id = :pesanan_id
                ", [
                    'driver_id' => $driverId,
                    'pesanan_id' => $sj['pesanan_id']
                ]);
            }

            ActivityLog::log(
                'Logistik',
                'UPDATE',
                "Mengubah data Surat Jalan #{$sj['nomor_surat_jalan']} ({$sj['nama_toko']}): Supir diubah menjadi {$namaDriver}, Tanggal Kirim {$tanggalSj}",
                'surat_jalan',
                $id
            );

            $this->flashSuccess("Surat Jalan <strong>{$sj['nomor_surat_jalan']}</strong> berhasil diperbarui!");
            $this->redirect('/deliveries');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui surat jalan: ' . $e->getMessage());
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

        $validStatuses = ['siap_kirim', 'sedang_dikirim', 'selesai_diterima', 'gagal_kembali', 'gagal_kirim'];
        if (!in_array($status, $validStatuses, true)) {
            $this->flashError('Status pengiriman tidak valid.');
            $this->redirect('/deliveries');
            return;
        }

        // Scope check untuk driver: pastikan surat jalan ini miliknya jika tidak punya deliveries.update_all
        if (!Auth::can('deliveries.update_all') && !Auth::isAssignedDelivery((string)$id)) {
            $this->flashError('Akses Ditolak: Tugas pengiriman ini bukan dialokasikan ke Anda.');
            $this->redirect('/deliveries');
            return;
        }

        // Cek status pengiriman saat ini
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

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // 1. Kunci surat jalan dengan FOR UPDATE untuk mencegah race condition / double update
            $stmtLockSj = $pdo->prepare("SELECT id, status_surat_jalan, nomor_surat_jalan, pesanan_id FROM public.surat_jalan WHERE id = :id FOR UPDATE");
            $stmtLockSj->execute(['id' => $id]);
            $lockedSj = $stmtLockSj->fetch(\PDO::FETCH_ASSOC);

            if (!$lockedSj) {
                $pdo->rollBack();
                $this->flashError('Surat Jalan tidak ditemukan.');
                $this->redirect('/deliveries');
                return;
            }

            if ($lockedSj['status_surat_jalan'] === 'selesai_diterima') {
                $pdo->rollBack();
                $this->flashError("Surat Jalan #{$lockedSj['nomor_surat_jalan']} sudah selesai diterima oleh toko mitra dan tidak dapat diubah lagi.");
                $this->redirect('/deliveries');
                return;
            }

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

            // Ambil data pesanan terkait dengan FOR UPDATE
            $orderId = $lockedSj['pesanan_id'] ?? null;

            if ($orderId) {
                $stmtOrder = $pdo->prepare("
                    SELECT p.*, pel.nama_toko, pel.is_konsinyasi 
                    FROM public.pesanan p 
                    JOIN public.pelanggan pel ON p.pelanggan_id = pel.id 
                    WHERE p.id = :id FOR UPDATE
                ");
                $stmtOrder->execute(['id' => $orderId]);
                $orderData = $stmtOrder->fetch(\PDO::FETCH_ASSOC);

                if ($orderData) {
                    $isKonsinyasi = (bool)$orderData['is_konsinyasi'] || ($orderData['tipe_pembayaran'] === 'konsinyasi');
                    $alreadyDelivered = in_array($orderData['status_pemrosesan'] ?? '', ['selesai_dikirim', 'selesai', 'selesai_diterima'], true);

                    if ($status === 'sedang_dikirim') {
                        $pdo->prepare("UPDATE public.pesanan SET status_pemrosesan = 'sedang_dikirim', diubah_pada = NOW() WHERE id = :id")->execute(['id' => $orderId]);
                    } elseif ($status === 'gagal_kembali' || $status === 'gagal_kirim') {
                        // Kembalikan stok fisik ke gudang jika pesanan sebelumnya sudah dipotong stok
                        if (StockHelper::isPhysicalStockCut($orderData['status_pemrosesan'] ?? '')) {
                            StockHelper::revertOrderStockToWarehouse(
                                $pdo,
                                $orderId,
                                "Pengembalian Barang Gagal Kirim #{$orderData['nomor_nota']}",
                                Auth::id() ?: null
                            );
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
                        $pdo->prepare("UPDATE public.pesanan SET status_pemrosesan = 'selesai_dikirim', diubah_pada = NOW() WHERE id = :id")->execute(['id' => $orderId]);

                        if ($isKonsinyasi) {
                            // Konsinyasi: Pemotongan stok gudang, riwayat mutasi stok konsinyasi keluar,
                            // dan penambahan saldo rak toko diproses secara atomik oleh trigger DB: trg_proses_pengiriman_konsinyasi.
                        } else {
                            // 2. Reguler: Catat Pengakuan Piutang & Arus Kas Masuk HANYA JIKA belum selesai dikirim sebelumnya
                            if (!$alreadyDelivered) {
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

                                // Anti-Duplikasi: Cek apakah pembayaran pesanan ini sudah pernah dicatat di arus kas (misal saat input PO)
                                $stmtCheckKas = $pdo->prepare("SELECT COUNT(*) FROM public.arus_kas WHERE referensi_tabel = 'pesanan' AND referensi_id = :id AND jenis_kas = 'masuk'");
                                $stmtCheckKas->execute(['id' => $orderId]);
                                $alreadyInCash = (int)$stmtCheckKas->fetchColumn() > 0;

                                if (!$alreadyInCash && $totalDibayar > 0 && !empty($akunKasId)) {
                                    $stmtKas = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE");
                                    $stmtKas->execute(['id' => $akunKasId]);
                                    $akunKas = $stmtKas->fetch(\PDO::FETCH_ASSOC);
                                    if ($akunKas) {
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

                                        $voucherNo = CashVoucher::generate('masuk', date('Y-m-d'), $pdo);

                                        $pdo->prepare("
                                            INSERT INTO public.arus_kas (
                                                nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                                                keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                                            ) VALUES (
                                                :nomor_tx, :akun_kas, CURRENT_DATE, 'masuk', 'penjualan', :nominal,
                                                :ket, 'pesanan', :ref_id, :saldo_berjalan, :user_id, NOW()
                                            )
                                        ")->execute([
                                            'nomor_tx' => $voucherNo,
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
                        $pdo = Database::getConnection();
                        $pdo->beginTransaction();
                        try {
                            $nomorSj = DocumentNumber::nextDeliveryNumber($pdo);
                            $wilayahInfo = null;
                            if (!empty($pesanan['wilayah_id'])) {
                                $wilayahInfo = Database::fetchOne("SELECT nama_wilayah, kode_rute FROM public.wilayah WHERE id = :id", ['id' => $pesanan['wilayah_id']]);
                            }
                            $stmtInsert = $pdo->prepare("
                                INSERT INTO public.surat_jalan (
                                    nomor_surat_jalan, pesanan_id, sales_driver_id, rute_wilayah_id,
                                    nama_wilayah_snapshot, kode_rute_snapshot,
                                    status_surat_jalan, disetujui_oleh, dibuat_pada
                                ) VALUES (
                                    :no_sj, :pesanan_id, :driver_id, :wilayah_id,
                                    :wilayah_snap, :rute_snap,
                                    'sedang_dikirim', :user_id, NOW()
                                )
                            ");
                            $stmtInsert->execute([
                                'no_sj' => $nomorSj,
                                'pesanan_id' => $orderId,
                                'driver_id' => $pesanan['sales_driver_id'] ?: null,
                                'wilayah_id' => $pesanan['wilayah_id'] ?? null,
                                'wilayah_snap' => $wilayahInfo['nama_wilayah'] ?? null,
                                'rute_snap' => $wilayahInfo['kode_rute'] ?? null,
                                'user_id' => Auth::id() ?: null,
                            ]);
                            $pdo->commit();
                        } catch (Throwable $e) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }
                            throw $e;
                        }
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
                       COALESCE(sj.nama_wilayah_snapshot, w.nama_wilayah, '-') as nama_wilayah,
                       COALESCE(sj.kode_rute_snapshot, w.kode_rute, '-') as kode_rute
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
                SELECT ip.*, i.nama_item, i.kode_sku, i.satuan_dasar
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
            $isRestricted = !Auth::can('deliveries.view_all');
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
                       sj.tanggal_surat_jalan,
                       sj.waktu_berangkat, sj.waktu_sampai, sj.nama_penerima_toko, sj.bukti_terima_foto,
                       sj.foto_bukti_gagal, sj.alasan_gagal, sj.catatan_gagal,
                       sj.dibuat_pada as waktu_terbit_sj,
                       p.id as pesanan_id, p.nomor_nota, p.tanggal_pesanan, p.total_bruto, p.total_netto,
                       p.total_dibayar, p.sisa_tagihan, p.tipe_pembayaran, p.status_pembayaran, p.status_pemrosesan,
                       p.catatan as catatan_pesanan, p.waktu_gagal_kirim,
                       pel.id as pelanggan_id, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik,
                       pel.nomor_whatsapp, pel.alamat_lengkap, pel.link_google_maps, pel.is_konsinyasi,
                       COALESCE(sj.nama_wilayah_snapshot, w.nama_wilayah, '-') as nama_wilayah,
                       COALESCE(sj.kode_rute_snapshot, w.kode_rute, '-') as kode_rute,
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

            // Filter Tanggal Surat Jalan (Rencana Pengiriman yang diset di public/deliveries)
            if (!empty($selectedDate)) {
                if ($selectedDate === date('Y-m-d')) {
                    // Untuk hari ini: sertakan jadwal hari ini ATAU pengiriman aktif yang sedang berjalan (in-transit)
                    $sql .= " AND (COALESCE(sj.tanggal_surat_jalan, DATE(sj.dibuat_pada)) = :sel_date OR sj.status_surat_jalan IN ('sedang_dikirim', 'dalam_perjalanan'))";
                } else {
                    // Untuk tanggal spesifik lain (misal besok atau riwayat): tampilkan murni yang dijadwalkan pada tanggal tersebut
                    $sql .= " AND COALESCE(sj.tanggal_surat_jalan, DATE(sj.dibuat_pada)) = :sel_date";
                }
                $params['sel_date'] = $selectedDate;
            }

            // Scope Filter Driver: Prioritaskan driver yang ditugaskan di Surat Jalan
            if (!empty($driverId)) {
                $sql .= " AND COALESCE(sj.sales_driver_id, p.sales_driver_id) = :driver_id";
                $params['driver_id'] = $driverId;
            }

            $sql .= " ORDER BY 
                CASE 
                    WHEN sj.status_surat_jalan = 'sedang_dikirim' THEN 1
                    WHEN sj.status_surat_jalan = 'siap_kirim' THEN 2
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
                if ($st === 'siap_kirim') {
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
                        return ($st === 'siap_kirim');
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
                               ip.is_bonus, ip.catatan_bonus,
                               it.nama_item, it.kode_sku, it.satuan_dasar
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

            // Ambil Tugas Belanja PO Driver yang ditugaskan ke driver pada tanggal ini
            $sqlShopping = "
                SELECT pb.id, pb.nomor_faktur_pembelian, pb.tanggal_pembelian, pb.tanggal_jadwal_belanja, pb.total_biaya,
                       pb.status_pembayaran, pb.status_penerimaan, pb.catatan, pb.instruksi_driver,
                       pb.metode_bayar_belanja, pb.nominal_dibayar_driver, pb.nomor_nota_vendor,
                       pb.path_foto_nota, pb.path_foto_nota as url_foto_nota, 
                       pb.path_bukti_kendala, pb.path_bukti_kendala as foto_bukti_kendala, 
                       sup.id as pemasok_id, sup.nama_pemasok, sup.kode_pemasok,
                       sup.alamat_lengkap as alamat_pemasok, sup.link_google_maps, sup.nama_kontak as supplier_kontak, sup.nomor_whatsapp as supplier_wa,
                       sup.email as supplier_email, sup.termin_bayar as supplier_termin_bayar, sup.catatan as supplier_catatan,
                       sup.nama_bank, sup.nomor_rekening, sup.atas_nama_rekening,
                       drv.nama_karyawan as nama_driver, drv.nomor_polisi_kendaraan as nopol_driver,
                       (SELECT COUNT(*) FROM public.rincian_pembelian rp WHERE rp.pembelian_id = pb.id) as total_sku,
                       (SELECT COALESCE(SUM(kuantitas), 0) FROM public.rincian_pembelian rp WHERE rp.pembelian_id = pb.id) as total_pcs
                FROM public.pembelian pb
                JOIN public.pemasok sup ON pb.pemasok_id = sup.id
                LEFT JOIN public.v_karyawan_info drv ON pb.sales_driver_id = drv.id
                WHERE pb.jenis_dokumen = 'po'
                  AND pb.metode_logistik = 'diambil_driver'
                  AND pb.status_pembayaran != 'batal'
            ";
            $paramsShopping = [];
            if (!empty($selectedDate)) {
                $sqlShopping .= " AND (
                    (pb.tanggal_jadwal_belanja = :shop_date OR (pb.tanggal_jadwal_belanja IS NULL AND pb.tanggal_pembelian = :shop_date))
                    OR (pb.status_penerimaan = 'ditugaskan_driver' AND COALESCE(pb.tanggal_jadwal_belanja, pb.tanggal_pembelian) <= :shop_date)
                )";
                $paramsShopping['shop_date'] = $selectedDate;
            }
            if (!empty($driverId)) {
                $sqlShopping .= " AND pb.sales_driver_id = :shop_driver_id";
                $paramsShopping['shop_driver_id'] = $driverId;
            }
            $sqlShopping .= " ORDER BY 
                CASE 
                    WHEN pb.status_penerimaan = 'ditugaskan_driver' THEN 1
                    WHEN pb.status_penerimaan = 'sudah_diambil' THEN 2
                    WHEN pb.status_penerimaan = 'diterima' THEN 3
                    ELSE 4
                END, pb.dibuat_pada ASC";

            $shoppingTasks = Database::fetchAll($sqlShopping, $paramsShopping);

            if (!empty($shoppingTasks)) {
                $shopIds = array_column($shoppingTasks, 'id');
                $placeholders = implode(',', array_map(fn($k) => ':sp_id_' . $k, array_keys($shopIds)));
                $shopParams = [];
                foreach ($shopIds as $k => $sid) {
                    $shopParams['sp_id_' . $k] = (string)$sid;
                }
                $rawShopItems = Database::fetchAll("
                    SELECT rp.pembelian_id, rp.item_id, rp.kuantitas, rp.satuan, rp.harga_satuan, rp.subtotal,
                           it.nama_item, it.kode_sku
                    FROM public.rincian_pembelian rp
                    JOIN public.item it ON rp.item_id = it.id
                    WHERE rp.pembelian_id IN ($placeholders)
                    ORDER BY it.nama_item ASC
                ", $shopParams);

                $itemsByShop = [];
                foreach ($rawShopItems as $rsi) {
                    $itemsByShop[$rsi['pembelian_id']][] = $rsi;
                }
                foreach ($shoppingTasks as &$st) {
                    $st['items'] = $itemsByShop[$st['id']] ?? [];
                }
                unset($st);
            }

            // Injeksi Cloudflare R2 Presigned URLs (10 Menit) untuk Pengiriman & Tugas Belanja
            foreach ($deliveries as &$deliv) {
                $deliv['presigned_bukti_terima'] = \App\Helpers\Upload::presignedUrl($deliv['bukti_terima_foto'] ?? null, 10);
                $deliv['presigned_bukti_gagal'] = \App\Helpers\Upload::presignedUrl($deliv['foto_bukti_gagal'] ?? null, 10);
                if (!empty($deliv['presigned_bukti_terima'])) {
                    $deliv['bukti_terima_foto'] = $deliv['presigned_bukti_terima'];
                }
                if (!empty($deliv['presigned_bukti_gagal'])) {
                    $deliv['foto_bukti_gagal'] = $deliv['presigned_bukti_gagal'];
                }
            }
            unset($deliv);

            foreach ($shoppingTasks as &$st) {
                $st['presigned_foto_nota'] = \App\Helpers\Upload::presignedUrl($st['path_foto_nota'] ?? null, 10);
                $st['presigned_bukti_kendala'] = \App\Helpers\Upload::presignedUrl($st['path_bukti_kendala'] ?? null, 10);
                $st['url_foto_nota'] = $st['presigned_foto_nota'] ?: ($st['path_foto_nota'] ?? '');
                $st['foto_bukti_kendala'] = $st['presigned_bukti_kendala'] ?: ($st['path_bukti_kendala'] ?? '');
            }
            unset($st);

            $this->view('deliveries.driver_route', [
                'pageTitle' => 'Pengiriman Driver',
                'pageSubtitle' => 'Rute Distribusi & Konfirmasi Serah Terima Toko',
                'deliveries' => $deliveries,
                'shoppingTasks' => $shoppingTasks,
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
     * Helper redirect kembali ke portal driver dengan mempertahankan filter tanggal, driver, dan status
     */
    private function redirectDriverDeliveries(): void
    {
        $redirectUrl = '/driver-deliveries';
        $params = [];
        $fDate = $this->input('filter_date') ?: $this->input('date');
        $fDriver = $this->input('filter_driver_id') ?: $this->input('driver_id');
        $fStatus = $this->input('filter_status') ?: $this->input('status');

        if (!empty($fDate)) {
            $params['date'] = $fDate;
        }
        if (!empty($fDriver)) {
            $params['driver_id'] = $fDriver;
        }
        if (!empty($fStatus) && $fStatus !== 'semua') {
            $params['status'] = $fStatus;
        }

        if (!empty($params)) {
            $redirectUrl .= '?' . http_build_query($params);
        }
        $this->redirect($redirectUrl);
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
            $this->redirectDriverDeliveries();
            return;
        }

        try {
            $sj = Database::fetchOne("SELECT * FROM public.surat_jalan WHERE id = :id", ['id' => $sjId]);
            if (!$sj) {
                $this->flashError('Surat jalan tidak ditemukan.');
                $this->redirectDriverDeliveries();
                return;
            }

            // Scope check jika bukan admin/manajer
            if (!Auth::can('deliveries.update_all') && !Auth::isAssignedDelivery((string)$sjId)) {
                $this->flashError('Akses Ditolak: Surat jalan ini tidak ditugaskan ke Anda.');
                $this->redirectDriverDeliveries();
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
            $this->redirectDriverDeliveries();

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal memulai pengiriman: ' . $e->getMessage());
            $this->redirectDriverDeliveries();
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
            $this->redirectDriverDeliveries();
            return;
        }

        try {
            $sj = Database::fetchOne("
                SELECT sj.*, p.nomor_nota, p.total_netto, p.total_dibayar, p.sisa_tagihan, p.status_pemrosesan, p.tipe_pembayaran, p.pelanggan_id,
                       pel.nama_toko, pel.is_konsinyasi
                FROM public.surat_jalan sj
                JOIN public.pesanan p ON sj.pesanan_id = p.id
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                WHERE sj.id = :id
            ", ['id' => $sjId]);

            if (!$sj) {
                $this->flashError('Surat jalan tidak ditemukan.');
                $this->redirectDriverDeliveries();
                return;
            }

            if ($sj['status_surat_jalan'] === 'selesai_diterima') {
                $this->flashError('Surat jalan ini sudah berstatus selesai diterima sebelumnya.');
                $this->redirectDriverDeliveries();
                return;
            }

            if (!Auth::can('deliveries.update_all') && !Auth::isAssignedDelivery((string)$sjId)) {
                $this->flashError('Akses Ditolak: Surat jalan ini tidak ditugaskan ke Anda.');
                $this->redirectDriverDeliveries();
                return;
            }

            // Handle Upload Foto Bukti Serah Terima via Upload Helper (Anti Dobel Folder & Validasi Gambar)
            $fotoPath = null;
            if (isset($_FILES['bukti_foto']) && $_FILES['bukti_foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadRes = \App\Helpers\Upload::storeImage($_FILES['bukti_foto'], 'delivery_proofs', 'PROOF');
                if (!$uploadRes['success']) {
                    $this->flashError($uploadRes['error']);
                    $this->redirectDriverDeliveries();
                    return;
                }
                $fotoPath = $uploadRes['path'];
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Lock row surat jalan untuk mencegah race condition double complete
            $stmtCheckLock = $pdo->prepare("SELECT status_surat_jalan FROM public.surat_jalan WHERE id = :id FOR UPDATE");
            $stmtCheckLock->execute(['id' => $sjId]);
            $lockedSj = $stmtCheckLock->fetch(\PDO::FETCH_ASSOC);
            if (!$lockedSj || $lockedSj['status_surat_jalan'] === 'selesai_diterima') {
                throw new \Exception('Surat jalan ini sudah berstatus selesai diterima sebelumnya.');
            }

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
                $settlement = PaymentHelper::calculateSettlement((float)$sj['total_netto'], $newTotalDibayar);
                $sisa = $settlement['sisa_tagihan'];
                $newStatusBayar = $settlement['status_pembayaran'];

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
                    $voucherNo = CashVoucher::generate('masuk', date('Y-m-d'), $pdo);
                    $pdo->prepare("
                        INSERT INTO public.arus_kas (
                            nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                            keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                        ) VALUES (
                            :nomor_tx, :kas_id, CURRENT_DATE, 'masuk', 'penjualan', :nominal,
                            :ket, 'pesanan', :order_id, :saldo_berjalan, :user_id, NOW()
                        )
                    ")->execute([
                        'nomor_tx' => $voucherNo,
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

            // Catat Pengakuan Piutang Berjalan Toko Pelanggan untuk Pesanan Reguler (Kredit / Tempo / Sisa Tagihan > 0)
            if (!$isKonsinyasi) {
                $finalSisaTagihan = ($nominalTunai > 0 && isset($settlement['sisa_tagihan']))
                    ? (float)$settlement['sisa_tagihan']
                    : max(0, (float)$sj['total_netto'] - (float)$sj['total_dibayar']);

                if ($finalSisaTagihan > 0 && !empty($sj['pelanggan_id'])) {
                    $pdo->prepare("
                        UPDATE public.pelanggan
                        SET total_piutang_berjalan = COALESCE(total_piutang_berjalan, 0) + :sisa,
                            diubah_pada = NOW()
                        WHERE id = :pelanggan_id
                    ")->execute([
                        'sisa' => $finalSisaTagihan,
                        'pelanggan_id' => $sj['pelanggan_id']
                    ]);
                }
            }

            $pdo->commit();

            ActivityLog::log(
                'Delivery',
                'UPDATE',
                "Driver menyelesaikan serah terima pengiriman ke {$sj['nama_toko']} (Penerima: {$penerima})",
                'surat_jalan',
                (string)$sjId
            );

            $this->flashSuccess("Pengiriman ke {$sj['nama_toko']} berhasil diselesaikan (Penerima: {$penerima})!");
            $this->redirectDriverDeliveries();

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal konfirmasi selesai pengiriman: ' . $e->getMessage());
            $this->redirectDriverDeliveries();
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
            $this->redirectDriverDeliveries();
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
                $this->redirectDriverDeliveries();
                return;
            }

            if (!Auth::can('deliveries.update_all') && !Auth::isAssignedDelivery((string)$sjId)) {
                $this->flashError('Akses Ditolak: Surat jalan ini tidak ditugaskan ke Anda.');
                $this->redirectDriverDeliveries();
                return;
            }

            // Handle Upload Foto Bukti Gagal Kirim via Upload Helper (Anti Dobel Folder, Kompresi WebP & Acak Hash)
            $fotoGagalPath = null;
            $fileInput = $_FILES['foto_bukti_gagal'] ?? $_FILES['bukti_foto_gagal'] ?? null;
            if ($fileInput && ($fileInput['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploadRes = \App\Helpers\Upload::storeImage($fileInput, 'delivery_proofs', 'FAIL');
                if (!$uploadRes['success']) {
                    $this->flashError($uploadRes['error']);
                    $this->redirectDriverDeliveries();
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
            $isPhysicalStockCut = $orderBefore && StockHelper::isPhysicalStockCut($orderBefore['status_pemrosesan'] ?? '');

            if ($isPhysicalStockCut) {
                StockHelper::revertOrderStockToWarehouse(
                    $pdo,
                    $sj['pesanan_id'],
                    "Pengembalian Barang Gagal Kirim #{$sj['nomor_nota']} ({$alasan})",
                    Auth::id() ?: null
                );
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
            $this->redirectDriverDeliveries();

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal melaporkan pengiriman: ' . $e->getMessage());
            $this->redirectDriverDeliveries();
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
        $format = $this->input('format', 'standard');

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
                       COALESCE(sj.nama_wilayah_snapshot, w.nama_wilayah, '-') as nama_wilayah,
                       COALESCE(sj.kode_rute_snapshot, w.kode_rute, '-') as kode_rute
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
                SELECT ip.*, i.nama_item, i.kode_sku, i.satuan_dasar
                FROM public.item_pesanan ip
                JOIN public.item i ON ip.item_id = i.id
                WHERE ip.pesanan_id = :pesanan_id
                ORDER BY i.nama_item ASC
            ", ['pesanan_id' => $delivery['pesanan_id']]);

            ob_start();
            extract(['delivery' => $delivery, 'items' => $items, 'isPdf' => true, 'formatMode' => $format]);
            require ROOT_PATH . '/views/deliveries/print.php';
            $html = ob_get_clean();

            $cleanSj = preg_replace('/[^A-Za-z0-9]/', ' ', (string)$delivery['nomor_surat_jalan']);
            PrintDocumentHelper::downloadPdf($html, "Surat Jalan {$cleanSj}", $format);
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
                       COALESCE(sj.nama_wilayah_snapshot, w.nama_wilayah, '-') as nama_wilayah
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

            $cleanStart = str_replace('-', ' ', $startDate);
            $cleanEnd = str_replace('-', ' ', $endDate);
            ExcelExport::download("Daftar Surat Jalan {$cleanStart} sd {$cleanEnd}.xlsx", $headers, $rows, "Surat Jalan");
        } catch (Throwable $e) {
            $this->flashError('Gagal export data surat jalan: ' . $e->getMessage());
            $this->redirect('/deliveries');
        }
    }

    /**
     * Driver Menyelesaikan Tugas Belanja PO (Upload Foto Nota & Input Nominal Riil Tunai)
     */
    public function completeShoppingTask(): void
    {
        Auth::requirePermission(['deliveries.update_all', 'deliveries.update_assigned']);

        $purchaseId = $this->input('purchase_id');
        $nominalTunai = (float)str_replace(['.', ','], '', (string)$this->input('nominal_dibayar_driver', 0));
        $nomorNotaVendor = trim((string)$this->input('nomor_nota_vendor', ''));
        $catatanDriver = trim((string)$this->input('catatan_driver', ''));

        if (empty($purchaseId)) {
            $this->flashError('Parameter PO belanja tidak valid.');
            $this->redirect('/driver-deliveries');
            return;
        }

        try {
            $pb = Database::fetchOne("
                SELECT pb.*, sup.nama_pemasok 
                FROM public.pembelian pb
                JOIN public.pemasok sup ON pb.pemasok_id = sup.id
                WHERE pb.id = :id
            ", ['id' => $purchaseId]);

            if (!$pb) {
                $this->flashError('Data PO belanja tidak ditemukan.');
                $this->redirect('/driver-deliveries');
                return;
            }

            // Validasi hak akses driver
            $myEmpId = Auth::user()['karyawan_id'] ?? null;
            if (!Auth::can('deliveries.update_all') && !empty($pb['sales_driver_id']) && $pb['sales_driver_id'] !== $myEmpId) {
                $this->flashError('Akses Ditolak: Tugas belanja ini tidak ditugaskan ke Anda.');
                $this->redirect('/driver-deliveries');
                return;
            }

            $fotoPath = null;
            if (isset($_FILES['foto_nota']) && $_FILES['foto_nota']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadRes = \App\Helpers\Upload::storeImage($_FILES['foto_nota'], 'purchases', 'NOTA_DRIVER');
                if (!$uploadRes['success']) {
                    $this->flashError($uploadRes['error']);
                    $this->redirect('/driver-deliveries');
                    return;
                }
                $fotoPath = $uploadRes['path'];
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $sqlUpdate = "
                UPDATE public.pembelian 
                SET status_penerimaan = 'sudah_diambil',
                    waktu_diambil = NOW(),
                    nominal_dibayar_driver = :nominal,
                    alasan_kendala = NULL,
                    path_bukti_kendala = NULL,
                    catatan = CASE 
                        WHEN :catatan != '' THEN COALESCE(catatan, '') || ' | Catatan Driver: ' || :catatan
                        ELSE catatan 
                    END
            ";
            $params = [
                'id' => $purchaseId,
                'nominal' => $nominalTunai,
                'catatan' => $catatanDriver
            ];

            if (!empty($nomorNotaVendor)) {
                $sqlUpdate .= ", nomor_nota_vendor = :nomor_nota";
                $params['nomor_nota'] = $nomorNotaVendor;
            }

            if (!empty($fotoPath)) {
                $sqlUpdate .= ", path_foto_nota = :foto";
                $params['foto'] = $fotoPath;
            }

            $sqlUpdate .= " WHERE id = :id";
            $pdo->prepare($sqlUpdate)->execute($params);

            $pdo->commit();

            ActivityLog::log(
                'Delivery',
                'UPDATE',
                "Driver selesai berbelanja PO #{$pb['nomor_faktur_pembelian']} di {$pb['nama_pemasok']} (Nominal Rp " . number_format($nominalTunai, 0, ',', '.') . ")",
                'pembelian',
                (string)$purchaseId
            );

            $this->flashSuccess("Tugas Belanja di <strong>{$pb['nama_pemasok']}</strong> selesai dicatat! Barang dibawa menuju gudang.");
            $this->redirect('/driver-deliveries');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $this->flashError("Gagal mencatat tugas belanja: " . $e->getMessage());
            $this->redirect('/driver-deliveries');
        }
    }

    /**
     * Driver Melaporkan Kendala Belanja PO (Toko Tutup / Barang Kosong / Kendala Fisik)
     */
    public function reportShoppingIssue(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();

        $purchaseId = (int)($_POST['purchase_id'] ?? 0);
        $alasan = trim($_POST['alasan'] ?? '');

        if (!$purchaseId || empty($alasan)) {
            $this->flashError('ID PO dan alasan kendala wajib diisi!');
            $this->redirect('/driver-deliveries');
            return;
        }

        try {
            $pb = Database::fetchOne("
                SELECT pb.id, pb.nomor_faktur_pembelian, sup.nama_pemasok 
                FROM public.pembelian pb 
                JOIN public.pemasok sup ON pb.pemasok_id = sup.id 
                WHERE pb.id = :id
            ", ['id' => $purchaseId]);

            if (!$pb) {
                $this->flashError('Data PO Belanja tidak ditemukan!');
                $this->redirect('/driver-deliveries');
                return;
            }

            $fotoPath = null;
            if (isset($_FILES['foto_kendala']) && $_FILES['foto_kendala']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadRes = \App\Helpers\Upload::storeImage($_FILES['foto_kendala'], 'purchases', 'KENDALA_PO');
                if (!$uploadRes['success']) {
                    $this->flashError($uploadRes['error']);
                    $this->redirect('/driver-deliveries');
                    return;
                }
                $fotoPath = $uploadRes['path'];
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $sqlUpdate = "
                UPDATE public.pembelian 
                SET status_penerimaan = 'kendala_batal',
                    alasan_kendala = :alasan
            ";
            $params = [
                'id' => $purchaseId,
                'alasan' => $alasan
            ];

            if (!empty($fotoPath)) {
                $sqlUpdate .= ", path_bukti_kendala = :foto";
                $params['foto'] = $fotoPath;
            }

            $sqlUpdate .= " WHERE id = :id";
            $pdo->prepare($sqlUpdate)->execute($params);

            $pdo->commit();

            ActivityLog::log(
                'Delivery',
                'UPDATE',
                "Driver melaporkan kendala belanja PO #{$pb['nomor_faktur_pembelian']} ({$alasan})",
                'pembelian',
                (string)$purchaseId
            );

            $this->flashWarning("Kendala belanja di <strong>{$pb['nama_pemasok']}</strong> berhasil dilaporkan ke admin gudang.");
            $this->redirect('/driver-deliveries');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $this->flashError('Gagal melaporkan kendala: ' . $e->getMessage());
            $this->redirect('/driver-deliveries');
        }
    }
}
