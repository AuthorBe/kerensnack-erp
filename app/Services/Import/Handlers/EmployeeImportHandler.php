<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Services\Import\SmartReader;
use PDO;

class EmployeeImportHandler implements EntityImportHandlerInterface
{
    public function getEntityKey(): string
    {
        return 'employees';
    }

    public function getEntityLabel(): string
    {
        return 'Data Karyawan & Akun';
    }

    public function getRequiredPermission(): string
    {
        return 'master.employees_manage';
    }

    public function getRequiredHeaderGroups(): array
    {
        return [
            ['nama_lengkap', 'nama', 'nama_karyawan'],
            ['posisi', 'jabatan']
        ];
    }

    public function getTemplateHeaders(): array
    {
        return [
            'NIK Karyawan',
            'Nama Lengkap',
            'Username / Nama Pengguna',
            'Posisi / Tugas',
            'Tipe Penggajian',
            'Gaji Pokok Bulanan (Rp)',
            'Uang Hadir Harian (Rp)',
            'Tunjangan Bulanan (Rp)',
            'No WhatsApp',
            'No Polisi Kendaraan',
            'Alamat',
            'Tanggal Bergabung (YYYY-MM-DD)',
            'Nama Bank',
            'No Rekening',
            'Atas Nama Rekening',
            'Status Aktif'
        ];
    }

    public function getTemplateWidths(): array
    {
        return [16, 26, 22, 18, 18, 22, 20, 20, 18, 18, 30, 18, 14, 18, 22, 14];
    }

    public function getTemplateExamples(): array
    {
        return [
            ['NIK-001', 'Budi Santoso', 'budi_sales', 'sales', 'bulanan', 3500000, 25000, 500000, '081234567890', 'B 1234 ABC', 'Tangerang', '2023-01-15', 'BCA', '1122334455', 'Budi Santoso', 'Aktif'],
            ['NIK-002', 'Ahmad Dani', 'dani_driver', 'driver', 'bulanan', 0, 120000, 0, '085678901234', 'B 5678 XYZ', 'Jakarta Barat', '2023-05-10', 'BRI', '5566778899', 'Ahmad Dani', 'Aktif'],
            ['NIK-003', 'Siti Rohani', '', 'pengemasan', 'borongan', 0, 0, 0, '087812345678', '', 'Pasar Kemis', '2024-02-01', '', '', '', 'Aktif'],
        ];
    }

