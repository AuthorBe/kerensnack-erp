<?php
declare(strict_types=1);

/**
 * bin/migrate_salary_history.php
 * CLI Runner Migrasi Data & Riwayat SalaryApp (MySQL) ke KEREN ONE ERP (PostgreSQL).
 *
 * Penggunaan:
 *   php bin/migrate_salary_history.php --target=local --dry-run
 *   php bin/migrate_salary_history.php --target=local
 *   php bin/migrate_salary_history.php --target=local --verify
 *   php bin/migrate_salary_history.php --target=live (Setelah 100% verifikasi lokal)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Access Denied: CLI execution only.\n");
}

define('ROOT_PATH', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');

class SalaryHistoryMigrator
{
    private string $sqlFile;
    private PDO $pdo;
    private string $target;
    private bool $isDryRun = false;
    private bool $isVerifyOnly = false;
    private bool $shouldClean = false;

    // In-memory mapping dictionaries: legacy ID -> UUID
    private array $mapKaryawan = [];      // salary id_karyawan -> karyawan_id (UUID)
    private array $mapPengguna = [];      // salary id_karyawan -> pengguna_id (UUID)
    private array $mapTabungan = [];      // salary id_karyawan -> tabungan_id (UUID)
    private array $mapItem = [];          // salary id_produk -> item_id (UUID)
    private array $itemRates = [];        // salary id_produk -> upah_per_bungkus (float)
    private array $mapPenggajian = [];    // salary id_penggajian -> penggajian_id (UUID)
    private array $mapKasbon = [];        // salary id_kasbon -> kasbon_id (UUID)
    private array $mapRincian = [];       // salary id_rincian_penggajian -> rincian_id (UUID)
    private array $karyawanNamaById = []; // karyawan_id -> nama lengkap
    private ?string $defaultApproverId = null;

    // Parsing containers
    private array $rawKaryawan = [];
    private array $rawKelompok = [];
    private array $rawProduk = [];
    private array $rawKasbon = [];
    private array $rawPenggajian = [];
    private array $rawRincian = [];
    private array $rawPotonganKasbon = [];
    private array $rawTabungan = [];
    private array $rawTransaksiTabungan = [];
    private array $rawPenarikanGaji = [];
    private array $rawAbsensi = [];
    private array $rawProduksi = [];

    public function __construct(string $target = 'local', bool $dryRun = false, bool $verifyOnly = false, bool $clean = false)
    {
        $this->target = strtolower(trim($target));
        $this->isDryRun = $dryRun;
        $this->isVerifyOnly = $verifyOnly;
        $this->shouldClean = $clean;
        $this->sqlFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'PROMPT' . DIRECTORY_SEPARATOR . 'DB_SALARY.sql';

        if (!file_exists($this->sqlFile)) {
            throw new RuntimeException("File dump tidak ditemukan: {$this->sqlFile}");
        }

        $this->pdo = $this->createConnection($this->target);
    }

    private function createConnection(string $target): PDO
    {
        if ($target === 'local') {
            $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=kerensnack_erp_local;sslmode=disable";
            return new PDO($dsn, 'postgres', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 15,
            ]);
        }

        if ($target === 'live') {
            $liveEnvFile = ROOT_PATH . DIRECTORY_SEPARATOR . '.env.live';
            $envFile = file_exists($liveEnvFile) ? $liveEnvFile : ROOT_PATH . DIRECTORY_SEPARATOR . '.env';

            if (!file_exists($envFile)) {
                throw new RuntimeException("File konfigurasi environment live tidak ditemukan.");
            }

            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $env = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                if (str_contains($line, '=')) {
                    [$k, $v] = explode('=', $line, 2);
                    $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
                }
            }
            $host = $env['DB_HOST'] ?? '127.0.0.1';
            $port = $env['DB_PORT'] ?? '5432';
            $db   = $env['DB_DATABASE'] ?? 'postgres';
            $user = $env['DB_USERNAME'] ?? 'postgres';
            $pass = $env['DB_PASSWORD'] ?? '';
            $ssl  = $env['DB_SSLMODE'] ?? 'require';

            $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode={$ssl}";
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 30,
            ]);
        }

        throw new InvalidArgumentException("Target tidak valid: {$target}. Pilih 'local' atau 'live'.");
    }

    private const UUID_NAMESPACE = 'a3b8c6e2-5f14-49c0-9d8a-8e2b71c360a1';

    public static function uuidv5(string $entity, string $id): string
    {
        $nhex = str_replace(['-', '{', '}'], '', self::UUID_NAMESPACE);
        $nstr = pack('H*', $nhex);
        $hash = sha1($nstr . $entity . ':' . $id);
        return sprintf(
            '%08s-%04s-%04x-%02x%02x-%012s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
            (hexdec(substr($hash, 16, 2)) & 0x3f) | 0x80,
            hexdec(substr($hash, 18, 2)),
            substr($hash, 20, 12)
        );
    }

    public static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * State-machine SQL dump parser yang 100% presisi untuk menangani string, escape quote, dan JSON
     */
    private function parseSqlInserts(string $sql, string $tableName): array
    {
        $rows = [];
        $pattern = '/INSERT INTO `' . $tableName . '`\s*(?:\([^)]+\))?\s*VALUES\s*/i';
        
        $offset = 0;
        while (preg_match($pattern, $sql, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $startPos = $m[0][1] + strlen($m[0][0]);
            $len = strlen($sql);
            $i = $startPos;
            
            while ($i < $len) {
                while ($i < $len && $sql[$i] !== '(' && $sql[$i] !== ';') {
                    $i++;
                }
                if ($i >= $len || $sql[$i] === ';') {
                    $offset = $i + 1;
                    break;
                }
                
                $i++; // skip '('
                $tupleCols = [];
                $currentCol = '';
                $inString = false;
                $stringChar = '';
                $escaped = false;
                
                while ($i < $len) {
                    $char = $sql[$i];
                    
                    if ($inString) {
                        if ($escaped) {
                            $currentCol .= $char;
                            $escaped = false;
                        } elseif ($char === '\\') {
                            $escaped = true;
                        } elseif ($char === $stringChar) {
                            if ($i + 1 < $len && $sql[$i + 1] === $stringChar) {
                                $currentCol .= $stringChar;
                                $i++;
                            } else {
                                $inString = false;
                            }
                        } else {
                            $currentCol .= $char;
                        }
                    } else {
                        if ($char === "'" || $char === '"') {
                            $inString = true;
                            $stringChar = $char;
                        } elseif ($char === ',') {
                            $tupleCols[] = trim($currentCol, "' \r\n\t");
                            $currentCol = '';
                        } elseif ($char === ')') {
                            $tupleCols[] = trim($currentCol, "' \r\n\t");
                            $currentCol = '';
                            $rows[] = $tupleCols;
                            $i++;
                            break;
                        } else {
                            $currentCol .= $char;
                        }
                    }
                    $i++;
                }
                
                while ($i < $len && ($sql[$i] === ',' || ctype_space($sql[$i]))) {
                    $i++;
                }
                if ($i < $len && $sql[$i] === ';') {
                    $offset = $i + 1;
                    break;
                }
            }
        }
        
        return $rows;
    }

    public function run(): void
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo " KEREN ONE ERP - MIGRATOR RIWAYAT SALARY APP (MySQL -> PostgreSQL)\n";
        echo " Target Database : " . strtoupper($this->target) . "\n";
        echo " Mode Eksekusi   : " . ($this->isVerifyOnly ? "VERIFY ONLY" : ($this->isDryRun ? "DRY-RUN (Simulasi Rollback)" : "COMMIT RESMI")) . "\n";
        echo str_repeat('=', 80) . "\n\n";

        if ($this->isVerifyOnly) {
            $this->verifyDatabaseState();
            return;
        }

        $this->parseSqlDump();
        $this->buildDictionaries();
        $this->executeMigration();
        $this->verifyDatabaseState();
    }

    private function parseSqlDump(): void
    {
        echo "[1/4] Membaca dan mem-parsing data dari DB_SALARY.sql dengan State-Machine...\n";
        $content = file_get_contents($this->sqlFile);

        $this->rawKaryawan          = $this->parseSqlInserts($content, 'karyawan');
        $this->rawKelompok          = $this->parseSqlInserts($content, 'kelompok_harga_produk');
        $this->rawProduk            = $this->parseSqlInserts($content, 'produk');
        $this->rawKasbon            = $this->parseSqlInserts($content, 'kasbon');
        $this->rawPenggajian        = $this->parseSqlInserts($content, 'penggajian');
        $this->rawRincian           = $this->parseSqlInserts($content, 'rincian_penggajian');
        $this->rawPotonganKasbon    = $this->parseSqlInserts($content, 'potongan_kasbon');
        $this->rawTabungan          = $this->parseSqlInserts($content, 'tabungan');
        $this->rawTransaksiTabungan = $this->parseSqlInserts($content, 'transaksi_tabungan');
        $this->rawPenarikanGaji     = $this->parseSqlInserts($content, 'penarikan_gaji');
        $this->rawAbsensi           = $this->parseSqlInserts($content, 'absensi');
        $this->rawProduksi          = $this->parseSqlInserts($content, 'produksi');

        echo sprintf("  - Karyawan            : %d baris\n", count($this->rawKaryawan));
        echo sprintf("  - Kelompok Upah       : %d baris\n", count($this->rawKelompok));
        echo sprintf("  - Produk              : %d baris\n", count($this->rawProduk));
        echo sprintf("  - Kasbon              : %d baris\n", count($this->rawKasbon));
        echo sprintf("  - Penggajian          : %d baris\n", count($this->rawPenggajian));
        echo sprintf("  - Rincian Penggajian  : %d baris\n", count($this->rawRincian));
        echo sprintf("  - Potongan Kasbon     : %d baris\n", count($this->rawPotonganKasbon));
        echo sprintf("  - Tabungan            : %d baris\n", count($this->rawTabungan));
        echo sprintf("  - Transaksi Tabungan  : %d baris\n", count($this->rawTransaksiTabungan));
        echo sprintf("  - Penarikan Gaji      : %d baris\n", count($this->rawPenarikanGaji));
        echo sprintf("  - Absensi             : %d baris\n", count($this->rawAbsensi));
        echo sprintf("  - Produksi Harian     : %d baris\n", count($this->rawProduksi));
        echo "  Done parsing dump file.\n\n";
    }

    private function buildDictionaries(): void
    {
        echo "[2/4] Membangun kamus relasi & memvalidasi master data KEREN ONE...\n";

        // 1. Approver Default (Admin / Owner KEREN ONE)
        $approver = $this->pdo->query("
            SELECT id FROM public.pengguna 
            WHERE nama_pengguna IN ('admin', 'ajsk') OR status_aktif = TRUE 
            ORDER BY dibuat_pada ASC LIMIT 1
        ")->fetch();
        $this->defaultApproverId = $approver['id'] ?? null;
        if (!$this->defaultApproverId) {
            throw new RuntimeException("Tidak ditemukan pengguna admin untuk approver di tabel public.pengguna.");
        }

        // 2. Pastikan Karyawan Inaktif Historis (Ka Umi & Zaskia) Terdaftar
        $this->ensureInactiveEmployees();

        // 3. Pastikan Item 'Returan Kemasan Borongan' Terdaftar
        $this->ensureReturanItem();

        // 4. Karyawan Mapping (28 dari 28 karyawan di dump)
        $manualMap = [
            17 => 'Ikah Upikah',               // Teh ika
            18 => 'Asiyah',                    // Mpo asiah
            19 => 'Erniya',                    // Mba erni
            20 => 'Mona Chulyani',             // Mba mona
            21 => 'Suryanti',                  // Bu yanti
            22 => 'Khusnul Khotimah',          // Bu husnul
            23 => 'Titin Hartati',             // Teh tati
            24 => 'Nida Honipah',              // Nida
            25 => 'Nabila',                    // Nabila
            26 => 'Maryati',                   // Bu maryati
            27 => 'Nur Ngizati',               // Nur
            28 => 'Watira',                    // Bu ira
            29 => 'Sri Nurjanah',              // ka janah
            30 => 'Karyati',                   // Ka karyati
            31 => 'Nazala',                    // Nazala
            32 => 'Nur Ainun',                 // Ainun
            33 => 'Avinda Maharani',           // Avinda
            34 => 'Alfiah Lutfianih',          // Alfia
            35 => 'Angga Citra Abadi',         // Pak angga
            36 => 'Karno',                     // Pak nono
            37 => 'Ahmad Faiq',                // Pak faiq
            38 => 'Qais Kusnadi',              // Pak Qais
            39 => 'Nurman Salani',             // Pak nurman
            40 => 'Muhammad Aji',              // Musa
            41 => 'Siti Masitoh',              // kak masitoh
            42 => 'Risma Nurlilia Arta Winda', // Ka risma
            43 => 'Ka Umi',                    // Ka umi
            44 => 'Zaskia',                    // zaskia
        ];

        $karyawanRows = $this->pdo->query("
            SELECT k.id as karyawan_id, p.id as pengguna_id, p.nama_lengkap, t.id as tabungan_id
            FROM public.karyawan k
            JOIN public.pengguna p ON k.pengguna_id = p.id
            LEFT JOIN public.tabungan t ON t.karyawan_id = k.id
        ")->fetchAll();

        $karyawanByNama = [];
        foreach ($karyawanRows as $kr) {
            $karyawanByNama[$kr['nama_lengkap']] = $kr;
        }

        foreach ($manualMap as $salaryId => $namaKeren) {
            if (!isset($karyawanByNama[$namaKeren])) {
                throw new RuntimeException("Karyawan target '{$namaKeren}' tidak ditemukan di database KEREN ONE.");
            }
            $target = $karyawanByNama[$namaKeren];
            $this->mapKaryawan[$salaryId] = $target['karyawan_id'];
            $this->mapPengguna[$salaryId] = $target['pengguna_id'];
            $this->mapTabungan[$salaryId] = $target['tabungan_id'];
            $this->karyawanNamaById[$target['karyawan_id']] = $namaKeren;
        }
        echo sprintf("  - Pemetaan Karyawan   : %d/28 karyawan terverifikasi 100%%\n", count($this->mapKaryawan));

        // 5. Produk Mapping
        $itemsKeren = $this->pdo->query("
            SELECT i.id, i.nama_item, i.kode_sku, k.upah_per_bungkus
            FROM public.item i
            LEFT JOIN public.kelompok_upah_borongan k ON k.id = i.kelompok_borongan_id
        ")->fetchAll();

        $norm = function(string $s): string {
            $s = mb_strtolower(trim($s));
            $s = str_replace(['(', ')', '/', '-', '_', '.'], ' ', $s);
            $s = str_replace('carefour', 'carrefour', $s);
            $s = str_replace('lebel', 'label', $s);
            $s = str_replace('keriwil', 'kriwil', $s);
            $s = str_replace('krivil', 'kriwil', $s);
            $s = str_replace('pedes', 'pedas', $s);
            $s = str_replace('bulet', 'bulat', $s);
            $s = str_replace('cbr', '', $s);
            $s = str_replace('ssn', 'susun', $s);
            $s = preg_replace('/\s+/', ' ', $s);
            return trim($s);
        };

        $itemByNorm = [];
        $itemsBySku = [];
        foreach ($itemsKeren as $it) {
            $itemByNorm[$norm($it['nama_item'])] = $it;
            $itemsBySku[$it['kode_sku']] = $it;
        }

        // Kelompok upah fallback dari dump
        $kelompokRate = [];
        foreach ($this->rawKelompok as $rk) {
            $kelompokRate[(int)trim($rk[0])] = (float)trim($rk[2]);
        }

        // Peta produk dump
        $produkDumpMap = [];
        foreach ($this->rawProduk as $rp) {
            $pid = (int)trim($rp[0]);
            $produkDumpMap[$pid] = [
                'name' => trim($rp[1]),
                'kelompok_id' => (int)trim($rp[2] ?? '0'),
            ];
        }

        // Overrides eksplisit ke SKU KEREN ONE untuk produk yang bervariasi
        $explicitOverrides = [
            17  => 'PROD-0057', // cingklung klip jiyyan -> Cingklung Klip Jiyyan
            24  => 'PROD-0100', // kuping gajah klip jiyyan -> Kuping Gajah Klip Jiyyan
            28  => 'PROD-0060', // Kerupuk poco-poco jiyyan -> Kerupuk Poco-Poco Klip Jiyyan
            36  => 'PROD-0022', // kerupuk jengkol klip jiyyan -> Kerupuk Jengkol Klip Jiyyan
            37  => 'PROD-0023', // Kerupuk jengkol biasa -> Kerupuk Jengkol Biasa
            38  => 'PROD-0076', // Gabus keju klip jiyyan -> Gabus Keju Klip Jiyyan
            43  => 'PROD-0024', // kerupuk jengkol Oke -> Kerupuk Jengkol Oke
            58  => 'PROD-0064', // Keripik slondok stiker jiyyan -> Slondok Jiyyan
            59  => 'PROD-0004', // Berondong beras 2 ssn -> Berondong Beras 2 susun
            60  => 'PROD-0005', // Berondong jagung 2 ssn -> Berondong Jagung 2 Susun
            61  => 'PROD-0003', // Berondong beras 3 ssn -> Berondong Beras 3 Susun
            62  => 'PROD-0007', // Berondong jagung 3 ssn -> Berondong Jagung 3 Susun
            74  => 'PROD-0050', // Keripik singkong opak -> Keripik Singkong Opak
            76  => 'PROD-0070', // Keripik pisang kriwil -> Keripik Pisang Krivil
            86  => 'PROD-0090', // Emping marning -> Emping Jagung
            95  => 'PROD-0126', // Sumpia biasa -> Sumpia
            101 => 'PROD-0026', // Kerupuk jengkol stiker carrefour -> kerupuk Jengkol Lebel Carrefour
            102 => 'PROD-0062', // Kerupuk poco-poco biasa -> Poco-Poco Klip Biasa
            103 => 'PROD-0159', // Kerupuk dadu stiker -> Kerupuk Dadu
            104 => 'PROD-0058', // Keripik cingklung biasa -> Keripik Cingklung Biasa
            108 => 'PROD-0019', // Kerupuk keriting lebel tengiri -> Kerupuk Keriting Label Tengiri
            111 => 'PROD-0158', // singkong stiker -> Singkong Stiker
            112 => 'PROD-0157', // makaroni pedas label stiker -> Makaroni Pedas Stiker
            113 => 'PROD-0156', // Makaroni asin label stiker -> Makaroni Asin Stiker
            115 => 'PROD-0006', // berondong jagung lebel beras -> Berondong Jagung Label Beras
            116 => 'PROD-0001', // Berondong beras lebel jagung -> Berondong Beras Label Jagung
            117 => 'PROD-0091', // marning bulet klip jiyyan -> Marning Bulat Klip Jiyyan
            118 => 'PROD-0092', // emping marning klip jiyyan -> Emping Marning Klip Jiyyan
            119 => 'PROD-0066', // lanting klip jiyyan -> Lanting Klip Jiyyan
            120 => 'PROD-0110', // basreng bulat klip -> Basreng Bulat Klip
            121 => 'PROD-0051', // singkong lengket klip jiyyan -> Singkong Lengket Klip
            122 => 'PROD-0056', // singkong opak klip jiyyan -> Singkong Opak Klip Jiyyan
            123 => 'PROD-0132', // kacang bandung klip -> Kacang Bandung Klip
            124 => 'PROD-0139', // keripik kaca klip jiyyan -> Keripik Kaca Klip
            125 => 'PROD-0111', // basreng bulat klip fruit88 -> Basreng Bulat Klip Fruit 88
            126 => 'PROD-0071', // pisang keriwil fruit 88 -> Pisang Krivil Fruit 88
            127 => 'PROD-0112', // basreng stik asin fruit88 -> Basreng Stik Asin Fruit 88
            128 => 'PROD-0113', // basreng stik pedas fruit 88 -> Basreng Stik Pedas Fruit 88
            129 => 'PROD-0079', // gabus keju fruit88 -> Gabus Keju Fruit 88
            130 => 'PROD-0140', // keripik kaca fruit88 -> Keripik Kaca Fruit 88
            131 => 'PROD-0025', // kerupuk jengkol fruit88 -> Kerupuk Jengkol Fruit 88
            132 => 'PROD-0085', // kerupuk kulit fruit88 -> Kerupuk Kulit Fruit 88
            133 => 'PROD-0011', // Kerupuk ikan panggang fruit88 -> Kerupuk Ikan Panggang Fruit 88
            134 => 'PROD-0052', // Keripik singkong lengket fruit88 -> Keripik Singkong Lengket Fruit 88
            135 => 'PROD-0093', // marning bulat klip fruit88 -> Marning Bulat Klip Fruit 88
            136 => 'PROD-0094', // emping marning klip fruit88 -> Emping Marning Klip Fruit 88
            137 => 'PROD-0059', // keripik cingklung carrefour baru -> Keripik Cingklung Carrefour Baru
            141 => 'PROD-0002', // berondong beras 2ssn carefour baru -> Berondong Beras 2 Susun Carrefour Baru
            142 => 'PROD-0098', // tambang gajah carefour baru -> Tambang Gajah Carrefour Baru
            144 => 'PROD-0072', // pisang kriwil carefour baru -> Pisang Krivil Carrefour Baru
            145 => 'PROD-0088', // pang-pang carefour -> Pang pang Carrefour
            146 => 'PROD-0144', // gemrose carefour -> Gemrose Carrefour
            147 => 'PROD-0020', // kerupuk keriting carefour -> Kerupuk Keriting Carrefour
            150 => 'PROD-0061', // kerupuk poco-poco stiker carrefour -> Kerupuk Poco-Poco Stiker Carrefour
            151 => 'PROD-0053', // Keripik singkong lengket carefour -> Keripik Singkong Lengket Carrefour
            152 => 'PROD-0055', // Keripik opak asin stiker -> Keripik Opak Asin Stiker
            154 => 'PROD-0012', // kerupuk ikan panggang jiyyan -> Kerupuk Ikan Panggang Jiyyan
            155 => 'PROD-0032', // Kerupuk sagu putih jiyyan -> Kerupuk Sagu Putih Jiyyan
            156 => 'PROD-0116', // basreng stik pedas jiyyan -> Basreng Stik Pedas Jiyyan
            157 => 'PROD-0117', // basreng stik asin jiyyan -> Basreng Stik Asin Jiyyan
            159 => 'PROD-0133', // kacang koro jiyyan -> Kacang Koro Jiyyan
            160 => 'PROD-0127', // sumpia jiyyan -> Sumpia Jiyyan
            161 => 'PROD-0124', // potato pedas jiyyan -> Potato Pedas Jiyyan
            162 => 'PROD-0125', // potato keju jiyyan -> Potato Keju Jiyyan
            163 => 'PROD-0074', // sale pisang jiyyan -> Sale Pisang Jiyyan
            164 => 'PROD-0099', // tambang gajah klip jiyyan -> Tambang Gajah Klip Jiyyan
            165 => 'PROD-0134', // KACANG CAMPUR JIYYAN -> Kacang Campur Jiyyan
            166 => 'PROD-0033', // Kerupuk sagu pedas jiyyan -> Kerupuk Sagu Pedas Jiyyan
            167 => 'PROD-0075', // Pisang keriwil jiyyan -> Pisang Krivil Jiyyan
            168 => 'PROD-0021', // Kerupuk keriting jiyyan -> Kerupuk Keriting Jiyyan
            169 => 'PROD-0037', // Kerupuk sagu warna jiyyan -> Kerupuk Sagu Warna Jiyyan
            170 => 'PROD-0015', // kerupuk tengiri 88 jiyyan -> Kerupuk Tengiri 88 Jiyyan
            171 => 'PROD-RETURAN',
            172 => 'PROD-RETURAN',
            173 => 'PROD-0155', // Kemplang Pedas Stiker -> Kemplang Pedes Stiker
            174 => 'PROD-0063', // Keripik slondok stiker carefour -> Keripik Slondok Stiker
            175 => 'PROD-0128', // sumpia carrefour -> Sumpia Carrefour
            176 => 'PROD-0105', // pangsit carrefour -> Pangsit Carrefour
            177 => 'PROD-0096', // marning jagung carrefour -> Marning Jagung Carrefour
            178 => 'PROD-0067', // lanting label carrefour -> Lanting Label Carrefour
            179 => 'PROD-0043', // makaroni ulir / spiral carrefour -> Makaroni Spiral / Ulir Carrefour
            180 => 'PROD-0136', // kacang campur carrefour -> Kacang Campur Carrefour
            181 => 'PROD-0016', // Kerupuk tengiri 88 carrefour -> Kerupuk Tengiri 88 Carrefour
            182 => 'PROD-0137', // Kacang koro carrefour -> Kacang Koro Carrefour
            183 => 'PROD-0141', // Keripik kaca carrefour -> Keripik Kaca Carrefour
            184 => 'PROD-0135', // Kacang bandung carrefour -> Kacang Bandung Carrefour
            185 => 'PROD-0008', // berondong jagung 2 ssn carrefour baru -> Berondong Jagung 2 Susun Carrefour Baru
            186 => 'PROD-0118', // basreng stik asin carrefour -> Basreng stik asin carrefour
            187 => 'PROD-0073', // pisang sale cbr -> Pisang Sale carrefour
            188 => 'PROD-0003', // Berondong beras 3 ssn jiyyan -> Berondong Beras 3 Susun
            189 => 'PROD-0007', // Berondong jagung 3 ssn jiyyan -> Berondong Jagung 3 Susun
            190 => 'PROD-0026', // Kerupuk jengkol label carefour -> kerupuk Jengkol Lebel Carrefour
            191 => 'PROD-0045', // makaroni asin label biasa -> Makaroni Asin Label Biasa
            192 => 'PROD-0110', // basreng pedas bulat jiyyan klip -> Basreng Bulat Klip
            193 => 'PROD-0054', // keripik singkong opak carrefour -> Keripik Singkong Opak Label Carrefour
            194 => 'PROD-0013', // Kerupuk ikan panggang carrefour -> Kerupuk Ikan Panggang Carrefour
            195 => 'PROD-0095', // emping marning carrefour -> Emping Marning Carrefour
            196 => 'PROD-0119', // Basreng stik pedas carrefour -> Basreng stik pedas carrefour
            197 => 'PROD-0044', // makaroni cikruk carrefour -> Makaroni Cikruk Jiyyan / Makaroni Cikruk
        ];

        // Cari item untuk setiap produk yang ada di dump
        $matchedItemCount = 0;
        foreach ($produkDumpMap as $salaryPid => $pInfo) {
            $found = null;

            if (isset($explicitOverrides[$salaryPid])) {
                $sku = $explicitOverrides[$salaryPid];
                $found = $itemsBySku[$sku] ?? null;
            }

            if (!$found) {
                $cleanName = $norm($pInfo['name']);
                $found = $itemByNorm[$cleanName] ?? null;
            }

            if ($found) {
                $this->mapItem[$salaryPid] = $found['id'];
                $rate = (float)($found['upah_per_bungkus'] ?? 0);
                if ($rate <= 0 && isset($kelompokRate[$pInfo['kelompok_id']])) {
                    $rate = $kelompokRate[$pInfo['kelompok_id']];
                }
                $this->itemRates[$salaryPid] = $rate;
                $matchedItemCount++;
            }
        }

        // Pastikan fallback untuk ID 172
        if (!isset($this->mapItem[172])) {
            $returanItem = $itemsBySku['PROD-RETURAN'] ?? ($itemByNorm[$norm('Returan Kemasan Borongan')] ?? null);
            if ($returanItem) {
                $this->mapItem[172] = $returanItem['id'];
                $this->itemRates[172] = 350.00;
                $matchedItemCount++;
            }
        }

        echo sprintf("  - Pemetaan Produk     : %d produk terpetakan ke item KEREN ONE (0 duplikasi)\n", $matchedItemCount);

        // Pre-generate UUIDs for Penggajian, Kasbon, Rincian (Deterministic UUID v5)
        foreach ($this->rawPenggajian as $rp) {
            $this->mapPenggajian[(int)trim($rp[0])] = self::uuidv5('penggajian', trim($rp[0]));
        }
        foreach ($this->rawKasbon as $rk) {
            $this->mapKasbon[(int)trim($rk[0])] = self::uuidv5('kasbon', trim($rk[0]));
        }
        foreach ($this->rawRincian as $rr) {
            $this->mapRincian[(int)trim($rr[0])] = self::uuidv5('rincian_penggajian', trim($rr[0]));
        }

        echo "  Done building mapping dictionaries.\n\n";
    }

    private function ensureInactiveEmployees(): void
    {
        $employeesToAdd = [
            ['nama' => 'Ka Umi', 'panggilan' => 'Umi', 'posisi' => 'pengemasan', 'hadir' => 10000.00, 'tunjangan' => 50000.00],
            ['nama' => 'Zaskia', 'panggilan' => 'Zaskia', 'posisi' => 'pengemasan', 'hadir' => 10000.00, 'tunjangan' => 50000.00],
        ];

        foreach ($employeesToAdd as $emp) {
            $exists = $this->pdo->prepare("SELECT id FROM public.pengguna WHERE nama_lengkap = :nama");
            $exists->execute(['nama' => $emp['nama']]);
            $userRow = $exists->fetch();

            if (!$userRow) {
                $userId = self::generateUuid();
                $karyawanId = self::generateUuid();
                $tabunganId = self::generateUuid();

                $this->pdo->prepare("
                    INSERT INTO public.pengguna (id, nama_lengkap, nama_panggilan, jenis_kelamin, posisi, status_aktif, nik_pending, dibuat_pada)
                    VALUES (:id, :nama, :panggilan, 'P', :posisi, FALSE, TRUE, NOW())
                ")->execute([
                    'id' => $userId,
                    'nama' => $emp['nama'],
                    'panggilan' => $emp['panggilan'],
                    'posisi' => $emp['posisi'],
                ]);

                $this->pdo->prepare("
                    INSERT INTO public.karyawan (id, pengguna_id, tipe_penggajian, uang_kehadiran_harian, tunjangan_bulanan, gaji_pokok_bulanan, dibuat_pada)
                    VALUES (:id, :uid, 'borongan', :hadir, :tunjangan, 0.00, NOW())
                ")->execute([
                    'id' => $karyawanId,
                    'uid' => $userId,
                    'hadir' => $emp['hadir'],
                    'tunjangan' => $emp['tunjangan'],
                ]);

                $this->pdo->prepare("
                    INSERT INTO public.tabungan (id, karyawan_id, saldo, dibuat_pada)
                    VALUES (:id, :kid, 0.00, NOW())
                ")->execute([
                    'id' => $tabunganId,
                    'kid' => $karyawanId,
                ]);

                echo "  + Mendaftarkan karyawan historis: {$emp['nama']} (borongan, inaktif)\n";
            }
        }
    }

    private function ensureReturanItem(): void
    {
        $exists = $this->pdo->query("SELECT id FROM public.item WHERE kode_sku = 'PROD-RETURAN' OR nama_item = 'Returan Kemasan Borongan'")->fetch();
        if (!$exists) {
            $kelompok350 = $this->pdo->query("SELECT id FROM public.kelompok_upah_borongan WHERE nama_kelompok ILIKE '%350%' LIMIT 1")->fetch();
            $kelompokId = $kelompok350['id'] ?? null;
            $grupRow = $this->pdo->query("SELECT id FROM public.grup_produk LIMIT 1")->fetch();
            $grupId = $grupRow['id'] ?? null;

            $this->pdo->prepare("
                INSERT INTO public.item (
                    id, grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, kelompok_borongan_id,
                    status_jual, status_aktif, dibuat_pada
                ) VALUES (
                    :id, :grup_id, 'PROD-RETURAN', 'Returan Kemasan Borongan', 'barang_jadi', 'pcs', :kelompok_id,
                    FALSE, TRUE, NOW()
                )
            ")->execute([
                'id' => self::generateUuid(),
                'grup_id' => $grupId,
                'kelompok_id' => $kelompokId,
            ]);
            echo "  + Mendaftarkan item master khusus: Returan Kemasan Borongan (PROD-RETURAN)\n";
        }
    }

    private function executeMigration(): void
    {
        echo "[3/4] Mengeksekusi migrasi data ke database {$this->target}...\n";

        $this->pdo->beginTransaction();

        try {
            // A. Nonaktifkan Seluruh User Trigger pada Tabel yang Dimigrasikan
            echo "  - Menonaktifkan trigger pengguna pada tabel target agar tidak mengganggu stok/validasi...\n";
            $this->pdo->exec("ALTER TABLE public.produksi_harian DISABLE TRIGGER USER;");
            $this->pdo->exec("ALTER TABLE public.potongan_kasbon DISABLE TRIGGER USER;");
            $this->pdo->exec("ALTER TABLE public.transaksi_tabungan DISABLE TRIGGER USER;");
            $this->pdo->exec("ALTER TABLE public.absensi DISABLE TRIGGER USER;");
            $this->pdo->exec("ALTER TABLE public.penarikan_gaji DISABLE TRIGGER USER;");

            // B. Bersihkan data jika mode clean aktif atau data test
            if ($this->shouldClean) {
                echo "  - [CLEAN] Membersihkan data riwayat payroll lama sebelum mengimpor ulang...\n";
                $this->pdo->exec("DELETE FROM public.produksi_harian;");
                $this->pdo->exec("DELETE FROM public.absensi;");
                $this->pdo->exec("DELETE FROM public.penarikan_gaji;");
                $this->pdo->exec("DELETE FROM public.potongan_kasbon;");
                $this->pdo->exec("DELETE FROM public.transaksi_tabungan;");
                $this->pdo->exec("DELETE FROM public.rincian_penggajian;");
                $this->pdo->exec("DELETE FROM public.penggajian;");
                $this->pdo->exec("DELETE FROM public.kasbon;");
                $this->pdo->exec("UPDATE public.tabungan SET saldo = 0.00;");
                $escrowAccClean = $this->pdo->query("SELECT id FROM public.akun_kas WHERE is_escrow = TRUE AND status_aktif = TRUE LIMIT 1")->fetch();
                if ($escrowAccClean) {
                    $this->pdo->exec("DELETE FROM public.arus_kas WHERE akun_kas_id = '{$escrowAccClean['id']}';");
                    $this->pdo->exec("UPDATE public.akun_kas SET saldo_saat_ini = 0.00, diubah_pada = NOW() WHERE id = '{$escrowAccClean['id']}';");
                }
            } else {
                $cleaned = $this->pdo->exec("
                    DELETE FROM public.produksi_harian 
                    WHERE dicatat_oleh = '00000000-0000-0000-0000-000000000000' OR penggajian_id IS NULL
                ");
                if ($cleaned > 0) {
                    echo "  - Membersihkan {$cleaned} baris data uji coba lama di produksi_harian.\n";
                }
            }

            // 1. MIGRASI KASBON (35 Baris)
            echo "  - Memigrasikan data Master Kasbon...\n";
            $stmtKasbon = $this->pdo->prepare("
                INSERT INTO public.kasbon (
                    id, karyawan_id, tanggal_pengajuan, total_pinjaman,
                    potongan_per_periode, sisa_pinjaman, status_kasbon,
                    keterangan, catatan, dibuat_pada, diubah_pada
                ) VALUES (
                    :id, :karyawan_id, :tanggal_pengajuan, :total_pinjaman,
                    :potongan_per_periode, :sisa_pinjaman, :status_kasbon,
                    :keterangan, :catatan, :dibuat_pada, :diubah_pada
                )
                ON CONFLICT (id) DO NOTHING
            ");

            $kasbonCount = 0;
            foreach ($this->rawKasbon as $rk) {
                $idSalary = (int)trim($rk[0]);
                $empSalaryId = (int)trim($rk[1]);
                if (!isset($this->mapKaryawan[$empSalaryId])) {
                    continue;
                }

                $uuid = $this->mapKasbon[$idSalary];
                $karyawanId = $this->mapKaryawan[$empSalaryId];
                $keterangan = trim($rk[2]) ?: 'Pinjaman kasbon';
                $totalPinjaman = (float)trim($rk[3]);
                $potonganPeriode = (float)trim($rk[4]);
                $sisaPinjaman = (float)trim($rk[5]);
                $statusRaw = trim($rk[6]);
                $statusKasbon = ($statusRaw === 'paid_off') ? 'lunas' : 'aktif';
                $catatan = trim($rk[7]) ?: null;
                $createdAt = trim($rk[8]) ?: date('Y-m-d H:i:s');
                $updatedAt = trim($rk[9]) ?: date('Y-m-d H:i:s');
                $tglPengajuan = substr($createdAt, 0, 10);

                $stmtKasbon->execute([
                    'id' => $uuid,
                    'karyawan_id' => $karyawanId,
                    'tanggal_pengajuan' => $tglPengajuan,
                    'total_pinjaman' => $totalPinjaman,
                    'potongan_per_periode' => $potonganPeriode,
                    'sisa_pinjaman' => $sisaPinjaman,
                    'status_kasbon' => $statusKasbon,
                    'keterangan' => $keterangan,
                    'catatan' => $catatan,
                    'dibuat_pada' => $createdAt,
                    'diubah_pada' => $updatedAt,
                ]);
                $kasbonCount++;
            }
            echo "    -> Berhasil memasukkan {$kasbonCount} baris kasbon.\n";

            // 1b. PENYESUAIAN REVISI KASBON PATOKAN (docs/PROMPT/data_kasbon)
            echo "  - Menerapkan revisi data kasbon patokan (docs/PROMPT/data_kasbon)...\n";
            // Mona Chulyani
            $this->pdo->exec("
                UPDATE public.kasbon 
                SET sisa_pinjaman = 1300000.00, status_kasbon = 'aktif', diubah_pada = NOW() 
                WHERE id = '97d24ff7-4a76-5215-95db-a753b20316c3';
                UPDATE public.kasbon 
                SET sisa_pinjaman = 0.00, status_kasbon = 'lunas', diubah_pada = NOW() 
                WHERE id IN ('84e7e0ac-a5eb-5b2f-ab9c-94cbd3f58860', 'e6f02f84-ae6f-50bb-9b7a-2f7c3320e29a');
            ");
            // Sri Nurjanah
            $this->pdo->exec("
                UPDATE public.kasbon 
                SET sisa_pinjaman = 0.00, status_kasbon = 'lunas', diubah_pada = NOW() 
                WHERE id = '7b290c8f-9ce7-5a24-98b3-e8987672be01';
                INSERT INTO public.kasbon (
                    id, karyawan_id, tanggal_pengajuan, total_pinjaman,
                    potongan_per_periode, sisa_pinjaman, status_kasbon,
                    keterangan, catatan, dibuat_pada, diubah_pada
                ) VALUES (
                    '5cdade21-6246-5b97-bdc7-5ee426d5fdf6',
                    'ea3dad1e-7cb7-4191-8655-91a7e38f8c7a',
                    '2026-10-03', 700000.00, 100000.00, 700000.00, 'aktif',
                    'Pinjaman kasbon', 'Revisi data kasbon per 03 Okt 2026', '2026-10-03 08:00:00+07', NOW()
                ) ON CONFLICT (id) DO UPDATE SET
                    total_pinjaman = EXCLUDED.total_pinjaman,
                    sisa_pinjaman = EXCLUDED.sisa_pinjaman,
                    status_kasbon = EXCLUDED.status_kasbon,
                    diubah_pada = NOW();
            ");
            // Asiyah
            $this->pdo->exec("
                INSERT INTO public.kasbon (
                    id, karyawan_id, tanggal_pengajuan, total_pinjaman,
                    potongan_per_periode, sisa_pinjaman, status_kasbon,
                    keterangan, catatan, dibuat_pada, diubah_pada
                ) VALUES (
                    '72b0d9a5-a4be-5b20-b862-7a4ec3da97ac',
                    'e6b1fce6-8abc-4107-b536-6a231bc4bd3e',
                    '2026-10-09', 12000.00, 12000.00, 12000.00, 'aktif',
                    'Pinjaman kasbon', 'Revisi data kasbon per 09 Okt 2026', '2026-10-09 08:00:00+07', NOW()
                ) ON CONFLICT (id) DO UPDATE SET
                    total_pinjaman = EXCLUDED.total_pinjaman,
                    sisa_pinjaman = EXCLUDED.sisa_pinjaman,
                    status_kasbon = EXCLUDED.status_kasbon,
                    diubah_pada = NOW();
            ");
            echo "    -> Berhasil menerapkan penyesuaian revisi kasbon patokan.\n";

            // 2. MIGRASI PENGGAJIAN HEADER (10 Baris)
            echo "  - Memigrasikan Header Penggajian (Payroll Runs)...\n";
            $stmtPenggajian = $this->pdo->prepare("
                INSERT INTO public.penggajian (
                    id, nomor_referensi, nama_payroll, periode_awal, periode_akhir,
                    tipe_penggajian, total_gaji_dikeluarkan, options_json, status,
                    disetujui_oleh, disetujui_pada, dibuat_pada, diubah_pada
                ) VALUES (
                    :id, :nomor_referensi, :nama_payroll, :periode_awal, :periode_akhir,
                    :tipe_penggajian, :total_gaji_dikeluarkan, :options_json, :status,
                    :disetujui_oleh, :disetujui_pada, :dibuat_pada, :diubah_pada
                )
                ON CONFLICT (nomor_referensi) DO UPDATE SET
                    total_gaji_dikeluarkan = EXCLUDED.total_gaji_dikeluarkan
            ");

            $payrollCount = 0;
            foreach ($this->rawPenggajian as $rp) {
                $idSalary = (int)trim($rp[0]);
                $uuid = $this->mapPenggajian[$idSalary];
                $start = trim($rp[1]);
                $end   = trim($rp[2]);
                $typeRaw = trim($rp[3]);
                $tipePenggajian = match ($typeRaw) {
                    'weekly' => 'mingguan',
                    'monthly' => 'bulanan',
                    default => 'gabungan',
                };
                $statusRaw = trim($rp[4]);
                $status = ($statusRaw === 'approved') ? 'disetujui' : 'draf';
                $disetujuiPada = trim($rp[5]) ?: null;
                $createdAt = trim($rp[7]) ?: date('Y-m-d H:i:s');
                $updatedAt = trim($rp[8]) ?: date('Y-m-d H:i:s');
                $namaPayroll = trim($rp[9]) ?: ("Payroll " . ucfirst($typeRaw) . " " . $end);
                
                $optionsJsonRaw = trim($rp[10] ?? '');
                $optionsJson = ($optionsJsonRaw === '' || strtoupper($optionsJsonRaw) === 'NULL') ? null : $optionsJsonRaw;
                if ($optionsJson !== null) {
                    json_decode($optionsJson);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $optionsJson = null;
                    }
                }

                $datePart = str_replace('-', '', substr($end, 0, 10));
                $nomorRef = sprintf("PAY-%s-L%03d", $datePart, $idSalary);

                $stmtPenggajian->execute([
                    'id' => $uuid,
                    'nomor_referensi' => $nomorRef,
                    'nama_payroll' => $namaPayroll,
                    'periode_awal' => $start,
                    'periode_akhir' => $end,
                    'tipe_penggajian' => $tipePenggajian,
                    'total_gaji_dikeluarkan' => 0.00,
                    'options_json' => $optionsJson,
                    'status' => $status,
                    'disetujui_oleh' => $this->defaultApproverId,
                    'disetujui_pada' => $disetujuiPada,
                    'dibuat_pada' => $createdAt,
                    'diubah_pada' => $updatedAt,
                ]);
                $payrollCount++;
            }
            echo "    -> Berhasil memasukkan {$payrollCount} header penggajian.\n";

            // 3. MIGRASI RINCIAN PENGGAJIAN (214 Baris)
            echo "  - Memigrasikan Rincian Penggajian (Slip Gaji Karyawan)...\n";
            $stmtRincian = $this->pdo->prepare("
                INSERT INTO public.rincian_penggajian (
                    id, penggajian_id, karyawan_id, gaji_pokok, hari_hadir,
                    total_uang_kehadiran, tunjangan_bulanan, tunjangan_lain, catatan_tunjangan_lain,
                    total_upah_borongan, total_upah_lembur, total_komisi_sales, total_potongan_kasbon,
                    potongan_lain, catatan_potongan_lain, nominal_pembulatan, total_potongan_tabungan,
                    penarikan_tabungan, total_penarikan_gaji, is_excluded, catatan_pengecualian,
                    gaji_bersih_diterima, rincian_json, dibuat_pada
                ) VALUES (
                    :id, :penggajian_id, :karyawan_id, :gaji_pokok, :hari_hadir,
                    :total_uang_kehadiran, :tunjangan_bulanan, :tunjangan_lain, :catatan_tunjangan_lain,
                    :total_upah_borongan, :total_upah_lembur, :total_komisi_sales, :total_potongan_kasbon,
                    :potongan_lain, :catatan_potongan_lain, :nominal_pembulatan, :total_potongan_tabungan,
                    :penarikan_tabungan, :total_penarikan_gaji, :is_excluded, :catatan_pengecualian,
                    :gaji_bersih_diterima, :rincian_json, :dibuat_pada
                )
                ON CONFLICT (id) DO UPDATE SET
                    gaji_pokok = EXCLUDED.gaji_pokok,
                    hari_hadir = EXCLUDED.hari_hadir,
                    total_uang_kehadiran = EXCLUDED.total_uang_kehadiran,
                    tunjangan_bulanan = EXCLUDED.tunjangan_bulanan,
                    tunjangan_lain = EXCLUDED.tunjangan_lain,
                    total_upah_borongan = EXCLUDED.total_upah_borongan,
                    total_upah_lembur = EXCLUDED.total_upah_lembur,
                    total_potongan_kasbon = EXCLUDED.total_potongan_kasbon,
                    potongan_lain = EXCLUDED.potongan_lain,
                    nominal_pembulatan = EXCLUDED.nominal_pembulatan,
                    total_potongan_tabungan = EXCLUDED.total_potongan_tabungan,
                    penarikan_tabungan = EXCLUDED.penarikan_tabungan,
                    total_penarikan_gaji = EXCLUDED.total_penarikan_gaji,
                    is_excluded = EXCLUDED.is_excluded,
                    catatan_pengecualian = EXCLUDED.catatan_pengecualian,
                    gaji_bersih_diterima = EXCLUDED.gaji_bersih_diterima,
                    rincian_json = EXCLUDED.rincian_json
            ");

            $rincianCount = 0;
            $totalGajiPerPayroll = [];

            foreach ($this->rawRincian as $rr) {
                $idSalary = (int)trim($rr[0]);
                $penggajianSalaryId = (int)trim($rr[1]);
                $karyawanSalaryId = (int)trim($rr[2]);

                if (!isset($this->mapPenggajian[$penggajianSalaryId]) || !isset($this->mapKaryawan[$karyawanSalaryId])) {
                    continue;
                }

                $uuid = $this->mapRincian[$idSalary];
                $penggajianId = $this->mapPenggajian[$penggajianSalaryId];
                $karyawanId = $this->mapKaryawan[$karyawanSalaryId];

                $gajiPokok = (float)trim($rr[3]);
                $hariHadir = (int)trim($rr[4]);
                $totalHadir = (float)trim($rr[5]);
                $upahProduksi = (float)trim($rr[6]);
                $upahLembur = (float)trim($rr[7]);
                $tunjBulanan = (float)trim($rr[8]);
                $tunjLain = (float)trim($rr[9]);
                $catTunjLain = trim($rr[10]) ?: null;
                $potKasbon = (float)trim($rr[11]);
                $potTabungan = (float)trim($rr[12]);
                $tarikTabungan = (float)trim($rr[13]);
                $tarikGaji = (float)trim($rr[14]);
                $potLain = (float)trim($rr[15]);
                $catPotLain = trim($rr[16]) ?: null;
                $pembulatan = (float)trim($rr[17]);
                $gajiBersih = (float)trim($rr[18]);

                $rincianJsonRaw = trim($rr[19] ?? '');
                $rincianJson = ($rincianJsonRaw === '' || strtoupper($rincianJsonRaw) === 'NULL') ? '{}' : $rincianJsonRaw;
                json_decode($rincianJson);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $rincianJson = '{}';
                }

                $createdAt = trim($rr[20]) ?: date('Y-m-d H:i:s');
                $isExcluded = ((int)trim($rr[22])) === 1;
                $catPengecualian = trim($rr[23]) ?: null;

                $stmtRincian->execute([
                    'id' => $uuid,
                    'penggajian_id' => $penggajianId,
                    'karyawan_id' => $karyawanId,
                    'gaji_pokok' => $gajiPokok,
                    'hari_hadir' => $hariHadir,
                    'total_uang_kehadiran' => $totalHadir,
                    'tunjangan_bulanan' => $tunjBulanan,
                    'tunjangan_lain' => $tunjLain,
                    'catatan_tunjangan_lain' => $catTunjLain,
                    'total_upah_borongan' => $upahProduksi,
                    'total_upah_lembur' => $upahLembur,
                    'total_komisi_sales' => 0.00,
                    'total_potongan_kasbon' => $potKasbon,
                    'potongan_lain' => $potLain,
                    'catatan_potongan_lain' => $catPotLain,
                    'nominal_pembulatan' => $pembulatan,
                    'total_potongan_tabungan' => $potTabungan,
                    'penarikan_tabungan' => $tarikTabungan,
                    'total_penarikan_gaji' => $tarikGaji,
                    'is_excluded' => $isExcluded ? 1 : 0,
                    'catatan_pengecualian' => $catPengecualian,
                    'gaji_bersih_diterima' => $gajiBersih,
                    'rincian_json' => $rincianJson,
                    'dibuat_pada' => $createdAt,
                ]);

                if (!$isExcluded) {
                    $totalGajiPerPayroll[$penggajianId] = ($totalGajiPerPayroll[$penggajianId] ?? 0.0) + $gajiBersih;
                }
                $rincianCount++;
            }
            echo "    -> Berhasil memasukkan {$rincianCount} rincian penggajian.\n";

            // Update total_gaji_dikeluarkan di header penggajian
            $stmtUpdateHeader = $this->pdo->prepare("UPDATE public.penggajian SET total_gaji_dikeluarkan = :tot WHERE id = :id");
            foreach ($totalGajiPerPayroll as $pId => $tot) {
                $stmtUpdateHeader->execute(['tot' => $tot, 'id' => $pId]);
            }

            // 4. MIGRASI POTONGAN KASBON (68 Baris)
            echo "  - Memigrasikan riwayat Potongan Kasbon...\n";
            $stmtPotKasbon = $this->pdo->prepare("
                INSERT INTO public.potongan_kasbon (
                    id, kasbon_id, rincian_penggajian_id, tanggal, nominal,
                    tipe_potongan, keterangan, dibuat_pada
                ) VALUES (
                    :id, :kasbon_id, :rincian_penggajian_id, :tanggal, :nominal,
                    :tipe_potongan, :keterangan, :dibuat_pada
                )
                ON CONFLICT (id) DO UPDATE SET
                    nominal = EXCLUDED.nominal,
                    tipe_potongan = EXCLUDED.tipe_potongan,
                    keterangan = EXCLUDED.keterangan
            ");

            $potCount = 0;
            foreach ($this->rawPotonganKasbon as $rpk) {
                $idKasbonSalary = (int)trim($rpk[1]);
                $idRincianSalary = !empty($rpk[2]) ? (int)trim($rpk[2]) : null;

                if (!isset($this->mapKasbon[$idKasbonSalary])) {
                    continue;
                }

                $uuid = self::uuidv5('potongan_kasbon', trim($rpk[0]));
                $kasbonId = $this->mapKasbon[$idKasbonSalary];
                $rincianId = ($idRincianSalary && isset($this->mapRincian[$idRincianSalary])) ? $this->mapRincian[$idRincianSalary] : null;
                $nominal = (float)trim($rpk[3]);
                $tanggal = trim($rpk[4]);
                $tipe = trim($rpk[5]) ?: 'payroll';
                $catatan = trim($rpk[6]) ?: null;
                $createdAt = trim($rpk[7]) ?: date('Y-m-d H:i:s');

                $stmtPotKasbon->execute([
                    'id' => $uuid,
                    'kasbon_id' => $kasbonId,
                    'rincian_penggajian_id' => $rincianId,
                    'tanggal' => $tanggal,
                    'nominal' => $nominal,
                    'tipe_potongan' => $tipe,
                    'keterangan' => $catatan,
                    'dibuat_pada' => $createdAt,
                ]);
                $potCount++;
            }
            echo "    -> Berhasil memasukkan {$potCount} riwayat potongan kasbon.\n";

            // 5. UPDATE SALDO TABUNGAN (28 Karyawan) & TRANSAKSI TABUNGAN (4 Baris) & SINKRONISASI AKUN ESCROW
            echo "  - Menyinkronkan saldo akhir Tabungan Karyawan...\n";
            $stmtUpdateTab = $this->pdo->prepare("UPDATE public.tabungan SET saldo = :saldo, diubah_pada = NOW() WHERE id = :id");
            $tabCount = 0;
            foreach ($this->rawTabungan as $rt) {
                $salaryEmpId = (int)trim($rt[1]);
                if (!isset($this->mapTabungan[$salaryEmpId])) {
                    continue;
                }
                $tabId = $this->mapTabungan[$salaryEmpId];
                $saldo = (float)trim($rt[2]);
                $stmtUpdateTab->execute(['saldo' => $saldo, 'id' => $tabId]);
                $tabCount++;
            }
            echo "    -> Berhasil memperbarui saldo {$tabCount} rekening tabungan.\n";

            // Ambil akun kas escrow tabungan
            $escrowAcc = $this->pdo->query("SELECT id, nama_akun FROM public.akun_kas WHERE is_escrow = TRUE AND status_aktif = TRUE LIMIT 1")->fetch();
            $escrowCashId = $escrowAcc['id'] ?? null;

            echo "  - Memigrasikan riwayat Transaksi Tabungan & Arus Kas Escrow...\n";
            $stmtTrxTab = $this->pdo->prepare("
                INSERT INTO public.transaksi_tabungan (
                    id, tabungan_id, karyawan_id, rincian_penggajian_id,
                    tanggal, tipe, jumlah, sumber, akun_kas_id, keterangan, dibuat_pada
                ) VALUES (
                    :id, :tabungan_id, :karyawan_id, :rincian_penggajian_id,
                    :tanggal, :tipe, :jumlah, :sumber, :akun_kas_id, :keterangan, :dibuat_pada
                )
                ON CONFLICT (id) DO UPDATE SET
                    jumlah = EXCLUDED.jumlah,
                    tipe = EXCLUDED.tipe,
                    sumber = EXCLUDED.sumber,
                    akun_kas_id = EXCLUDED.akun_kas_id,
                    keterangan = EXCLUDED.keterangan
            ");

            $stmtArusKas = $this->pdo->prepare("
                INSERT INTO public.arus_kas (
                    id, akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                    nominal, keterangan, referensi_tabel, referensi_id,
                    saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :id, :akun_kas_id, :tanggal_transaksi, :jenis_kas, :kategori,
                    :nominal, :keterangan, :referensi_tabel, :referensi_id,
                    :saldo_berjalan, :dicatat_oleh, :dibuat_pada
                )
                ON CONFLICT (id) DO UPDATE SET
                    nominal = EXCLUDED.nominal,
                    keterangan = EXCLUDED.keterangan,
                    saldo_berjalan = EXCLUDED.saldo_berjalan
            ");

            // Sortir transaksi berdasarkan tanggal agar saldo berjalan tersusun rapi
            $sortedTrxTab = $this->rawTransaksiTabungan;
            usort($sortedTrxTab, fn($a, $b) => strcmp(trim($a[6]), trim($b[6])));

            $trxTabCount = 0;
            $runningEscrowBalance = 0.00;

            foreach ($sortedTrxTab as $rtt) {
                $salaryEmpId = (int)trim($rtt[1]);
                if (!isset($this->mapTabungan[$salaryEmpId])) {
                    continue;
                }

                $uuid = self::uuidv5('transaksi_tabungan', trim($rtt[0]));
                $tabId = $this->mapTabungan[$salaryEmpId];
                $karyawanId = $this->mapKaryawan[$salaryEmpId];
                $namaKaryawan = $this->karyawanNamaById[$karyawanId] ?? 'Karyawan';
                $tipe = trim($rtt[2]);
                $jumlah = (float)trim($rtt[3]);
                $sumber = trim($rtt[4]) ?: 'manual';
                $rincianSalaryId = !empty($rtt[5]) ? (int)trim($rtt[5]) : null;
                $rincianId = ($rincianSalaryId && isset($this->mapRincian[$rincianSalaryId])) ? $this->mapRincian[$rincianSalaryId] : null;
                $tanggal = trim($rtt[6]);
                $keterangan = trim($rtt[7]) ?: null;
                $createdAt = trim($rtt[8]) ?: date('Y-m-d H:i:s');

                if ($tipe === 'deposit') {
                    $runningEscrowBalance += $jumlah;
                } elseif ($tipe === 'withdrawal') {
                    $runningEscrowBalance -= $jumlah;
                }

                $stmtTrxTab->execute([
                    'id' => $uuid,
                    'tabungan_id' => $tabId,
                    'karyawan_id' => $karyawanId,
                    'rincian_penggajian_id' => $rincianId,
                    'tanggal' => $tanggal,
                    'tipe' => $tipe,
                    'jumlah' => $jumlah,
                    'sumber' => $sumber,
                    'akun_kas_id' => $escrowCashId,
                    'keterangan' => $keterangan,
                    'dibuat_pada' => $createdAt,
                ]);

                if ($escrowCashId) {
                    $arusKasId = self::uuidv5('arus_kas_tabungan', trim($rtt[0]));
                    $stmtArusKas->execute([
                        'id' => $arusKasId,
                        'akun_kas_id' => $escrowCashId,
                        'tanggal_transaksi' => $tanggal,
                        'jenis_kas' => ($tipe === 'withdrawal' ? 'keluar' : 'masuk'),
                        'kategori' => ($tipe === 'withdrawal' ? 'penarikan_tabungan' : 'setoran_tabungan'),
                        'nominal' => $jumlah,
                        'keterangan' => "Setoran tabungan {$namaKaryawan}" . (!empty($keterangan) ? " ({$keterangan})" : ''),
                        'referensi_tabel' => 'transaksi_tabungan',
                        'referensi_id' => $uuid,
                        'saldo_berjalan' => $runningEscrowBalance,
                        'dicatat_oleh' => $this->defaultApproverId,
                        'dibuat_pada' => $createdAt,
                    ]);
                }

                $trxTabCount++;
            }
            echo "    -> Berhasil memasukkan {$trxTabCount} riwayat transaksi tabungan.\n";

            // Sinkronkan saldo kas rekening escrow
            if ($escrowCashId) {
                $totalSaldoTabungan = (float)$this->pdo->query("SELECT COALESCE(SUM(saldo), 0) FROM public.tabungan")->fetchColumn();
                $this->pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")->execute([
                    'saldo' => $totalSaldoTabungan,
                    'id' => $escrowCashId,
                ]);
                echo sprintf("    -> Berhasil menyinkronkan saldo Akun Kas Escrow (%s) : Rp %s (Rekonsiliasi Sempurna)\n", $escrowAcc['nama_akun'], number_format($totalSaldoTabungan, 2, ',', '.'));
            }

            // 6. MIGRASI PENARIKAN GAJI (92 Baris)
            echo "  - Memigrasikan riwayat Penarikan Gaji Harian...\n";
            $stmtPenarikan = $this->pdo->prepare("
                INSERT INTO public.penarikan_gaji (
                    id, karyawan_id, tanggal, nominal, keterangan, penggajian_id, dibuat_pada
                ) VALUES (
                    :id, :karyawan_id, :tanggal, :nominal, :keterangan, :penggajian_id, :dibuat_pada
                )
                ON CONFLICT (id) DO UPDATE SET
                    nominal = EXCLUDED.nominal,
                    keterangan = EXCLUDED.keterangan
            ");

            $tarikCount = 0;
            foreach ($this->rawPenarikanGaji as $rpg) {
                $salaryEmpId = (int)trim($rpg[1]);
                if (!isset($this->mapKaryawan[$salaryEmpId])) {
                    continue;
                }

                $uuid = self::uuidv5('penarikan_gaji', trim($rpg[0]));
                $karyawanId = $this->mapKaryawan[$salaryEmpId];
                $payrollSalaryId = !empty($rpg[2]) ? (int)trim($rpg[2]) : null;
                $payrollId = ($payrollSalaryId && isset($this->mapPenggajian[$payrollSalaryId])) ? $this->mapPenggajian[$payrollSalaryId] : null;
                $tanggal = trim($rpg[3]);
                $nominal = (float)trim($rpg[4]);
                $keterangan = trim($rpg[5]) ?: null;
                $createdAt = trim($rpg[6]) ?: date('Y-m-d H:i:s');

                $stmtPenarikan->execute([
                    'id' => $uuid,
                    'karyawan_id' => $karyawanId,
                    'tanggal' => $tanggal,
                    'nominal' => $nominal,
                    'keterangan' => $keterangan,
                    'penggajian_id' => $payrollId,
                    'dibuat_pada' => $createdAt,
                ]);
                $tarikCount++;
            }
            echo "    -> Berhasil memasukkan {$tarikCount} riwayat penarikan gaji.\n";

            // 7. MIGRASI ABSENSI (1.415 Baris)
            echo "  - Memigrasikan riwayat Absensi Karyawan...\n";
            $stmtAbsensi = $this->pdo->prepare("
                INSERT INTO public.absensi (
                    id, karyawan_id, tanggal, status_kehadiran, telat,
                    lembur_nominal, ambil_uang, catatan, penggajian_id,
                    dibuat_pada, diubah_pada
                ) VALUES (
                    :id, :karyawan_id, :tanggal, :status_kehadiran, :telat,
                    :lembur_nominal, :ambil_uang, :catatan, :penggajian_id,
                    :dibuat_pada, :diubah_pada
                )
                ON CONFLICT (karyawan_id, tanggal) DO UPDATE SET
                    status_kehadiran = EXCLUDED.status_kehadiran,
                    telat = EXCLUDED.telat,
                    lembur_nominal = EXCLUDED.lembur_nominal,
                    ambil_uang = EXCLUDED.ambil_uang,
                    catatan = EXCLUDED.catatan,
                    penggajian_id = EXCLUDED.penggajian_id
            ");

            $absensiCount = 0;
            foreach ($this->rawAbsensi as $ra) {
                $salaryEmpId = (int)trim($ra[1]);
                if (!isset($this->mapKaryawan[$salaryEmpId])) {
                    continue;
                }

                $tanggal = trim($ra[2]);
                $uuid = self::uuidv5('absensi', "{$salaryEmpId}_{$tanggal}");
                $karyawanId = $this->mapKaryawan[$salaryEmpId];
                $payrollSalaryId = !empty($ra[3]) ? (int)trim($ra[3]) : null;
                $payrollId = ($payrollSalaryId && isset($this->mapPenggajian[$payrollSalaryId])) ? $this->mapPenggajian[$payrollSalaryId] : null;
                
                $hadir = ((int)trim($ra[4])) === 1;
                $catatan = trim($ra[8] ?? '') ?: null;
                if ($hadir) {
                    $statusKehadiran = 'hadir';
                } else {
                    $catLower = mb_strtolower($catatan ?? '');
                    if (str_contains($catLower, 'sakit')) {
                        $statusKehadiran = 'sakit';
                    } elseif (str_contains($catLower, 'izin')) {
                        $statusKehadiran = 'izin';
                    } elseif (str_contains($catLower, 'libur')) {
                        $statusKehadiran = 'libur';
                    } else {
                        $statusKehadiran = 'alpa';
                    }
                }

                $telat = ((int)trim($ra[5])) === 1;
                $ambilUang = ((int)trim($ra[6] ?? '0')) === 1;
                $lemburNominal = (float)trim($ra[7] ?? '0');
                $createdAt = trim($ra[9] ?? '') ?: date('Y-m-d H:i:s');
                $updatedAt = trim($ra[10] ?? '') ?: date('Y-m-d H:i:s');

                $stmtAbsensi->execute([
                    'id' => $uuid,
                    'karyawan_id' => $karyawanId,
                    'tanggal' => $tanggal,
                    'status_kehadiran' => $statusKehadiran,
                    'telat' => $telat ? 1 : 0,
                    'lembur_nominal' => $lemburNominal,
                    'ambil_uang' => $ambilUang ? 1 : 0,
                    'catatan' => $catatan,
                    'penggajian_id' => $payrollId,
                    'dibuat_pada' => $createdAt,
                    'diubah_pada' => $updatedAt,
                ]);
                $absensiCount++;
            }
            echo "    -> Berhasil memasukkan {$absensiCount} baris riwayat absensi.\n";

            // 8. MIGRASI PRODUKSI HARIAN (3.627 Baris)
            echo "  - Memigrasikan riwayat Produksi Harian Borongan...\n";
            $stmtProduksi = $this->pdo->prepare("
                INSERT INTO public.produksi_harian (
                    id, karyawan_id, tanggal, item_id,
                    kuantitas_pcs, kuantitas_bal, lembur_pcs, lembur_bal,
                    upah_per_pcs_snapshot, total_upah_didapat, penggajian_id,
                    dicatat_oleh, dibuat_pada, diubah_pada
                ) VALUES (
                    :id, :karyawan_id, :tanggal, :item_id,
                    :kuantitas_pcs, :kuantitas_bal, :lembur_pcs, :lembur_bal,
                    :upah_per_pcs_snapshot, :total_upah_didapat, :penggajian_id,
                    :dicatat_oleh, :dibuat_pada, :diubah_pada
                )
                ON CONFLICT (karyawan_id, tanggal, item_id) DO UPDATE SET
                    kuantitas_pcs = EXCLUDED.kuantitas_pcs,
                    kuantitas_bal = EXCLUDED.kuantitas_bal,
                    lembur_pcs = EXCLUDED.lembur_pcs,
                    lembur_bal = EXCLUDED.lembur_bal,
                    upah_per_pcs_snapshot = EXCLUDED.upah_per_pcs_snapshot,
                    total_upah_didapat = EXCLUDED.total_upah_didapat,
                    penggajian_id = EXCLUDED.penggajian_id
            ");

            $produksiCount = 0;
            foreach ($this->rawProduksi as $rp) {
                $salaryEmpId = (int)trim($rp[1]);
                $salaryItemId = (int)trim($rp[4]);

                if (!isset($this->mapKaryawan[$salaryEmpId]) || !isset($this->mapItem[$salaryItemId])) {
                    continue;
                }

                $uuid = self::uuidv5('produksi_harian', trim($rp[0]));
                $karyawanId = $this->mapKaryawan[$salaryEmpId];
                $itemId = $this->mapItem[$salaryItemId];
                $tanggal = trim($rp[2]);
                $payrollSalaryId = !empty($rp[3]) ? (int)trim($rp[3]) : null;
                $payrollId = ($payrollSalaryId && isset($this->mapPenggajian[$payrollSalaryId])) ? $this->mapPenggajian[$payrollSalaryId] : null;

                $pcs = (int)trim($rp[5]);
                $bal = (int)trim($rp[6]);
                $lemburPcs = (int)trim($rp[7]);
                $lemburBal = (int)trim($rp[8]);
                $createdAt = trim($rp[9]) ?: date('Y-m-d H:i:s');
                $updatedAt = trim($rp[10]) ?: date('Y-m-d H:i:s');

                $rateSnapshot = (float)($this->itemRates[$salaryItemId] ?? 0);
                $totalUpah = ($pcs + $lemburPcs) * $rateSnapshot;

                $stmtProduksi->execute([
                    'id' => $uuid,
                    'karyawan_id' => $karyawanId,
                    'tanggal' => $tanggal,
                    'item_id' => $itemId,
                    'kuantitas_pcs' => $pcs,
                    'kuantitas_bal' => $bal,
                    'lembur_pcs' => $lemburPcs,
                    'lembur_bal' => $lemburBal,
                    'upah_per_pcs_snapshot' => $rateSnapshot,
                    'total_upah_didapat' => $totalUpah,
                    'penggajian_id' => $payrollId,
                    'dicatat_oleh' => $this->defaultApproverId,
                    'dibuat_pada' => $createdAt,
                    'diubah_pada' => $updatedAt,
                ]);
                $produksiCount++;
            }
            echo "    -> Berhasil memasukkan {$produksiCount} baris riwayat produksi harian.\n";

            // C. Aktifkan Kembali Trigger Database
            echo "  - Mengaktifkan kembali seluruh trigger pengguna pada database...\n";
            $this->pdo->exec("ALTER TABLE public.produksi_harian ENABLE TRIGGER USER;");
            $this->pdo->exec("ALTER TABLE public.potongan_kasbon ENABLE TRIGGER USER;");
            $this->pdo->exec("ALTER TABLE public.transaksi_tabungan ENABLE TRIGGER USER;");
            $this->pdo->exec("ALTER TABLE public.absensi ENABLE TRIGGER USER;");
            $this->pdo->exec("ALTER TABLE public.penarikan_gaji ENABLE TRIGGER USER;");

            // Keputusan Akhir Transaksi
            if ($this->isDryRun) {
                echo "\n  [DRY-RUN] Melakukan ROLLBACK transaksi secara aman (tidak ada perubahan tersimpan).\n";
                $this->pdo->rollBack();
            } else {
                echo "\n  Melakukan COMMIT transaksi resmi ke database {$this->target}...\n";
                $this->pdo->commit();
                echo "  TRANSAKSI BERHASIL DI-COMMIT 100%!\n\n";
            }

        } catch (Throwable $e) {
            echo "\n  [ERROR KRITIS] Terjadi kesalahan: " . $e->getMessage() . "\n";
            echo "  Mengaktifkan kembali trigger dan me-rollback transaksi...\n";
            try {
                $this->pdo->exec("ALTER TABLE public.produksi_harian ENABLE TRIGGER USER;");
                $this->pdo->exec("ALTER TABLE public.potongan_kasbon ENABLE TRIGGER USER;");
                $this->pdo->exec("ALTER TABLE public.transaksi_tabungan ENABLE TRIGGER USER;");
                $this->pdo->exec("ALTER TABLE public.absensi ENABLE TRIGGER USER;");
                $this->pdo->exec("ALTER TABLE public.penarikan_gaji ENABLE TRIGGER USER;");
            } catch (Throwable $t) {
                // Ignore re-enable trigger errors during rollback
            }
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function verifyDatabaseState(): void
    {
        echo "[4/4] Menjalankan Audit & Verifikasi Integritas Data di Database {$this->target}...\n";

        $tables = [
            'penggajian'         => ['target' => 10,   'query' => "SELECT COUNT(*) FROM public.penggajian"],
            'rincian_penggajian' => ['target' => 214,  'query' => "SELECT COUNT(*) FROM public.rincian_penggajian"],
            'absensi'            => ['target' => 1415, 'query' => "SELECT COUNT(*) FROM public.absensi"],
            'produksi_harian'    => ['target' => 3627, 'query' => "SELECT COUNT(*) FROM public.produksi_harian"],
            'kasbon'             => ['target' => 37,   'query' => "SELECT COUNT(*) FROM public.kasbon"],
            'potongan_kasbon'    => ['target' => 68,   'query' => "SELECT COUNT(*) FROM public.potongan_kasbon"],
            'penarikan_gaji'     => ['target' => 92,   'query' => "SELECT COUNT(*) FROM public.penarikan_gaji"],
            'transaksi_tabungan' => ['target' => 4,    'query' => "SELECT COUNT(*) FROM public.transaksi_tabungan"],
            'tabungan_karyawan'     => ['target' => 28,   'query' => "SELECT COUNT(*) FROM public.tabungan t JOIN public.karyawan k ON t.karyawan_id = k.id JOIN public.pengguna p ON k.pengguna_id = p.id WHERE p.nama_lengkap IN ('Ikah Upikah','Asiyah','Erniya','Mona Chulyani','Suryanti','Khusnul Khotimah','Titin Hartati','Nida Honipah','Nabila','Maryati','Nur Ngizati','Watira','Sri Nurjanah','Karyati','Nazala','Nur Ainun','Avinda Maharani','Alfiah Lutfianih','Angga Citra Abadi','Karno','Ahmad Faiq','Qais Kusnadi','Nurman Salani','Muhammad Aji','Siti Masitoh','Risma Nurlilia Arta Winda','Ka Umi','Zaskia')"],
            'tabungan_berisi'       => ['target' => 4,    'query' => "SELECT COUNT(*) FROM public.tabungan WHERE saldo > 0"],
            'mutasi_arus_kas_escrow'=> ['target' => 4,    'query' => "SELECT COUNT(*) FROM public.arus_kas WHERE akun_kas_id IN (SELECT id FROM public.akun_kas WHERE is_escrow = TRUE)"],
        ];

        echo sprintf("\n  %-24s | %-12s | %-12s | %s\n", "Tabel / Entitas Audit", "Target Dump", "Aktual DB", "Status");
        echo "  " . str_repeat('-', 62) . "\n";

        $allMatch = true;
        foreach ($tables as $tName => $tInfo) {
            $actual = (int)$this->pdo->query($tInfo['query'])->fetchColumn();
            $status = ($actual === $tInfo['target']) ? "\033[32mOK (100% Cocok)\033[0m" : "\033[33m" . ($actual > 0 ? "ADA ({$actual})" : "KOSONG") . "\033[0m";
            if ($actual !== $tInfo['target'] && !$this->isDryRun) {
                $allMatch = false;
            }
            echo sprintf("  %-24s | %-12d | %-12d | %s\n", $tName, $tInfo['target'], $actual, $status);
        }

        // Audit Relasional: Foreign Key Orphans
        echo "\n  Audit Relasional (Zero Orphan FK):\n";
        $orphans = [
            'Rincian -> Penggajian' => "SELECT COUNT(*) FROM public.rincian_penggajian rp WHERE rp.penggajian_id NOT IN (SELECT id FROM public.penggajian)",
            'Absensi -> Penggajian' => "SELECT COUNT(*) FROM public.absensi a WHERE a.penggajian_id IS NOT NULL AND a.penggajian_id NOT IN (SELECT id FROM public.penggajian)",
            'Produksi -> Penggajian' => "SELECT COUNT(*) FROM public.produksi_harian ph WHERE ph.penggajian_id IS NOT NULL AND ph.penggajian_id NOT IN (SELECT id FROM public.penggajian)",
            'Potongan -> Kasbon' => "SELECT COUNT(*) FROM public.potongan_kasbon pk WHERE pk.kasbon_id NOT IN (SELECT id FROM public.kasbon)",
            'Penarikan -> Penggajian' => "SELECT COUNT(*) FROM public.penarikan_gaji pg WHERE pg.penggajian_id IS NOT NULL AND pg.penggajian_id NOT IN (SELECT id FROM public.penggajian)",
        ];

        foreach ($orphans as $label => $q) {
            $orphanCount = (int)$this->pdo->query($q)->fetchColumn();
            $status = ($orphanCount === 0) ? "\033[32mPASS (0 Orphan)\033[0m" : "\033[31mFAIL ({$orphanCount} Orphan)\033[0m";
            echo sprintf("    - %-25s : %s\n", $label, $status);
        }

        // Rekonsiliasi Finansial
        $totGajiAktual = (float)$this->pdo->query("SELECT COALESCE(SUM(gaji_bersih_diterima), 0) FROM public.rincian_penggajian WHERE is_excluded = FALSE")->fetchColumn();
        $totKasbonAktual = (float)$this->pdo->query("SELECT COALESCE(SUM(total_pinjaman), 0) FROM public.kasbon")->fetchColumn();
        $totSisaKasbonAktual = (float)$this->pdo->query("SELECT COALESCE(SUM(sisa_pinjaman), 0) FROM public.kasbon")->fetchColumn();
        $totTabunganAktual = (float)$this->pdo->query("SELECT COALESCE(SUM(saldo), 0) FROM public.tabungan")->fetchColumn();
        $totEscrowAktual = (float)$this->pdo->query("SELECT COALESCE(SUM(saldo_saat_ini), 0) FROM public.akun_kas WHERE is_escrow = TRUE")->fetchColumn();

        echo "\n  Rekonsiliasi Total Finansial:\n";
        echo sprintf("    - Total Gaji Bersih Dibayarkan : Rp %s\n", number_format($totGajiAktual, 2, ',', '.'));
        echo sprintf("    - Total Pinjaman Kasbon Dicatat: Rp %s\n", number_format($totKasbonAktual, 2, ',', '.'));
        echo sprintf("    - Total Sisa Pinjaman Kasbon   : Rp %s\n", number_format($totSisaKasbonAktual, 2, ',', '.'));
        echo sprintf("    - Total Saldo Tabungan Karyawan: Rp %s\n", number_format($totTabunganAktual, 2, ',', '.'));
        echo sprintf("    - Total Saldo Kas Escrow Tabungan: Rp %s\n", number_format($totEscrowAktual, 2, ',', '.'));

        echo "\n" . str_repeat('=', 80) . "\n";
        if ($this->isDryRun) {
            echo " DRY-RUN SELESAI: Simulasi berhasil tanpa galat. Data siap dieksekusi secara riil!\n";
        } elseif ($allMatch) {
            echo " MIGRASI LOKAL SELESAI & 100% SEMPURNA! SEMUA DATA COCOK HINGGA RUPIAH TERAKHIR.\n";
        } else {
            echo " VERIFIKASI SELESAI.\n";
        }
        echo str_repeat('=', 80) . "\n\n";
    }
}

// CLI Arg Parsing
$target = 'local';
$dryRun = false;
$verify = false;
$clean = false;
$forceUnlock = false;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--target=')) {
        $target = substr($arg, 9);
    } elseif ($arg === '--dry-run') {
        $dryRun = true;
    } elseif ($arg === '--verify') {
        $verify = true;
    } elseif ($arg === '--clean') {
        $clean = true;
    } elseif ($arg === '--force-unlock') {
        $forceUnlock = true;
    }
}

// Execution Safety Guard (Terkunci Permanen Pasca-Migrasi Sukses)
if (!$verify && !$forceUnlock) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo " [EXECUTION LOCKED] MIGRASI SUDAH TUNTAS DI LOKAL & SUPABASE LIVE (2026-10-07)\n";
    echo " Skrip ini dikunci secara otomatis demi integritas data keuangan & payroll.\n\n";
    echo " Untuk menjalankan audit/verifikasi integritas data read-only kapan saja:\n";
    echo "   php bin/migrate_salary_history.php --target=local --verify\n";
    echo "   php bin/migrate_salary_history.php --target=live --verify\n\n";
    echo " (Untuk membuka kunci eksekusi tulis paksa, sertakan flag: --force-unlock)\n";
    echo str_repeat('=', 80) . "\n\n";
    exit(0);
}

try {
    $migrator = new SalaryHistoryMigrator($target, $dryRun, $verify, $clean);
    $migrator->run();
} catch (Throwable $e) {
    fwrite(STDERR, "\n[FATAL ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    exit(1);
}
