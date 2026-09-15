<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/PricingController.php
 * Pengendali Matriks 28+ Tingkat Harga Jual & Diskon Grup Pelanggan.
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
            // 1. Ambil seluruh grup produk beserta daftar level harga aktifnya
            $groups = Database::fetchAll("
                SELECT id, kode_grup, nama_grup, barcode_universal, konversi_bal_ke_pcs
                FROM public.grup_produk
                WHERE status_aktif = TRUE
                ORDER BY kode_grup ASC
            ");

            $priceLevels = Database::fetchAll("
                SELECT phl.id, phl.grup_produk_id, phl.level_harga, phl.nama_level,
                       phl.harga_jual_pcs, phl.harga_jual_bal
                FROM public.grup_produk_harga_level phl
                ORDER BY phl.level_harga ASC
            ");

            // Kelompokkan harga per grup
            $groupedPrices = [];
            foreach ($priceLevels as $pl) {
                $groupedPrices[$pl['grup_produk_id']][] = $pl;
            }

            // 2. Ambil grup pelanggan
            $customerGroups = Database::fetchAll("
                SELECT id, kode_grup, nama_grup, default_level_harga, diskon_persen_default, diskon_nominal_default, status_aktif
                FROM public.grup_pelanggan
                ORDER BY default_level_harga ASC
            ");

            $this->view('pricing.index', [
                'pageTitle' => 'Matriks Level Harga Produk',
                'pageSubtitle' => 'Pengaturan 28 Tingkat Level Harga Jual Per Bungkus / Pcs',
                'groups' => $groups,
                'groupedPrices' => $groupedPrices,
                'customerGroups' => $customerGroups
            ]);

        } catch (Throwable $e) {
            $this->flashError("Gagal memuat matriks harga: " . $e->getMessage());
            $this->view('pricing.index', [
                'pageTitle' => 'Matriks Level Harga Produk',
                'pageSubtitle' => 'Pengaturan 28 Tingkat Level Harga Jual Per Bungkus / Pcs',
                'groups' => [],
                'groupedPrices' => [],
                'customerGroups' => []
            ]);
        }
    }

    public function updateLevelPrice(): void
    {
        Auth::requirePermission('master.pricing_manage');

        $groupId = $this->input('grup_produk_id');
        $level = (int)$this->input('level_harga', 1);
        $namaLevel = trim((string)$this->input('nama_level', "Level {$level}"));
        $hargaPcs = (float)preg_replace('/[^0-9]/', '', (string)$this->input('harga_jual_pcs', '0'));
        $hargaBal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('harga_jual_bal', '0'));

        if (empty($groupId) || $level < 1 || $level > 28) {
            $this->flashError('Grup produk dan level harga (1-28) tidak valid.');
            $this->redirect('/pricing');
            return;
        }

        try {
            Database::execute("
                INSERT INTO public.grup_produk_harga_level (
                    grup_produk_id, level_harga, nama_level, harga_jual_pcs, harga_jual_bal, diubah_pada
                ) VALUES (
                    :group_id, :level, :nama, :pcs, :bal, NOW()
                )
                ON CONFLICT (grup_produk_id, level_harga) 
                DO UPDATE SET 
                    nama_level = EXCLUDED.nama_level,
                    harga_jual_pcs = EXCLUDED.harga_jual_pcs,
                    harga_jual_bal = EXCLUDED.harga_jual_bal,
                    diubah_pada = NOW()
            ", [
                'group_id' => $groupId,
                'level' => $level,
                'nama' => $namaLevel,
                'pcs' => $hargaPcs,
                'bal' => $hargaBal
            ]);

            $this->flashSuccess("Level harga {$level} berhasil disimpan!");
            $this->redirect('/pricing');

        } catch (Throwable $e) {
            $this->flashError('Gagal update level harga: ' . $e->getMessage());
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
            Database::execute("DELETE FROM public.grup_produk_harga_level WHERE id = :id", ['id' => $id]);
            $this->flashSuccess('Level harga berhasil dihapus.');
            $this->redirect('/pricing');
        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus level harga: ' . $e->getMessage());
            $this->redirect('/pricing');
        }
    }
}