    public function getTemplateNotes(): array
    {
        return [
            'NIK Karyawan unik (contoh: NIK-001). Jika kosong, sistem membuat otomatis.',
            'Nama Lengkap dan Posisi WAJIB diisi.',
            'Posisi yang valid: admin, mandor, pengemasan, sales, driver (developer/owner diatur khusus).',
            'Tipe Penggajian: borongan, bulanan.',
            'No WhatsApp: Nomor WhatsApp aktif karyawan untuk koordinasi kerja (format 08xxx).',
            'Username bersifat opsional (jika diisi, akun dapat login ke ERP). Password default akun baru: "KerenSnack2026!".'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $sql = "SELECT COALESCE(p.nik, '') as nik, p.nama_lengkap, COALESCE(p.nama_pengguna, '') as nama_pengguna,
                       p.posisi, COALESCE(k.tipe_penggajian, 'borongan') as tipe_penggajian,
                       COALESCE(k.gaji_pokok_bulanan, 0) as gaji_pokok,
                       COALESCE(k.uang_kehadiran_harian, 0) as uang_hadir,
                       COALESCE(k.tunjangan_bulanan, 0) as tunjangan,
                       COALESCE(p.nomor_whatsapp, p.nomor_telepon, '') as whatsapp,
                       COALESCE(p.nomor_polisi_kendaraan, '') as nopol,
                       COALESCE(p.alamat, '') as alamat,
                       COALESCE(p.tanggal_bergabung::text, '') as tgl_gabung,
                       COALESCE(p.bank_nama, '') as bank,
                       COALESCE(p.bank_nomor_rekening, '') as no_rek,
                       COALESCE(p.bank_atas_nama, '') as atas_nama,
                       CASE WHEN p.status_aktif THEN 'Aktif' ELSE 'Nonaktif' END as status_aktif_label
                FROM public.pengguna p
                LEFT JOIN public.karyawan k ON k.pengguna_id = p.id
                WHERE p.posisi NOT IN ('developer')
                ORDER BY p.nik ASC NULLS LAST, p.nama_lengkap ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_NUM);
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
        $roles = $pdo->query("SELECT id, nama_peran FROM public.peran")->fetchAll(PDO::FETCH_ASSOC);
        $roleMap = [];
        foreach ($roles as $r) {
            $roleMap[strtolower(trim($r['nama_peran']))] = $r['id'];
        }

        $dbUsers = $pdo->query("SELECT p.*, k.tipe_penggajian, k.gaji_pokok_bulanan, k.uang_kehadiran_harian, k.tunjangan_bulanan 
                                FROM public.pengguna p 
                                LEFT JOIN public.karyawan k ON k.pengguna_id = p.id
                                WHERE p.posisi NOT IN ('developer')")->fetchAll(PDO::FETCH_ASSOC);
        $dbByNik = [];
        $dbByName = [];
        $dbByUsername = [];
        foreach ($dbUsers as $u) {
            if (!empty($u['nik'])) $dbByNik[strtolower(trim($u['nik']))] = $u;
            $dbByName[strtolower(trim($u['nama_lengkap']))] = $u;
            if (!empty($u['nama_pengguna'])) $dbByUsername[strtolower(trim($u['nama_pengguna']))] = $u;
        }

        $previewList = [];
        $seenNiks = [];
        $seenUsernames = [];
        $processedDbIds = [];

        foreach ($rows as $idx => $row) {
            $lineNo = $idx + 2;
            $rowData = SmartReader::buildRowData($header, $row);

            $nik = (string)(SmartReader::getSmartValue($rowData, ['nik_karyawan', 'nik', 'nomor_induk']) ?? '');
            $nama = (string)(SmartReader::getSmartValue($rowData, ['nama_lengkap', 'nama', 'nama_karyawan']) ?? '');
            $username = (string)(SmartReader::getSmartValue($rowData, ['username', 'nama_pengguna']) ?? '');
            $posisiRaw = (string)(SmartReader::getSmartValue($rowData, ['posisi', 'jabatan', 'tugas']) ?? '');
            $tipeGajiRaw = (string)(SmartReader::getSmartValue($rowData, ['tipe_penggajian', 'sistem_gaji', 'tipe_gaji']) ?? 'borongan');
            $gapokRaw = SmartReader::getSmartValue($rowData, ['gaji_pokok_bulanan', 'gaji_pokok', 'gapok']);
            $hadirRaw = SmartReader::getSmartValue($rowData, ['uang_hadir_harian', 'uang_kehadiran_harian', 'uang_hadir']);
            $tunjanganRaw = SmartReader::getSmartValue($rowData, ['tunjangan_bulanan', 'tunjangan']);
            $whatsapp = (string)(SmartReader::getSmartValue($rowData, ['nomor_whatsapp', 'whatsapp', 'no_wa', 'wa', 'no_hp', 'hp', 'nomor_telepon', 'telepon', 'no_telp']) ?? '');
            $nopol = (string)(SmartReader::getSmartValue($rowData, ['nomor_polisi_kendaraan', 'no_polisi', 'nopol']) ?? '');
            $alamat = (string)(SmartReader::getSmartValue($rowData, ['alamat', 'alamat_lengkap']) ?? '');
            $tglGabung = (string)(SmartReader::getSmartValue($rowData, ['tanggal_bergabung', 'tgl_gabung']) ?? '');
            $bankNama = (string)(SmartReader::getSmartValue($rowData, ['nama_bank', 'bank']) ?? '');
            $bankRek = (string)(SmartReader::getSmartValue($rowData, ['no_rekening', 'nomor_rekening']) ?? '');
            $bankAtasNama = (string)(SmartReader::getSmartValue($rowData, ['atas_nama_rekening', 'atas_nama']) ?? '');
            $statusAktifRaw = SmartReader::getSmartValue($rowData, ['status_aktif', 'status', 'aktif']);

            if (empty($nik) && empty($nama)) {
                continue;
            }

            if (empty($nama)) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Nama Lengkap kosong pada baris {$lineNo}. Wajib diisi.",
                    'data' => ['nik' => $nik, 'nama_lengkap' => '—', 'posisi' => $posisiRaw]
                ];
                continue;
            }

            $posisi = strtolower(trim($posisiRaw));
            $validPositions = ['admin', 'mandor', 'pengemasan', 'sales', 'driver', 'owner'];
            if (!in_array($posisi, $validPositions, true)) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Posisi '{$posisiRaw}' pada baris {$lineNo} tidak valid. Harus salah satu dari: admin, mandor, pengemasan, sales, driver.",
                    'data' => ['nik' => $nik, 'nama_lengkap' => $nama, 'posisi' => $posisiRaw]
                ];
                continue;
            }

            $tipeGaji = strtolower(trim($tipeGajiRaw));
            if (!in_array($tipeGaji, ['borongan', 'bulanan'], true)) {
                $tipeGaji = ($posisi === 'pengemasan') ? 'borongan' : 'bulanan';
            }

            $gapok = SmartReader::normalizeNumeric($gapokRaw, 0.0);
            $hadir = SmartReader::normalizeNumeric($hadirRaw, 0.0);
            $tunjangan = SmartReader::normalizeNumeric($tunjanganRaw, 0.0);
            $statusAktif = SmartReader::normalizeBoolean($statusAktifRaw, true);

            if (!empty($nik)) {
                $nKey = strtolower(trim($nik));
                if (isset($seenNiks[$nKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => "Duplikasi NIK '{$nik}' pada baris {$lineNo}.",
                        'data' => ['nik' => $nik, 'nama_lengkap' => $nama, 'posisi' => $posisi]
                    ];
                    continue;
                }
                $seenNiks[$nKey] = true;
            }

            if (!empty($username)) {
                $uKey = strtolower(trim($username));
                if (isset($seenUsernames[$uKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => "Duplikasi Username '{$username}' pada baris {$lineNo}.",
                        'data' => ['nik' => $nik, 'nama_lengkap' => $nama, 'posisi' => $posisi]
                    ];
                    continue;
                }
                $seenUsernames[$uKey] = true;
            }

            $dbRow = null;
            if (!empty($nik) && isset($dbByNik[strtolower(trim($nik))])) {
                $dbRow = $dbByNik[strtolower(trim($nik))];
            } elseif (!empty($username) && isset($dbByUsername[strtolower(trim($username))])) {
                $dbRow = $dbByUsername[strtolower(trim($username))];
            } elseif (empty($nik) && isset($dbByName[strtolower(trim($nama))])) {
                $dbRow = $dbByName[strtolower(trim($nama))];
            }

            $peranId = $roleMap[$posisi] ?? ($roleMap['admin'] ?? null);

            $itemData = [
                'id'                     => $dbRow['id'] ?? null,
                'nik'                    => !empty($nik) ? $nik : ($dbRow['nik'] ?? ''),
                'nama_lengkap'           => $nama,
                'nama_pengguna'          => !empty($username) ? $username : ($dbRow['nama_pengguna'] ?? null),
                'posisi'                 => $posisi,
                'peran_id'               => $peranId,
                'tipe_penggajian'        => $tipeGaji,
                'gaji_pokok_bulanan'     => $gapok,
                'uang_kehadiran_harian'  => $hadir,
                'tunjangan_bulanan'      => $tunjangan,
                'nomor_telepon'          => $whatsapp,
                'nomor_whatsapp'         => $whatsapp,
                'nomor_polisi_kendaraan' => $nopol,
                'alamat'                 => $alamat,
                'tanggal_bergabung'      => !empty($tglGabung) ? $tglGabung : ($dbRow['tanggal_bergabung'] ?? date('Y-m-d')),
                'bank_nama'              => $bankNama,
                'bank_nomor_rekening'    => $bankRek,
                'bank_atas_nama'         => $bankAtasNama,
                'status_aktif'           => $statusAktif,
            ];

            if ($dbRow) {
                $processedDbIds[] = $dbRow['id'];

                if (!empty($nik) && !SmartReader::isSimilarName($dbRow['nama_lengkap'], $nama)) {
                    $previewList[] = [
                        'action'       => 'INSERT',
                        'is_fatal'     => true,
                        'fatal_reason' => "NIK '{$nik}' di database terdaftar atas \"{$dbRow['nama_lengkap']}\", berbeda dengan \"{$nama}\". Dibuat sebagai karyawan baru dengan NIK otomatis.",
                        'data'         => $itemData,
                        'old_data'     => $dbRow
                    ];
                    continue;
                }

                $isDiff = trim($nama) !== trim((string)$dbRow['nama_lengkap'])
                    || trim($posisi) !== trim((string)$dbRow['posisi'])
                    || trim($tipeGaji) !== trim((string)($dbRow['tipe_penggajian'] ?? ''))
                    || abs($gapok - (float)($dbRow['gaji_pokok_bulanan'] ?? 0)) > 0.01
                    || abs($hadir - (float)($dbRow['uang_kehadiran_harian'] ?? 0)) > 0.01
                    || abs($tunjangan - (float)($dbRow['tunjangan_bulanan'] ?? 0)) > 0.01
                    || trim($whatsapp) !== trim((string)($dbRow['nomor_whatsapp'] ?: ($dbRow['nomor_telepon'] ?? '')))
                    || trim($nopol) !== trim((string)($dbRow['nomor_polisi_kendaraan'] ?? ''))
                    || $statusAktif !== (bool)$dbRow['status_aktif'];

                if ($isDiff) {
                    $previewList[] = [
                        'action'   => 'UPDATE',
                        'data'     => $itemData,
                        'old_data' => $dbRow
                    ];
                }
            } else {
                $previewList[] = [
                    'action' => 'INSERT',
                    'data'   => $itemData
                ];
            }
        }

        if ($mode === 'full_sync') {
            foreach ($dbUsers as $u) {
                if (!in_array($u['id'], $processedDbIds, true)) {
                    $previewList[] = [
                        'action' => 'DELETE',
                        'data'   => $u
                    ];
                }
            }
        }

        return $previewList;
    }

    public function applySync(array $previewList, PDO $pdo): array
    {
        $insertCount = 0;
        $updateCount = 0;
        $deleteCount = 0;
        $deactivateCount = 0;

        $stmtMaxNik = $pdo->query("SELECT MAX(SUBSTRING(nik FROM 5)::int) as max_seq FROM public.pengguna WHERE nik ~ '^NIK-[0-9]+$'");
        $nextSeq = ((int)($stmtMaxNik->fetch(PDO::FETCH_ASSOC)['max_seq'] ?? 0)) + 1;

        $defaultHash = password_hash('KerenSnack2026!', PASSWORD_BCRYPT);

        $stmtInsUser = $pdo->prepare("INSERT INTO public.pengguna 
            (nama_lengkap, nama_pengguna, kata_sandi, nik, posisi, peran_id, nomor_telepon, nomor_whatsapp, nomor_polisi_kendaraan, alamat, tanggal_bergabung, bank_nama, bank_nomor_rekening, bank_atas_nama, status_aktif)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::date, ?, ?, ?, ?) RETURNING id");

        $stmtInsKaryawan = $pdo->prepare("INSERT INTO public.karyawan 
            (pengguna_id, tipe_penggajian, gaji_pokok_bulanan, uang_kehadiran_harian, tunjangan_bulanan)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT (pengguna_id) DO UPDATE SET 
                tipe_penggajian = EXCLUDED.tipe_penggajian,
                gaji_pokok_bulanan = EXCLUDED.gaji_pokok_bulanan,
                uang_kehadiran_harian = EXCLUDED.uang_kehadiran_harian,
                tunjangan_bulanan = EXCLUDED.tunjangan_bulanan,
                diubah_pada = NOW()");

        $stmtUpdUser = $pdo->prepare("UPDATE public.pengguna SET 
            nama_lengkap = ?, posisi = ?, peran_id = ?, nomor_telepon = ?, nomor_whatsapp = ?, nomor_polisi_kendaraan = ?, alamat = ?, bank_nama = ?, bank_nomor_rekening = ?, bank_atas_nama = ?, status_aktif = ?, diubah_pada = NOW()
            WHERE id = ?");

        $stmtDeactivate = $pdo->prepare("UPDATE public.pengguna SET status_aktif = FALSE, diubah_pada = NOW() WHERE id = ?");
        $stmtDelUser = $pdo->prepare("DELETE FROM public.pengguna WHERE id = ?");

        $stmtCheckUsage = $pdo->prepare("SELECT 
            (SELECT COUNT(*) FROM public.surat_jalan WHERE sales_driver_id = ?) +
            (SELECT COUNT(*) FROM public.pelanggan WHERE sales_driver_id = ?) +
            (SELECT COUNT(*) FROM public.pesanan WHERE dibuat_oleh = ?) AS total_usage");

        foreach ($previewList as $row) {
            $act = $row['action'];
            $isFatal = !empty($row['is_fatal']);
            $d = $row['data'];

            if ($act === 'INSERT' || $isFatal) {
                $nik = $d['nik'];
                if (empty($nik) || $isFatal) {
                    $nik = 'NIK-' . str_pad((string)$nextSeq++, 3, '0', STR_PAD_LEFT);
                }

                $stmtInsUser->execute([
                    $d['nama_lengkap'],
                    !empty($d['nama_pengguna']) ? $d['nama_pengguna'] : null,
                    $defaultHash,
                    $nik,
                    $d['posisi'],
                    $d['peran_id'],
                    $d['nomor_whatsapp'] ?: null,
                    $d['nomor_whatsapp'] ?: null,
                    $d['nomor_polisi_kendaraan'] ?: null,
                    $d['alamat'] ?: null,
                    $d['tanggal_bergabung'] ?: date('Y-m-d'),
                    $d['bank_nama'] ?: null,
                    $d['bank_nomor_rekening'] ?: null,
                    $d['bank_atas_nama'] ?: null,
                    $d['status_aktif'] ? 1 : 0
                ]);
                $newUserId = $stmtInsUser->fetchColumn();

                if ($newUserId) {
                    $stmtInsKaryawan->execute([
                        $newUserId,
                        $d['tipe_penggajian'] ?: 'borongan',
                        $d['gaji_pokok_bulanan'] ?: 0,
                        $d['uang_kehadiran_harian'] ?: 0,
                        $d['tunjangan_bulanan'] ?: 0
                    ]);
                }
                $insertCount++;
            } elseif ($act === 'UPDATE') {
                $userId = $d['id'];
                $stmtUpdUser->execute([
                    $d['nama_lengkap'],
                    $d['posisi'],
                    $d['peran_id'],
                    $d['nomor_whatsapp'] ?: null,
                    $d['nomor_whatsapp'] ?: null,
                    $d['nomor_polisi_kendaraan'] ?: null,
                    $d['alamat'] ?: null,
                    $d['bank_nama'] ?: null,
                    $d['bank_nomor_rekening'] ?: null,
                    $d['bank_atas_nama'] ?: null,
                    $d['status_aktif'] ? 1 : 0,
                    $userId
                ]);

                $stmtInsKaryawan->execute([
                    $userId,
                    $d['tipe_penggajian'] ?: 'borongan',
                    $d['gaji_pokok_bulanan'] ?: 0,
                    $d['uang_kehadiran_harian'] ?: 0,
                    $d['tunjangan_bulanan'] ?: 0
                ]);
                $updateCount++;
            } elseif ($act === 'DELETE') {
                $uid = $d['id'];
                $stmtCheckUsage->execute([$uid, $uid, $uid]);
                $usage = (int)$stmtCheckUsage->fetchColumn();

                if ($usage > 0) {
                    $stmtDeactivate->execute([$uid]);
                    $deactivateCount++;
                } else {
                    $stmtDelUser->execute([$uid]);
                    $deleteCount++;
                }
            }
        }

        return [
            'insert'     => $insertCount,
            'update'     => $updateCount,
            'delete'     => $deleteCount,
            'deactivate' => $deactivateCount
        ];
    }
}
