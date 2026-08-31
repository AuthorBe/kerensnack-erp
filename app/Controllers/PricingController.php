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
            echo "Database Error: " . $e->getMessage();
        }
    }

    public function updateLevelPrice(): void
    {
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

    public function storeCustomerGroup(): void
    {
        $nama = trim((string)$this->input('nama_grup'));
        $kode = trim((string)$this->input('kode_grup'));
        $level = (int)$this->input('default_level_harga', 1);
        $discPersen = (float)$this->input('diskon_persen_default', 0);
        $discNominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('diskon_nominal_default', '0'));

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

            $this->flashSuccess("Grup pelanggan {$nama} berhasil ditambahkan!");
            $this->redirect('/customers?tab=customer_groups');

        } catch (Throwable $e) {
            $this->flashError('Gagal menambahkan grup pelanggan: ' . $e->getMessage());
            $this->redirect('/customers?tab=customer_groups');
        }
    }

    public function updateCustomerGroup(): void
    {
        $id = $this->input('id');
        $nama = trim((string)$this->input('nama_grup'));
        $rawKode = trim((string)$this->input('kode_grup'));
        $suffix = strtoupper((string)preg_replace('/[^A-Z0-9-]/', '', (string)preg_replace('/^GRP-?/i', '', $rawKode)));
        $suffix = substr($suffix, 0, 10);
        $kode = 'GRP-' . ($suffix ?: '01');
        $level = (int)$this->input('default_level_harga', 1);
        $discPersen = (float)$this->input('diskon_persen_default', 0);
        $discNominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('diskon_nominal_default', '0'));
        $statusAktif = (bool)$this->input('status_aktif', true);

        if (empty($id) || empty($nama)) {
            $this->flashError('Data grup pelanggan tidak valid.');
            $this->redirect('/customers?tab=customer_groups');
            return;
        }

        try {
            Database::execute("
                UPDATE public.grup_pelanggan SET
                    nama_grup = :nama,
                    kode_grup = :kode,
                    default_level_harga = :level,
                    diskon_persen_default = :disc_p,
                    diskon_nominal_default = :disc_n,
                    status_aktif = :status,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $id,
                'nama' => $nama,
                'kode' => $kode,
                'level' => $level,
                'disc_p' => $discPersen,
                'disc_n' => $discNominal,
                'status' => $statusAktif ? 'true' : 'false'
            ]);

            $this->flashSuccess("Grup pelanggan {$nama} berhasil diperbarui!");
            $this->redirect('/customers?tab=customer_groups');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui grup pelanggan: ' . $e->getMessage());
            $this->redirect('/customers?tab=customer_groups');
        }
    }

    public function deleteCustomerGroup(): void
    {
        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID grup pelanggan tidak valid.');
            $this->redirect('/customers?tab=customer_groups');
            return;
        }

        try {
            // Cek apakah ada pelanggan yang sedang menggunakan grup ini
            $used = Database::fetchOne("SELECT count(*) as total FROM public.pelanggan WHERE grup_pelanggan_id = :id", ['id' => $id])['total'] ?? 0;
            if ($used > 0) {
                $this->flashError("Grup tidak dapat dihapus karena masih digunakan oleh {$used} toko pelanggan. Silakan nonaktifkan statusnya.");
                $this->redirect('/customers?tab=customer_groups');
                return;
            }

            Database::execute("DELETE FROM public.grup_pelanggan WHERE id = :id", ['id' => $id]);
            $this->flashSuccess('Grup pelanggan berhasil dihapus.');
            $this->redirect('/customers?tab=customer_groups');

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus grup pelanggan: ' . $e->getMessage());
            $this->redirect('/customers?tab=customer_groups');
        }
    }
}

