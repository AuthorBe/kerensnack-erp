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
        Auth::requireRole(['owner', 'admin']);
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
                SELECT id, kode_grup, nama_grup, default_level_harga, diskon_persen_default, diskon_nominal_default
                FROM public.grup_pelanggan
                WHERE status_aktif = TRUE
                ORDER BY default_level_harga ASC
            ");

            $this->view('pricing.index', [
                'pageTitle' => 'Matriks Harga Jual Dinamis',
                'pageSubtitle' => 'Unlimited Level Harga (Level 1 - 28+) & Diskon Toko',
                'groups' => $groups,
                'groupedPrices' => $groupedPrices,
                'customerGroups' => $customerGroups
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    public function updateLevelPrice(): void
    {
        $groupId = $this->input('group_id');
        $level = (int)$this->input('level_harga', 1);
        $namaLevel = $this->input('nama_level', "Level {$level}");
        $hargaPcs = (float)$this->input('harga_pcs', 0);
        $hargaBal = (float)$this->input('harga_bal', 0);

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

            $this->redirect('/pricing?success=1');

        } catch (Throwable $e) {
            echo "Gagal update harga: " . $e->getMessage();
        }
    }
}
