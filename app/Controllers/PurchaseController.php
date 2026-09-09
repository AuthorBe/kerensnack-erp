<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
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
                       pb.status_pembayaran, pb.status_penerimaan, pb.catatan, pb.url_foto_nota, pb.dibuat_pada,
                       sup.id as pemasok_id, sup.nama_pemasok, sup.kode_pemasok, sup.nomor_telepon as supplier_telepon,
                       p.nama_lengkap as pembuat,
                       (SELECT COUNT(*) FROM public.rincian_pembelian WHERE pembelian_id = pb.id) as total_items
                FROM public.pembelian pb
                LEFT JOIN public.pemasok sup ON pb.pemasok_id = sup.id
                LEFT JOIN public.pengguna p ON pb.dibuat_oleh = p.id
                ORDER BY pb.tanggal_pembelian DESC, pb.dibuat_pada DESC
            ");

            $suppliers = Database::fetchAll("
                SELECT id, kode_pemasok, nama_pemasok, nomor_telepon, alamat_lengkap, nama_bank, nomor_rekening, atas_nama_rekening 
                FROM public.pemasok 
                WHERE status_aktif = TRUE 
                ORDER BY nama_pemasok ASC
            ");

            $items = Database::fetchAll("
                SELECT id, kode_sku, nama_item, satuan_dasar, tipe_item, harga_pokok_pembelian, stok_fisik_saat_ini, pemasok_utama_id 
                FROM public.item 
                WHERE status_aktif = TRUE AND tipe_item IN ('bahan_mentah', 'bahan_kemas') 
                ORDER BY tipe_item ASC, nama_item ASC
            ");

            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, saldo_saat_ini 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE 
                ORDER BY nama_akun ASC
            ");

            // Generate Next Suggested PB Number (PB-YYYYMMDD-XXX) based on current records today
            $todayDate = date('Ymd');
            $todayPrefix = 'PB-' . $todayDate . '-';
            $latestPb = Database::fetchOne("
                SELECT nomor_faktur_pembelian 
                FROM public.pembelian 
                WHERE nomor_faktur_pembelian LIKE :pref 
                ORDER BY nomor_faktur_pembelian DESC 
                LIMIT 1
            ", ['pref' => $todayPrefix . '%']);

            if ($latestPb && !empty($latestPb['nomor_faktur_pembelian'])) {
                $lastStr = (string)$latestPb['nomor_faktur_pembelian'];
                $parts = explode('-', $lastStr);
                $seq = (int)end($parts);
                $suggestedPbSuffix = $todayDate . '-' . str_pad((string)($seq + 1), 3, '0', STR_PAD_LEFT);
            } else {
                $suggestedPbSuffix = $todayDate . '-001';
            }

            $this->view('purchases.index', [
                'pageTitle' => 'Pembelian & Faktur Vendor',
                'pageSubtitle' => 'Penerimaan Bahan Mentah, Bumbu & Kemasan dari Supplier',
                'purchases' => $purchases,
                'suppliers' => $suppliers,
                'items' => $items,
                'cashAccounts' => $cashAccounts,
                'suggestedPbSuffix' => $suggestedPbSuffix
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
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
                       pb.status_pembayaran, pb.status_penerimaan, pb.url_foto_nota, pb.catatan, pb.dibuat_pada,
                       sup.id as pemasok_id, sup.kode_pemasok, sup.nama_pemasok, sup.nomor_telepon, sup.alamat_lengkap,
                       sup.nama_bank, sup.nomor_rekening, sup.atas_nama_rekening,
                       p.nama_lengkap as pembuat,
                       ak_info.nama_akun as akun_kas_nama,
                       ak_info.tanggal_transaksi as tanggal_bayar_kas
                FROM public.pembelian pb
                LEFT JOIN public.pemasok sup ON pb.pemasok_id = sup.id
                LEFT JOIN public.pengguna p ON pb.dibuat_oleh = p.id
                LEFT JOIN LATERAL (
                    SELECT ak.nama_akun, ak_ref.tanggal_transaksi
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
                SELECT rp.id, rp.kuantitas, rp.satuan, rp.harga_satuan, rp.subtotal,
                       it.nama_item, it.kode_sku, it.tipe_item
                FROM public.rincian_pembelian rp
                JOIN public.item it ON rp.item_id = it.id
                WHERE rp.pembelian_id = :id
                ORDER BY rp.dibuat_pada ASC
            ", ['id' => $id]);

            $this->json([
                'success' => true,
                'purchase' => $purchase,
                'items' => $items
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

            $qty = (float)($it['qty'] ?? 0);
            $qtyInt = (int)round($qty);
            if ($qtyInt <= 0) {
                $this->json(['success' => false, 'message' => 'Kuantitas setiap item minimal 1.'], 400);
                return;
            }

            $harga = (float)($it['harga_satuan'] ?? 0);
            if ($harga < 0) {
                $this->json(['success' => false, 'message' => 'Harga satuan barang tidak boleh bernilai negatif.'], 400);
                return;
            }

            // Hitung subtotal mutlak di server untuk akurasi finansial
            $subtotal = round($qtyInt * $harga, 2);
            $validItems[] = [
                'item_id' => $itemId,
                'qty' => $qtyInt,
                'harga_satuan' => $harga,
                'subtotal' => $subtotal
            ];
            $totalBiaya += $subtotal;
        }

        if (empty($validItems) || $totalBiaya <= 0) {
            $this->json(['success' => false, 'message' => 'Faktur pembelian harus memiliki minimal 1 item dengan kuantitas > 0 dan total nominal > Rp 0.'], 400);
            return;
        }

        $statusBayar = $payload['status_pembayaran'] ?? 'lunas';
        $akunKasId = !empty($payload['akun_kas_id']) ? $payload['akun_kas_id'] : null;

        // 3. Validasi Pembayaran Lunas & Kecukupan Saldo Kas
        if ($statusBayar === 'lunas') {
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

        // 4. Handle Upload Foto Bukti Nota Fisik via Upload Helper (Anti Dobel Folder & Validasi Gambar)
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

            // Kunci advisory lock transaksi PostgreSQL untuk menjamin nomor berurutan bebas dari race condition
            $pdo->query("SELECT pg_advisory_xact_lock(hashtext('pembelian_nomor_faktur'))");

            // Penomoran Faktur Pembelian Otomatis & Terkunci oleh Sistem (PB-YYYYMMDD-XXX)
            $todayDate = date('Ymd');
            $todayPrefix = 'PB-' . $todayDate . '-';
            
            $stmtLatest = $pdo->prepare("
                SELECT nomor_faktur_pembelian 
                FROM public.pembelian 
                WHERE nomor_faktur_pembelian LIKE :pref 
                ORDER BY nomor_faktur_pembelian DESC 
                LIMIT 1
            ");
            $stmtLatest->execute(['pref' => $todayPrefix . '%']);
            $latestPb = $stmtLatest->fetch();

            if ($latestPb && !empty($latestPb['nomor_faktur_pembelian'])) {
                $lastStr = (string)$latestPb['nomor_faktur_pembelian'];
                $parts = explode('-', $lastStr);
                $seq = (int)end($parts);
                $nomorFaktur = $todayPrefix . str_pad((string)($seq + 1), 3, '0', STR_PAD_LEFT);
            } else {
                $nomorFaktur = $todayPrefix . '001';
            }

            $catatan = trim($payload['catatan'] ?? 'Penerimaan barang dari supplier');
            $userId = Auth::id() ?: null;

            // 1. Insert Header Pembelian
            $stmtPb = $pdo->prepare("
                INSERT INTO public.pembelian (
                    nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya,
                    status_pembayaran, status_penerimaan, url_foto_nota, catatan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :no_faktur, :pemasok, :tgl, :total,
                    :bayar, 'diterima', :foto, :catatan, :user_id, NOW()
                ) RETURNING id
            ");

            $stmtPb->execute([
                'no_faktur' => $nomorFaktur,
                'pemasok' => $pemasokId,
                'tgl' => $tanggal,
                'total' => $totalBiaya,
                'bayar' => $statusBayar,
                'foto' => $fotoPath,
                'catatan' => $catatan,
                'user_id' => $userId
            ]);

            $pembelianId = $stmtPb->fetchColumn();

            // 2. Insert Items & Auto Increment Stok Fisik
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
                $qtyInt = $it['qty'];
                $harga = $it['harga_satuan'];
                $subtotal = $it['subtotal'];

                // Kunci baris item untuk kalkulasi stok & HPP yang 100% konsisten
                $stmtItemLock->execute(['id' => $itemId]);
                $current = $stmtItemLock->fetch();

                $stokSebelum = (int)($current['stok_fisik_saat_ini'] ?? 0);
                $stokSesudah = $stokSebelum + $qtyInt;
                $satuan = $current['satuan_dasar'] ?? 'pcs';
                $hppLama = (float)($current['harga_pokok_pembelian'] ?? 0);

                // Hitung Weighted Moving Average HPP
                if ($harga > 0) {
                    if ($stokSebelum > 0 && $hppLama > 0) {
                        $hppBaru = round((($stokSebelum * $hppLama) + ($qtyInt * $harga)) / $stokSesudah, 2);
                    } else {
                        $hppBaru = $harga;
                    }
                } else {
                    $hppBaru = $hppLama;
                }

                $stmtItem->execute([
                    'pb_id' => $pembelianId,
                    'item_id' => $itemId,
                    'qty' => $qtyInt,
                    'satuan' => $satuan,
                    'harga' => $harga,
                    'subtotal' => $subtotal
                ]);

                $stmtUpdateStock->execute([
                    'qty' => $qtyInt,
                    'harga_baru' => $hppBaru,
                    'item_id' => $itemId
                ]);

                $stmtRiwayat->execute([
                    'item_id' => $itemId,
                    'qty' => $qtyInt,
                    'sebelum' => $stokSebelum,
                    'sesudah' => $stokSesudah,
                    'pb_id' => $pembelianId,
                    'ket' => "Penerimaan barang vendor faktur: {$nomorFaktur}",
                    'user_id' => $userId
                ]);
            }

            // 3. Catat Kas Keluar jika Lunas & ada akun_kas_id
            if ($statusBayar === 'lunas' && $akunKasId) {
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

                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
                        referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :akun_id, :tgl, 'keluar', 'pembelian_bahan', :nominal, :ket,
                        'pembelian', :pb_id, :saldo_berjalan, :user_id, NOW()
                    )
                ")->execute([
                    'akun_id' => $akunKasId,
                    'tgl' => $tanggal,
                    'nominal' => $totalBiaya,
                    'ket' => "Pembayaran faktur pembelian vendor: {$nomorFaktur}",
                    'pb_id' => $pembelianId,
                    'saldo_berjalan' => $saldoBaru,
                    'user_id' => $userId
                ]);
            }

            // 4. Catat Audit Trail
            $pdo->prepare("
                INSERT INTO public.log_aktivitas (
                    nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas, jenis_aksi,
                    tabel_terdampak, id_referensi, deskripsi_aktivitas, data_sesudah
                ) VALUES (
                    :nama, :peran, 'web_app', 'gudang_stok', 'INSERT',
                    'pembelian', :pb_id, :desc, :data_json
                )
            ")->execute([
                'nama' => Auth::name(),
                'peran' => Auth::role(),
                'pb_id' => $pembelianId,
                'desc' => "Faktur Pembelian Vendor: {$nomorFaktur} total Rp " . number_format($totalBiaya, 0, ',', '.') . " (" . ($statusBayar === 'lunas' ? 'LUNAS' : 'TEMPO/HUTANG') . ")",
                'data_json' => json_encode([
                    'nomor_faktur' => $nomorFaktur,
                    'total' => $totalBiaya,
                    'status_pembayaran' => $statusBayar,
                    'items_count' => count($validItems)
                ])
            ]);

            $pdo->commit();

            $this->json([
                'success' => true,
                'message' => 'Faktur pembelian berhasil disimpan dan stok gudang otomatis bertambah!',
                'data' => ['pembelian_id' => $pembelianId, 'nomor_faktur' => $nomorFaktur]
            ]);

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->json(['success' => false, 'message' => 'Gagal simpan pembelian: ' . $e->getMessage()], 500);
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
            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
                    referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :akun_id, :tgl, 'keluar', 'pembelian_bahan', :nominal, :ket,
                    'pembelian', :pb_id, :saldo_berjalan, :user_id, NOW()
                )
            ")->execute([
                'akun_id' => $akunKasId,
                'tgl' => $tanggalBayar,
                'nominal' => $totalBiaya,
                'ket' => "Pelunasan hutang faktur vendor: {$nomorFaktur} ({$namaSupplier})" . ($catatan ? " - {$catatan}" : ""),
                'pb_id' => $pembelianId,
                'saldo_berjalan' => $saldoBaru,
                'user_id' => $userId
            ]);

            // 4. Catat log aktivitas
            $pdo->prepare("
                INSERT INTO public.log_aktivitas (
                    nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas, jenis_aksi,
                    tabel_terdampak, id_referensi, deskripsi_aktivitas, data_sesudah
                ) VALUES (
                    :nama, :peran, 'web_app', 'keuangan_kas', 'UPDATE',
                    'pembelian', :pb_id, :desc, :data_json
                )
            ")->execute([
                'nama' => Auth::name(),
                'peran' => Auth::role(),
                'pb_id' => $pembelianId,
                'desc' => "Pelunasan Faktur Vendor: {$nomorFaktur} sebesar Rp " . number_format($totalBiaya, 0, ',', '.'),
                'data_json' => json_encode(['nomor_faktur' => $nomorFaktur, 'total' => $totalBiaya, 'akun_kas' => $akunKas['nama_akun']])
            ]);

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
                $curStock = (int)($itemDb['stok_fisik_saat_ini'] ?? 0);
                $buyQty = (int)$it['kuantitas'];

                if ($curStock < $buyQty) {
                    $pdo->rollBack();
                    $this->json([
                        'success' => false,
                        'message' => "Faktur {$nomorFaktur} tidak dapat dibatalkan karena sisa stok fisik '{$itemDb['nama_item']}' di gudang saat ini ({$curStock} {$itemDb['satuan_dasar']}) lebih sedikit dari jumlah pembelian ({$buyQty} {$itemDb['satuan_dasar']}). Sebagian bahan kemungkinan telah digunakan dalam operasional produksi. Lakukan penyesuaian opname manual jika terdapat koreksi fisik."
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
                $qty = (int)$it['kuantitas'];
                if ($qty <= 0) continue;

                $current = Database::fetchOne("SELECT stok_fisik_saat_ini, harga_pokok_pembelian FROM public.item WHERE id = :id FOR UPDATE", ['id' => $itemId]);
                $stokSebelum = (int)($current['stok_fisik_saat_ini'] ?? 0);
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

            // 3. Jika berstatus lunas, kembalikan saldo kas
            if ($purchase['status_pembayaran'] === 'lunas') {
                $arusKas = Database::fetchOne("
                    SELECT akun_kas_id 
                    FROM public.arus_kas 
                    WHERE referensi_tabel = 'pembelian' AND referensi_id = :id AND jenis_kas = 'keluar'
                    ORDER BY dibuat_pada DESC LIMIT 1
                ", ['id' => $pembelianId]);

                if ($arusKas && !empty($arusKas['akun_kas_id'])) {
                    $akunKasId = $arusKas['akun_kas_id'];
                    $akunKas = Database::fetchOne("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE", ['id' => $akunKasId]);
                    if ($akunKas) {
                        $saldoLama = (float)$akunKas['saldo_saat_ini'];
                        $saldoBaru = $saldoLama + $totalBiaya;

                        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")
                            ->execute(['saldo' => $saldoBaru, 'id' => $akunKasId]);

                        $pdo->prepare("
                            INSERT INTO public.arus_kas (
                                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
                                referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                            ) VALUES (
                                :akun_id, CURRENT_DATE, 'masuk', 'pembelian_bahan', :nominal, :ket,
                                'pembelian', :pb_id, :saldo_berjalan, :user_id, NOW()
                            )
                        ")->execute([
                            'akun_id' => $akunKasId,
                            'nominal' => $totalBiaya,
                            'ket' => "Pengembalian dana pembatalan faktur vendor: {$nomorFaktur} ({$alasan})",
                            'pb_id' => $pembelianId,
                            'saldo_berjalan' => $saldoBaru,
                            'user_id' => $userId
                        ]);
                    }
                }
            }

            // 4. Update status pembelian jadi batal
            $pdo->prepare("
                UPDATE public.pembelian 
                SET status_pembayaran = 'batal',
                    catatan = COALESCE(catatan, '') || ' [DIBATALKAN: ' || :alasan || ']'
                WHERE id = :id
            ")->execute(['id' => $pembelianId, 'alasan' => $alasan]);

            // 5. Log audit trail
            $pdo->prepare("
                INSERT INTO public.log_aktivitas (
                    nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas, jenis_aksi,
                    tabel_terdampak, id_referensi, deskripsi_aktivitas, data_sesudah
                ) VALUES (
                    :nama, :peran, 'web_app', 'gudang_stok', 'CANCEL',
                    'pembelian', :pb_id, :desc, :data_json
                )
            ")->execute([
                'nama' => Auth::name(),
                'peran' => Auth::role(),
                'pb_id' => $pembelianId,
                'desc' => "Pembatalan Faktur Vendor: {$nomorFaktur} ({$alasan})",
                'data_json' => json_encode(['nomor_faktur' => $nomorFaktur, 'alasan' => $alasan, 'total' => $totalBiaya])
            ]);

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

