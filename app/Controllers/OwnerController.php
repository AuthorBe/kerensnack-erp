<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/OwnerController.php
 * Pengendali Owner Executive Command Center, Live AI Stream & Approval Hub.
 */

class OwnerController extends Controller
{
    public function __construct()
    {
        Auth::requireRole(['owner']);
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

            // 2. Draf Pengeluaran yang Menunggu Persetujuan (Approval Hub)
            $pendingDrafts = Database::fetchAll("
                SELECT dp.id, dp.nominal, dp.kategori_beban, dp.keterangan_mentah, dp.keterangan_ai,
                       dp.url_foto_nota, dp.dibuat_pada, p.nama_lengkap as pemohon
                FROM public.draf_pengeluaran dp
                LEFT JOIN public.pengguna p ON dp.diajukan_oleh_pengguna_id = p.id
                WHERE dp.status_approval = 'menunggu'
                ORDER BY dp.dibuat_pada DESC
            ");

            // 3. Live AI Activity Stream (Forensik Audit Log)
            $activityLogs = Database::fetchAll("
                SELECT id, nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas,
                       jenis_aksi, tabel_terdampak, deskripsi_aktivitas, waktu_kejadian
                FROM public.log_aktivitas
                ORDER BY waktu_kejadian DESC
                LIMIT 30
            ");

            $this->view('owner.index', [
                'pageTitle' => 'Owner Command Center',
                'pageSubtitle' => 'Live AI Activity Stream, Approval Hub & Analisis Bisnis',
                'omzetToday' => $omzetToday,
                'totalSaldoKas' => $totalSaldoKas,
                'totalPiutang' => $totalPiutang,
                'pendingDrafts' => $pendingDrafts,
                'activityLogs' => $activityLogs
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    public function approveDraft(): void
    {
        $draftId = $this->input('draft_id');
        if (empty($draftId)) {
            $this->redirect('/owner');
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
}
