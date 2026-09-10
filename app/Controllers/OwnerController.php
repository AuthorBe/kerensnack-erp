<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\Format;
use App\Helpers\ActivityLog;
use Database;
use Throwable;

/**
 * app/Controllers/OwnerController.php
 * Pengendali Owner Executive Command Center, Live AI Stream & Approval Hub Konsinyasi.
 */
class OwnerController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission('owner.dashboard');
    }

    public function index(): void
    {
        try {
            // 1. Ringkasan Eksekutif Keuangan Hari Ini
            $omzetToday = Database::fetchOne("
                SELECT COALESCE(SUM(total_netto), 0) as total 
                FROM public.pesanan 
                WHERE tanggal_pesanan = CURRENT_DATE AND status_pembayaran != 'dibatalkan'
            ")['total'] ?? 0;

            $totalSaldoKas = Database::fetchOne("
                SELECT COALESCE(SUM(saldo_saat_ini), 0) as total 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE
            ")['total'] ?? 0;

            $totalPiutang = Database::fetchOne("
                SELECT COALESCE(SUM(total_piutang_berjalan), 0) as total 
                FROM public.pelanggan 
                WHERE status_aktif = TRUE
            ")['total'] ?? 0;

            // 2. Draf Pengeluaran yang Menunggu Persetujuan
            $pendingDrafts = Database::fetchAll("
                SELECT dp.id, dp.nominal, dp.kategori_beban, dp.keterangan_mentah, dp.keterangan_ai,
                       dp.url_foto_nota, dp.dibuat_pada, p.nama_lengkap as pemohon
                FROM public.draf_pengeluaran dp
                LEFT JOIN public.pengguna p ON dp.diajukan_oleh_pengguna_id = p.id
                WHERE dp.status_approval = 'menunggu'
                ORDER BY dp.dibuat_pada DESC
            ");

            // =========================================================================
            // FASE 3: MODUL OWNER KONSINYASI COMMAND CENTER (C1 s/d C6)
            // =========================================================================

            // C1: Rekap Omzet Konsinyasi vs Direct Orders (Bulan Ini)
            $omzetComparison = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(CASE WHEN tipe_pembayaran = 'konsinyasi' AND adalah_tagihan = TRUE THEN total_netto ELSE 0 END), 0) as omzet_konsinyasi,
                    COALESCE(SUM(CASE WHEN tipe_pembayaran != 'konsinyasi' THEN total_netto ELSE 0 END), 0) as omzet_direct
                FROM public.pesanan
                WHERE tanggal_pesanan >= DATE_TRUNC('month', CURRENT_DATE)
                  AND status_pembayaran != 'dibatalkan'
            ");

            $topStores = Database::fetchAll("
                SELECT p.id, p.nama_toko, p.kode_pelanggan,
                       COALESCE(SUM(kk.total_laku_nominal), 0) as total_omzet,
                       COUNT(kk.id) as total_kunjungan
                FROM public.pelanggan p
                LEFT JOIN public.kunjungan_konsinyasi kk ON kk.pelanggan_id = p.id AND kk.tanggal_kunjungan >= DATE_TRUNC('month', CURRENT_DATE)
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                GROUP BY p.id, p.nama_toko, p.kode_pelanggan
                ORDER BY total_omzet DESC
                LIMIT 5
            ");

            // C2: Approval Pengiriman Satu Pintu (Gatekeeper Owner)
            $pendingConsignmentShipments = Database::fetchAll("
                SELECT sj.id as surat_jalan_id, sj.nomor_surat_jalan, sj.dibuat_pada,
                       pes.id as pesanan_id, pes.nomor_nota, pes.catatan,
                       p.nama_toko, p.kode_pelanggan,
                       COALESCE(k.nama_karyawan, peng.nama_lengkap, 'Sales Driver') as nama_sales
                FROM public.surat_jalan sj
                JOIN public.pesanan pes ON sj.pesanan_id = pes.id
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.v_karyawan_info k ON sj.sales_driver_id = k.id
                LEFT JOIN public.pengguna peng ON pes.dibuat_oleh = peng.id
                WHERE sj.status_surat_jalan = 'draf_n8n'
                ORDER BY sj.dibuat_pada DESC
            ");

            // Ambil rincian SKU untuk masing-masing pengiriman draft
            foreach ($pendingConsignmentShipments as &$shipment) {
                $shipment['items'] = Database::fetchAll("
                    SELECT ip.kuantitas_satuan_dasar, i.nama_item, i.kode_sku, i.satuan_dasar, i.stok_fisik_saat_ini
                    FROM public.item_pesanan ip
                    JOIN public.item i ON ip.item_id = i.id
                    WHERE ip.pesanan_id = :p
                ", ['p' => $shipment['pesanan_id']]);
            }
            unset($shipment);

            // C3: Rekap Komisi Karyawan Sales (Bulan Ini)
            $salesCommissions = Database::fetchAll("
                SELECT k.id as sales_id, k.nama_karyawan, k.nomor_telepon,
                       COALESCE(NULLIF(k.persentase_komisi_sales, 0), 2.50) as persentase_komisi,
                       COUNT(DISTINCT p.id) as total_toko_binaan,
                       COALESCE(SUM(kk.total_laku_nominal), 0) as total_omzet_laku,
                       (COALESCE(SUM(kk.total_laku_nominal), 0) * COALESCE(NULLIF(k.persentase_komisi_sales, 0), 2.50) / 100.0) as estimasi_komisi_rp
                FROM public.v_karyawan_info k
                LEFT JOIN public.pelanggan p ON p.sales_driver_id = k.id AND p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                LEFT JOIN public.kunjungan_konsinyasi kk ON kk.pelanggan_id = p.id AND kk.tanggal_kunjungan >= DATE_TRUNC('month', CURRENT_DATE)
                WHERE k.posisi = 'sales' AND k.status_aktif = TRUE
                GROUP BY k.id, k.nama_karyawan, k.nomor_telepon, k.persentase_komisi_sales
                ORDER BY total_omzet_laku DESC
            ");

            // C4: Outstanding Piutang Konsinyasi & Aging
            $agingSummary = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(CASE WHEN CURRENT_DATE - tanggal_pesanan < 14 THEN sisa_tagihan ELSE 0 END), 0) as aging_under_14,
                    COALESCE(SUM(CASE WHEN CURRENT_DATE - tanggal_pesanan BETWEEN 14 AND 30 THEN sisa_tagihan ELSE 0 END), 0) as aging_14_to_30,
                    COALESCE(SUM(CASE WHEN CURRENT_DATE - tanggal_pesanan > 30 THEN sisa_tagihan ELSE 0 END), 0) as aging_over_30,
                    COALESCE(SUM(sisa_tagihan), 0) as total_outstanding
                FROM public.pesanan
                WHERE tipe_pembayaran = 'konsinyasi' AND adalah_tagihan = TRUE AND status_pembayaran != 'lunas' AND status_pembayaran != 'dibatalkan'
            ");

            $unpaidStoreList = Database::fetchAll("
                SELECT p.id, p.nama_toko, p.kode_pelanggan,
                       COALESCE(k.nama_karyawan, '—') as nama_sales,
                       SUM(pes.sisa_tagihan) as total_sisa_tagihan,
                       COUNT(pes.id) as total_nota_belum_lunas
                FROM public.pesanan pes
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.v_karyawan_info k ON pes.sales_driver_id = k.id
                WHERE pes.tipe_pembayaran = 'konsinyasi' AND pes.adalah_tagihan = TRUE AND pes.status_pembayaran != 'lunas' AND pes.status_pembayaran != 'dibatalkan'
                GROUP BY p.id, p.nama_toko, p.kode_pelanggan, k.nama_karyawan
                ORDER BY total_sisa_tagihan DESC
                LIMIT 5
            ");

            // C5: Laporan Kerugian Barang Rusak (Retur Rusak)
            $lossReport = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(rkk.nilai_kerugian_rusak), 0) as total_kerugian_rusak,
                    COALESCE(SUM(rkk.retur_rusak), 0) as total_pcs_rusak
                FROM public.rincian_kunjungan_konsinyasi rkk
                JOIN public.kunjungan_konsinyasi kk ON rkk.kunjungan_id = kk.id
                WHERE kk.tanggal_kunjungan >= DATE_TRUNC('month', CURRENT_DATE)
            ");

            $topDamagedItems = Database::fetchAll("
                SELECT i.nama_item, i.kode_sku, i.satuan_dasar,
                       SUM(rkk.retur_rusak) as total_pcs_rusak,
                       SUM(rkk.nilai_kerugian_rusak) as total_nominal_kerugian
                FROM public.rincian_kunjungan_konsinyasi rkk
                JOIN public.item i ON rkk.item_id = i.id
                JOIN public.kunjungan_konsinyasi kk ON rkk.kunjungan_id = kk.id
                WHERE kk.tanggal_kunjungan >= DATE_TRUNC('month', CURRENT_DATE)
                GROUP BY i.nama_item, i.kode_sku, i.satuan_dasar
                HAVING SUM(rkk.retur_rusak) > 0
                ORDER BY total_nominal_kerugian DESC
                LIMIT 5
            ");

            // C6: Warning Toko >14 Hari Tidak Dikunjungi
            $overdueStores = Database::fetchAll("
                SELECT p.id, p.nama_toko, p.kode_pelanggan, p.alamat_lengkap,
                       k.nama_karyawan as nama_sales,
                       (SELECT MAX(skt.terakhir_opname_pada) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) as terakhir_opname
                FROM public.pelanggan p
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                  AND (
                      (SELECT MAX(skt.terakhir_opname_pada) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) IS NULL
                      OR (SELECT MAX(skt.terakhir_opname_pada) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) < NOW() - INTERVAL '14 days'
                  )
                ORDER BY terakhir_opname ASC NULLS FIRST
            ");

            // 3. Live AI Activity Stream
            $activityLogs = Database::fetchAll("
                SELECT id, nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas,
                       jenis_aksi, tabel_terdampak, deskripsi_aktivitas, waktu_kejadian
                FROM public.log_aktivitas
                ORDER BY waktu_kejadian DESC
                LIMIT 30
            ");

            $this->view('owner.index', [
                'pageTitle' => 'Owner Command Center',
                'pageSubtitle' => 'Executive Overview, Konsinyasi Gatekeeper & Live Stream',
                'omzetToday' => $omzetToday,
                'totalSaldoKas' => $totalSaldoKas,
                'totalPiutang' => $totalPiutang,
                'pendingDrafts' => $pendingDrafts,
                'omzetComparison' => $omzetComparison,
                'topStores' => $topStores,
                'pendingConsignmentShipments' => $pendingConsignmentShipments,
                'salesCommissions' => $salesCommissions,
                'agingSummary' => $agingSummary,
                'unpaidStoreList' => $unpaidStoreList,
                'lossReport' => $lossReport,
                'topDamagedItems' => $topDamagedItems,
                'overdueStores' => $overdueStores,
                'activityLogs' => $activityLogs
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    public function approveDraft(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/owner');
            return;
        }

        $draftId = $this->input('draft_id');
        if (empty($draftId)) {
            $this->redirect('/owner');
            return;
        }

        try {
            Database::execute("
                UPDATE public.draf_pengeluaran 
                SET status_approval = 'disetujui', disetujui_pada = NOW() 
                WHERE id = :id
            ", ['id' => $draftId]);

            $this->flashSuccess('Pengajuan beban operasional berhasil disetujui!');
            $this->redirect('/owner');

        } catch (Throwable $e) {
            $this->flashError('Gagal menyetujui pengajuan: ' . $e->getMessage());
            $this->redirect('/owner');
        }
    }

    public function rejectDraft(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/owner');
            return;
        }

        $draftId = $this->input('draft_id');
        $alasan = $this->input('alasan', 'Ditolak oleh Owner');

        try {
            Database::execute("
                UPDATE public.draf_pengeluaran 
                SET status_approval = 'ditolak', alasan_penolakan = :alasan, diubah_pada = NOW() 
                WHERE id = :id
            ", ['id' => $draftId, 'alasan' => $alasan]);

            $this->flashWarning('Pengajuan beban operasional telah ditolak.');
            $this->redirect('/owner');

        } catch (Throwable $e) {
            $this->flashError('Gagal menolak pengajuan: ' . $e->getMessage());
            $this->redirect('/owner');
        }
    }

    /**
     * Layar C2: Owner Menyetujui & Memberangkatkan Pengiriman Konsinyasi
     */
    public function approveConsignmentDelivery(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/owner');
            return;
        }

        $suratJalanId = (string)$this->input('surat_jalan_id');

        if (empty($suratJalanId)) {
            $this->flashError('Pengiriman tidak ditemukan.');
            $this->redirect('/owner');
            return;
        }

        try {
            $sj = Database::fetchOne("
                SELECT sj.*, p.nama_toko 
                FROM public.surat_jalan sj
                JOIN public.pesanan pes ON sj.pesanan_id = pes.id
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                WHERE sj.id = :id AND sj.status_surat_jalan = 'draf_n8n'
            ", ['id' => $suratJalanId]);

            if (!$sj) {
                $this->flashError('Pengiriman tidak valid atau sudah disetujui sebelumnya.');
                $this->redirect('/owner');
                return;
            }

            // Ubah status ke 'sedang_dikirim' (barang diberangkatkan menuju toko)
            Database::execute("
                UPDATE public.surat_jalan 
                SET status_surat_jalan = 'sedang_dikirim',
                    waktu_berangkat = NOW(),
                    disetujui_oleh = :owner_id,
                    diubah_pada = NOW() 
                WHERE id = :id
            ", ['id' => $suratJalanId, 'owner_id' => Auth::id()]);

            // Ubah status pesanan pengiriman ke 'dalam_pengiriman'
            Database::execute("
                UPDATE public.pesanan 
                SET status_pemrosesan = 'dalam_pengiriman', diubah_pada = NOW() 
                WHERE id = :id
            ", ['id' => $sj['pesanan_id']]);

            ActivityLog::log(
                'logistik',
                'UPDATE',
                "Owner menyetujui pengiriman konsinyasi {$sj['nomor_surat_jalan']} ke toko {$sj['nama_toko']}.",
                'surat_jalan',
                $suratJalanId
            );

            $this->flashSuccess("Pengiriman {$sj['nomor_surat_jalan']} berhasil disetujui & diberangkatkan ke toko {$sj['nama_toko']}!");
            $this->redirect('/owner');

        } catch (Throwable $e) {
            $this->flashError('Gagal menyetujui pengiriman: ' . $e->getMessage());
            $this->redirect('/owner');
        }
    }

    /**
     * Layar C2: Owner Menolak Pengiriman Konsinyasi
     */
    public function rejectConsignmentDelivery(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/owner');
            return;
        }

        $suratJalanId = (string)$this->input('surat_jalan_id');
        $alasan = trim((string)$this->input('alasan', 'Ditolak oleh Owner'));

        if (empty($suratJalanId)) {
            $this->flashError('Pengiriman tidak ditemukan.');
            $this->redirect('/owner');
            return;
        }

        try {
            $sj = Database::fetchOne("
                SELECT sj.*, p.nama_toko 
                FROM public.surat_jalan sj
                JOIN public.pesanan pes ON sj.pesanan_id = pes.id
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                WHERE sj.id = :id AND sj.status_surat_jalan = 'draf_n8n'
            ", ['id' => $suratJalanId]);

            if (!$sj) {
                $this->flashError('Pengiriman tidak valid.');
                $this->redirect('/owner');
                return;
            }

            Database::execute("
                UPDATE public.surat_jalan 
                SET status_surat_jalan = 'ditolak_owner', diubah_pada = NOW() 
                WHERE id = :id
            ", ['id' => $suratJalanId]);

            Database::execute("
                UPDATE public.pesanan 
                SET status_pemrosesan = 'dibatalkan', catatan = catatan || ' [Ditolak Owner: ' || :alasan || ']', diubah_pada = NOW() 
                WHERE id = :id
            ", ['id' => $sj['pesanan_id'], 'alasan' => $alasan]);

            ActivityLog::log(
                'logistik',
                'UPDATE',
                "Owner menolak pengiriman konsinyasi {$sj['nomor_surat_jalan']} ke toko {$sj['nama_toko']}. Alasan: {$alasan}",
                'surat_jalan',
                $suratJalanId
            );

            $this->flashWarning("Pengiriman {$sj['nomor_surat_jalan']} telah ditolak.");
            $this->redirect('/owner');

        } catch (Throwable $e) {
            $this->flashError('Gagal menolak pengiriman: ' . $e->getMessage());
            $this->redirect('/owner');
        }
    }
}
