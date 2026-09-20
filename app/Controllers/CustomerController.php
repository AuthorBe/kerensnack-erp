<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\DocumentNumber;
use Database;
use PDO;
use Throwable;

/**
 * app/Controllers/CustomerController.php
 * Pengendali Master Toko Pelanggan Terpadu:
 * 1. Data Toko & Plafon Kredit
 * 2. Grup Pelanggan (Level 1–30)
 * 3. Master Rute / Wilayah (CRUD Terpadu untuk Pelanggan & Vendor Supplier)
 * 4. Tipe Bayar (Cash / Tempo / Konsinyasi)
 * 5. Daftar Item Khusus Toko (Whitelist Katalog Item per Toko)
 */
class CustomerController extends Controller
{
    public const ALLOWED_TIPE_BAYAR = [
        'cash', 'qris', 'transfer', 'tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari', 'konsinyasi'
    ];

    public function __construct()
    {
        Auth::requirePermission(['master.customers_view_all', 'master.customers_view_assigned', 'master.customers_manage', 'master.pricing_manage']);
    }

    /**
     * Anti-Collision sequential generator untuk kode_pelanggan (CUST-XXXX)
     */
    public function generateCustomerCode(): string
    {
        $stmt = Database::getConnection()->query("
            SELECT kode_pelanggan 
            FROM public.pelanggan 
            WHERE kode_pelanggan ~ '^CUST-[0-9]+$' 
            ORDER BY CAST(SUBSTRING(kode_pelanggan FROM 6) AS INTEGER) DESC 
            LIMIT 1
        ");
        $latest = $stmt->fetch();
        $nextSeq = 1;
        if ($latest && !empty($latest['kode_pelanggan'])) {
            $num = (int)substr((string)$latest['kode_pelanggan'], 5);
            $nextSeq = $num + 1;
        }

        // Loop failsafe jika nomor sudah ada
        do {
            $kodePelanggan = 'CUST-' . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
            $exists = (int)(Database::fetchOne("SELECT count(*) as total FROM public.pelanggan WHERE kode_pelanggan = :k", ['k' => $kodePelanggan])['total'] ?? 0);
            if ($exists > 0) {
                $nextSeq++;
            }
        } while ($exists > 0);

        return $kodePelanggan;
    }

    public function index(): void
    {
        try {
            $q = trim((string)$this->input('q', ''));
            $page = max(1, (int)$this->input('page', 1));
            $perPage = max(10, min(200, (int)$this->input('per_page', 50)));
            $offset = ($page - 1) * $perPage;

            $whereClause = "";
            $params = [];
            if (!empty($q)) {
                $whereClause = "WHERE (p.nama_toko ILIKE :q OR p.kode_pelanggan ILIKE :q OR p.nama_pemilik ILIKE :q OR gp.nama_grup ILIKE :q OR w.nama_wilayah ILIKE :q)";
                $params['q'] = "%{$q}%";
            }

            // Hitung total pelanggan untuk paginasi
            $countSql = "
                SELECT COUNT(*) as total
                FROM public.pelanggan p
                LEFT JOIN public.grup_pelanggan gp ON p.grup_pelanggan_id = gp.id
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                {$whereClause}
            ";
            $totalCustomers = (int)(Database::fetchOne($countSql, $params)['total'] ?? 0);
            $totalPages = max(1, (int)ceil($totalCustomers / $perPage));

            // Statistik global (seluruh database, tidak terpotong paginasi)
            $totalGlobalCustomers = (int)(Database::fetchOne("SELECT COUNT(*) as total FROM public.pelanggan")['total'] ?? 0);
            $totalActiveCustomers = (int)(Database::fetchOne("SELECT COUNT(*) as total FROM public.pelanggan WHERE status_aktif = TRUE")['total'] ?? 0);
            $totalKonsinyasiCustomers = (int)(Database::fetchOne("SELECT COUNT(*) as total FROM public.pelanggan WHERE is_konsinyasi = TRUE AND status_aktif = TRUE")['total'] ?? 0);
            $totalRegulerCustomers = (int)(Database::fetchOne("SELECT COUNT(*) as total FROM public.pelanggan WHERE (is_konsinyasi = FALSE OR is_konsinyasi IS NULL) AND status_aktif = TRUE")['total'] ?? 0);
            $totalGlobalPiutang = (float)(Database::fetchOne("SELECT COALESCE(SUM(total_piutang_berjalan), 0) as total FROM public.pelanggan WHERE status_aktif = TRUE")['total'] ?? 0);

            // 1. Ambil data toko pelanggan beserta relasi & jumlah item khusus (dengan limit & offset)
            $customers = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.is_konsinyasi,
                       p.alamat_lengkap, p.link_google_maps, p.nomor_whatsapp, p.tipe_pembayaran_default,
                       p.nama_bank, p.nomor_rekening, p.atas_nama_rekening,
                       p.plafon_piutang, p.total_piutang_berjalan, p.status_aktif,
                       p.grup_pelanggan_id, p.wilayah_id, p.sales_driver_id,
                       gp.nama_grup, gp.default_level_harga, gp.status_aktif as grup_status_aktif,
                       w.nama_wilayah, w.kode_rute, w.status_aktif as wilayah_status_aktif,
                       k.nama_karyawan as nama_sales,
                       (SELECT COUNT(*) FROM public.pelanggan_item pi WHERE pi.pelanggan_id = p.id) as total_item_khusus,
                       (SELECT COALESCE(SUM(sk.stok_titip_saat_ini), 0) FROM public.stok_konsinyasi_toko sk WHERE sk.pelanggan_id = p.id) as stok_titip_aktif
                FROM public.pelanggan p
                LEFT JOIN public.grup_pelanggan gp ON p.grup_pelanggan_id = gp.id
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                {$whereClause}
                ORDER BY p.status_aktif DESC, p.nama_toko ASC
                LIMIT {$perPage} OFFSET {$offset}
            ", $params);

            // 2. Ambil master grup pelanggan beserta detail diskon dan jumlah toko
            $customerGroups = Database::fetchAll("
                SELECT gp.id, gp.kode_grup, gp.nama_grup, gp.default_level_harga,
                       gp.diskon_persen_default, gp.diskon_nominal_default, gp.status_aktif,
                       mlh.nama_level as master_nama_level,
                       (SELECT COUNT(*) FROM public.pelanggan p WHERE p.grup_pelanggan_id = gp.id) as total_pelanggan
                FROM public.grup_pelanggan gp
                LEFT JOIN public.master_level_harga mlh ON gp.default_level_harga = mlh.level_nomor
                ORDER BY gp.nama_grup ASC
            ");

            $masterLevels = Database::fetchAll("
                SELECT level_nomor, nama_level 
                FROM public.master_level_harga 
                ORDER BY level_nomor ASC
            ");

            // 3. Ambil master wilayah / rute logistik
            $territories = Database::fetchAll("
                SELECT w.id, w.kode_rute, w.nama_wilayah, w.provinsi, w.kota_kabupaten, w.sub_wilayah, w.status_aktif,
                       (SELECT COUNT(*) FROM public.pelanggan p WHERE p.wilayah_id = w.id) as total_pelanggan,
                       (SELECT COUNT(*) FROM public.pemasok sup WHERE sup.wilayah_id = w.id) as total_pemasok
                FROM public.wilayah w
                ORDER BY w.status_aktif DESC, w.nama_wilayah ASC
            ");

            // 4. Ambil seluruh Barang Jadi (Finished Goods) untuk modal item whitelist
            $finishedGoods = Database::fetchAll("
                SELECT i.id, i.grup_id, i.kode_sku, i.nama_item,
                       gp.nama_grup, gp.kode_grup, gp.barcode_universal
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE i.status_aktif = TRUE AND i.tipe_item = 'barang_jadi'
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            // 5. Ambil pemetaan item khusus per pelanggan
            $rawCustomerItems = Database::fetchAll("SELECT pelanggan_id, item_id FROM public.pelanggan_item");
            $customerItemsMap = [];
            foreach ($rawCustomerItems as $ci) {
                $customerItemsMap[$ci['pelanggan_id']][] = $ci['item_id'];
            }

            // 6. Ambil daftar petugas sales aktif untuk dropdown pembina toko (hanya jabatan sales)
            $salesEmployees = Database::fetchAll("
                SELECT id, nama_karyawan, posisi 
                FROM public.v_karyawan_info 
                WHERE status_aktif = TRUE AND LOWER(posisi) = 'sales'
                ORDER BY nama_karyawan ASC
            ");

            // 7. Ambil akun kas aktif (untuk opsi resolusi beli putus lunas kasir/bank)
            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, nomor_rekening, atas_nama, saldo_saat_ini, tipe_akun, is_default_pos
                FROM public.akun_kas
                WHERE status_aktif = TRUE
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            // 8. Ambil daftar stok konsinyasi aktif di rak toko beserta harga jual per toko
            $shelfRows = Database::fetchAll("
                SELECT sk.pelanggan_id, sk.item_id, sk.stok_titip_saat_ini,
                       i.nama_item, i.kode_sku, i.harga_pokok_pembelian as harga_pokok,
                       public.fn_hitung_harga_jual_item(i.id, sk.pelanggan_id) as harga_info
                FROM public.stok_konsinyasi_toko sk
                JOIN public.item i ON sk.item_id = i.id
                WHERE sk.stok_titip_saat_ini > 0
                ORDER BY i.nama_item ASC
            ");
            $shelfItemsMap = [];
            foreach ($shelfRows as $row) {
                $hargaData = json_decode((string)($row['harga_info'] ?? ''), true) ?: [];
                $hargaPcs = (float)($hargaData['harga_pcs_netto'] ?? 0);
                $qty = (int)$row['stok_titip_saat_ini'];
                $subtotal = $hargaPcs * $qty;
                $shelfItemsMap[$row['pelanggan_id']][] = [
                    'item_id' => $row['item_id'],
                    'nama_item' => $row['nama_item'],
                    'kode_sku' => $row['kode_sku'],
                    'harga_pokok' => (float)($row['harga_pokok'] ?? 0),
                    'qty' => $qty,
                    'harga_pcs' => $hargaPcs,
                    'subtotal' => $subtotal
                ];
            }

            $this->view('customers.index', [
                'pageTitle' => 'Master Toko Pelanggan & Wilayah',
                'pageSubtitle' => 'Kelola Data Toko, Tier Harga, Rute Logistik & Item Khusus Toko',
                'customers' => $customers,
                'groups' => $customerGroups,
                'customerGroups' => $customerGroups,
                'masterLevels' => $masterLevels,
                'territories' => $territories,
                'finishedGoods' => $finishedGoods,
                'customerItemsMap' => $customerItemsMap,
                'salesEmployees' => $salesEmployees,
                'cashAccounts' => $cashAccounts,
                'shelfItemsMap' => $shelfItemsMap,
                'totalGlobalCustomers' => $totalGlobalCustomers,
                'totalActiveCustomers' => $totalActiveCustomers,
                'totalKonsinyasiCustomers' => $totalKonsinyasiCustomers,
                'totalRegulerCustomers' => $totalRegulerCustomers,
                'totalGlobalPiutang' => $totalGlobalPiutang,
                'pagination' => [
                    'page' => $page,
                    'perPage' => $perPage,
                    'total' => $totalCustomers,
                    'totalPages' => $totalPages,
                    'q' => $q
                ]
            ]);

        } catch (Throwable $e) {
            $this->flashError("Gagal memuat data pelanggan: " . $e->getMessage());
            $this->redirect('/dashboard');
        }
    }

    // ==========================================
    // 1. DATA TOKO PELANGGAN (STORE, UPDATE, DELETE)
    // ==========================================
    public function store(): void
    {
        Auth::requirePermission('master.customers_manage');

        $namaToko = trim((string)$this->input('nama_toko'));
        $namaPemilik = trim((string)$this->input('nama_pemilik'));
        $grupId = $this->input('grup_pelanggan_id');
        $wilayahId = $this->input('wilayah_id') ?: null;
        $salesDriverId = $this->input('sales_driver_id') ?: null;
        $alamat = trim((string)$this->input('alamat_lengkap', '-'));
        $whatsapp = trim((string)($this->input('nomor_whatsapp') ?: $this->input('nomor_telepon')));
        $tipeBayar = (string)$this->input('tipe_pembayaran_default', 'cash');
        if (!in_array($tipeBayar, self::ALLOWED_TIPE_BAYAR, true)) {
            $tipeBayar = 'cash';
        }

        $isKonsinyasi = ($tipeBayar === 'konsinyasi') || (bool)$this->input('is_konsinyasi', false);
        $plafon = (float)preg_replace('/[^0-9]/', '', (string)$this->input('plafon_piutang', '0'));
        $linkMaps = trim((string)$this->input('link_google_maps')) ?: null;
        $namaBank = trim((string)$this->input('nama_bank'));
        $nomorRekening = trim((string)$this->input('nomor_rekening'));
        $atasNamaRekening = trim((string)$this->input('atas_nama_rekening'));

        if (empty($namaToko) || empty($grupId)) {
            $this->flashError('Nama toko dan grup pelanggan wajib diisi.');
            $this->redirect('/customers');
            return;
        }

        if (!empty($nomorRekening) && empty($atasNamaRekening)) {
            $this->flashError('Pemilik rekening wajib diisi jika nomor rekening diisi.');
            $this->redirect('/customers');
            return;
        }

        if (!empty($salesDriverId)) {
            $checkEmp = Database::fetchOne("
                SELECT k.id, p.posisi 
                FROM public.karyawan k 
                JOIN public.pengguna p ON k.pengguna_id = p.id 
                WHERE k.id = :id
            ", ['id' => $salesDriverId]);
            if (!$checkEmp) {
                $salesDriverId = null;
            } elseif (strtolower($checkEmp['posisi'] ?? '') === 'driver') {
                $this->flashError('Penanggung jawab toko binaan harus berposisi Sales, tidak boleh Driver.');
                $this->redirect('/customers');
                return;
            }
        }

        try {
            $kodePelanggan = $this->generateCustomerCode();

            Database::execute("
                INSERT INTO public.pelanggan (
                    kode_pelanggan, nama_toko, nama_pemilik, grup_pelanggan_id, is_konsinyasi,
                    wilayah_id, sales_driver_id, alamat_lengkap, link_google_maps, nomor_whatsapp,
                    tipe_pembayaran_default, plafon_piutang,
                    nama_bank, nomor_rekening, atas_nama_rekening, status_aktif
                ) VALUES (
                    :kode, :nama, :pemilik, :grup, :konsinyasi,
                    :wilayah, :sales_driver_id, :alamat, :link_maps, :wa,
                    :bayar, :plafon,
                    :nama_bank, :nomor_rek, :atas_nama, TRUE
                )
            ", [
                'kode' => $kodePelanggan,
                'nama' => $namaToko,
                'pemilik' => $namaPemilik ?: null,
                'grup' => $grupId,
                'konsinyasi' => $isKonsinyasi ? 'true' : 'false',
                'wilayah' => $wilayahId,
                'sales_driver_id' => $salesDriverId,
                'alamat' => $alamat,
                'link_maps' => $linkMaps,
                'wa' => $whatsapp ?: null,
                'bayar' => $tipeBayar,
                'plafon' => $plafon,
                'nama_bank' => $namaBank ?: null,
                'nomor_rek' => $nomorRekening ?: null,
                'atas_nama' => $atasNamaRekening ?: null,
            ]);

            ActivityLog::log(
                'master_data',
                'TAMBAH_TOKO',
                "Menambahkan toko pelanggan baru: {$namaToko} ({$kodePelanggan})",
                'pelanggan'
            );

            $this->flashSuccess("Toko {$namaToko} ({$kodePelanggan}) berhasil ditambahkan!");
            $this->redirect('/customers');

        } catch (Throwable $e) {
            $this->flashError('Gagal menambahkan toko: ' . $e->getMessage());
            $this->redirect('/customers');
        }
    }

    public function update(): void
    {
        Auth::requirePermission('master.customers_manage');

        $id = $this->input('id');
        $namaToko = trim((string)$this->input('nama_toko'));
        $namaPemilik = trim((string)$this->input('nama_pemilik'));
        $grupId = $this->input('grup_pelanggan_id');
        $wilayahId = $this->input('wilayah_id') ?: null;
        $salesDriverId = $this->input('sales_driver_id') ?: null;
        $alamat = trim((string)$this->input('alamat_lengkap', '-'));
        if ($alamat === '') {
            $alamat = '-';
        }
        $whatsapp = trim((string)($this->input('nomor_whatsapp') ?: $this->input('nomor_telepon')));
        $tipeBayar = (string)$this->input('tipe_pembayaran_default', 'cash');
        if (!in_array($tipeBayar, self::ALLOWED_TIPE_BAYAR, true)) {
            $tipeBayar = 'cash';
        }

        $isKonsinyasi = ($tipeBayar === 'konsinyasi') || (bool)$this->input('is_konsinyasi', false);
        $plafon = (float)preg_replace('/[^0-9]/', '', (string)$this->input('plafon_piutang', '0'));
        $linkMaps = trim((string)$this->input('link_google_maps')) ?: null;
        $statusAktif = (bool)$this->input('status_aktif', true);
        $namaBank = trim((string)$this->input('nama_bank'));
        $nomorRekening = trim((string)$this->input('nomor_rekening'));
        $atasNamaRekening = trim((string)$this->input('atas_nama_rekening'));

        if (empty($id) || empty($namaToko) || empty($grupId)) {
            $this->flashError('Parameter data toko tidak lengkap.');
            $this->redirect('/customers');
            return;
        }

        if (!empty($salesDriverId)) {
            $checkEmp = Database::fetchOne("
                SELECT k.id, p.posisi 
                FROM public.karyawan k 
                JOIN public.pengguna p ON k.pengguna_id = p.id 
                WHERE k.id = :id
            ", ['id' => $salesDriverId]);
            if (!$checkEmp) {
                $salesDriverId = null;
            } elseif (strtolower($checkEmp['posisi'] ?? '') === 'driver') {
                $this->flashError('Penanggung jawab toko binaan harus berposisi Sales, tidak boleh Driver.');
                $this->redirect('/customers');
                return;
            }
        }

        // Cek data toko yang ada di database
        $currentCust = Database::fetchOne("SELECT id, kode_pelanggan, nama_toko, is_konsinyasi, status_aktif FROM public.pelanggan WHERE id = :id", ['id' => $id]);
        if (!$currentCust) {
            $this->flashError('Data toko pelanggan tidak ditemukan.');
            $this->redirect('/customers');
            return;
        }

        // Proteksi Pelanggan Default POS (CUST-001): Status wajib aktif
        if ($currentCust['kode_pelanggan'] === 'CUST-001' && !$statusAktif) {
            $this->flashError('Status toko default sistem (CUST-001 / Toko Umum / Walk-in Cash) wajib tetap aktif untuk operasional kasir POS.');
            $this->redirect('/customers');
            return;
        }

        // Cek data stok konsinyasi aktif jika toko beralih dari konsinyasi ke non-konsinyasi
        $konversiOpsi = null;
        $shelfItems = [];
        $totalTitip = 0;

        if ($currentCust['is_konsinyasi'] && !$isKonsinyasi) {
            $shelfItems = Database::fetchAll("
                SELECT sk.item_id, sk.stok_titip_saat_ini, i.nama_item, i.kode_sku, i.harga_pokok_pembelian as harga_pokok, i.stok_fisik_saat_ini,
                       public.fn_hitung_harga_jual_item(i.id, :id) as harga_info
                FROM public.stok_konsinyasi_toko sk
                JOIN public.item i ON sk.item_id = i.id
                WHERE sk.pelanggan_id = :id AND sk.stok_titip_saat_ini > 0
            ", ['id' => $id]);

            foreach ($shelfItems as $si) {
                $totalTitip += (int)$si['stok_titip_saat_ini'];
            }

            if ($totalTitip > 0) {
                $konversiOpsi = trim((string)$this->input('konversi_konsinyasi_opsi'));
                if (!in_array($konversiOpsi, ['retur', 'beli_putus'], true)) {
                    $this->flashError("Toko ini masih memiliki {$totalTitip} pcs stok konsinyasi di rak toko. Silakan pilih Opsi 1 (Tarik/Retur Fisik ke Gudang) atau Opsi 2 (Beli Putus Sisa Barang) di modal edit toko sebelum beralih ke non-konsinyasi.");
                    $this->redirect('/customers');
                    return;
                }
            }
        }

        if (!empty($nomorRekening) && empty($atasNamaRekening)) {
            $this->flashError('Pemilik rekening wajib diisi jika nomor rekening diisi.');
            $this->redirect('/customers');
            return;
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $catatanKonversi = trim((string)$this->input('catatan_konversi'));
            $userId = Auth::id();
            $nomorNotaBeliPutus = null;
            $totalNominalBeliPutus = 0.0;

            if ($currentCust['is_konsinyasi'] && !$isKonsinyasi && $totalTitip > 0) {
                if ($konversiOpsi === 'retur') {
                    // Opsi 1: Retur Fisik ke Gudang Pusat (saldo rak toko 0, item.stok_fisik_saat_ini + qty)
                    foreach ($shelfItems as $si) {
                        $itemId = $si['item_id'];
                        $qty = (int)$si['stok_titip_saat_ini'];

                        // 1. Tambah stok fisik gudang pusat (atomik dengan RETURNING)
                        $stmtItem = $pdo->prepare("
                            UPDATE public.item 
                            SET stok_fisik_saat_ini = stok_fisik_saat_ini + :qty, diubah_pada = NOW() 
                            WHERE id = :item_id
                            RETURNING stok_fisik_saat_ini
                        ");
                        $stmtItem->execute(['qty' => $qty, 'item_id' => $itemId]);
                        $stokSesudah = (float)$stmtItem->fetchColumn();
                        $stokSebelum = $stokSesudah - $qty;

                        // 2. Nolkan saldo rak toko
                        $stmtRak = $pdo->prepare("
                            UPDATE public.stok_konsinyasi_toko 
                            SET stok_titip_saat_ini = 0, terakhir_opname_pada = NOW(), diubah_pada = NOW() 
                            WHERE pelanggan_id = :cust_id AND item_id = :item_id
                        ");
                        $stmtRak->execute(['cust_id' => $id, 'item_id' => $itemId]);

                        // 3. Catat riwayat_stok
                        $ket = "Retur fisik penarikan sisa konsinyasi ({$namaToko}) saat beralih tipe toko ke " . strtoupper($tipeBayar);
                        if (!empty($catatanKonversi)) {
                            $ket .= " - BAST: {$catatanKonversi}";
                        }

                        $stmtMutasi = $pdo->prepare("
                            INSERT INTO public.riwayat_stok (
                                item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                                referensi_tabel, referensi_id, keterangan, dibuat_oleh
                            ) VALUES (
                                :item_id, 'konsinyasi_retur_masuk', :qty, :sebelum, :sesudah,
                                'pelanggan', :cust_id, :keterangan, :user_id
                            )
                        ");
                        $stmtMutasi->execute([
                            'item_id' => $itemId,
                            'qty' => $qty,
                            'sebelum' => $stokSebelum,
                            'sesudah' => $stokSesudah,
                            'cust_id' => $id,
                            'keterangan' => $ket,
                            'user_id' => $userId
                        ]);
                    }
                } elseif ($konversiOpsi === 'beli_putus') {
                    // Opsi 2: Beli Putus Sisa Barang di Rak Toko
                    $metodeBeliPutus = (string)$this->input('metode_beli_putus', 'lunas');
                    if (!in_array($metodeBeliPutus, ['lunas', 'tempo'], true)) {
                        $metodeBeliPutus = 'lunas';
                    }

                    $akunKasId = null;
                    $akunKas = null;
                    if ($metodeBeliPutus === 'lunas') {
                        $akunKasId = $this->input('akun_kas_id');
                        if (empty($akunKasId)) {
                            $defaultKas = Database::fetchOne("SELECT id FROM public.akun_kas WHERE status_aktif = TRUE AND is_default_pos = TRUE LIMIT 1");
                            if ($defaultKas) {
                                $akunKasId = $defaultKas['id'];
                            } else {
                                throw new RuntimeException("Akun kas/bank penerima pembayaran beli putus wajib dipilih.");
                            }
                        }
                        $stmtKas = $pdo->prepare("
                            SELECT id, nama_akun, saldo_saat_ini 
                            FROM public.akun_kas 
                            WHERE id = :id AND status_aktif = TRUE 
                            FOR UPDATE
                        ");
                        $stmtKas->execute(['id' => $akunKasId]);
                        $akunKas = $stmtKas->fetch(PDO::FETCH_ASSOC);
                        if (!$akunKas) {
                            throw new RuntimeException("Akun kas/bank yang dipilih tidak ditemukan atau nonaktif.");
                        }
                    }

                    $totalBruto = 0.0;
                    $orderItemsData = [];
                    foreach ($shelfItems as $si) {
                        $hargaData = json_decode((string)($si['harga_info'] ?? ''), true) ?: [];
                        $hargaDeal = (float)($hargaData['harga_pcs_netto'] ?? 0);
                        $qty = (int)$si['stok_titip_saat_ini'];
                        $subtotal = $hargaDeal * $qty;
                        $totalBruto += $subtotal;

                        $orderItemsData[] = [
                            'item_id' => $si['item_id'],
                            'qty' => $qty,
                            'harga_deal' => $hargaDeal,
                            'subtotal' => $subtotal,
                            'harga_pokok' => (float)($si['harga_pokok'] ?? 0),
                            'stok_fisik' => (float)($si['stok_fisik_saat_ini'] ?? 0),
                        ];
                    }
                    $totalNetto = $totalBruto;
                    $totalNominalBeliPutus = $totalNetto;

                    // Generate nomor nota urut anti-collision
                    $nomorNota = DocumentNumber::nextOrderNumber($pdo);
                    $nomorNotaBeliPutus = $nomorNota;

                    $tanggalJatuhTempo = null;
                    $tipeBayarPesanan = $tipeBayar;
                    if ($metodeBeliPutus === 'lunas') {
                        $statusPembayaran = 'lunas';
                        $totalDibayar = $totalNetto;
                        $sisaTagihan = 0.0;
                        $uangDiterima = $totalNetto;
                        if ($tipeBayarPesanan === 'konsinyasi' || str_starts_with($tipeBayarPesanan, 'tempo')) {
                            $tipeBayarPesanan = 'cash';
                        }
                    } else {
                        $statusPembayaran = 'belum_lunas';
                        $totalDibayar = 0.0;
                        $sisaTagihan = $totalNetto;
                        $uangDiterima = 0.0;
                        $akunKasId = null;

                        $hariTempo = 14;
                        if ($tipeBayar === 'tempo_7_hari') {
                            $hariTempo = 7;
                        } elseif ($tipeBayar === 'tempo_30_hari') {
                            $hariTempo = 30;
                        }
                        $tanggalJatuhTempo = date('Y-m-d', strtotime("+{$hariTempo} days"));
                        if (!str_starts_with($tipeBayarPesanan, 'tempo')) {
                            $tipeBayarPesanan = 'tempo_14_hari';
                        }
                    }

                    $catatanPesanan = "Beli putus konversi toko konsinyasi ke " . strtoupper($tipeBayar);
                    if (!empty($catatanKonversi)) {
                        $catatanPesanan .= " - " . $catatanKonversi;
                    }

                    // Insert Header Pesanan
                    $stmtPesanan = $pdo->prepare("
                        INSERT INTO public.pesanan (
                            nomor_nota, pelanggan_id, sales_driver_id, tanggal_pesanan,
                            total_bruto, total_diskon, total_netto, tipe_pembayaran,
                            tanggal_jatuh_tempo, status_pembayaran, status_pemrosesan,
                            catatan, dibuat_oleh, akun_kas_id, total_dibayar, sisa_tagihan,
                            adalah_tagihan, uang_diterima, kembalian
                        ) VALUES (
                            :nomor_nota, :pelanggan_id, :sales_driver_id, CURRENT_DATE,
                            :total_bruto, 0, :total_netto, :tipe_pembayaran,
                            :tanggal_jatuh_tempo, :status_pembayaran, 'selesai',
                            :catatan, :dibuat_oleh, :akun_kas_id, :total_dibayar, :sisa_tagihan,
                            TRUE, :uang_diterima, 0
                        ) RETURNING id
                    ");
                    $stmtPesanan->execute([
                        'nomor_nota' => $nomorNota,
                        'pelanggan_id' => $id,
                        'sales_driver_id' => $salesDriverId,
                        'total_bruto' => $totalBruto,
                        'total_netto' => $totalNetto,
                        'tipe_pembayaran' => $tipeBayarPesanan,
                        'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                        'status_pembayaran' => $statusPembayaran,
                        'catatan' => $catatanPesanan,
                        'dibuat_oleh' => $userId,
                        'akun_kas_id' => $akunKasId,
                        'total_dibayar' => $totalDibayar,
                        'sisa_tagihan' => $sisaTagihan,
                        'uang_diterima' => $uangDiterima,
                    ]);
                    $pesananId = $stmtPesanan->fetchColumn();

                    // Insert Item Pesanan, Nolkan Rak, Catat Riwayat Stok
                    $stmtItemPesanan = $pdo->prepare("
                        INSERT INTO public.item_pesanan (
                            pesanan_id, item_id, kuantitas_satuan_dasar,
                            harga_satuan_deal, diskon_item_persen, diskon_item_nominal, is_bonus,
                            subtotal, harga_pokok_satuan
                        ) VALUES (
                            :pesanan_id, :item_id, :qty,
                            :harga_deal, 0, 0, FALSE,
                            :subtotal, :harga_pokok
                        )
                    ");

                    $stmtNolRak = $pdo->prepare("
                        UPDATE public.stok_konsinyasi_toko 
                        SET stok_titip_saat_ini = 0, terakhir_opname_pada = NOW(), diubah_pada = NOW() 
                        WHERE pelanggan_id = :cust_id AND item_id = :item_id
                    ");

                    $stmtMutasiBeli = $pdo->prepare("
                        INSERT INTO public.riwayat_stok (
                            item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                            referensi_tabel, referensi_id, keterangan, dibuat_oleh
                        ) VALUES (
                            :item_id, 'penjualan_keluar', 0, :sebelum, :sesudah,
                            'pesanan', :pesanan_id, :keterangan, :user_id
                        )
                    ");

                    foreach ($orderItemsData as $oid) {
                        $stmtItemPesanan->execute([
                            'pesanan_id' => $pesananId,
                            'item_id' => $oid['item_id'],
                            'qty' => $oid['qty'],
                            'harga_deal' => $oid['harga_deal'],
                            'subtotal' => $oid['subtotal'],
                            'harga_pokok' => $oid['harga_pokok'],
                        ]);

                        $stmtNolRak->execute([
                            'cust_id' => $id,
                            'item_id' => $oid['item_id'],
                        ]);

                        $stmtMutasiBeli->execute([
                            'item_id' => $oid['item_id'],
                            'sebelum' => $oid['stok_fisik'],
                            'sesudah' => $oid['stok_fisik'],
                            'pesanan_id' => $pesananId,
                            'keterangan' => "Beli putus sisa stok rak toko ({$nomorNota}) konversi ke {$tipeBayar}",
                            'user_id' => $userId,
                        ]);
                    }

                    // Finansial: Kas atau Piutang
                    if ($metodeBeliPutus === 'lunas') {
                        if ($totalNetto > 0 && !empty($akunKasId)) {
                            $saldoLama = (float)$akunKas['saldo_saat_ini'];
                            $saldoBaru = $saldoLama + $totalNetto;

                            $stmtUpdateKas = $pdo->prepare("
                                UPDATE public.akun_kas 
                                SET saldo_saat_ini = :saldo_baru, diubah_pada = NOW() 
                                WHERE id = :akun_id
                            ");
                            $stmtUpdateKas->execute(['saldo_baru' => $saldoBaru, 'akun_id' => $akunKasId]);

                            $stmtArusKas = $pdo->prepare("
                                INSERT INTO public.arus_kas (
                                    akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                                    keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh
                                ) VALUES (
                                    :akun_id, CURRENT_DATE, 'masuk', 'penjualan', :nominal,
                                    :keterangan, 'pesanan', :pesanan_id, :saldo_berjalan, :user_id
                                )
                            ");
                            $stmtArusKas->execute([
                                'akun_id' => $akunKasId,
                                'nominal' => $totalNetto,
                                'keterangan' => "Penerimaan beli putus sisa barang konsinyasi - {$namaToko} (Nota: {$nomorNota})",
                                'pesanan_id' => $pesananId,
                                'saldo_berjalan' => $saldoBaru,
                                'user_id' => $userId,
                            ]);
                        }
                    } else {
                        // Tambah piutang berjalan toko
                        if ($totalNetto > 0) {
                            $stmtPiutang = $pdo->prepare("
                                UPDATE public.pelanggan 
                                SET total_piutang_berjalan = total_piutang_berjalan + :nominal 
                                WHERE id = :cust_id
                            ");
                            $stmtPiutang->execute(['nominal' => $totalNetto, 'cust_id' => $id]);
                        }
                    }
                }
            }

            // Update Profil Master Data Pelanggan
            $stmtUpdateCust = $pdo->prepare("
                UPDATE public.pelanggan SET
                    nama_toko = :nama,
                    nama_pemilik = :pemilik,
                    grup_pelanggan_id = :grup,
                    is_konsinyasi = :konsinyasi,
                    wilayah_id = :wilayah,
                    sales_driver_id = :sales_driver_id,
                    alamat_lengkap = :alamat,
                    link_google_maps = :link_maps,
                    nomor_whatsapp = :wa,
                    tipe_pembayaran_default = :bayar,
                    plafon_piutang = :plafon,
                    nama_bank = :nama_bank,
                    nomor_rekening = :nomor_rek,
                    atas_nama_rekening = :atas_nama,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id
            ");
            $stmtUpdateCust->execute([
                'id' => $id,
                'nama' => $namaToko,
                'pemilik' => $namaPemilik ?: null,
                'grup' => $grupId,
                'konsinyasi' => $isKonsinyasi ? 'true' : 'false',
                'wilayah' => $wilayahId,
                'sales_driver_id' => $salesDriverId,
                'alamat' => $alamat,
                'link_maps' => $linkMaps,
                'wa' => $whatsapp ?: null,
                'bayar' => $tipeBayar,
                'plafon' => $plafon,
                'nama_bank' => $namaBank ?: null,
                'nomor_rek' => $nomorRekening ?: null,
                'atas_nama' => $atasNamaRekening ?: null,
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            $pdo->commit();

            $logDetail = "Memperbarui data toko pelanggan: {$namaToko} ({$currentCust['kode_pelanggan']})";
            if ($currentCust['is_konsinyasi'] && !$isKonsinyasi && $totalTitip > 0) {
                if ($konversiOpsi === 'retur') {
                    $logDetail .= " [Konversi Konsinyasi: Retur fisik {$totalTitip} pcs ke gudang pusat]";
                } elseif ($konversiOpsi === 'beli_putus') {
                    $logDetail .= " [Konversi Konsinyasi: Beli putus {$totalTitip} pcs senilai Rp " . number_format($totalNominalBeliPutus, 0, ',', '.') . " (Nota: {$nomorNotaBeliPutus})]";
                }
            }

            ActivityLog::log(
                'master_data',
                'UBAH_TOKO',
                $logDetail,
                'pelanggan',
                (string)$id
            );

            if ($currentCust['is_konsinyasi'] && !$isKonsinyasi && $totalTitip > 0) {
                if ($konversiOpsi === 'retur') {
                    $this->flashSuccess("Data toko {$namaToko} berhasil diperbarui menjadi non-konsinyasi. Sebanyak {$totalTitip} pcs stok konsinyasi telah diretur kembali ke gudang pusat.");
                } elseif ($konversiOpsi === 'beli_putus') {
                    $statusBayarLabel = ($metodeBeliPutus === 'lunas') ? 'LUNAS (Kas/Bank)' : 'TEMPO (Piutang Dagang)';
                    $this->flashSuccess("Data toko {$namaToko} berhasil diperbarui menjadi non-konsinyasi. Faktur Beli Putus {$nomorNotaBeliPutus} senilai Rp " . number_format($totalNominalBeliPutus, 0, ',', '.') . " berhasil diterbitkan ({$statusBayarLabel}). Faktur dapat dilihat & dicetak di menu Pesanan Pelanggan (/customer-orders).");
                }
            } else {
                $this->flashSuccess("Data toko {$namaToko} berhasil diperbarui!");
            }

            $this->redirect('/customers');

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal memperbarui toko: ' . $e->getMessage());
            $this->redirect('/customers');
        }
    }

    public function delete(): void
    {
        Auth::requirePermission('master.customers_manage');

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID toko tidak valid.');
            $this->redirect('/customers');
            return;
        }

        try {
            $custRow = Database::fetchOne("SELECT id, kode_pelanggan, nama_toko FROM public.pelanggan WHERE id = :id", ['id' => $id]);
            if (!$custRow) {
                $this->flashError('Data toko pelanggan tidak ditemukan.');
                $this->redirect('/customers');
                return;
            }

            // Proteksi Pelanggan Default POS (CUST-001 / Toko Umum / Walk-in Cash)
            $namaTokoUpper = strtoupper(trim($custRow['nama_toko']));
            if ($custRow['kode_pelanggan'] === 'CUST-001' || $namaTokoUpper === 'UMUM/CASH' || str_contains($namaTokoUpper, 'WALK-IN CASH') || str_contains($namaTokoUpper, 'TOKO UMUM')) {
                $this->flashError('Toko pelanggan default sistem (CUST-001 / Toko Umum / Walk-in Cash) terkunci permanen dan tidak dapat dihapus.');
                $this->redirect('/customers');
                return;
            }

            // 1. Cek stok konsinyasi aktif di rak toko
            $titipRow = Database::fetchOne("
                SELECT COALESCE(SUM(stok_titip_saat_ini), 0) as total_titip 
                FROM public.stok_konsinyasi_toko 
                WHERE pelanggan_id = :id
            ", ['id' => $id]);
            $totalTitip = (int)($titipRow['total_titip'] ?? 0);
            if ($totalTitip > 0) {
                $this->flashError("Toko ini masih memiliki {$totalTitip} pcs stok konsinyasi yang dititipkan. Lakukan opname penarikan barang konsinyasi terlebih dahulu atau ubah status toko menjadi nonaktif.");
                $this->redirect('/customers');
                return;
            }

            // 2. Cek tagihan/piutang berjalan yang belum lunas
            $unpaidRow = Database::fetchOne("
                SELECT COUNT(*) as total_unpaid, COALESCE(SUM(sisa_tagihan), 0) as total_piutang 
                FROM public.pesanan 
                WHERE pelanggan_id = :id AND status_pembayaran NOT IN ('lunas', 'dibatalkan')
            ", ['id' => $id]);
            $unpaidCount = (int)($unpaidRow['total_unpaid'] ?? 0);
            $totalPiutang = (float)($unpaidRow['total_piutang'] ?? 0);
            if ($unpaidCount > 0 || $totalPiutang > 0.01) {
                $formattedPiutang = number_format($totalPiutang, 0, ',', '.');
                $this->flashError("Toko ini memiliki {$unpaidCount} transaksi belum lunas dengan sisa piutang Rp {$formattedPiutang}. Selesaikan pelunasan terlebih dahulu atau ubah status toko menjadi nonaktif.");
                $this->redirect('/customers');
                return;
            }

            // 3. Cek riwayat transaksi pesanan
            $orders = (int)(Database::fetchOne("SELECT count(*) as total FROM public.pesanan WHERE pelanggan_id = :id", ['id' => $id])['total'] ?? 0);
            if ($orders > 0) {
                $this->flashError("Toko ini tidak dapat dihapus karena memiliki riwayat {$orders} transaksi pesanan/penjualan. Untuk menghentikan operasional, silakan ubah status toko menjadi nonaktif.");
                $this->redirect('/customers');
                return;
            }

            // 4. Cek riwayat kunjungan konsinyasi
            $visits = (int)(Database::fetchOne("SELECT count(*) as total FROM public.kunjungan_konsinyasi WHERE pelanggan_id = :id", ['id' => $id])['total'] ?? 0);
            if ($visits > 0) {
                $this->flashError("Toko ini tidak dapat dihapus karena memiliki riwayat {$visits} kunjungan konsinyasi. Silakan ubah status toko menjadi nonaktif.");
                $this->redirect('/customers');
                return;
            }

            // Jika bersih dari seluruh transaksi, lakukan penghapusan
            Database::execute("DELETE FROM public.pelanggan WHERE id = :id", ['id' => $id]);

            ActivityLog::log(
                'master_data',
                'HAPUS_TOKO',
                "Menghapus toko pelanggan: {$custRow['nama_toko']} ({$custRow['kode_pelanggan']})",
                'pelanggan',
                (string)$id
            );

            $this->flashSuccess('Toko pelanggan berhasil dihapus.');
            $this->redirect('/customers');

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus toko: ' . $e->getMessage());
            $this->redirect('/customers');
        }
    }

    // ==========================================
    // 2. DAFTAR ITEM KHUSUS TOKO (ITEM WHITELIST)
    // ==========================================
    public function saveCustomerItems(): void
    {
        Auth::requirePermission('master.customers_manage');

        $pelangganId = $this->input('pelanggan_id');
        $itemIds = $this->input('item_ids') ?? [];

        if (is_string($itemIds)) {
            $itemIds = json_decode($itemIds, true) ?: [];
        }

        if (empty($pelangganId)) {
            $this->flashError('ID toko pelanggan tidak valid.');
            $this->redirect('/customers');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Hapus item khusus sebelumnya
            $pdo->prepare("DELETE FROM public.pelanggan_item WHERE pelanggan_id = :cust_id")
                ->execute(['cust_id' => $pelangganId]);

            // Simpan item khusus baru jika ada yang dipilih (filter ID valid)
            if (!empty($itemIds) && is_array($itemIds)) {
                $stmt = $pdo->prepare("
                    INSERT INTO public.pelanggan_item (pelanggan_id, item_id, dibuat_pada)
                    VALUES (:cust_id, :item_id, NOW())
                    ON CONFLICT DO NOTHING
                ");

                foreach ($itemIds as $itemId) {
                    if (!empty($itemId) && is_string($itemId)) {
                        $stmt->execute(['cust_id' => $pelangganId, 'item_id' => $itemId]);
                    }
                }
            }

            $pdo->commit();

            $total = count($itemIds);
            ActivityLog::log(
                'master_data',
                'WHITELIST_ITEM_TOKO',
                "Mengatur {$total} item khusus untuk toko ID: {$pelangganId}",
                'pelanggan_item',
                (string)$pelangganId
            );

            $msg = $total > 0 
                ? "Daftar {$total} item khusus toko berhasil disimpan!" 
                : "Semua produk kini diizinkan untuk toko ini (Katalog Default).";

            $this->flashSuccess($msg);
            $this->redirect('/customers');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal menyimpan item khusus toko: ' . $e->getMessage());
            $this->redirect('/customers');
        }
    }

    // ==========================================
    // 3. MASTER WILAYAH & RUTE LOGISTIK (CRUD)
    // ==========================================
    public function storeTerritory(): void
    {
        Auth::requirePermission('master.territories_manage');

        $nama = trim((string)$this->input('nama_wilayah'));
        $rawKode = trim((string)$this->input('kode_rute'));
        $kota = trim((string)$this->input('kota_kabupaten', 'Bandung'));
        $provinsi = trim((string)$this->input('provinsi', 'Jawa Barat'));
        $sub = trim((string)$this->input('sub_wilayah', ''));

        if (empty($nama)) {
            $this->flashError('Nama wilayah wajib diisi.');
            $this->redirect('/customers?tab=territories');
            return;
        }

        if (empty($rawKode) || $rawKode === 'RTE-' || $rawKode === 'RUTE-') {
            $stmt = Database::getConnection()->query("
                SELECT kode_rute 
                FROM public.wilayah 
                WHERE kode_rute ~ '^(?:RTE|RUTE)-[0-9]+$' 
                ORDER BY CAST(SUBSTRING(kode_rute FROM 5) AS INTEGER) DESC 
                LIMIT 1
            ");
            $latest = $stmt->fetch();
            $nextSeq = 1;
            if ($latest && !empty($latest['kode_rute'])) {
                $num = (int)preg_replace('/[^0-9]/', '', (string)$latest['kode_rute']);
                $nextSeq = $num + 1;
            }

            do {
                $kode = 'RTE-' . str_pad((string)$nextSeq, 3, '0', STR_PAD_LEFT);
                $exists = (int)(Database::fetchOne("SELECT count(*) as total FROM public.wilayah WHERE kode_rute = :k", ['k' => $kode])['total'] ?? 0);
                if ($exists > 0) $nextSeq++;
            } while ($exists > 0);
        } else {
            $cleanCode = strtoupper((string)preg_replace('/[^A-Z0-9-]/', '', $rawKode));
            if (!str_starts_with($cleanCode, 'RTE-') && !str_starts_with($cleanCode, 'RUTE-')) {
                $cleanCode = 'RTE-' . $cleanCode;
            }
            $kode = substr($cleanCode, 0, 30);

            $checkDup = Database::fetchOne("SELECT id FROM public.wilayah WHERE kode_rute = :kode", ['kode' => $kode]);
            if ($checkDup) {
                $this->flashError("Kode rute {$kode} sudah terdaftar. Silakan gunakan kode rute lain.");
                $this->redirect('/customers?tab=territories');
                return;
            }
        }

        try {
            Database::execute("
                INSERT INTO public.wilayah (
                    kode_rute, nama_wilayah, kota_kabupaten, provinsi, sub_wilayah, status_aktif
                ) VALUES (
                    :kode, :nama, :kota, :provinsi, :sub, TRUE
                )
            ", [
                'kode' => $kode,
                'nama' => $nama,
                'kota' => $kota ?: '-',
                'provinsi' => $provinsi ?: '-',
                'sub' => $sub ?: null
            ]);

            ActivityLog::log(
                'master_data',
                'TAMBAH_WILAYAH',
                "Menambahkan wilayah/rute pengiriman baru: {$nama} ({$kode})",
                'wilayah'
            );

            $this->flashSuccess("Wilayah {$nama} ({$kode}) berhasil ditambahkan!");
            $this->redirect('/customers?tab=territories');

        } catch (Throwable $e) {
            $this->flashError('Gagal menambahkan wilayah: ' . $e->getMessage());
            $this->redirect('/customers?tab=territories');
        }
    }

    public function updateTerritory(): void
    {
        Auth::requirePermission('master.territories_manage');

        $id = $this->input('id');
        $nama = trim((string)$this->input('nama_wilayah'));
        $rawKode = trim((string)$this->input('kode_rute'));
        $cleanCode = strtoupper((string)preg_replace('/[^A-Z0-9-]/', '', $rawKode));
        if (!str_starts_with($cleanCode, 'RTE-') && !str_starts_with($cleanCode, 'RUTE-')) {
            $cleanCode = 'RTE-' . $cleanCode;
        }
        $kode = substr($cleanCode, 0, 30);
        $kota = trim((string)$this->input('kota_kabupaten', 'Bandung'));
        $provinsi = trim((string)$this->input('provinsi', 'Jawa Barat'));
        $sub = trim((string)$this->input('sub_wilayah', ''));
        $statusAktif = (bool)$this->input('status_aktif', true);

        if (empty($id) || empty($nama)) {
            $this->flashError('Parameter wilayah tidak lengkap.');
            $this->redirect('/customers?tab=territories');
            return;
        }

        $checkDup = Database::fetchOne("SELECT id FROM public.wilayah WHERE kode_rute = :kode AND id != :id", ['kode' => $kode, 'id' => $id]);
        if ($checkDup) {
            $this->flashError("Kode rute {$kode} sudah digunakan oleh wilayah lain.");
            $this->redirect('/customers?tab=territories');
            return;
        }

        try {
            Database::execute("
                UPDATE public.wilayah SET
                    kode_rute = :kode,
                    nama_wilayah = :nama,
                    kota_kabupaten = :kota,
                    provinsi = :provinsi,
                    sub_wilayah = :sub,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $id,
                'kode' => $kode,
                'nama' => $nama,
                'kota' => $kota,
                'provinsi' => $provinsi,
                'sub' => $sub ?: null,
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            ActivityLog::log(
                'master_data',
                'UBAH_WILAYAH',
                "Memperbarui wilayah/rute pengiriman: {$nama} ({$kode})",
                'wilayah',
                (string)$id
            );

            $this->flashSuccess("Wilayah {$nama} berhasil diperbarui!");
            $this->redirect('/customers?tab=territories');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui wilayah: ' . $e->getMessage());
            $this->redirect('/customers?tab=territories');
        }
    }

    public function deleteTerritory(): void
    {
        Auth::requirePermission('master.territories_manage');

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID wilayah tidak valid.');
            $this->redirect('/customers?tab=territories');
            return;
        }

        try {
            $territory = Database::fetchOne("SELECT id, nama_wilayah, kode_rute FROM public.wilayah WHERE id = :id", ['id' => $id]);
            if (!$territory) {
                $this->flashError('Data wilayah tidak ditemukan.');
                $this->redirect('/customers?tab=territories');
                return;
            }

            $usedPelanggan = (int)(Database::fetchOne("SELECT count(*) as total FROM public.pelanggan WHERE wilayah_id = :id", ['id' => $id])['total'] ?? 0);
            $usedPemasok = (int)(Database::fetchOne("SELECT count(*) as total FROM public.pemasok WHERE wilayah_id = :id", ['id' => $id])['total'] ?? 0);
            $usedSuratJalan = (int)(Database::fetchOne("SELECT count(*) as total FROM public.surat_jalan WHERE rute_wilayah_id = :id", ['id' => $id])['total'] ?? 0);

            if ($usedPelanggan > 0 || $usedPemasok > 0 || $usedSuratJalan > 0) {
                $parts = [];
                if ($usedPelanggan > 0) $parts[] = "{$usedPelanggan} toko pelanggan";
                if ($usedPemasok > 0) $parts[] = "{$usedPemasok} vendor pemasok";
                if ($usedSuratJalan > 0) $parts[] = "{$usedSuratJalan} riwayat pengiriman/surat jalan";
                $detail = implode(', ', $parts);
                $this->flashError("Wilayah ini tidak dapat dihapus karena sedang digunakan oleh {$detail}. Silakan ubah status wilayah menjadi nonaktif.");
                $this->redirect('/customers?tab=territories');
                return;
            }

            Database::execute("DELETE FROM public.wilayah WHERE id = :id", ['id' => $id]);

            ActivityLog::log(
                'master_data',
                'HAPUS_WILAYAH',
                "Menghapus wilayah/rute pengiriman: {$territory['nama_wilayah']} ({$territory['kode_rute']})",
                'wilayah',
                (string)$id
            );

            $this->flashSuccess('Wilayah berhasil dihapus.');
            $this->redirect('/customers?tab=territories');

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus wilayah: ' . $e->getMessage());
            $this->redirect('/customers?tab=territories');
        }
    }

    // ==========================================
    // 4. MASTER GRUP PELANGGAN & TIER (STORE, UPDATE, DELETE)
    // ==========================================
    public function storeGroup(): void
    {
        Auth::requirePermission('master.pricing_manage');

        $nama = trim((string)$this->input('nama_grup'));
        $kode = trim((string)$this->input('kode_grup'));
        $level = max(1, min(30, (int)$this->input('default_level_harga', 1)));
        $discPersen = max(0.0, (float)$this->input('diskon_persen_default', 0));
        $discNominal = max(0.0, (float)preg_replace('/[^0-9]/', '', (string)$this->input('diskon_nominal_default', '0')));

        if (empty($nama)) {
            $this->flashError('Nama grup pelanggan wajib diisi.');
            $this->redirect('/customers?tab=customer_groups');
            return;
        }

        if (empty($kode) || $kode === 'GRP-') {
            $kode = 'GRP-' . strtoupper((string)preg_replace('/[^A-Z0-9]/', '', substr($nama, 0, 6))) . '-' . $level;
        } else {
            $suffix = strtoupper((string)preg_replace('/[^A-Z0-9-]/', '', (string)preg_replace('/^GRP-?/i', '', $kode)));
            $suffix = substr($suffix, 0, 10);
            $kode = 'GRP-' . ($suffix ?: '01');
        }

        $redirectTarget = (string)$this->input('redirect_to', '/customers?tab=customer_groups');
        if (!str_starts_with($redirectTarget, '/') || str_starts_with($redirectTarget, '//')) {
            $redirectTarget = '/customers?tab=customer_groups';
        }

        $checkDup = Database::fetchOne("SELECT id FROM public.grup_pelanggan WHERE kode_grup = :kode", ['kode' => $kode]);
        if ($checkDup) {
            $this->flashError("Kode grup {$kode} sudah terdaftar. Silakan gunakan kode lain.");
            $this->redirect($redirectTarget);
            return;
        }

        try {
            Database::execute("
                INSERT INTO public.grup_pelanggan (
                    kode_grup, nama_grup, default_level_harga,
                    diskon_persen_default, diskon_nominal_default, status_aktif
                ) VALUES (
                    :kode, :nama, :level, :disc_p, :disc_n, TRUE
                )
            ", [
                'kode' => $kode,
                'nama' => $nama,
                'level' => $level,
                'disc_p' => $discPersen,
                'disc_n' => $discNominal
            ]);

            ActivityLog::log(
                'master_data',
                'TAMBAH_GRUP_PELANGGAN',
                "Menambahkan grup pelanggan baru: {$nama} ({$kode}) level {$level}",
                'grup_pelanggan'
            );

            $this->flashSuccess("Grup pelanggan {$nama} berhasil ditambahkan!");
            $this->redirect($redirectTarget);

        } catch (Throwable $e) {
            $this->flashError('Gagal menambahkan grup pelanggan: ' . $e->getMessage());
            $this->redirect($redirectTarget);
        }
    }

    public function updateGroup(): void
    {
        Auth::requirePermission('master.pricing_manage');

        $redirectTarget = (string)$this->input('redirect_to', '/customers?tab=customer_groups');
        if (!str_starts_with($redirectTarget, '/') || str_starts_with($redirectTarget, '//')) {
            $redirectTarget = '/customers?tab=customer_groups';
        }

        $id = $this->input('id');
        $nama = trim((string)$this->input('nama_grup'));
        $kode = trim((string)$this->input('kode_grup'));
        $level = max(1, min(30, (int)$this->input('default_level_harga', 1)));
        $discPersen = max(0.0, (float)$this->input('diskon_persen_default', 0));
        $discNominal = max(0.0, (float)preg_replace('/[^0-9]/', '', (string)$this->input('diskon_nominal_default', '0')));
        $statusAktif = (bool)$this->input('status_aktif', true);

        if (empty($id) || empty($nama)) {
            $this->flashError('Parameter tidak lengkap.');
            $this->redirect($redirectTarget);
            return;
        }

        if (empty($kode) || $kode === 'GRP-') {
            $kode = 'GRP-' . strtoupper((string)preg_replace('/[^A-Z0-9]/', '', substr($nama, 0, 6))) . '-' . $level;
        } else {
            $suffix = strtoupper((string)preg_replace('/[^A-Z0-9-]/', '', (string)preg_replace('/^GRP-?/i', '', $kode)));
            $suffix = substr($suffix, 0, 10);
            $kode = 'GRP-' . ($suffix ?: '01');
        }

        $checkDup = Database::fetchOne("SELECT id FROM public.grup_pelanggan WHERE kode_grup = :kode AND id != :id", ['kode' => $kode, 'id' => $id]);
        if ($checkDup) {
            $this->flashError("Kode grup {$kode} sudah digunakan oleh grup pelanggan lain.");
            $this->redirect($redirectTarget);
            return;
        }

        try {
            Database::execute("
                UPDATE public.grup_pelanggan SET
                    kode_grup = :kode,
                    nama_grup = :nama,
                    default_level_harga = :level,
                    diskon_persen_default = :disc_p,
                    diskon_nominal_default = :disc_n,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $id,
                'kode' => $kode,
                'nama' => $nama,
                'level' => $level,
                'disc_p' => $discPersen,
                'disc_n' => $discNominal,
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            ActivityLog::log(
                'master_data',
                'UBAH_GRUP_PELANGGAN',
                "Memperbarui grup pelanggan: {$nama} ({$kode}) level {$level}",
                'grup_pelanggan',
                (string)$id
            );

            $this->flashSuccess("Grup pelanggan {$nama} berhasil diperbarui!");
            $this->redirect($redirectTarget);

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui grup pelanggan: ' . $e->getMessage());
            $this->redirect($redirectTarget);
        }
    }

    public function deleteGroup(): void
    {
        Auth::requirePermission('master.pricing_manage');

        $redirectTarget = (string)$this->input('redirect_to', '/customers?tab=customer_groups');
        if (!str_starts_with($redirectTarget, '/') || str_starts_with($redirectTarget, '//')) {
            $redirectTarget = '/customers?tab=customer_groups';
        }

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID grup pelanggan tidak valid.');
            $this->redirect($redirectTarget);
            return;
        }

        try {
            $group = Database::fetchOne("SELECT id, nama_grup, kode_grup FROM public.grup_pelanggan WHERE id = :id", ['id' => $id]);
            if (!$group) {
                $this->flashError('Data grup pelanggan tidak ditemukan.');
                $this->redirect($redirectTarget);
                return;
            }

            // Minimal 1 grup di sistem
            $totalCount = (int)(Database::fetchOne("SELECT count(*) as total FROM public.grup_pelanggan")['total'] ?? 0);
            if ($totalCount <= 1) {
                $this->flashError("Sistem wajib memiliki minimal 1 grup pelanggan.");
                $this->redirect($redirectTarget);
                return;
            }

            $usedCount = (int)(Database::fetchOne("SELECT count(*) as total FROM public.pelanggan WHERE grup_pelanggan_id = :id", ['id' => $id])['total'] ?? 0);
            if ($usedCount > 0) {
                $this->flashError("Grup pelanggan ini sedang digunakan oleh {$usedCount} toko pelanggan dan tidak dapat dihapus.");
                $this->redirect($redirectTarget);
                return;
            }

            Database::execute("DELETE FROM public.grup_pelanggan WHERE id = :id", ['id' => $id]);

            ActivityLog::log(
                'master_data',
                'HAPUS_GRUP_PELANGGAN',
                "Menghapus grup pelanggan: {$group['nama_grup']} ({$group['kode_grup']})",
                'grup_pelanggan',
                (string)$id
            );

            $this->flashSuccess('Grup pelanggan berhasil dihapus.');
            $this->redirect($redirectTarget);

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus grup pelanggan: ' . $e->getMessage());
            $this->redirect($redirectTarget);
        }
    }
}

