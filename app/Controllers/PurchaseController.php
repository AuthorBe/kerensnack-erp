<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\DocumentNumber;
use App\Helpers\CashVoucher;
use App\Helpers\PrintDocumentHelper;
use Database;
use Throwable;

/**
 * app/Controllers/PurchaseController.php
 * Pengendali Faktur Pembelian Bahan Mentah & Kemasan dari Vendor Pemasok.
 */
class PurchaseController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission('purchases.view');
    }

    public function index(): void
    {
        try {
            $purchases = Database::fetchAll("
                SELECT pb.id, pb.nomor_faktur_pembelian, pb.tanggal_pembelian, pb.total_biaya,
                       pb.status_pembayaran, pb.status_penerimaan, pb.catatan, 
                       pb.path_foto_nota, pb.path_foto_nota as url_foto_nota,
                       pb.path_bukti_kendala, pb.path_bukti_kendala as foto_bukti_kendala, pb.dibuat_pada,
                       pb.jenis_dokumen, pb.metode_logistik, pb.sales_driver_id, pb.tanggal_jadwal_belanja,
                       pb.instruksi_driver, pb.metode_bayar_belanja, pb.nominal_dibayar_driver, pb.nomor_nota_vendor,
                       pb.waktu_diambil, pb.waktu_diterima_gudang,
                       sup.id as pemasok_id, sup.nama_pemasok, sup.kode_pemasok, sup.nomor_whatsapp as supplier_telepon,
                       sup.nomor_whatsapp as supplier_wa, sup.link_google_maps as supplier_maps, sup.nama_kontak as supplier_kontak, sup.termin_bayar as supplier_termin_bayar,
                       p.nama_lengkap as pembuat,
                       drv.nama_karyawan as nama_driver, drv.nomor_polisi_kendaraan as nopol_driver,
                       (SELECT COUNT(*) FROM public.rincian_pembelian WHERE pembelian_id = pb.id) as total_items
                FROM public.pembelian pb
                LEFT JOIN public.pemasok sup ON pb.pemasok_id = sup.id
                LEFT JOIN public.pengguna p ON pb.dibuat_oleh = p.id
                LEFT JOIN public.v_karyawan_info drv ON pb.sales_driver_id = drv.id
                ORDER BY pb.tanggal_pembelian DESC, pb.dibuat_pada DESC
            ");

            // Injeksi Cloudflare R2 Presigned URLs (10 Menit)
            foreach ($purchases as &$pb) {
                $pb['presigned_foto_nota'] = \App\Helpers\Upload::presignedUrl($pb['path_foto_nota'] ?? null, 10);
                $pb['presigned_bukti_kendala'] = \App\Helpers\Upload::presignedUrl($pb['path_bukti_kendala'] ?? null, 10);
                $pb['url_foto_nota'] = $pb['presigned_foto_nota'] ?: ($pb['path_foto_nota'] ?? '');
                $pb['foto_bukti_kendala'] = $pb['presigned_bukti_kendala'] ?: ($pb['path_bukti_kendala'] ?? '');
            }
            unset($pb);

            $suppliers = Database::fetchAll("
                SELECT id, kode_pemasok, nama_pemasok, nama_kontak, nomor_whatsapp as nomor_telepon, nomor_whatsapp, email, termin_bayar, link_google_maps, alamat_lengkap, catatan, nama_bank, nomor_rekening, atas_nama_rekening 
                FROM public.pemasok 
                WHERE status_aktif = TRUE 
                ORDER BY nama_pemasok ASC
            ");

            $items = Database::fetchAll("
                SELECT id, kode_sku, nama_item, satuan_dasar, tipe_item, harga_pokok_pembelian, stok_fisik_saat_ini, pemasok_utama_id 
                FROM public.item 
                WHERE status_aktif = TRUE 
                  AND (
                      tipe_item IN ('bahan_mentah', 'bahan_kemas')
                      OR (tipe_item = 'barang_jadi' AND pemasok_utama_id IS NOT NULL)
                  )
                ORDER BY 
                    CASE 
                        WHEN tipe_item = 'bahan_mentah' THEN 1 
                        WHEN tipe_item = 'bahan_kemas' THEN 2 
                        ELSE 3 
                    END, 
                    nama_item ASC
            ");

            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, saldo_saat_ini 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE 
                ORDER BY nama_akun ASC
            ");

            $drivers = Database::fetchAll("
                SELECT id, nama_karyawan, nomor_telepon, nomor_polisi_kendaraan, posisi 
                FROM public.v_karyawan_info 
                WHERE posisi IN ('driver', 'sales') AND status_aktif = TRUE
                ORDER BY (posisi = 'driver') DESC, nama_karyawan ASC
            ");

            // Generate Next Suggested PB Number (PB-YYYYMMDD-XXX) based on current records today
            $suggestedPb = DocumentNumber::suggestPurchaseNumber();
            $suggestedPbSuffix = substr($suggestedPb, 3);

            $this->view('purchases.index', [
                'pageTitle' => 'Pembelian & Faktur Vendor',
                'pageSubtitle' => 'Penerimaan Bahan Mentah, Bumbu & Kemasan dari Supplier',
                'purchases' => $purchases,
                'suppliers' => $suppliers,
                'items' => $items,
                'cashAccounts' => $cashAccounts,
                'drivers' => $drivers,
                'suggestedPbSuffix' => $suggestedPbSuffix
            ]);

        } catch (Throwable $e) {
            $this->flashError("Gagal memuat daftar pembelian: " . $e->getMessage());
            $this->redirect('/dashboard');
        }
    }

    public function detailAjax(): void
    {
        Auth::requirePermission('purchases.view');
        $id = $this->input('id');
        if (empty($id)) {
            $this->json(['success' => false, 'message' => 'ID faktur pembelian tidak ditemukan.'], 400);
            return;
        }

        try {
            $purchase = Database::fetchOne("
                SELECT pb.id, pb.nomor_faktur_pembelian, pb.tanggal_pembelian, pb.total_biaya,
                       pb.status_pembayaran, pb.status_penerimaan, 
                       pb.path_foto_nota, pb.path_foto_nota as url_foto_nota,
                       pb.path_bukti_kendala, pb.path_bukti_kendala as foto_bukti_kendala,
                       pb.catatan, pb.dibuat_pada,
                       pb.jenis_dokumen, pb.metode_logistik, pb.sales_driver_id, pb.tanggal_jadwal_belanja,
                       pb.instruksi_driver, pb.metode_bayar_belanja, pb.nominal_dibayar_driver, pb.nomor_nota_vendor,
                       pb.alasan_kendala, pb.waktu_diambil, pb.waktu_diterima_gudang,
                       sup.id as pemasok_id, sup.kode_pemasok, sup.nama_pemasok, sup.nomor_whatsapp as nomor_telepon, sup.nomor_whatsapp as supplier_telepon, sup.alamat_lengkap,
                       sup.nama_kontak as supplier_kontak, sup.nomor_whatsapp as supplier_wa, sup.email as supplier_email,
                       sup.link_google_maps as supplier_maps, sup.termin_bayar as supplier_termin_bayar, sup.catatan as supplier_catatan,
                       sup.nama_bank, sup.nomor_rekening, sup.atas_nama_rekening,
                       p.nama_lengkap as pembuat,
                       drv.nama_karyawan as nama_driver, drv.nomor_telepon as telp_driver, drv.nomor_polisi_kendaraan as nopol_driver,
                       ak_info.nama_akun as akun_kas_nama,
                       ak_info.tanggal_transaksi as tanggal_bayar_kas
                FROM public.pembelian pb
                LEFT JOIN public.pemasok sup ON pb.pemasok_id = sup.id
                LEFT JOIN public.pengguna p ON pb.dibuat_oleh = p.id
                LEFT JOIN public.v_karyawan_info drv ON pb.sales_driver_id = drv.id
                LEFT JOIN LATERAL (
                    SELECT ak.id as akun_kas_id, ak.nama_akun, ak_ref.tanggal_transaksi, ak_ref.nominal as nominal_sudah_dibayar_kas
                    FROM public.arus_kas ak_ref
                    JOIN public.akun_kas ak ON ak_ref.akun_kas_id = ak.id
                    WHERE ak_ref.referensi_tabel = 'pembelian' 
                      AND ak_ref.referensi_id = pb.id 
                      AND ak_ref.jenis_kas = 'keluar'
                    ORDER BY ak_ref.dibuat_pada DESC 
                    LIMIT 1
                ) ak_info ON TRUE
                WHERE pb.id = :id
            ", ['id' => $id]);

            if (!$purchase) {
                $this->json(['success' => false, 'message' => 'Data faktur pembelian tidak ditemukan.'], 404);
                return;
            }

            $items = Database::fetchAll("
                SELECT rp.id, rp.kuantitas, rp.satuan, rp.harga_satuan, rp.subtotal, rp.item_id,
                       it.nama_item, it.kode_sku, it.tipe_item, it.stok_fisik_saat_ini, it.satuan_dasar
                FROM public.rincian_pembelian rp
                JOIN public.item it ON rp.item_id = it.id
                WHERE rp.pembelian_id = :id
                ORDER BY rp.dibuat_pada ASC
            ", ['id' => $id]);

            // Riwayat Audit Trail
            $activityLogs = Database::fetchAll("
                SELECT id, nama_aktor, peran_aktor, jenis_aksi, deskripsi_aktivitas, waktu_kejadian
                FROM public.log_aktivitas
                WHERE (tabel_terdampak = 'pembelian' AND id_referensi = :id)
                   OR (deskripsi_aktivitas LIKE :faktur_match)
                ORDER BY waktu_kejadian DESC
                LIMIT 20
            ", [
                'id' => $id,
                'faktur_match' => '%' . ($purchase['nomor_faktur_pembelian'] ?? '---') . '%'
            ]);

            // Injeksi Cloudflare R2 Presigned URLs (10 Menit)
            $purchase['presigned_foto_nota'] = \App\Helpers\Upload::presignedUrl($purchase['path_foto_nota'] ?? null, 10);
            $purchase['presigned_bukti_kendala'] = \App\Helpers\Upload::presignedUrl($purchase['path_bukti_kendala'] ?? null, 10);
            $purchase['url_foto_nota'] = $purchase['presigned_foto_nota'] ?: ($purchase['path_foto_nota'] ?? '');
            $purchase['foto_bukti_kendala'] = $purchase['presigned_bukti_kendala'] ?: ($purchase['path_bukti_kendala'] ?? '');

            $this->json([
                'success' => true,
                'purchase' => $purchase,
                'items' => $items,
                'activityLogs' => $activityLogs
            ]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => 'Gagal memuat detail faktur: ' . $e->getMessage()], 500);
        }
    }

    public function store(): void
    {
        Auth::requirePermission('purchases.create');

        $payload = null;
        if (!empty($_POST)) {
            $payload = $_POST;
            if (isset($_POST['items']) && is_string($_POST['items'])) {
                $payload['items'] = json_decode($_POST['items'], true) ?: [];
            }
        } else {
            $rawBody = file_get_contents('php://input');
            $payload = json_decode($rawBody, true);
        }

        if (empty($payload) || empty($payload['pemasok_id']) || empty($payload['items']) || !is_array($payload['items'])) {
            $this->json(['success' => false, 'message' => 'Data faktur pembelian dan rincian item tidak lengkap.'], 400);
            return;
        }

        $pemasokId = $payload['pemasok_id'];
        $tanggal = $payload['tanggal_pembelian'] ?? date('Y-m-d');
        $rawItems = $payload['items'];

        // 1. Validasi Keberadaan & Keaktifan Vendor Pemasok
        $supplier = Database::fetchOne("SELECT id, nama_pemasok FROM public.pemasok WHERE id = :id AND status_aktif = TRUE", ['id' => $pemasokId]);
        if (!$supplier) {
            $this->json(['success' => false, 'message' => 'Vendor pemasok tidak valid atau sudah tidak aktif.'], 400);
            return;
        }

        // 2. Filter & Validasi Ketat Item Pembelian (Anti-Duplikasi, Anti-Negatif, Kalkulasi Server-Side)
        $seenItemIds = [];
        $validItems = [];
        $totalBiaya = 0.0;
        foreach ($rawItems as $it) {
            $itemId = $it['item_id'] ?? null;
            if (empty($itemId)) continue;

            if (isset($seenItemIds[$itemId])) {
                $itemData = Database::fetchOne("SELECT nama_item FROM public.item WHERE id = :id", ['id' => $itemId]);
                $itemName = $itemData['nama_item'] ?? 'Barang';
                $this->json([
                    'success' => false,
                    'message' => "Item '{$itemName}' dipilih lebih dari satu kali. Silakan gabungkan kuantitasnya menjadi satu baris."
                ], 400);
                return;
            }
            $seenItemIds[$itemId] = true;

            $qty = round((float)($it['qty'] ?? 0), 2);
            if ($qty <= 0.0001) {
                $this->json(['success' => false, 'message' => 'Kuantitas setiap item harus lebih dari 0.'], 400);
                return;
            }

            $harga = (float)($it['harga_satuan'] ?? 0);
            if ($harga < 0) {
                $this->json(['success' => false, 'message' => 'Harga satuan barang tidak boleh bernilai negatif.'], 400);
                return;
            }

            // Hitung subtotal mutlak di server untuk akurasi finansial
            $subtotal = round($qty * $harga, 2);
            $validItems[] = [
                'item_id' => $itemId,
                'qty' => $qty,
                'harga_satuan' => $harga,
                'subtotal' => $subtotal
            ];
            $totalBiaya += $subtotal;
        }

        if (empty($validItems) || $totalBiaya <= 0) {
            $this->json(['success' => false, 'message' => 'Faktur pembelian harus memiliki minimal 1 item dengan kuantitas > 0 dan total nominal > Rp 0.'], 400);
            return;
        }

        $jenisDokumen = $payload['jenis_dokumen'] ?? 'faktur';
        if (!in_array($jenisDokumen, ['faktur', 'po'], true)) {
            $jenisDokumen = 'faktur';
        }

        $metodeLogistik = $payload['metode_logistik'] ?? 'diantar_supplier';
        $driverId = (!empty($payload['sales_driver_id']) && $metodeLogistik === 'diambil_driver') ? $payload['sales_driver_id'] : null;
        $tglJadwal = !empty($payload['tanggal_jadwal_belanja']) ? $payload['tanggal_jadwal_belanja'] : $tanggal;
        $instruksi = trim((string)($payload['instruksi_driver'] ?? ''));
        $metodeBayarBelanja = $payload['metode_bayar_belanja'] ?? 'tempo_vendor';
        $nomorNotaVendor = trim((string)($payload['nomor_nota_vendor'] ?? ''));

        if ($jenisDokumen === 'po') {
            $statusPenerimaan = ($metodeLogistik === 'diambil_driver') ? 'ditugaskan_driver' : 'menunggu_supplier';
            $statusBayar = $payload['status_pembayaran'] ?? 'belum_lunas';
        } else {
            $statusPenerimaan = 'diterima';
            $statusBayar = $payload['status_pembayaran'] ?? 'lunas';
        }

        $akunKasId = !empty($payload['akun_kas_id']) ? $payload['akun_kas_id'] : null;

        // 3. Validasi Pembayaran Lunas & Kecukupan Saldo Kas (Khusus Faktur Langsung)
        if ($jenisDokumen === 'faktur' && $statusBayar === 'lunas') {
            if (empty($akunKasId)) {
                $this->json(['success' => false, 'message' => 'Akun kas sumber dana wajib dipilih untuk pembayaran lunas.'], 400);
                return;
            }
            $akunKas = Database::fetchOne("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE id = :id AND status_aktif = TRUE", ['id' => $akunKasId]);
            if (!$akunKas) {
                $this->json(['success' => false, 'message' => 'Akun kas sumber dana yang dipilih tidak valid.'], 400);
                return;
            }
            $saldoKas = (float)$akunKas['saldo_saat_ini'];
            if ($saldoKas < $totalBiaya) {
                $saldoFmt = number_format($saldoKas, 0, ',', '.');
                $biayaFmt = number_format($totalBiaya, 0, ',', '.');
                $this->json([
                    'success' => false,
                    'message' => "Saldo akun kas '{$akunKas['nama_akun']}' (Rp {$saldoFmt}) tidak mencukupi untuk pembayaran lunas sebesar Rp {$biayaFmt}. Silakan pilih akun kas lain atau catat sebagai faktur Tempo (Hutang)."
                ], 400);
                return;
            }
        }

        // 4. Handle Upload Foto Bukti Nota Fisik via Upload Helper
        $fotoPath = null;
        if (isset($_FILES['foto_nota']) && $_FILES['foto_nota']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadRes = \App\Helpers\Upload::storeImage($_FILES['foto_nota'], 'purchases', 'NOTA');
            if (!$uploadRes['success']) {
                $this->json(['success' => false, 'message' => $uploadRes['error']], 400);
                return;
            }
            $fotoPath = $uploadRes['path'];
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Penomoran Pembelian Konsisten Selalu Berawalan PB-YYYYMMDD-XXX
            $nomorFaktur = DocumentNumber::nextPurchaseNumber($pdo);

            $catatan = trim($payload['catatan'] ?? ($jenisDokumen === 'po' ? 'Rencana PO Pembelian vendor' : 'Penerimaan barang dari supplier'));
            $userId = Auth::id() ?: null;

            // 1. Insert Header Pembelian
            $stmtPb = $pdo->prepare("
                INSERT INTO public.pembelian (
                    nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya,
                    status_pembayaran, status_penerimaan, path_foto_nota, catatan, dibuat_oleh, dibuat_pada,
                    jenis_dokumen, metode_logistik, sales_driver_id, tanggal_jadwal_belanja,
                    instruksi_driver, metode_bayar_belanja, nomor_nota_vendor
                ) VALUES (
                    :no_faktur, :pemasok, :tgl, :total,
                    :bayar, :penerimaan, :foto, :catatan, :user_id, NOW(),
                    :jenis, :logistik, :driver_id, :tgl_jadwal,
                    :instruksi, :metode_bayar, :nota_vendor
                ) RETURNING id
            ");

            $stmtPb->execute([
                'no_faktur' => $nomorFaktur,
                'pemasok' => $pemasokId,
                'tgl' => $tanggal,
                'total' => $totalBiaya,
                'bayar' => $statusBayar,
                'penerimaan' => $statusPenerimaan,
                'foto' => $fotoPath,
                'catatan' => $catatan,
                'user_id' => $userId,
                'jenis' => $jenisDokumen,
                'logistik' => $metodeLogistik,
                'driver_id' => $driverId,
                'tgl_jadwal' => $tglJadwal,
                'instruksi' => $instruksi,
                'metode_bayar' => $metodeBayarBelanja,
                'nota_vendor' => $nomorNotaVendor
            ]);

            $pembelianId = $stmtPb->fetchColumn();

            // 2. Insert Items
            $stmtItem = $pdo->prepare("
                INSERT INTO public.rincian_pembelian (
                    pembelian_id, item_id, kuantitas, satuan, harga_satuan, subtotal
                ) VALUES (
                    :pb_id, :item_id, :qty, :satuan, :harga, :subtotal
                )
            ");

            $stmtUpdateStock = $pdo->prepare("
                UPDATE public.item 
                SET stok_fisik_saat_ini = stok_fisik_saat_ini + :qty,
                    harga_pokok_pembelian = :harga_baru,
                    diubah_pada = NOW() 
                WHERE id = :item_id
            ");

            $stmtRiwayat = $pdo->prepare("
                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                    referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :item_id, 'pembelian_masuk', :qty, :sebelum, :sesudah,
                    'pembelian', :pb_id, :ket, :user_id, NOW()
                )
            ");

            $stmtItemLock = $pdo->prepare("
                SELECT stok_fisik_saat_ini, satuan_dasar, harga_pokok_pembelian 
                FROM public.item 
                WHERE id = :id 
                FOR UPDATE
            ");

            foreach ($validItems as $it) {
                $itemId = $it['item_id'];
                $qtyItem = (float)$it['qty'];
                $harga = (float)$it['harga_satuan'];
                $subtotal = (float)$it['subtotal'];

                $stmtItemLock->execute(['id' => $itemId]);
                $current = $stmtItemLock->fetch();
                $satuan = $current['satuan_dasar'] ?? 'pcs';

                $stmtItem->execute([
                    'pb_id' => $pembelianId,
                    'item_id' => $itemId,
                    'qty' => $qtyItem,
                    'satuan' => $satuan,
                    'harga' => $harga,
                    'subtotal' => $subtotal
                ]);

                // Khusus Faktur Langsung: Langsung update stok fisik & hitung HPP
                if ($jenisDokumen === 'faktur') {
                    $stokSebelum = (float)($current['stok_fisik_saat_ini'] ?? 0);
                    $stokSesudah = $stokSebelum + $qtyItem;
                    $hppLama = (float)($current['harga_pokok_pembelian'] ?? 0);

                    if ($harga > 0) {
                        if ($stokSebelum > 0 && $hppLama > 0) {
                            $hppBaru = round((($stokSebelum * $hppLama) + ($qtyItem * $harga)) / $stokSesudah, 2);
                        } else {
                            $hppBaru = $harga;
                        }
                    } else {
                        $hppBaru = $hppLama;
                    }

                    $stmtUpdateStock->execute([
                        'qty' => $qtyItem,
                        'harga_baru' => $hppBaru,
                        'item_id' => $itemId
                    ]);

                    $stmtRiwayat->execute([
                        'item_id' => $itemId,
                        'qty' => $qtyItem,
                        'sebelum' => $stokSebelum,
                        'sesudah' => $stokSesudah,
                        'pb_id' => $pembelianId,
                        'ket' => "Penerimaan barang vendor faktur: {$nomorFaktur}",
                        'user_id' => $userId
                    ]);
                }
            }

            // 3. Catat Kas Keluar jika pembelian berstatus lunas & ada akun_kas_id (baik Faktur Langsung maupun PO Lunas Transfer)
            if ($statusBayar === 'lunas' && !empty($akunKasId)) {
                $stmtKasLock = $pdo->prepare("SELECT saldo_saat_ini, nama_akun FROM public.akun_kas WHERE id = :id FOR UPDATE");
                $stmtKasLock->execute(['id' => $akunKasId]);
                $akunKasRow = $stmtKasLock->fetch();

                $saldoLama = (float)($akunKasRow['saldo_saat_ini'] ?? 0);
                if ($saldoLama < $totalBiaya) {
                    $pdo->rollBack();
                    $saldoFmt = number_format($saldoLama, 0, ',', '.');
                    $biayaFmt = number_format($totalBiaya, 0, ',', '.');
                    $this->json([
                        'success' => false,
                        'message' => "Saldo kas '{$akunKasRow['nama_akun']}' (Rp {$saldoFmt}) tidak mencukupi untuk total faktur Rp {$biayaFmt}."
                    ], 400);
                    return;
                }
                $saldoBaru = $saldoLama - $totalBiaya;

                $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")
                    ->execute(['saldo' => $saldoBaru, 'id' => $akunKasId]);

                $voucherNo = CashVoucher::generate('keluar', $tanggal, $pdo);
                $keteranganKas = ($jenisDokumen === 'faktur')
                    ? "Pembayaran faktur pembelian vendor: {$nomorFaktur}"
                    : "Pembayaran PO pembelian vendor (Transfer): {$nomorFaktur}";

                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
                        referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :nomor_tx, :akun_id, :tgl, 'keluar', 'pembelian_bahan', :nominal, :ket,
                        'pembelian', :pb_id, :saldo_berjalan, :user_id, NOW()
                    )
                ")->execute([
                    'nomor_tx' => $voucherNo,
                    'akun_id' => $akunKasId,
                    'tgl' => $tanggal,
                    'nominal' => $totalBiaya,
                    'ket' => $keteranganKas,
                    'pb_id' => $pembelianId,
                    'saldo_berjalan' => $saldoBaru,
                    'user_id' => $userId
                ]);
            }

            // 4. Catat Audit Trail
            $descLog = ($jenisDokumen === 'po')
                ? "Menerbitkan PO Pembelian #{$nomorFaktur} ke vendor {$supplier['nama_pemasok']} (Metode: " . ($metodeLogistik === 'diambil_driver' ? 'Diambil Driver' : 'Diantar Supplier') . ")"
                : "Mencatat Faktur Pembelian Langsung #{$nomorFaktur} vendor {$supplier['nama_pemasok']} total Rp " . number_format($totalBiaya, 0, ',', '.') . " (" . ($statusBayar === 'lunas' ? 'LUNAS' : 'TEMPO/HUTANG') . ")";

            \App\Helpers\ActivityLog::log(
                'gudang_stok',
                'INSERT',
                $descLog,
                'pembelian',
                (string)$pembelianId,
                null,
                [
                    'nomor_faktur' => $nomorFaktur,
                    'jenis_dokumen' => $jenisDokumen,
                    'total' => $totalBiaya,
                    'status_pembayaran' => $statusBayar,
                    'status_penerimaan' => $statusPenerimaan,
                    'metode_logistik' => $metodeLogistik,
                    'items_count' => count($validItems)
                ]
            );

            $pdo->commit();

            $msg = ($jenisDokumen === 'po')
                ? "PO Pembelian {$nomorFaktur} berhasil diterbitkan dan dijadwalkan!"
                : "Faktur pembelian langsung {$nomorFaktur} berhasil disimpan dan stok gudang otomatis bertambah!";

            $this->json([
                'success' => true,
                'message' => $msg,
                'data' => ['pembelian_id' => $pembelianId, 'nomor_faktur' => $nomorFaktur]
            ]);

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $this->json(['success' => false, 'message' => 'Gagal simpan pembelian: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Memperbarui Data PO Pembelian (Metode Logistik, Driver, Jadwal, Item)
     */
    public function updatePo(): void
    {
        Auth::requirePermission(['purchases.edit', 'purchases.create']);

        $payload = null;
        if (!empty($_POST)) {
            $payload = $_POST;
            if (isset($_POST['items']) && is_string($_POST['items'])) {
                $payload['items'] = json_decode($_POST['items'], true) ?: [];
            }
        } else {
            $rawBody = file_get_contents('php://input');
            $payload = json_decode($rawBody, true);
        }

        $id = $payload['id'] ?? null;
        if (empty($id)) {
            $this->json(['success' => false, 'message' => 'ID PO pembelian tidak ditemukan.'], 400);
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $stmtLock = $pdo->prepare("SELECT * FROM public.pembelian WHERE id = :id FOR UPDATE");
            $stmtLock->execute(['id' => $id]);
            $purchase = $stmtLock->fetch(\PDO::FETCH_ASSOC);

            if (!$purchase) {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'Data pembelian/PO tidak ditemukan.'], 404);
                return;
            }

            if ($purchase['status_penerimaan'] === 'diterima') {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'PO yang sudah berstatus Diterima di gudang tidak dapat diedit.'], 400);
                return;
            }

            if ($purchase['status_pembayaran'] === 'batal') {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'PO yang telah dibatalkan tidak dapat diedit.'], 400);
                return;
            }

            $pemasokId = $payload['pemasok_id'] ?? $purchase['pemasok_id'];
            $metodeLogistik = $payload['metode_logistik'] ?? $purchase['metode_logistik'] ?? 'diantar_supplier';
            $driverId = (!empty($payload['sales_driver_id']) && $metodeLogistik === 'diambil_driver') ? $payload['sales_driver_id'] : null;
            $tglJadwal = !empty($payload['tanggal_jadwal_belanja']) ? $payload['tanggal_jadwal_belanja'] : $purchase['tanggal_jadwal_belanja'];
            $instruksi = trim((string)($payload['instruksi_driver'] ?? $purchase['instruksi_driver'] ?? ''));
            $metodeBayar = $payload['metode_bayar_belanja'] ?? $purchase['metode_bayar_belanja'] ?? 'tempo_vendor';
            $statusBayar = $payload['status_pembayaran'] ?? $purchase['status_pembayaran'] ?? 'belum_lunas';
            $catatan = trim((string)($payload['catatan'] ?? $purchase['catatan'] ?? ''));

            // Status penerimaan menyesuaikan metode logistik jika belum diambil
            $statusPenerimaan = ($metodeLogistik === 'diambil_driver') ? 'ditugaskan_driver' : 'menunggu_supplier';
            if ($purchase['status_penerimaan'] === 'sudah_diambil') {
                $statusPenerimaan = 'sudah_diambil';
            }

            // Validasi Items jika ada perubahan
            $rawItems = $payload['items'] ?? [];
            $validItems = [];
            $totalBiaya = 0.0;
            if (!empty($rawItems) && is_array($rawItems)) {
                $seen = [];
                foreach ($rawItems as $it) {
                    $itemId = $it['item_id'] ?? null;
                    if (empty($itemId) || isset($seen[$itemId])) continue;
                    $seen[$itemId] = true;
                    $qty = round((float)($it['qty'] ?? 0), 2);
                    $harga = (float)($it['harga_satuan'] ?? 0);
                    if ($qty <= 0.0001) continue;
                    $sub = round($qty * $harga, 2);
                    $validItems[] = [
                        'item_id' => $itemId,
                        'qty' => $qty,
                        'harga_satuan' => $harga,
                        'subtotal' => $sub
                    ];
                    $totalBiaya += $sub;
                }
            }

            $sqlUpdate = "
                UPDATE public.pembelian
                SET pemasok_id = :pemasok_id,
                    metode_logistik = :metode_logistik,
                    sales_driver_id = :sales_driver_id,
                    tanggal_jadwal_belanja = :tanggal_jadwal_belanja,
                    instruksi_driver = :instruksi_driver,
                    metode_bayar_belanja = :metode_bayar_belanja,
                    status_pembayaran = :status_pembayaran,
                    catatan = :catatan,
                    status_penerimaan = :status_penerimaan
            ";
            $params = [
                'id' => $id,
                'pemasok_id' => $pemasokId,
                'metode_logistik' => $metodeLogistik,
                'sales_driver_id' => $driverId,
                'tanggal_jadwal_belanja' => $tglJadwal,
                'instruksi_driver' => $instruksi,
                'metode_bayar_belanja' => $metodeBayar,
                'status_pembayaran' => $statusBayar,
                'catatan' => $catatan,
                'status_penerimaan' => $statusPenerimaan
            ];

            if (!empty($validItems)) {
                $sqlUpdate .= ", total_biaya = :total_biaya";
                $params['total_biaya'] = $totalBiaya;

                $pdo->prepare("DELETE FROM public.rincian_pembelian WHERE pembelian_id = :id")->execute(['id' => $id]);
                $stmtIns = $pdo->prepare("
                    INSERT INTO public.rincian_pembelian (pembelian_id, item_id, kuantitas, satuan, harga_satuan, subtotal)
                    VALUES (:pb_id, :item_id, :qty, :satuan, :harga, :subtotal)
                ");
                $stmtGetSatuan = $pdo->prepare("SELECT satuan_dasar FROM public.item WHERE id = :id");
                foreach ($validItems as $vi) {
                    $stmtGetSatuan->execute(['id' => $vi['item_id']]);
                    $satuan = $stmtGetSatuan->fetchColumn() ?: 'pcs';
                    $stmtIns->execute([
                        'pb_id' => $id,
                        'item_id' => $vi['item_id'],
                        'qty' => $vi['qty'],
                        'satuan' => $satuan,
                        'harga' => $vi['harga_satuan'],
                        'subtotal' => $vi['subtotal']
                    ]);
                }
            }

            $sqlUpdate .= " WHERE id = :id";
            $pdo->prepare($sqlUpdate)->execute($params);

            $pdo->commit();

            \App\Helpers\ActivityLog::log(
                'Pembelian',
                'UPDATE',
                "Memperbarui data PO Pembelian #{$purchase['nomor_faktur_pembelian']} (Metode: " . ($metodeLogistik === 'diambil_driver' ? 'Diambil Driver' : 'Diantar Supplier') . ")",
                'pembelian',
                (string)$id
            );

            $this->json(['success' => true, 'message' => 'PO Pembelian berhasil diperbarui!']);

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $this->json(['success' => false, 'message' => 'Gagal memperbarui PO: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Konfirmasi Penerimaan Barang Fisik di Gudang (Verifikasi Qty/Harga, Tambah Stok & Kas)
     */
    public function receiveGoods(): void
    {
        Auth::requirePermission('purchases.receive');

        $payload = null;
        if (!empty($_POST)) {
            $payload = $_POST;
            if (isset($_POST['items']) && is_string($_POST['items'])) {
                $payload['items'] = json_decode($_POST['items'], true) ?: [];
            }
        } else {
            $rawBody = file_get_contents('php://input');
            $payload = json_decode($rawBody, true);
        }

        $id = $payload['id'] ?? null;
        if (empty($id)) {
            $this->json(['success' => false, 'message' => 'ID dokumen pembelian tidak valid.'], 400);
            return;
        }

        try {
            // Upload Foto Nota Fisik terlebih dahulu jika diunggah baru
            $fotoPath = null;
            if (isset($_FILES['foto_nota']) && $_FILES['foto_nota']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadRes = \App\Helpers\Upload::storeImage($_FILES['foto_nota'], 'purchases', 'NOTA');
                if (!$uploadRes['success']) {
                    $this->json(['success' => false, 'message' => $uploadRes['error']], 400);
                    return;
                }
                $fotoPath = $uploadRes['path'];
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // 1. Kunci dan Validasi Dokumen Pembelian via FOR UPDATE (Anti Race Condition & Double Stock Receipt)
            $stmtLock = $pdo->prepare("SELECT * FROM public.pembelian WHERE id = :id FOR UPDATE");
            $stmtLock->execute(['id' => $id]);
            $purchase = $stmtLock->fetch(\PDO::FETCH_ASSOC);

            if (!$purchase) {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'Dokumen pembelian tidak ditemukan.'], 404);
                return;
            }

            if ($purchase['status_penerimaan'] === 'diterima') {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'Dokumen ini sudah berstatus Diterima sebelumnya.'], 400);
                return;
            }

            if ($purchase['status_pembayaran'] === 'batal') {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'Dokumen yang telah dibatalkan tidak dapat dikonfirmasi penerimaannya.'], 400);
                return;
            }

            if ($purchase['status_penerimaan'] === 'kendala_batal') {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'PO ini berstatus Kendala Belanja. Silakan lakukan penjadwalan ulang atau ganti driver terlebih dahulu sebelum melakukan verifikasi fisik.'], 400);
                return;
            }

            if ($fotoPath === null) {
                $fotoPath = $purchase['path_foto_nota'] ?? $purchase['url_foto_nota'] ?? null;
            }

            $rawItems = $payload['items'] ?? [];
            if (empty($rawItems) || !is_array($rawItems)) {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'Rincian item penerimaan fisik tidak valid.'], 400);
                return;
            }

            $validItems = [];
            $totalBiaya = 0.0;
            foreach ($rawItems as $it) {
                $itemId = $it['item_id'] ?? null;
                $qty = round((float)($it['qty'] ?? 0), 2);
                $harga = (float)($it['harga_satuan'] ?? 0);
                if (empty($itemId) || $qty <= 0) continue;
                $sub = round($qty * $harga, 2);
                $validItems[] = [
                    'item_id' => $itemId,
                    'qty' => $qty,
                    'harga_satuan' => $harga,
                    'subtotal' => $sub
                ];
                $totalBiaya += $sub;
            }

            if (empty($validItems)) {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'Penerimaan harus memiliki minimal 1 item dengan kuantitas > 0.'], 400);
                return;
            }

            // Status pembayaran dikunci agar selaras dan sesuai dengan PO / kesepakatan awal:
            if (($purchase['jenis_dokumen'] ?? '') === 'po') {
                if ($purchase['status_pembayaran'] === 'lunas' || ($purchase['metode_logistik'] === 'diambil_driver' && $purchase['metode_bayar_belanja'] === 'tunai_driver')) {
                    $statusBayar = 'lunas';
                } else {
                    $statusBayar = 'belum_lunas';
                }
            } else {
                $statusBayar = $payload['status_pembayaran'] ?? $purchase['status_pembayaran'] ?? 'lunas';
            }
            $akunKasId = !empty($payload['akun_kas_id']) ? $payload['akun_kas_id'] : null;
            $nomorNotaVendor = trim((string)($payload['nomor_nota_vendor'] ?? ''));
            if ($nomorNotaVendor === '') {
                $nomorNotaVendor = $purchase['nomor_faktur_pembelian'];
            }

            // Periksa riwayat pembayaran kas sebelumnya untuk dokumen ini (Kas Keluar & Kas Masuk/Refund)
            $stmtSumKas = $pdo->prepare("
                SELECT akun_kas_id,
                       COALESCE(SUM(CASE WHEN jenis_kas = 'keluar' THEN nominal ELSE -nominal END), 0) as netto_kas_keluar
                FROM public.arus_kas 
                WHERE referensi_tabel = 'pembelian' AND referensi_id = :id
                GROUP BY akun_kas_id
                ORDER BY netto_kas_keluar DESC
                LIMIT 1
            ");
            $stmtSumKas->execute(['id' => $id]);
            $kasPrev = $stmtSumKas->fetch(\PDO::FETCH_ASSOC);
            $kasKeluarSebelumnya = (float)($kasPrev['netto_kas_keluar'] ?? 0);
            $akunKasPrevId = $kasPrev['akun_kas_id'] ?? null;
            $targetKasId = $akunKasId ?: $akunKasPrevId;

            // Validasi Kas jika membutuhkan pengeluaran kas baru
            if ($statusBayar === 'lunas') {
                $kasYangPerluDipotong = 0.0;
                if ($kasKeluarSebelumnya > 0) {
                    if ($totalBiaya > $kasKeluarSebelumnya) {
                        $kasYangPerluDipotong = round($totalBiaya - $kasKeluarSebelumnya, 2);
                    }
                } else {
                    $kasYangPerluDipotong = $totalBiaya;
                }

                if ($kasYangPerluDipotong > 0) {
                    if (empty($targetKasId)) {
                        $pdo->rollBack();
                        $this->json(['success' => false, 'message' => 'Pilih akun kas sumber dana untuk pembayaran tunai/lunas.'], 400);
                        return;
                    }
                    $stmtKasLockCheck = $pdo->prepare("SELECT saldo_saat_ini, nama_akun FROM public.akun_kas WHERE id = :id AND status_aktif = TRUE FOR UPDATE");
                    $stmtKasLockCheck->execute(['id' => $targetKasId]);
                    $akunKas = $stmtKasLockCheck->fetch(\PDO::FETCH_ASSOC);
                    if (!$akunKas || (float)$akunKas['saldo_saat_ini'] < $kasYangPerluDipotong) {
                        $pdo->rollBack();
                        $saldoFmt = number_format((float)($akunKas['saldo_saat_ini'] ?? 0), 0, ',', '.');
                        $tagihanFmt = number_format($kasYangPerluDipotong, 0, ',', '.');
                        $this->json(['success' => false, 'message' => "Saldo akun kas '{$akunKas['nama_akun']}' (Rp {$saldoFmt}) tidak mencukupi untuk pembayaran sebesar Rp {$tagihanFmt}."], 400);
                        return;
                    }
                }
            }

            $userId = Auth::id() ?: null;
            $nomorFaktur = $purchase['nomor_faktur_pembelian'];

            // 1. Re-sync items in rincian_pembelian
            $pdo->prepare("DELETE FROM public.rincian_pembelian WHERE pembelian_id = :id")->execute(['id' => $id]);
            $stmtItemIns = $pdo->prepare("
                INSERT INTO public.rincian_pembelian (pembelian_id, item_id, kuantitas, satuan, harga_satuan, subtotal)
                VALUES (:pb_id, :item_id, :qty, :satuan, :harga, :subtotal)
            ");

            $stmtUpdateStock = $pdo->prepare("
                UPDATE public.item 
                SET stok_fisik_saat_ini = stok_fisik_saat_ini + :qty,
                    harga_pokok_pembelian = :harga_baru,
                    diubah_pada = NOW() 
                WHERE id = :item_id
            ");

            $stmtRiwayat = $pdo->prepare("
                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                    referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :item_id, 'pembelian_masuk', :qty, :sebelum, :sesudah,
                    'pembelian', :pb_id, :ket, :user_id, NOW()
                )
            ");

            $stmtItemLock = $pdo->prepare("SELECT stok_fisik_saat_ini, satuan_dasar, harga_pokok_pembelian FROM public.item WHERE id = :id FOR UPDATE");

            foreach ($validItems as $vit) {
                $itemId = $vit['item_id'];
                $qtyVal = $vit['qty'];
                $harga = $vit['harga_satuan'];
                $subtotal = $vit['subtotal'];

                $stmtItemLock->execute(['id' => $itemId]);
                $curr = $stmtItemLock->fetch(\PDO::FETCH_ASSOC);
                $stokSebelum = (float)($curr['stok_fisik_saat_ini'] ?? 0);
                $stokSesudah = round($stokSebelum + $qtyVal, 2);
                $satuan = $curr['satuan_dasar'] ?? 'pcs';
                $hppLama = (float)($curr['harga_pokok_pembelian'] ?? 0);

                if ($harga > 0) {
                    if ($stokSebelum > 0 && $hppLama > 0) {
                        $hppBaru = round((($stokSebelum * $hppLama) + ($qtyVal * $harga)) / $stokSesudah, 2);
                    } else {
                        $hppBaru = $harga;
                    }
                } else {
                    $hppBaru = $hppLama;
                }

                $stmtItemIns->execute([
                    'pb_id' => $id,
                    'item_id' => $itemId,
                    'qty' => $qtyVal,
                    'satuan' => $satuan,
                    'harga' => $harga,
                    'subtotal' => $subtotal
                ]);

                $stmtUpdateStock->execute([
                    'qty' => $qtyVal,
                    'harga_baru' => $hppBaru,
                    'item_id' => $itemId
                ]);

                $stmtRiwayat->execute([
                    'item_id' => $itemId,
                    'qty' => $qtyVal,
                    'sebelum' => $stokSebelum,
                    'sesudah' => $stokSesudah,
                    'pb_id' => $id,
                    'ket' => "Penerimaan fisik barang PO/Faktur: {$nomorFaktur}",
                    'user_id' => $userId
                ]);
            }

            // 2. Sinkronisasi Finansial & Penyesuaian Kas Otomatis
            if ($kasKeluarSebelumnya > 0) {
                // Dokumen sebelumnya sudah dibayar sebagian/lunas (misal PO Lunas Transfer / Kas)
                if ($totalBiaya < $kasKeluarSebelumnya) {
                    // Kasus A: Fisik lebih sedikit -> Ada kelebihan bayar / Refund Kas kembali dari Vendor
                    $refundNominal = round($kasKeluarSebelumnya - $totalBiaya, 2);
                    if (!empty($targetKasId) && $refundNominal > 0) {
                        $stmtKasLock = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE");
                        $stmtKasLock->execute(['id' => $targetKasId]);
                        $kasRow = $stmtKasLock->fetch();
                        $saldoBaru = (float)($kasRow['saldo_saat_ini'] ?? 0) + $refundNominal;

                        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")
                            ->execute(['saldo' => $saldoBaru, 'id' => $targetKasId]);

                        $voucherNo = CashVoucher::generate('masuk', date('Y-m-d'), $pdo);
                        $pdo->prepare("
                            INSERT INTO public.arus_kas (
                                nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
                                referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                            ) VALUES (
                                :nomor_tx, :akun_id, CURRENT_DATE, 'masuk', 'pembelian_bahan', :nominal, :ket,
                                'pembelian', :pb_id, :saldo_berjalan, :user_id, NOW()
                            )
                        ")->execute([
                            'nomor_tx' => $voucherNo,
                            'akun_id' => $targetKasId,
                            'nominal' => $refundNominal,
                            'ket' => "Pengembalian selisih belanja/kelebihan bayar penerimaan PO: {$nomorFaktur} (Fisik Rp " . number_format($totalBiaya, 0, ',', '.') . " vs Bayar Rp " . number_format($kasKeluarSebelumnya, 0, ',', '.') . ")",
                            'pb_id' => $id,
                            'saldo_berjalan' => $saldoBaru,
                            'user_id' => $userId
                        ]);
                    }
                    $statusBayar = 'lunas';
                } elseif ($totalBiaya > $kasKeluarSebelumnya) {
                    // Kasus B: Fisik lebih banyak -> Ada kekurangan bayar
                    $kurangNominal = round($totalBiaya - $kasKeluarSebelumnya, 2);
                    if ($statusBayar === 'lunas' && !empty($targetKasId) && $kurangNominal > 0) {
                        $stmtKasLock = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE");
                        $stmtKasLock->execute(['id' => $targetKasId]);
                        $kasRow = $stmtKasLock->fetch();
                        $saldoBaru = (float)($kasRow['saldo_saat_ini'] ?? 0) - $kurangNominal;

                        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")
                            ->execute(['saldo' => $saldoBaru, 'id' => $targetKasId]);

                        $voucherNo = CashVoucher::generate('keluar', date('Y-m-d'), $pdo);
                        $pdo->prepare("
                            INSERT INTO public.arus_kas (
                                nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
                                referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                            ) VALUES (
                                :nomor_tx, :akun_id, CURRENT_DATE, 'keluar', 'pembelian_bahan', :nominal, :ket,
                                'pembelian', :pb_id, :saldo_berjalan, :user_id, NOW()
                            )
                        ")->execute([
                            'nomor_tx' => $voucherNo,
                            'akun_id' => $targetKasId,
                            'nominal' => $kurangNominal,
                            'ket' => "Pelunasan selisih kekurangan penerimaan fisik PO: {$nomorFaktur}",
                            'pb_id' => $id,
                            'saldo_berjalan' => $saldoBaru,
                            'user_id' => $userId
                        ]);
                        $statusBayar = 'lunas';
                    } else {
                        // Sisa kekurangan masuk hutang dagang
                        $statusBayar = 'belum_lunas';
                    }
                } else {
                    // Fisik persis sama dengan yang sudah dibayar
                    $statusBayar = 'lunas';
                }
            } else {
                // Belum pernah ada pembayaran kas keluar sebelumnya (misal PO Tempo)
                if ($statusBayar === 'lunas' && !empty($targetKasId)) {
                    $stmtKasLock = $pdo->prepare("SELECT saldo_saat_ini, nama_akun FROM public.akun_kas WHERE id = :id FOR UPDATE");
                    $stmtKasLock->execute(['id' => $targetKasId]);
                    $kasRow = $stmtKasLock->fetch();
                    $saldoLama = (float)($kasRow['saldo_saat_ini'] ?? 0);
                    $saldoBaru = $saldoLama - $totalBiaya;

                    $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")
                        ->execute(['saldo' => $saldoBaru, 'id' => $targetKasId]);

                    $driverKet = '';
                    if (!empty($purchase['sales_driver_id'])) {
                        $drvInfo = Database::fetchOne("SELECT nama_karyawan FROM public.v_karyawan_info WHERE id = :id", ['id' => $purchase['sales_driver_id']]);
                        if ($drvInfo) {
                            $driverKet = " (Belanja Driver: {$drvInfo['nama_karyawan']})";
                        }
                    }

                    $voucherNo = CashVoucher::generate('keluar', date('Y-m-d'), $pdo);

                    $pdo->prepare("
                        INSERT INTO public.arus_kas (
                            nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
                            referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                        ) VALUES (
                            :nomor_tx, :akun_id, CURRENT_DATE, 'keluar', 'pembelian_bahan', :nominal, :ket,
                            'pembelian', :pb_id, :saldo_berjalan, :user_id, NOW()
                        )
                    ")->execute([
                        'nomor_tx' => $voucherNo,
                        'akun_id' => $targetKasId,
                        'nominal' => $totalBiaya,
                        'ket' => "Pembayaran faktur vendor penerimaan fisik: {$nomorFaktur}{$driverKet}",
                        'pb_id' => $id,
                        'saldo_berjalan' => $saldoBaru,
                        'user_id' => $userId
                    ]);
                }
            }

            // 3. Update header pembelian
            $pdo->prepare("
                UPDATE public.pembelian
                SET status_penerimaan = 'diterima',
                    waktu_diterima_gudang = NOW(),
                    total_biaya = :total,
                    status_pembayaran = :status_bayar,
                    nomor_nota_vendor = :nota_vendor,
                    path_foto_nota = :foto
                WHERE id = :id
            ")->execute([
                'id' => $id,
                'total' => $totalBiaya,
                'status_bayar' => $statusBayar,
                'nota_vendor' => $nomorNotaVendor,
                'foto' => $fotoPath
            ]);

            $pdo->commit();

            \App\Helpers\ActivityLog::log(
                'Pembelian',
                'UPDATE',
                "Konfirmasi penerimaan barang PO #{$nomorFaktur} di gudang pusat (Total Rp " . number_format($totalBiaya, 0, ',', '.') . ")",
                'pembelian',
                (string)$id
            );

            $this->json(['success' => true, 'message' => 'Barang berhasil diverifikasi, stok gudang otomatis bertambah!']);

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $this->json(['success' => false, 'message' => 'Gagal konfirmasi penerimaan barang: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cetak Lembar Surat Pesanan Pembelian / PO Vendor (HTML Preview & Switcher)
     */
    public function print(): void
    {
        Auth::requirePermission('purchases.view');
        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID Pembelian/PO tidak ditemukan.');
            $this->redirect('/purchases');
            return;
        }

        try {
            $purchase = Database::fetchOne("
                SELECT pb.*, sup.nama_pemasok, sup.kode_pemasok, sup.nomor_whatsapp as supplier_telepon, sup.alamat_lengkap,
                       sup.nama_kontak as supplier_kontak, sup.nomor_whatsapp as supplier_wa, sup.email as supplier_email,
                       sup.termin_bayar as supplier_termin_bayar, sup.link_google_maps as supplier_maps,
                       p.nama_lengkap as pembuat,
                       drv.nama_karyawan as nama_driver, drv.nomor_telepon as telp_driver, drv.nomor_polisi_kendaraan as nopol_driver
                FROM public.pembelian pb
                JOIN public.pemasok sup ON pb.pemasok_id = sup.id
                LEFT JOIN public.pengguna p ON pb.dibuat_oleh = p.id
                LEFT JOIN public.v_karyawan_info drv ON pb.sales_driver_id = drv.id
                WHERE pb.id = :id
            ", ['id' => $id]);

            if (!$purchase) {
                $this->flashError('Dokumen tidak ditemukan.');
                $this->redirect('/purchases');
                return;
            }

            $items = Database::fetchAll("
                SELECT rp.*, it.nama_item, it.kode_sku 
                FROM public.rincian_pembelian rp
                JOIN public.item it ON rp.item_id = it.id
                WHERE rp.pembelian_id = :id
                ORDER BY it.nama_item ASC
            ", ['id' => $id]);

            $company = \App\Helpers\CompanySetting::getAll();

            $this->view('purchases.po_pdf', [
                'purchase' => $purchase,
                'items' => $items,
                'company' => $company,
                'isPdf' => false
            ]);

        } catch (Throwable $e) {
            $this->flashError("Gagal membuka dokumen PO: " . $e->getMessage());
            $this->redirect('/purchases');
        }
    }

    /**
     * Cetak Dokumen PDF Surat Pesanan Pembelian (PO)
     */
    public function pdf(): void
    {
        Auth::requirePermission('purchases.view');
        $id = $this->input('id');
        $format = PrintDocumentHelper::resolveFormat($this->input('format', 'standard'));

        if (empty($id)) {
            $this->flashError('ID Pembelian/PO tidak ditemukan.');
            $this->redirect('/purchases');
            return;
        }

        try {
            $purchase = Database::fetchOne("
                SELECT pb.*, sup.nama_pemasok, sup.kode_pemasok, sup.nomor_whatsapp as supplier_telepon, sup.alamat_lengkap,
                       sup.nama_kontak as supplier_kontak, sup.nomor_whatsapp as supplier_wa, sup.email as supplier_email,
                       sup.termin_bayar as supplier_termin_bayar, sup.link_google_maps as supplier_maps,
                       p.nama_lengkap as pembuat,
                       drv.nama_karyawan as nama_driver, drv.nomor_telepon as telp_driver, drv.nomor_polisi_kendaraan as nopol_driver
                FROM public.pembelian pb
                JOIN public.pemasok sup ON pb.pemasok_id = sup.id
                LEFT JOIN public.pengguna p ON pb.dibuat_oleh = p.id
                LEFT JOIN public.v_karyawan_info drv ON pb.sales_driver_id = drv.id
                WHERE pb.id = :id
            ", ['id' => $id]);

            if (!$purchase) {
                $this->flashError('Dokumen tidak ditemukan.');
                $this->redirect('/purchases');
                return;
            }

            $items = Database::fetchAll("
                SELECT rp.*, it.nama_item, it.kode_sku 
                FROM public.rincian_pembelian rp
                JOIN public.item it ON rp.item_id = it.id
                WHERE rp.pembelian_id = :id
                ORDER BY it.nama_item ASC
            ", ['id' => $id]);

            $company = \App\Helpers\CompanySetting::getAll();

            ob_start();
            extract(['purchase' => $purchase, 'items' => $items, 'company' => $company, 'isPdf' => true, 'formatMode' => $format]);
            require __DIR__ . '/../../views/purchases/po_pdf.php';
            $html = ob_get_clean();

            $cleanNomor = preg_replace('/[^A-Za-z0-9]/', ' ', (string)$purchase['nomor_faktur_pembelian']);
            $cleanNomor = trim(preg_replace('/\s+/', ' ', $cleanNomor));
            PrintDocumentHelper::downloadPdf($html, "Surat Pesanan {$cleanNomor}", $format);

        } catch (Throwable $e) {
            $this->flashError("Gagal cetak PDF: " . $e->getMessage());
            $this->redirect('/purchases/print?id=' . urlencode((string)$id));
        }
    }

    public function payDebt(): void
    {
        Auth::requirePermission(['purchases.edit', 'cash.outflow']);

        $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $pembelianId = $payload['pembelian_id'] ?? null;
        $akunKasId = $payload['akun_kas_id'] ?? null;
        $tanggalBayar = $payload['tanggal_bayar'] ?? date('Y-m-d');
        $catatan = trim((string)($payload['catatan'] ?? ''));

        if (empty($pembelianId) || empty($akunKasId)) {
            $this->json(['success' => false, 'message' => 'Data pelunasan tidak lengkap. Pilih faktur dan akun kas.'], 400);
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $purchase = Database::fetchOne("SELECT * FROM public.pembelian WHERE id = :id FOR UPDATE", ['id' => $pembelianId]);

            if (!$purchase) {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'Faktur pembelian tidak ditemukan.'], 404);
                return;
            }

            if (!empty($purchase['pemasok_id'])) {
                $sup = Database::fetchOne("SELECT nama_pemasok FROM public.pemasok WHERE id = :id", ['id' => $purchase['pemasok_id']]);
                $purchase['nama_pemasok'] = $sup['nama_pemasok'] ?? 'Supplier';
            } else {
                $purchase['nama_pemasok'] = 'Supplier';
            }

            if ($purchase['status_pembayaran'] === 'lunas') {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'Faktur ini sudah berstatus LUNAS.'], 400);
                return;
            }

            if ($purchase['status_pembayaran'] === 'batal') {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'Faktur yang telah dibatalkan tidak dapat dilunasi.'], 400);
                return;
            }

            $totalBiaya = (float)$purchase['total_biaya'];
            $nomorFaktur = $purchase['nomor_faktur_pembelian'];
            $namaSupplier = $purchase['nama_pemasok'] ?? 'Supplier';
            $userId = Auth::id() ?: null;

            $akunKas = Database::fetchOne("SELECT saldo_saat_ini, nama_akun FROM public.akun_kas WHERE id = :id FOR UPDATE", ['id' => $akunKasId]);
            if (!$akunKas) {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'Akun kas tidak valid.'], 400);
                return;
            }

            $saldoLama = (float)$akunKas['saldo_saat_ini'];
            if ($saldoLama < $totalBiaya) {
                $pdo->rollBack();
                $saldoFmt = number_format($saldoLama, 0, ',', '.');
                $biayaFmt = number_format($totalBiaya, 0, ',', '.');
                $this->json([
                    'success' => false,
                    'message' => "Saldo akun kas '{$akunKas['nama_akun']}' (Rp {$saldoFmt}) tidak mencukupi untuk pelunasan hutang sebesar Rp {$biayaFmt}. Silakan pilih akun kas lain."
                ], 400);
                return;
            }
            $saldoBaru = $saldoLama - $totalBiaya;

            // 1. Update status pembelian menjadi lunas
            $pdo->prepare("
                UPDATE public.pembelian 
                SET status_pembayaran = 'lunas',
                    catatan = CASE 
                        WHEN :catatan != '' THEN COALESCE(catatan, '') || ' | Pelunasan: ' || :catatan
                        ELSE catatan
                    END
                WHERE id = :id
            ")->execute(['id' => $pembelianId, 'catatan' => $catatan]);

            // 2. Potong saldo kas
            $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")
                ->execute(['saldo' => $saldoBaru, 'id' => $akunKasId]);

            // 3. Catat arus kas keluar
            $voucherNo = CashVoucher::generate('keluar', $tanggalBayar, $pdo);
            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
                    referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :nomor_tx, :akun_id, :tgl, 'keluar', 'pembelian_bahan', :nominal, :ket,
                    'pembelian', :pb_id, :saldo_berjalan, :user_id, NOW()
                )
            ")->execute([
                'nomor_tx' => $voucherNo,
                'akun_id' => $akunKasId,
                'tgl' => $tanggalBayar,
                'nominal' => $totalBiaya,
                'ket' => "Pelunasan hutang faktur vendor: {$nomorFaktur} ({$namaSupplier})" . ($catatan ? " - {$catatan}" : ""),
                'pb_id' => $pembelianId,
                'saldo_berjalan' => $saldoBaru,
                'user_id' => $userId
            ]);

            // 4. Catat log aktivitas
            \App\Helpers\ActivityLog::log(
                'keuangan',
                'UPDATE',
                "Pelunasan Faktur Vendor: {$nomorFaktur} sebesar Rp " . number_format($totalBiaya, 0, ',', '.'),
                'pembelian',
                (string)$pembelianId,
                null,
                ['nomor_faktur' => $nomorFaktur, 'total' => $totalBiaya, 'akun_kas' => $akunKas['nama_akun']]
            );

            $pdo->commit();

            $this->json([
                'success' => true,
                'message' => "Faktur {$nomorFaktur} berhasil dilunasi dan kas keluar telah dicatat!"
            ]);
        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->json(['success' => false, 'message' => 'Gagal memproses pelunasan: ' . $e->getMessage()], 500);
        }
    }

    public function cancel(): void
    {
        Auth::requirePermission('purchases.edit');

        $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $pembelianId = $payload['pembelian_id'] ?? null;
        $alasan = trim((string)($payload['alasan'] ?? 'Pembatalan faktur pembelian'));

        if (empty($pembelianId)) {
            $this->json(['success' => false, 'message' => 'ID faktur pembelian tidak ditemukan.'], 400);
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $purchase = Database::fetchOne("SELECT * FROM public.pembelian WHERE id = :id FOR UPDATE", ['id' => $pembelianId]);

            if (!$purchase) {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'Faktur pembelian tidak ditemukan.'], 404);
                return;
            }

            if (!empty($purchase['pemasok_id'])) {
                $sup = Database::fetchOne("SELECT nama_pemasok FROM public.pemasok WHERE id = :id", ['id' => $purchase['pemasok_id']]);
                $purchase['nama_pemasok'] = $sup['nama_pemasok'] ?? 'Supplier';
            } else {
                $purchase['nama_pemasok'] = 'Supplier';
            }

            if ($purchase['status_pembayaran'] === 'batal') {
                $pdo->rollBack();
                $this->json(['success' => false, 'message' => 'Faktur ini sudah dibatalkan sebelumnya.'], 400);
                return;
            }

            $nomorFaktur = $purchase['nomor_faktur_pembelian'];
            $userId = Auth::id() ?: null;
            $totalBiaya = (float)$purchase['total_biaya'];

            $isAlreadyReceived = ($purchase['status_penerimaan'] === 'diterima');

            if ($isAlreadyReceived) {
                // 1. Ambil rincian item pembelian
                $items = Database::fetchAll("
                    SELECT rp.*, it.nama_item, it.satuan_dasar 
                    FROM public.rincian_pembelian rp
                    JOIN public.item it ON rp.item_id = it.id
                    WHERE rp.pembelian_id = :id
                ", ['id' => $pembelianId]);

                // Cek terlebih dahulu apakah stok fisik saat ini mencukupi untuk di-reverse (tidak terpakai produksi)
                $stmtCheck = $pdo->prepare("SELECT id, nama_item, stok_fisik_saat_ini, satuan_dasar FROM public.item WHERE id = :id FOR UPDATE");
                foreach ($items as $it) {
                    $stmtCheck->execute(['id' => $it['item_id']]);
                    $itemDb = $stmtCheck->fetch();
                    $curStock = (float)($itemDb['stok_fisik_saat_ini'] ?? 0);
                    $buyQty = (float)$it['kuantitas'];

                    if ($curStock < $buyQty) {
                        $pdo->rollBack();
                        $this->json([
                            'success' => false,
                            'message' => "Dokumen {$nomorFaktur} tidak dapat dibatalkan karena sisa stok fisik '{$itemDb['nama_item']}' di gudang saat ini ({$curStock} {$itemDb['satuan_dasar']}) lebih sedikit dari jumlah pembelian ({$buyQty} {$itemDb['satuan_dasar']}). Sebagian bahan kemungkinan telah digunakan dalam operasional produksi."
                        ], 400);
                        return;
                    }
                }

                // 2. Reverse stok item di gudang & pulihkan HPP terakhir sebelum faktur ini
                $stmtUpdateStock = $pdo->prepare("
                    UPDATE public.item 
                    SET stok_fisik_saat_ini = stok_fisik_saat_ini - :qty,
                        harga_pokok_pembelian = :restored_hpp,
                        diubah_pada = NOW() 
                    WHERE id = :item_id
                ");

                $stmtRiwayat = $pdo->prepare("
                    INSERT INTO public.riwayat_stok (
                        item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                        referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
                    ) VALUES (
                        :item_id, 'penyesuaian_opname_kurang', :qty, :sebelum, :sesudah,
                        'pembelian', :pb_id, :ket, :user_id, NOW()
                    )
                ");

                foreach ($items as $it) {
                    $itemId = $it['item_id'];
                    $qty = (float)$it['kuantitas'];
                    if ($qty <= 0.0001) continue;

                    $current = Database::fetchOne("SELECT stok_fisik_saat_ini, harga_pokok_pembelian FROM public.item WHERE id = :id FOR UPDATE", ['id' => $itemId]);
                    $stokSebelum = (float)($current['stok_fisik_saat_ini'] ?? 0);
                    $stokSesudah = $stokSebelum - $qty;

                    // Cari riwayat harga pembelian valid terakhir sebelum faktur ini
                    $prevPb = Database::fetchOne("
                        SELECT rp.harga_satuan 
                        FROM public.rincian_pembelian rp 
                        JOIN public.pembelian pb ON rp.pembelian_id = pb.id 
                        WHERE rp.item_id = :item_id 
                          AND pb.status_pembayaran != 'batal' 
                          AND pb.id != :pb_id 
                        ORDER BY pb.tanggal_pembelian DESC, pb.dibuat_pada DESC 
                        LIMIT 1
                    ", ['item_id' => $itemId, 'pb_id' => $pembelianId]);

                    $restoredHpp = $prevPb ? (float)$prevPb['harga_satuan'] : (float)$current['harga_pokok_pembelian'];

                    $stmtUpdateStock->execute([
                        'qty' => $qty,
                        'restored_hpp' => $restoredHpp,
                        'item_id' => $itemId
                    ]);

                    $stmtRiwayat->execute([
                        'item_id' => $itemId,
                        'qty' => $qty,
                        'sebelum' => $stokSebelum,
                        'sesudah' => $stokSesudah,
                        'pb_id' => $pembelianId,
                        'ket' => "Batal faktur pembelian: {$nomorFaktur} ({$alasan})",
                        'user_id' => $userId
                    ]);
                }
            }

            // 3. Pengembalian Saldo Kas (Jika ada transaksi kas keluar sebelumnya untuk PO/Faktur ini)
            $stmtSumKas = $pdo->prepare("
                SELECT akun_kas_id,
                       COALESCE(SUM(CASE WHEN jenis_kas = 'keluar' THEN nominal ELSE -nominal END), 0) as netto_kas_keluar
                FROM public.arus_kas 
                WHERE referensi_tabel = 'pembelian' AND referensi_id = :id
                GROUP BY akun_kas_id
                HAVING COALESCE(SUM(CASE WHEN jenis_kas = 'keluar' THEN nominal ELSE -nominal END), 0) > 0
            ");
            $stmtSumKas->execute(['id' => $pembelianId]);
            $kasRows = $stmtSumKas->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($kasRows as $kasRow) {
                $akunKasId = $kasRow['akun_kas_id'];
                $nominalRefund = (float)$kasRow['netto_kas_keluar'];
                if ($nominalRefund <= 0.0001 || empty($akunKasId)) continue;

                $stmtAkunLock = $pdo->prepare("SELECT saldo_saat_ini, nama_akun FROM public.akun_kas WHERE id = :id FOR UPDATE");
                $stmtAkunLock->execute(['id' => $akunKasId]);
                $akunKas = $stmtAkunLock->fetch(\PDO::FETCH_ASSOC);
                if ($akunKas) {
                    $saldoLama = (float)$akunKas['saldo_saat_ini'];
                    $saldoBaru = $saldoLama + $nominalRefund;

                    $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")
                        ->execute(['saldo' => $saldoBaru, 'id' => $akunKasId]);

                    $voucherNo = CashVoucher::generate('masuk', date('Y-m-d'), $pdo);
                    $pdo->prepare("
                        INSERT INTO public.arus_kas (
                            nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
                            referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                        ) VALUES (
                            :nomor_tx, :akun_id, CURRENT_DATE, 'masuk', 'pembelian_bahan', :nominal, :ket,
                            'pembelian', :pb_id, :saldo_berjalan, :user_id, NOW()
                        )
                    ")->execute([
                        'nomor_tx' => $voucherNo,
                        'akun_id' => $akunKasId,
                        'nominal' => $nominalRefund,
                        'ket' => "Pengembalian dana pembatalan PO/faktur vendor: {$nomorFaktur} ({$alasan})",
                        'pb_id' => $pembelianId,
                        'saldo_berjalan' => $saldoBaru,
                        'user_id' => $userId
                    ]);
                }
            }

            // 4. Update status pembelian jadi batal & status_penerimaan = kendala_batal
            $pdo->prepare("
                UPDATE public.pembelian 
                SET status_pembayaran = 'batal',
                    status_penerimaan = 'kendala_batal',
                    catatan = COALESCE(catatan, '') || ' [DIBATALKAN: ' || :alasan || ']'
                WHERE id = :id
            ")->execute(['id' => $pembelianId, 'alasan' => $alasan]);

            // 5. Log audit trail
            \App\Helpers\ActivityLog::log(
                'gudang_stok',
                'CANCEL',
                "Pembatalan Faktur Vendor: {$nomorFaktur} ({$alasan})",
                'pembelian',
                (string)$pembelianId,
                null,
                ['nomor_faktur' => $nomorFaktur, 'alasan' => $alasan, 'total' => $totalBiaya]
            );

            $pdo->commit();

            $this->json([
                'success' => true,
                'message' => "Faktur {$nomorFaktur} berhasil dibatalkan. Stok fisik telah dikembalikan!"
            ]);
        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->json(['success' => false, 'message' => 'Gagal membatalkan faktur: ' . $e->getMessage()], 500);
        }
    }
}

