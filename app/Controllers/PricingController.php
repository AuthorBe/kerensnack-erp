<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\Format;
use Database;
use Throwable;

/**
 * app/Controllers/PricingController.php
 * Pengendali Matriks 30 Tingkat Level Harga Jual Per Bungkus / Pcs.
 */
class PricingController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission(['master.pricing_view', 'master.pricing_manage']);
    }

    public function index(): void
    {
        try {
            // 1. Ambil master level harga (Level 1 s/d 30)
            $masterLevels = Database::fetchAll("
                SELECT level_nomor, nama_level, deskripsi, status_aktif
                FROM public.master_level_harga
                ORDER BY level_nomor ASC
            ");

            // 2. Ambil seluruh grup produk aktif
            $groups = Database::fetchAll("
                SELECT id, kode_grup, nama_grup, barcode_universal
                FROM public.grup_produk
                WHERE status_aktif = TRUE
                ORDER BY kode_grup ASC
            ");

            // 3. Ambil seluruh level harga aktif (murni harga_jual_pcs)
            $priceLevels = Database::fetchAll("
                SELECT phl.id, phl.grup_produk_id, phl.level_harga,
                       COALESCE(mlh.nama_level, 'Level ' || phl.level_harga) as nama_level,
                       phl.harga_jual_pcs
                FROM public.grup_produk_harga_level phl
                LEFT JOIN public.master_level_harga mlh ON phl.level_harga = mlh.level_nomor
                ORDER BY phl.level_harga ASC
            ");

            // Kelompokkan harga per grup
            $groupedPrices = [];
            foreach ($priceLevels as $pl) {
                $groupedPrices[$pl['grup_produk_id']][] = $pl;
            }

            // 4. Ambil grup pelanggan
            $customerGroups = Database::fetchAll("
                SELECT gp.id, gp.kode_grup, gp.nama_grup, gp.default_level_harga,
                       gp.diskon_persen_default, gp.diskon_nominal_default, gp.status_aktif,
                       mlh.nama_level as master_nama_level
                FROM public.grup_pelanggan gp
                LEFT JOIN public.master_level_harga mlh ON gp.default_level_harga = mlh.level_nomor
                ORDER BY gp.default_level_harga ASC
            ");

            $this->view('pricing.index', [
                'pageTitle' => 'Matriks Level Harga Produk',
                'pageSubtitle' => 'Pengaturan 30 Tingkat Level Harga Jual Per Bungkus / Pcs',
                'groups' => $groups,
                'groupedPrices' => $groupedPrices,
                'customerGroups' => $customerGroups,
                'masterLevels' => $masterLevels
            ]);

        } catch (Throwable $e) {
            $this->flashError("Gagal memuat matriks harga: " . $e->getMessage());
            $this->view('pricing.index', [
                'pageTitle' => 'Matriks Level Harga Produk',
                'pageSubtitle' => 'Pengaturan 30 Tingkat Level Harga Jual Per Bungkus / Pcs',
                'groups' => [],
                'groupedPrices' => [],
                'customerGroups' => [],
                'masterLevels' => []
            ]);
        }
    }

    /**
     * Tambah atau Ubah Harga Level Suatu Grup Produk
     */
    public function storeLevel(): void
    {
        Auth::requirePermission('master.pricing_manage');

        $id = trim((string)$this->input('id', ''));
        $groupId = trim((string)$this->input('grup_produk_id', ''));
        $level = (int)$this->input('level_harga', 1);
        $hargaPcs = (float)preg_replace('/[^0-9]/', '', (string)$this->input('harga_jual_pcs', '0'));

        if (empty($groupId)) {
            $this->flashError('Grup produk tidak valid.');
            $this->redirect('/pricing');
            return;
        }

        try {
            $group = Database::fetchOne("SELECT nama_grup FROM public.grup_produk WHERE id = :gid", ['gid' => $groupId]);
            $groupName = $group['nama_grup'] ?? 'Grup Produk';

            // Mode UBAH (Edit baris yang sudah ada)
            if (!empty($id)) {
                $existing = Database::fetchOne("
                    SELECT id, grup_produk_id, level_harga, harga_jual_pcs 
                    FROM public.grup_produk_harga_level 
                    WHERE id = :id AND grup_produk_id = :gid
                ", ['id' => $id, 'gid' => $groupId]);

                if (!$existing) {
                    $this->flashError('Data level harga yang ingin diubah tidak ditemukan.');
                    $this->redirect('/pricing');
                    return;
                }

                $targetLevel = (int)$existing['level_harga'];
                $oldPrice = (float)$existing['harga_jual_pcs'];

                Database::execute("
                    UPDATE public.grup_produk_harga_level
                    SET harga_jual_pcs = :pcs,
                        diubah_pada = NOW()
                    WHERE id = :id
                ", [
                    'id' => $id,
                    'pcs' => $hargaPcs
                ]);

                ActivityLog::log(
                    'master_data',
                    'PRICE_CHANGE',
                    "Pembaruan harga Level {$targetLevel} pada grup '{$groupName}' dari Rp " . number_format($oldPrice, 0, ',', '.') . " menjadi Rp " . number_format($hargaPcs, 0, ',', '.'),
                    'grup_produk_harga_level',
                    $id,
                    ['harga_jual_pcs' => $oldPrice, 'level_harga' => $targetLevel],
                    ['harga_jual_pcs' => $hargaPcs, 'level_harga' => $targetLevel]
                );

                $this->flashSuccess("Harga Level {$targetLevel} berhasil diperbarui!");
                $this->redirect('/pricing');
                return;
            }

            // Mode TAMBAH (Level baru)
            if ($level < 1 || $level > 30) {
                $this->flashError('Tingkat level harga harus antara 1 sampai 30.');
                $this->redirect('/pricing');
                return;
            }

            // Cek proteksi anti-duplikasi: Pastikan level belum ada di grup ini
            $dup = Database::fetchOne("
                SELECT id 
                FROM public.grup_produk_harga_level 
                WHERE grup_produk_id = :gid AND level_harga = :lvl
            ", ['gid' => $groupId, 'lvl' => $level]);

            if ($dup) {
                $this->flashError("Level {$level} sudah terdaftar pada grup produk ini. Klik tombol Ubah pada kartu level jika ingin memperbarui harga.");
                $this->redirect('/pricing');
                return;
            }

            $stmtInsert = Database::getConnection()->prepare("
                INSERT INTO public.grup_produk_harga_level (
                    grup_produk_id, level_harga, harga_jual_pcs, dibuat_pada, diubah_pada
                ) VALUES (
                    :group_id, :level, :pcs, NOW(), NOW()
                ) RETURNING id
            ");
            $stmtInsert->execute([
                'group_id' => $groupId,
                'level' => $level,
                'pcs' => $hargaPcs
            ]);
            $newLevelId = $stmtInsert->fetchColumn() ?: null;

            ActivityLog::log(
                'master_data',
                'CREATE',
                "Menambahkan harga jual Level {$level} pada grup '{$groupName}' sebesar Rp " . number_format($hargaPcs, 0, ',', '.'),
                'grup_produk_harga_level',
                $newLevelId ? (string)$newLevelId : null,
                null,
                ['grup_produk_id' => $groupId, 'level_harga' => $level, 'harga_jual_pcs' => $hargaPcs]
            );

            $this->flashSuccess("Level harga {$level} berhasil ditambahkan!");
            $this->redirect('/pricing');

        } catch (Throwable $e) {
            $this->flashError('Gagal menyimpan level harga: ' . $e->getMessage());
            $this->redirect('/pricing');
        }
    }

    /**
     * Alias untuk rute POST /pricing/update-level
     */
    public function updateLevelPrice(): void
    {
        $this->storeLevel();
    }

    /**
     * Ubah Nama & Deskripsi Master Level Acuan Sistem (1 s/d 30)
     */
    public function updateMasterLevel(): void
    {
        Auth::requirePermission('master.pricing_manage');

        $levelNomor = (int)$this->input('level_nomor', 0);
        $namaLevel = trim((string)$this->input('nama_level', ''));
        $deskripsi = trim((string)$this->input('deskripsi', ''));

        if ($levelNomor < 1 || $levelNomor > 30) {
            $this->flashError('Nomor level harus antara 1 sampai 30.');
            $this->redirect('/pricing');
            return;
        }

        if (empty($namaLevel)) {
            $this->flashError('Nama level acuan tidak boleh kosong.');
            $this->redirect('/pricing');
            return;
        }

        try {
            $old = Database::fetchOne("SELECT nama_level, deskripsi FROM public.master_level_harga WHERE level_nomor = :lvl", ['lvl' => $levelNomor]);

            Database::execute("
                UPDATE public.master_level_harga
                SET nama_level = :nama,
                    deskripsi = :deskripsi
                WHERE level_nomor = :lvl
            ", [
                'nama' => $namaLevel,
                'deskripsi' => $deskripsi,
                'lvl' => $levelNomor
            ]);

            ActivityLog::log(
                'master_data',
                'UPDATE',
                "Memperbarui Master Level Acuan #{$levelNomor}: '{$namaLevel}'",
                'master_level_harga',
                (string)$levelNomor,
                $old,
                ['nama_level' => $namaLevel, 'deskripsi' => $deskripsi]
            );

            $this->flashSuccess("Nama acuan Level {$levelNomor} berhasil diperbarui!");
            $this->redirect('/pricing');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui master level acuan: ' . $e->getMessage());
            $this->redirect('/pricing');
        }
    }

    public function deleteLevelPrice(): void
    {
        Auth::requirePermission('master.pricing_manage');

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID level harga tidak valid.');
            $this->redirect('/pricing');
            return;
        }

        try {
            $row = Database::fetchOne("
                SELECT phl.id, phl.level_harga, phl.harga_jual_pcs, phl.grup_produk_id, gp.nama_grup 
                FROM public.grup_produk_harga_level phl 
                JOIN public.grup_produk gp ON phl.grup_produk_id = gp.id 
                WHERE phl.id = :id
            ", ['id' => $id]);

            if (!$row) {
                $this->flashError('Data level harga tidak ditemukan.');
                $this->redirect('/pricing');
                return;
            }

            $levelHarga = (int)$row['level_harga'];

            if ($levelHarga === 1) {
                $this->flashError('Level 1 (Ritel Standar) adalah harga dasar acuan utama dan tidak boleh dihapus.');
                $this->redirect('/pricing');
                return;
            }

            $inUse = Database::fetchOne("
                SELECT COUNT(*) as total FROM public.grup_pelanggan WHERE default_level_harga = :lvl
            ", ['lvl' => $levelHarga]);

            if ((int)($inUse['total'] ?? 0) > 0) {
                $this->flashError("Level {$levelHarga} sedang aktif digunakan oleh grup pelanggan dan tidak boleh dihapus.");
                $this->redirect('/pricing');
                return;
            }

            Database::execute("DELETE FROM public.grup_produk_harga_level WHERE id = :id", ['id' => $id]);

            ActivityLog::log(
                'master_data',
                'DELETE',
                "Menghapus harga jual Level {$levelHarga} pada grup '{$row['nama_grup']}'",
                'grup_produk_harga_level',
                (string)$id,
                ['grup_produk_id' => $row['grup_produk_id'], 'level_harga' => $levelHarga, 'harga_jual_pcs' => (float)$row['harga_jual_pcs']],
                null
            );

            $this->flashSuccess("Level harga {$levelHarga} berhasil dihapus dari grup {$row['nama_grup']}.");
            $this->redirect('/pricing');

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus level harga: ' . $e->getMessage());
            $this->redirect('/pricing');
        }
    }
}
