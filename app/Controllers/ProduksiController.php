<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\CSRF;
use App\Helpers\Format;
use Database;
use Throwable;

/**
 * app/Controllers/ProduksiController.php
 * Pengendali Modul Produksi Harian Karyawan Borongan Keren One ERP
 */
class ProduksiController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission(['hr.produksi_view', 'hr.produksi_manage']);
    }

    /**
     * Halaman Utama Input Produksi Harian Borongan
     */
    public function index(): void
    {
        try {
            $tanggal = (string)$this->input('tanggal', date('Y-m-d'));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
                $tanggal = date('Y-m-d');
            }

            // Master Item Borongan Aktif
            $itemBorongan = Database::fetchAll("
                SELECT
                    i.id, i.nama_item, i.kode_sku,
                    k.id as kelompok_id, k.nama_kelompok, k.upah_per_bungkus
                FROM public.item i
                JOIN public.kelompok_upah_borongan k ON k.id = i.kelompok_borongan_id
                WHERE i.status_aktif = TRUE AND i.kelompok_borongan_id IS NOT NULL
                ORDER BY k.nama_kelompok ASC, i.nama_item ASC
            ");

            // Karyawan Borongan Aktif
            $karyawanBorongan = Database::fetchAll("
                SELECT
                    k.id as karyawan_id,
                    v.nama_karyawan,
                    v.nama_panggilan,
                    v.posisi
                FROM public.karyawan k
                JOIN public.v_karyawan_info v ON v.id = k.id
                WHERE v.status_aktif = TRUE AND k.tipe_penggajian = 'borongan'
                ORDER BY v.nama_karyawan ASC
            ");

            $selectedKaryawanId = (string)$this->input('karyawan_id', '');

            // Produksi Hari Ini dengan Info Lengkap
            $produksiHariIni = Database::fetchAll("
                SELECT
                    ph.id, ph.karyawan_id, ph.item_id, ph.tanggal,
                    ph.kuantitas_pcs, ph.kuantitas_bal, ph.lembur_pcs, ph.lembur_bal,
                    ph.upah_per_pcs_snapshot, ph.total_upah_didapat,
                    ph.penggajian_id, ph.dibuat_pada, ph.diubah_pada,
                    v.nama_karyawan, v.nama_panggilan, v.posisi,
                    i.nama_item, i.kode_sku,
                    k.nama_kelompok,
                    p.nomor_referensi as nomor_payroll
                FROM public.produksi_harian ph
                JOIN public.v_karyawan_info v ON v.id = ph.karyawan_id
                JOIN public.item i ON i.id = ph.item_id
                JOIN public.kelompok_upah_borongan k ON k.id = i.kelompok_borongan_id
                LEFT JOIN public.penggajian p ON p.id = ph.penggajian_id
                WHERE ph.tanggal = :tanggal
                ORDER BY v.nama_karyawan ASC, i.nama_item ASC
            ", ['tanggal' => $tanggal]);

            // Grouping produksi per karyawan (1 Baris = 1 Karyawan)
            $produksiPerKaryawan = [];
            foreach ($produksiHariIni as $p) {
                $kid = $p['karyawan_id'];
                if (!isset($produksiPerKaryawan[$kid])) {
                    $produksiPerKaryawan[$kid] = [
                        'karyawan_id' => $kid,
                        'nama_karyawan' => $p['nama_karyawan'],
                        'nama_panggilan' => $p['nama_panggilan'],
                        'posisi' => $p['posisi'],
                        'items' => [],
                        'total_pcs' => 0,
                        'total_bal' => 0,
                        'total_lembur_pcs' => 0,
                        'total_lembur_bal' => 0,
                        'total_upah' => 0.0,
                        'is_locked' => false,
                        'nomor_payroll' => null,
                        'has_lembur' => false
                    ];
                }
                $hasLembur = ((int)$p['lembur_pcs'] > 0 || (int)$p['lembur_bal'] > 0);
                if ($hasLembur) {
                    $produksiPerKaryawan[$kid]['has_lembur'] = true;
                }
                $produksiPerKaryawan[$kid]['items'][] = $p;
                $produksiPerKaryawan[$kid]['total_pcs'] += (int)$p['kuantitas_pcs'];
                $produksiPerKaryawan[$kid]['total_bal'] += (int)$p['kuantitas_bal'];
                $produksiPerKaryawan[$kid]['total_lembur_pcs'] += (int)$p['lembur_pcs'];
                $produksiPerKaryawan[$kid]['total_lembur_bal'] += (int)$p['lembur_bal'];
                $produksiPerKaryawan[$kid]['total_upah'] += (float)$p['total_upah_didapat'];
                if (!empty($p['penggajian_id'])) {
                    $produksiPerKaryawan[$kid]['is_locked'] = true;
                    $produksiPerKaryawan[$kid]['nomor_payroll'] = $p['nomor_payroll'];
                }
            }

            // Metrics
            $totalPcsHariIni = 0;
            $totalBalHariIni = 0;
            $totalLemburPcsHariIni = 0;
            $totalLemburBalHariIni = 0;
            $totalUpahHariIni = 0.00;
            $karyawanBerproduksiIds = [];
            $hasLockedRecord = false;

            foreach ($produksiHariIni as $p) {
                $totalPcsHariIni += (int)$p['kuantitas_pcs'];
                $totalBalHariIni += (int)$p['kuantitas_bal'];
                $totalLemburPcsHariIni += (int)$p['lembur_pcs'];
                $totalLemburBalHariIni += (int)$p['lembur_bal'];
                $totalUpahHariIni += (float)$p['total_upah_didapat'];
                $karyawanBerproduksiIds[$p['karyawan_id']] = true;
                if (!empty($p['penggajian_id'])) {
                    $hasLockedRecord = true;
                }
            }

            $totalKaryawanBorongan = count($karyawanBorongan);
            $totalKaryawanBerproduksi = count($karyawanBerproduksiIds);

            // Absensi Karyawan Hari Ini
            $absensiHariIni = Database::fetchAll("
                SELECT karyawan_id, status_kehadiran
                FROM public.absensi
                WHERE tanggal = :tanggal
            ", ['tanggal' => $tanggal]);

            $absensiMap = [];
            foreach ($absensiHariIni as $a) {
                $absensiMap[$a['karyawan_id']] = $a;
            }

            $this->view('produksi.index', [
                'pageTitle' => 'Produksi Borongan',
                'pageSubtitle' => 'Pencatatan Hasil Kerja Harian & Upah Borongan',
                'tanggal' => $tanggal,
                'selectedKaryawanId' => $selectedKaryawanId,
                'itemBorongan' => $itemBorongan,
                'karyawanBorongan' => $karyawanBorongan,
                'absensiMap' => $absensiMap,
                'produksiHariIni' => $produksiHariIni,
                'produksiPerKaryawan' => $produksiPerKaryawan,
                'totalPcsHariIni' => $totalPcsHariIni,
                'totalBalHariIni' => $totalBalHariIni,
                'totalLemburPcsHariIni' => $totalLemburPcsHariIni,
                'totalLemburBalHariIni' => $totalLemburBalHariIni,
                'totalUpahHariIni' => $totalUpahHariIni,
                'totalKaryawanBorongan' => $totalKaryawanBorongan,
                'totalKaryawanBerproduksi' => $totalKaryawanBerproduksi,
                'isTanggalLocked' => $hasLockedRecord
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Terjadi kesalahan saat memuat data produksi: ' . $e->getMessage());
            $this->redirect('/dashboard');
        }
    }

    /**
     * POST Simpan / Tambah Data Produksi Baru (Mendukung Multi-Item per Karyawan)
     */
    public function store(): void
    {
        Auth::requirePermission('hr.produksi_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/produksi');
            return;
        }

        $tanggal = (string)$this->input('tanggal', date('Y-m-d'));
        $karyawanId = (string)$this->input('karyawan_id', '');
        $isEdit = !empty($this->input('is_edit'));
        $itemsInput = $this->input('items', []);

        // Fallback untuk single-item submission biasa
        if (empty($itemsInput) || !is_array($itemsInput)) {
            $singleItemId = (string)$this->input('item_id', '');
            if (!empty($singleItemId)) {
                $itemsInput = [[
                    'item_id' => $singleItemId,
                    'kuantitas_pcs' => (int)$this->input('kuantitas_pcs', 0),
                    'kuantitas_bal' => (int)$this->input('kuantitas_bal', 0),
                    'lembur_pcs' => (int)$this->input('lembur_pcs', 0),
                    'lembur_bal' => (int)$this->input('lembur_bal', 0)
                ]];
            }
        }

        if (empty($karyawanId)) {
            $this->flashError('Karyawan borongan wajib dipilih.');
            $this->redirect('/produksi?tanggal=' . $tanggal);
            return;
        }

        if (empty($itemsInput) || !is_array($itemsInput)) {
            $this->flashError('Setidaknya tambahkan satu item produk untuk dicatat.');
            $this->redirect('/produksi?tanggal=' . $tanggal . '&karyawan_id=' . $karyawanId);
            return;
        }

        // Validasi: pastikan seluruh item yang diinput memiliki kuantitas > 0
        foreach ($itemsInput as $idx => $row) {
            $itemId = (string)($row['item_id'] ?? '');
            $pcs = max(0, (int)($row['kuantitas_pcs'] ?? 0));
            $lembur = max(0, (int)($row['lembur_pcs'] ?? 0));
            $totalPcs = $pcs + $lembur;

            if (empty($itemId)) {
                $this->flashError('Item produk pada baris ke-' . ($idx + 1) . ' belum dipilih.');
                $this->redirect('/produksi?tanggal=' . $tanggal . '&karyawan_id=' . $karyawanId);
                return;
            }

            if ($totalPcs <= 0) {
                $this->flashError('Jumlah output produksi (Pcs) tidak boleh 0 atau kosong pada baris ke-' . ($idx + 1) . '.');
                $this->redirect('/produksi?tanggal=' . $tanggal . '&karyawan_id=' . $karyawanId);
                return;
            }
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $savedCount = 0;
            $userId = Auth::id() ?: null;

            // Jika dalam mode edit (memperbarui catatan batch), hapus data yang belum terkunci payroll sebelumnya
            if ($isEdit) {
                $stmtCheckLocked = $pdo->prepare("
                    SELECT id FROM public.produksi_harian 
                    WHERE karyawan_id = :kid AND tanggal = :tgl AND penggajian_id IS NOT NULL 
                    LIMIT 1
                ");
                $stmtCheckLocked->execute(['kid' => $karyawanId, 'tgl' => $tanggal]);
                if ($stmtCheckLocked->fetch()) {
                    throw new \RuntimeException('Data produksi karyawan ini sudah terkunci oleh payroll dan tidak dapat diedit.');
                }

                $stmtDelOld = $pdo->prepare("
                    DELETE FROM public.produksi_harian 
                    WHERE karyawan_id = :kid AND tanggal = :tgl AND penggajian_id IS NULL
                ");
                $stmtDelOld->execute(['kid' => $karyawanId, 'tgl' => $tanggal]);
            }

            foreach ($itemsInput as $row) {
                $itemId = (string)($row['item_id'] ?? '');
                $kuantitasPcs = max(0, (int)($row['kuantitas_pcs'] ?? 0));
                $kuantitasBal = max(0, (int)($row['kuantitas_bal'] ?? 0));
                $lemburPcs = max(0, (int)($row['lembur_pcs'] ?? 0));
                $lemburBal = max(0, (int)($row['lembur_bal'] ?? 0));

                if (empty($itemId) || ($kuantitasPcs + $lemburPcs <= 0)) {
                    continue;
                }

                // Ambil info tarif snapshot
                $stmtItem = $pdo->prepare("
                    SELECT i.nama_item, k.upah_per_bungkus
                    FROM public.item i
                    JOIN public.kelompok_upah_borongan k ON k.id = i.kelompok_borongan_id
                    WHERE i.id = :item_id AND i.status_aktif = TRUE
                ");
                $stmtItem->execute(['item_id' => $itemId]);
                $itemInfo = $stmtItem->fetch(\PDO::FETCH_ASSOC);

                if (!$itemInfo) {
                    continue;
                }

                // Cek apakah data sudah terkunci payroll jika bukan mode edit
                if (!$isEdit) {
                    $stmtExist = $pdo->prepare("
                        SELECT id, penggajian_id 
                        FROM public.produksi_harian 
                        WHERE karyawan_id = :kid AND tanggal = :tgl AND item_id = :item_id
                    ");
                    $stmtExist->execute(['kid' => $karyawanId, 'tgl' => $tanggal, 'item_id' => $itemId]);
                    $existing = $stmtExist->fetch(\PDO::FETCH_ASSOC);

                    if ($existing && !empty($existing['penggajian_id'])) {
                        throw new \RuntimeException("Produk {$itemInfo['nama_item']} sudah terkunci oleh payroll dan tidak dapat dimodifikasi.");
                    }
                }

                $upahSnapshot = (float)$itemInfo['upah_per_bungkus'];
                $totalUpah = ($kuantitasPcs + $lemburPcs) * $upahSnapshot;

                $sql = "
                    INSERT INTO public.produksi_harian (
                        karyawan_id, tanggal, item_id,
                        kuantitas_pcs, kuantitas_bal, lembur_pcs, lembur_bal,
                        upah_per_pcs_snapshot, total_upah_didapat, dicatat_oleh,
                        dibuat_pada, diubah_pada
                    ) VALUES (
                        :kid, :tgl, :item_id,
                        :pcs, :bal, :lembur_pcs, :lembur_bal,
                        :upah_snapshot, :total_upah, :user_id,
                        NOW(), NOW()
                    )
                    ON CONFLICT (karyawan_id, tanggal, item_id) DO UPDATE SET
                        kuantitas_pcs = EXCLUDED.kuantitas_pcs,
                        kuantitas_bal = EXCLUDED.kuantitas_bal,
                        lembur_pcs = EXCLUDED.lembur_pcs,
                        lembur_bal = EXCLUDED.lembur_bal,
                        upah_per_pcs_snapshot = EXCLUDED.upah_per_pcs_snapshot,
                        total_upah_didapat = EXCLUDED.total_upah_didapat,
                        dicatat_oleh = EXCLUDED.dicatat_oleh,
                        diubah_pada = NOW()
                    WHERE public.produksi_harian.penggajian_id IS NULL;
                ";

                $stmtInsert = $pdo->prepare($sql);
                $stmtInsert->execute([
                    'kid' => $karyawanId,
                    'tgl' => $tanggal,
                    'item_id' => $itemId,
                    'pcs' => $kuantitasPcs,
                    'bal' => $kuantitasBal,
                    'lembur_pcs' => $lemburPcs,
                    'lembur_bal' => $lemburBal,
                    'upah_snapshot' => $upahSnapshot,
                    'total_upah' => $totalUpah,
                    'user_id' => $userId
                ]);

                $savedCount++;
            }

            if ($savedCount === 0) {
                $pdo->rollBack();
                $this->flashError('Tidak ada item dengan jumlah produksi lebih dari 0 yang dapat disimpan.');
                $this->redirect('/produksi?tanggal=' . $tanggal);
                return;
            }

            $pdo->commit();

            if ($isEdit) {
                ActivityLog::log(
                    'hr_payroll',
                    'EDIT_PRODUKSI',
                    "Memperbarui {$savedCount} item hasil produksi borongan untuk karyawan ID {$karyawanId} pada tanggal {$tanggal}.",
                    'produksi_harian'
                );
                $this->flashSuccess("Berhasil memperbarui {$savedCount} item hasil produksi borongan.");
            } else {
                ActivityLog::log(
                    'hr_payroll',
                    'INPUT_PRODUKSI',
                    "Mencatat {$savedCount} item produksi borongan untuk karyawan ID {$karyawanId} pada tanggal {$tanggal}.",
                    'produksi_harian'
                );
                $this->flashSuccess("Berhasil menyimpan {$savedCount} item hasil produksi borongan.");
            }

            $this->redirect('/produksi?tanggal=' . $tanggal);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menyimpan data produksi: ' . $this->cleanErrorMessage($e));
            $this->redirect('/produksi?tanggal=' . $tanggal);
        }
    }

    /**
     * POST Update Data Produksi
     */
    public function update(): void
    {
        Auth::requirePermission('hr.produksi_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/produksi');
            return;
        }

        $id = (string)$this->input('id', '');
        $tanggal = (string)$this->input('tanggal', date('Y-m-d'));
        $kuantitasPcs = (int)$this->input('kuantitas_pcs', 0);
        $kuantitasBal = (int)$this->input('kuantitas_bal', 0);
        $lemburPcs = (int)$this->input('lembur_pcs', 0);
        $lemburBal = (int)$this->input('lembur_bal', 0);

        if (($kuantitasPcs + $lemburPcs) <= 0) {
            $this->flashError('Jumlah kuantitas hasil produksi (Pcs) tidak boleh 0 atau kosong.');
            $this->redirect('/produksi?tanggal=' . $tanggal);
            return;
        }

        try {
            $prod = Database::fetchOne("
                SELECT id, upah_per_pcs_snapshot, penggajian_id 
                FROM public.produksi_harian 
                WHERE id = :id
            ", ['id' => $id]);

            if (!$prod) {
                $this->flashError('Catatan produksi tidak ditemukan.');
                $this->redirect('/produksi?tanggal=' . $tanggal);
                return;
            }

            if (!empty($prod['penggajian_id'])) {
                $this->flashError('Catatan produksi ini sudah terkunci oleh penggajian.');
                $this->redirect('/produksi?tanggal=' . $tanggal);
                return;
            }

            $upahSnapshot = (float)$prod['upah_per_pcs_snapshot'];
            $totalUpah = ($kuantitasPcs + $lemburPcs) * $upahSnapshot;

            Database::execute("
                UPDATE public.produksi_harian SET
                    kuantitas_pcs = :pcs,
                    kuantitas_bal = :bal,
                    lembur_pcs = :lembur_pcs,
                    lembur_bal = :lembur_bal,
                    total_upah_didapat = :total_upah,
                    diubah_pada = NOW()
                WHERE id = :id AND penggajian_id IS NULL
            ", [
                'id' => $id,
                'pcs' => $kuantitasPcs,
                'bal' => $kuantitasBal,
                'lembur_pcs' => $lemburPcs,
                'lembur_bal' => $lemburBal,
                'total_upah' => $totalUpah
            ]);

            ActivityLog::log('hr_payroll', 'EDIT_PRODUKSI', "Memperbarui data produksi ID {$id}.", 'produksi_harian', $id);

            $this->flashSuccess('Data produksi berhasil diperbarui.');
            $this->redirect('/produksi?tanggal=' . $tanggal);
        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui produksi: ' . $this->cleanErrorMessage($e));
            $this->redirect('/produksi?tanggal=' . $tanggal);
        }
    }

    /**
     * POST Hapus Catatan Produksi
     */
    public function delete(): void
    {
        Auth::requirePermission('hr.produksi_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/produksi');
            return;
        }

        $id = (string)$this->input('id', '');
        $karyawanId = (string)$this->input('karyawan_id', '');
        $tanggal = (string)$this->input('tanggal', date('Y-m-d'));

        try {
            if (!empty($karyawanId)) {
                // Hapus seluruh catatan produksi karyawan pada tanggal tersebut
                $locked = Database::fetchOne("
                    SELECT id FROM public.produksi_harian 
                    WHERE karyawan_id = :kid AND tanggal = :tgl AND penggajian_id IS NOT NULL 
                    LIMIT 1
                ", ['kid' => $karyawanId, 'tgl' => $tanggal]);

                if ($locked) {
                    $this->flashError('Tidak dapat menghapus produksi yang sudah terkunci oleh payroll.');
                    $this->redirect('/produksi?tanggal=' . $tanggal);
                    return;
                }

                Database::execute("
                    DELETE FROM public.produksi_harian 
                    WHERE karyawan_id = :kid AND tanggal = :tgl AND penggajian_id IS NULL
                ", ['kid' => $karyawanId, 'tgl' => $tanggal]);

                ActivityLog::log('hr_payroll', 'DELETE_PRODUKSI', "Menghapus seluruh catatan produksi karyawan ID {$karyawanId} pada tanggal {$tanggal}.", 'produksi_harian');

                $this->flashSuccess('Seluruh catatan hasil produksi karyawan berhasil dihapus.');
                $this->redirect('/produksi?tanggal=' . $tanggal);
                return;
            }

            if (!empty($id)) {
                $prod = Database::fetchOne("
                    SELECT id, penggajian_id 
                    FROM public.produksi_harian 
                    WHERE id = :id
                ", ['id' => $id]);

                if (!$prod) {
                    $this->flashError('Catatan produksi tidak ditemukan.');
                    $this->redirect('/produksi?tanggal=' . $tanggal);
                    return;
                }

                if (!empty($prod['penggajian_id'])) {
                    $this->flashError('Tidak dapat menghapus produksi yang sudah terkunci oleh payroll.');
                    $this->redirect('/produksi?tanggal=' . $tanggal);
                    return;
                }

                Database::execute("DELETE FROM public.produksi_harian WHERE id = :id AND penggajian_id IS NULL", ['id' => $id]);

                ActivityLog::log('hr_payroll', 'DELETE_PRODUKSI', "Menghapus catatan produksi ID {$id}.", 'produksi_harian', $id);

                $this->flashSuccess('Catatan produksi berhasil dihapus.');
                $this->redirect('/produksi?tanggal=' . $tanggal);
                return;
            }

            $this->flashError('Parameter penghapusan tidak lengkap.');
            $this->redirect('/produksi?tanggal=' . $tanggal);
        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus produksi: ' . $this->cleanErrorMessage($e));
            $this->redirect('/produksi?tanggal=' . $tanggal);
        }
    }

    /**
     * Halaman Riwayat Produksi
     */
    public function history(): void
    {
        try {
            $tglAwal = (string)$this->input('tanggal_awal', date('Y-m-01'));
            $tglAkhir = (string)$this->input('tanggal_akhir', date('Y-m-d'));
            $karyawanId = (string)$this->input('karyawan_id', '');
            $itemId = (string)$this->input('item_id', '');

            $history = Database::fetchAll("
                SELECT
                    ph.id, ph.karyawan_id, ph.item_id, ph.tanggal,
                    ph.kuantitas_pcs, ph.kuantitas_bal,
                    ph.lembur_pcs, ph.lembur_bal, ph.upah_per_pcs_snapshot, ph.total_upah_didapat,
                    ph.penggajian_id,
                    v.nama_karyawan, v.nama_panggilan, v.posisi,
                    i.nama_item, i.kode_sku,
                    k.nama_kelompok,
                    p.nomor_referensi as nomor_payroll
                FROM public.produksi_harian ph
                JOIN public.v_karyawan_info v ON v.id = ph.karyawan_id
                JOIN public.item i ON i.id = ph.item_id
                JOIN public.kelompok_upah_borongan k ON k.id = i.kelompok_borongan_id
                LEFT JOIN public.penggajian p ON p.id = ph.penggajian_id
                WHERE ph.tanggal BETWEEN :tgl_awal AND :tgl_akhir
                  AND (:kid = '' OR ph.karyawan_id = :kid_uuid)
                  AND (:item_id = '' OR ph.item_id = :item_uuid)
                ORDER BY ph.tanggal DESC, v.nama_karyawan ASC, i.nama_item ASC
            ", [
                'tgl_awal' => $tglAwal,
                'tgl_akhir' => $tglAkhir,
                'kid' => $karyawanId,
                'kid_uuid' => !empty($karyawanId) ? $karyawanId : null,
                'item_id' => $itemId,
                'item_uuid' => !empty($itemId) ? $itemId : null
            ]);

            $karyawanList = Database::fetchAll("
                SELECT id, nama_karyawan, nama_panggilan, posisi
                FROM public.v_karyawan_info 
                WHERE status_aktif = TRUE AND tipe_penggajian = 'borongan'
                ORDER BY nama_karyawan ASC
            ");

            $itemList = Database::fetchAll("
                SELECT i.id, i.nama_item, i.kode_sku, k.nama_kelompok
                FROM public.item i
                LEFT JOIN public.kelompok_upah_borongan k ON k.id = i.kelompok_borongan_id
                WHERE i.status_aktif = TRUE AND i.kelompok_borongan_id IS NOT NULL 
                ORDER BY i.nama_item ASC
            ");

            // Totals
            $totalPcs = 0;
            $totalBal = 0;
            $totalLemburPcs = 0;
            $totalLemburBal = 0;
            $totalUpah = 0.00;

            // Grouping riwayat per tanggal dan per karyawan (1 Baris = 1 Karyawan)
            $historyGrouped = [];
            foreach ($history as $h) {
                $totalPcs += (int)$h['kuantitas_pcs'];
                $totalBal += (int)$h['kuantitas_bal'];
                $totalLemburPcs += (int)$h['lembur_pcs'];
                $totalLemburBal += (int)$h['lembur_bal'];
                $totalUpah += (float)$h['total_upah_didapat'];

                $groupKey = $h['tanggal'] . '_' . $h['karyawan_id'];
                if (!isset($historyGrouped[$groupKey])) {
                    $historyGrouped[$groupKey] = [
                        'tanggal' => $h['tanggal'],
                        'karyawan_id' => $h['karyawan_id'],
                        'nama_karyawan' => $h['nama_karyawan'],
                        'nama_panggilan' => $h['nama_panggilan'] ?? '',
                        'posisi' => $h['posisi'] ?? 'Pengemasan',
                        'items' => [],
                        'total_pcs' => 0,
                        'total_bal' => 0,
                        'total_lembur_pcs' => 0,
                        'total_lembur_bal' => 0,
                        'total_upah' => 0.0,
                        'is_locked' => false,
                        'nomor_payroll' => null,
                        'has_lembur' => false
                    ];
                }

                $hasLembur = ((int)$h['lembur_pcs'] > 0 || (int)$h['lembur_bal'] > 0);
                if ($hasLembur) {
                    $historyGrouped[$groupKey]['has_lembur'] = true;
                }

                $historyGrouped[$groupKey]['items'][] = $h;
                $historyGrouped[$groupKey]['total_pcs'] += (int)$h['kuantitas_pcs'];
                $historyGrouped[$groupKey]['total_bal'] += (int)$h['kuantitas_bal'];
                $historyGrouped[$groupKey]['total_lembur_pcs'] += (int)$h['lembur_pcs'];
                $historyGrouped[$groupKey]['total_lembur_bal'] += (int)$h['lembur_bal'];
                $historyGrouped[$groupKey]['total_upah'] += (float)$h['total_upah_didapat'];

                if (!empty($h['penggajian_id'])) {
                    $historyGrouped[$groupKey]['is_locked'] = true;
                    $historyGrouped[$groupKey]['nomor_payroll'] = $h['nomor_payroll'];
                }
            }

            // Rekapitulasi Pemakaian Bahan Mentah Curah (Bal) dalam rentang filter
            $rekapBalBahanRaw = Database::fetchAll("
                SELECT 
                    ib.id as item_bahan_id,
                    ib.nama_item as nama_bahan,
                    ib.satuan_dasar,
                    SUM(ph.kuantitas_bal + ph.lembur_bal) as total_bal_terpakai,
                    SUM(ph.kuantitas_pcs + ph.lembur_pcs) as total_pcs_dihasilkan,
                    COUNT(DISTINCT ph.karyawan_id) as jumlah_karyawan
                FROM public.produksi_harian ph
                JOIN public.komposisi_item ki ON ki.item_jadi_id = ph.item_id AND ki.potong_sesuai_bal = TRUE
                JOIN public.item ib ON ib.id = ki.item_bahan_id
                WHERE ph.tanggal BETWEEN :tgl_awal AND :tgl_akhir
                  AND (:kid = '' OR ph.karyawan_id = :kid_uuid)
                  AND (:item_id = '' OR ph.item_id = :item_uuid)
                GROUP BY ib.id, ib.nama_item, ib.satuan_dasar
                ORDER BY total_bal_terpakai DESC, ib.nama_item ASC
            ", [
                'tgl_awal' => $tglAwal,
                'tgl_akhir' => $tglAkhir,
                'kid' => $karyawanId,
                'kid_uuid' => !empty($karyawanId) ? $karyawanId : null,
                'item_id' => $itemId,
                'item_uuid' => !empty($itemId) ? $itemId : null
            ]);

            $rekapBalBahan = [];
            foreach ($rekapBalBahanRaw as $rkb) {
                $balUsed = (float)$rkb['total_bal_terpakai'];
                $pcsProduced = (int)$rkb['total_pcs_dihasilkan'];
                $yieldReal = ($balUsed > 0) ? round($pcsProduced / $balUsed, 1) : 0;
                $rekapBalBahan[] = [
                    'item_bahan_id' => $rkb['item_bahan_id'],
                    'nama_bahan' => $rkb['nama_bahan'],
                    'satuan_dasar' => $rkb['satuan_dasar'] ?: 'bal',
                    'total_bal_terpakai' => $balUsed,
                    'total_bal_formatted' => number_format($balUsed, 0, ',', '.'),
                    'total_pcs_dihasilkan' => $pcsProduced,
                    'total_pcs_formatted' => number_format($pcsProduced, 0, ',', '.'),
                    'jumlah_karyawan' => (int)$rkb['jumlah_karyawan'],
                    'yield_real' => $yieldReal
                ];
            }

            if ($this->isAjax() || (isset($_GET['ajax']) && $_GET['ajax'] === '1')) {
                $formattedRows = [];
                foreach ($historyGrouped as $emp) {
                    $parts = explode(' ', trim($emp['nama_karyawan']));
                    $initials = '';
                    foreach (array_slice($parts, 0, 2) as $p) {
                        $initials .= mb_substr($p, 0, 1);
                    }
                    $emp['initials'] = strtoupper($initials ?: 'KR');
                    $emp['tanggal_indo'] = Format::tanggalIndo($emp['tanggal']);
                    $emp['total_upah_formatted'] = Format::rupiah((float)$emp['total_upah']);
                    $formattedRows[] = $emp;
                }

                $this->json([
                    'success' => true,
                    'totalPcs' => $totalPcs,
                    'totalBal' => $totalBal,
                    'totalLemburPcs' => $totalLemburPcs,
                    'totalLemburBal' => $totalLemburBal,
                    'totalUpah' => $totalUpah,
                    'totalUpahFormatted' => Format::rupiah($totalUpah),
                    'periodeText' => Format::tanggalIndo($tglAwal) . ' s/d ' . Format::tanggalIndo($tglAkhir),
                    'rekapBalBahan' => $rekapBalBahan,
                    'historyGrouped' => $formattedRows
                ]);
                return;
            }

            $this->view('produksi.history', [
                'pageTitle' => 'Riwayat Produksi Borongan',
                'pageSubtitle' => 'Laporan Hasil Kerja & Akumulasi Upah',
                'tglAwal' => $tglAwal,
                'tglAkhir' => $tglAkhir,
                'karyawanId' => $karyawanId,
                'itemId' => $itemId,
                'karyawanList' => $karyawanList,
                'itemList' => $itemList,
                'history' => $history,
                'historyGrouped' => $historyGrouped,
                'rekapBalBahan' => $rekapBalBahan,
                'totalPcs' => $totalPcs,
                'totalBal' => $totalBal,
                'totalLemburPcs' => $totalLemburPcs,
                'totalLemburBal' => $totalLemburBal,
                'totalUpah' => $totalUpah
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat riwayat produksi: ' . $e->getMessage());
            $this->redirect('/produksi');
        }
    }

    /**
     * Ekstrak pesan human-friendly dari exception trigger PostgreSQL
     */
    private function cleanErrorMessage(Throwable $e): string
    {
        $msg = $e->getMessage();
        if (preg_match('/ERROR:\s*(.+?)(?:\n|CONTEXT:|$)/is', $msg, $matches)) {
            return trim($matches[1]);
        }
        return $msg;
    }
}
