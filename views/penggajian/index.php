<?php
/**
 * views/penggajian/index.php
 * Daftar Payroll Runs (Riwayat & Draf Penggajian Keren One ERP)
 * Mengadopsi Desain Dasbor Harmonis (Variasi Warna Tematik, Anti-Monoton, & Responsif)
 */
use App\Helpers\CSRF;
use App\Helpers\Format;
use App\Core\Auth;
use App\Core\Router;

ob_start();

$avgPerRun = $approvedCount > 0 ? ($totalGajiDisetujui / $approvedCount) : 0;
?>

<style>
/* ==========================================================================
   Payroll Index - Dashboard Harmonic DNA Styling
   ========================================================================== */

/* 0. Dashboard-Style KPI Cards with Color Borders */
.dashboard-kpi-card {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    min-height: 98px;
}
.dashboard-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
}
.dark .dashboard-kpi-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}
.dark .dashboard-kpi-card:hover {
    border-color: #475569;
}

/* Card 1: Indigo */
.dashboard-kpi-card.is-indigo {
    border-left: 3.5px solid #6366f1;
}
/* Card 2: Emerald */
.dashboard-kpi-card.is-emerald {
    border-left: 3.5px solid #10b981;
}
/* Card 3: Sky */
.dashboard-kpi-card.is-sky {
    border-left: 3.5px solid #0284c7;
}
/* Card 4: Amber */
.dashboard-kpi-card.is-amber {
    border-left: 3.5px solid #f59e0b;
}

/* Stat Icon Box (Pastel Dashboard Style) */
.dashboard-stat-icon {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.dashboard-stat-icon svg {
    width: 17px;
    height: 17px;
    display: block;
}

.dashboard-stat-icon.is-indigo {
    background: rgba(99, 102, 241, 0.12);
    color: #4f46e5;
}
.dark .dashboard-stat-icon.is-indigo {
    background: rgba(99, 102, 241, 0.2);
    color: #a5b4fc;
}

.dashboard-stat-icon.is-emerald {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
}
.dark .dashboard-stat-icon.is-emerald {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
}

.dashboard-stat-icon.is-sky {
    background: rgba(2, 132, 199, 0.12);
    color: #0284c7;
}
.dark .dashboard-stat-icon.is-sky {
    background: rgba(2, 132, 199, 0.2);
    color: #38bdf8;
}

.dashboard-stat-icon.is-amber {
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
}
.dark .dashboard-stat-icon.is-amber {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
}

/* 1. Filter Dock Container */
.payroll-filter-dock {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    padding: 10px 14px;
}
.dark .payroll-filter-dock {
    background: #1e293b;
    border-color: #334155;
}

.tab-pill-group {
    display: inline-flex;
    align-items: center;
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 10px;
    padding: 3px;
    gap: 3px;
    overflow-x: auto;
    max-width: 100%;
}
.dark .tab-pill-group {
    background: #0f172a;
    border-color: #334155;
}

.tab-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    color: var(--color-ink-secondary, #64748b);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    user-select: none;
}
.tab-pill-btn:hover {
    color: var(--color-ink, #0f172a);
    background: rgba(0, 0, 0, 0.04);
}
.dark .tab-pill-btn:hover {
    color: #f8fafc;
    background: rgba(255, 255, 255, 0.06);
}

/* Harmonious Active States per Tab */
.tab-pill-btn.is-active,
.tab-pill-btn.is-active-slate {
    background: #4f46e5 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(79, 70, 229, 0.3);
}
.dark .tab-pill-btn.is-active,
.dark .tab-pill-btn.is-active-slate {
    background: #6366f1 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(99, 102, 241, 0.35);
}

.tab-pill-btn.is-active-amber {
    background: #d97706 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(217, 119, 6, 0.3);
}
.dark .tab-pill-btn.is-active-amber {
    background: #f59e0b !important;
    color: #0f172a !important;
}

.tab-pill-btn.is-active-emerald {
    background: #059669 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(5, 150, 105, 0.3);
}
.dark .tab-pill-btn.is-active-emerald {
    background: #10b981 !important;
    color: #0f172a !important;
}

.tab-pill-btn.is-active-sky {
    background: #0284c7 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(2, 132, 199, 0.3);
}
.dark .tab-pill-btn.is-active-sky {
    background: #38bdf8 !important;
    color: #0f172a !important;
}

.tab-pill-btn.is-active-purple {
    background: #7e22ce !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(126, 34, 206, 0.3);
}
.dark .tab-pill-btn.is-active-purple {
    background: #c084fc !important;
    color: #0f172a !important;
}

.tab-pill-counter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 17px;
    height: 17px;
    padding: 0 4.5px;
    border-radius: 9999px;
    font-size: 10px;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    background: rgba(0, 0, 0, 0.08);
}
.dark .tab-pill-counter {
    background: rgba(255, 255, 255, 0.14);
}
.tab-pill-btn[class*="is-active-"] .tab-pill-counter {
    background: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
}

/* 3. Search Box in Filter */
.payroll-search-box {
    position: relative;
    display: flex;
    align-items: center;
    min-width: 220px;
    flex: 1;
    max-width: 320px;
}
.payroll-search-input {
    width: 100%;
    height: 36px;
    padding: 0 12px 0 34px;
    font-size: 12.5px;
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #cbd5e1);
    border-radius: 10px;
    color: var(--color-ink, #0f172a);
    transition: all 0.15s ease;
}
.payroll-search-input:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}
.dark .payroll-search-input {
    background: #0f172a;
    border-color: #334155;
    color: #f8fafc;
}
.dark .payroll-search-input:focus {
    border-color: #818cf8;
    box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.18);
}
.payroll-search-icon {
    position: absolute;
    left: 10px;
    width: 14px;
    height: 14px;
    color: var(--color-ink-muted, #94a3b8);
    pointer-events: none;
}

/* 4. Tipe & Status Badges */
.badge-tipe-borongan {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 700;
    background: rgba(217, 119, 6, 0.1);
    color: #b45309;
    border: 1px solid rgba(217, 119, 6, 0.22);
}
.dark .badge-tipe-borongan {
    background: rgba(217, 119, 6, 0.2);
    color: #fbbf24;
    border-color: rgba(217, 119, 6, 0.35);
}

.badge-tipe-bulanan {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 700;
    background: rgba(2, 132, 199, 0.1);
    color: #0284c7;
    border: 1px solid rgba(2, 132, 199, 0.22);
}
.dark .badge-tipe-bulanan {
    background: rgba(2, 132, 199, 0.2);
    color: #38bdf8;
    border-color: rgba(2, 132, 199, 0.35);
}

.badge-tipe-gabungan {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 700;
    background: rgba(147, 51, 234, 0.1);
    color: #7e22ce;
    border: 1px solid rgba(147, 51, 234, 0.22);
}
.dark .badge-tipe-gabungan {
    background: rgba(147, 51, 234, 0.2);
    color: #c084fc;
    border-color: rgba(147, 51, 234, 0.35);
}

.badge-status-draf {
    display: inline-flex;
    align-items: center;
    gap: 4.5px;
    padding: 3px 8.5px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(217, 119, 6, 0.12);
    color: #b45309;
    border: 1px solid rgba(217, 119, 6, 0.25);
}
.dark .badge-status-draf {
    background: rgba(217, 119, 6, 0.2);
    color: #fbbf24;
}

.badge-status-disetujui {
    display: inline-flex;
    align-items: center;
    gap: 4.5px;
    padding: 3px 8.5px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.25);
}
.dark .badge-status-disetujui {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
}

.badge-status-dibayarkan {
    display: inline-flex;
    align-items: center;
    gap: 4.5px;
    padding: 3px 8.5px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(13, 148, 136, 0.12);
    color: #0f766e;
    border: 1px solid rgba(13, 148, 136, 0.25);
}
.dark .badge-status-dibayarkan {
    background: rgba(13, 148, 136, 0.2);
    color: #2dd4bf;
}

/* Pulsing dot */
.dot-pulse {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: currentColor;
    animation: pulseDot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulseDot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: .4; transform: scale(0.85); }
}

/* Table Container Card */
.payroll-table-card {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    overflow: hidden;
}
.dark .payroll-table-card {
    background: #1e293b;
    border-color: #334155;
}

/* Table Header Bar */
.payroll-table-header {
    padding: 14px 18px;
    border-bottom: 1px solid var(--color-hairline, #e2e8f0);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.dark .payroll-table-header {
    border-color: #334155;
}

.payroll-table-counter {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    font-family: var(--font-mono, monospace);
    color: var(--color-ink-secondary, #64748b);
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline, #e2e8f0);
    white-space: nowrap;
}
.dark .payroll-table-counter {
    background: rgba(15, 23, 42, 0.6);
    border-color: #334155;
    color: #94a3b8;
}

/* Empty State Container */
.payroll-empty-state {
    padding: 56px 20px;
    text-align: center;
}
.payroll-empty-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    background: rgba(99, 102, 241, 0.08);
    color: #4f46e5;
    border: 1px solid rgba(99, 102, 241, 0.22);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px auto;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.06);
}
.dark .payroll-empty-icon {
    background: rgba(99, 102, 241, 0.16);
    color: #818cf8;
    border-color: rgba(99, 102, 241, 0.35);
}
.payroll-empty-icon svg {
    width: 26px;
    height: 26px;
    display: block;
}
.payroll-empty-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--color-ink, #0f172a);
    margin-bottom: 6px;
}
.dark .payroll-empty-title {
    color: #f1f5f9;
}
.payroll-empty-desc {
    font-size: 12.5px;
    color: var(--color-ink-muted, #64748b);
    max-width: 440px;
    margin: 0 auto 22px auto;
    line-height: 1.55;
}
.dark .payroll-empty-desc {
    color: #94a3b8;
}

/* Vibrant Action Buttons */
.btn-launch-primary {
    background: #2563eb;
    color: #ffffff;
    border: 1px solid #1d4ed8;
    box-shadow: 0 1px 2px rgba(37, 99, 235, 0.25);
    transition: all 0.15s ease;
}
.btn-launch-primary:hover {
    background: #1d4ed8;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
}

.btn-launch-secondary {
    background: var(--color-canvas, #ffffff);
    color: var(--color-ink-primary, #1e293b);
    border: 1px solid var(--color-hairline-strong, #cbd5e1);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    transition: all 0.15s ease;
}
.btn-launch-secondary:hover {
    background: var(--color-canvas-soft, #f8fafc);
    border-color: #94a3b8;
}
.dark .btn-launch-secondary {
    background: #1e293b;
    color: #f1f5f9;
    border-color: #475569;
}
.dark .btn-launch-secondary:hover {
    background: #334155;
}

/* =============================================================================
   Warning Banner - Pending Draft Alert (Harmonious Executive Style)
   ============================================================================= */
.payroll-warning-banner {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-left: 4px solid #f59e0b;
    border-radius: 12px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    box-shadow: 0 1px 3px rgba(245, 158, 11, 0.08);
}
.dark .payroll-warning-banner {
    background: rgba(245, 158, 11, 0.12);
    border-color: rgba(245, 158, 11, 0.35);
    border-left-color: #f59e0b;
}

.payroll-warning-icon {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 10px;
    background: #fef3c7;
    color: #d97706;
    border: 1px solid #fde68a;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.dark .payroll-warning-icon {
    background: rgba(245, 158, 11, 0.22);
    color: #fbbf24;
    border-color: rgba(245, 158, 11, 0.4);
}

.payroll-warning-title {
    font-size: 13px;
    font-weight: 700;
    color: #92400e;
    line-height: 1.35;
}
.dark .payroll-warning-title {
    color: #fbbf24;
}

.payroll-warning-desc {
    font-size: 12px;
    color: #b45309;
    margin-top: 2px;
    line-height: 1.4;
}
.dark .payroll-warning-desc {
    color: #fde68a;
}

.payroll-warning-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    background: #f59e0b;
    color: #ffffff !important;
    border: 1px solid #d97706;
    box-shadow: 0 1px 2px rgba(217, 119, 6, 0.25);
    white-space: nowrap;
    text-decoration: none;
    transition: all 0.15s ease;
    flex-shrink: 0;
}
.payroll-warning-btn:hover {
    background: #d97706;
    color: #ffffff !important;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(217, 119, 6, 0.35);
}
.dark .payroll-warning-btn {
    background: #f59e0b;
    color: #0f172a !important;
    border-color: #fbbf24;
    font-weight: 800;
}
.dark .payroll-warning-btn:hover {
    background: #fbbf24;
    color: #0f172a !important;
}

/* =============================================================================
   Action Buttons - Periksa & Detail (Soft Blue Pastel Enterprise Style)
   ============================================================================= */
.payroll-btn-periksa {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 13px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    background: #eff6ff;
    color: #2563eb !important;
    border: 1px solid #bfdbfe;
    box-shadow: 0 1px 2px rgba(37, 99, 235, 0.06);
    white-space: nowrap;
    text-decoration: none;
    transition: all 0.15s ease;
}
.payroll-btn-periksa:hover {
    background: #dbeafe;
    color: #1d4ed8 !important;
    border-color: #93c5fd;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.18);
}
.dark .payroll-btn-periksa {
    background: rgba(37, 99, 235, 0.18);
    color: #93c5fd !important;
    border-color: rgba(59, 130, 246, 0.35);
}
.dark .payroll-btn-periksa:hover {
    background: rgba(37, 99, 235, 0.28);
    color: #bfdbfe !important;
    border-color: rgba(59, 130, 246, 0.5);
}

.payroll-btn-pdf {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 31px;
    height: 31px;
    border-radius: 8px;
    background: var(--color-canvas, #ffffff);
    color: var(--color-ink-muted, #64748b);
    border: 1px solid var(--color-hairline, #cbd5e1);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    transition: all 0.15s ease;
}
.payroll-btn-pdf:hover {
    background: var(--color-canvas-soft, #f8fafc);
    color: var(--color-ink, #0f172a);
    border-color: var(--color-hairline-strong, #94a3b8);
    transform: translateY(-1px);
}
.dark .payroll-btn-pdf {
    background: #1e293b;
    border-color: #475569;
    color: #94a3b8;
}
.dark .payroll-btn-pdf:hover {
    background: #334155;
    color: #f1f5f9;
}
</style>

<div class="space-y-4" x-data="{
    searchQuery: '',
    statusFilter: 'all',
    typeFilter: 'all',
    payrollList: <?= htmlspecialchars(json_encode(array_map(function($p) {
        return [
            'id' => $p['id'],
            'nomor_referensi' => $p['nomor_referensi'],
            'nama_payroll' => $p['nama_payroll'] ?: ('Payroll ' . $p['nomor_referensi']),
            'periode_awal' => $p['periode_awal'],
            'periode_akhir' => $p['periode_akhir'],
            'periode_awal_fmt' => Format::tanggalIndo($p['periode_awal']),
            'periode_akhir_fmt' => Format::tanggalIndo($p['periode_akhir']),
            'tipe_penggajian' => $p['tipe_penggajian'],
            'status' => $p['status'],
            'total_gaji' => (float)$p['total_gaji_dikeluarkan'],
            'total_gaji_fmt' => Format::rupiah((float)$p['total_gaji_dikeluarkan']),
            'total_karyawan' => (int)$p['total_karyawan'],
            'total_karyawan_terbayar' => (int)$p['total_karyawan_terbayar'],
            'nama_approver' => $p['nama_approver'] ?? '',
            'dibuat_pada' => $p['dibuat_pada']
        ];
    }, $payrollList))) ?>,

    get filteredList() {
        const q = this.searchQuery.toLowerCase().trim();
        return this.payrollList.filter(p => {
            if (this.statusFilter !== 'all' && p.status !== this.statusFilter) {
                return false;
            }
            if (this.typeFilter !== 'all') {
                if (this.typeFilter === 'borongan' && p.tipe_penggajian !== 'borongan' && p.tipe_penggajian !== 'mingguan') {
                    return false;
                }
                if (this.typeFilter === 'bulanan' && p.tipe_penggajian !== 'bulanan') {
                    return false;
                }
                if (this.typeFilter === 'gabungan' && p.tipe_penggajian !== 'gabungan') {
                    return false;
                }
            }
            if (q) {
                const ref = (p.nomor_referensi || '').toLowerCase();
                const name = (p.nama_payroll || '').toLowerCase();
                if (!ref.includes(q) && !name.includes(q)) {
                    return false;
                }
            }
            return true;
        });
    },

    get draftCount() {
        return this.payrollList.filter(p => p.status === 'draf').length;
    },
    get approvedCount() {
        return this.payrollList.filter(p => p.status === 'disetujui' || p.status === 'dibayarkan').length;
    }
}">

    <!-- 1. PAGE HEADER (Pola Kanonikal KEREN ONE ERP) -->
    <div class="page-header flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-indigo" style="flex-shrink:0;">
                <i data-lucide="calculator"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#6366f1;"></span>
                    <span>Modul HR &bull; Siklus Penggajian &amp; Payroll Engine</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100">
                    <?= htmlspecialchars($pageTitle ?? 'Penggajian') ?>
                </h1>
                <p class="page-subtitle text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    <?= htmlspecialchars($pageSubtitle ?? 'Kalkulasi upah borongan, gaji pokok, uang hadir, lembur, dan potongan kasbon otomatis.') ?>
                </p>
            </div>
        </div>

        <div class="page-header-actions grid grid-cols-2 sm:flex items-center gap-2 w-full sm:w-auto justify-end">
            <a href="<?= Router::url('/penggajian/rekap/karyawan') ?>" class="btn btn-secondary text-xs sm:text-sm font-bold flex items-center justify-center gap-1.5 w-full sm:w-auto px-3.5 py-2" style="height:38px; border-radius:10px;">
                <i data-lucide="user-check" class="w-4 h-4 text-slate-500"></i>
                <span>Rekap Karyawan</span>
            </a>
            <?php if (Auth::hasPermission('hr.payroll_manage')): ?>
            <a href="<?= Router::url('/penggajian/create') ?>" class="btn btn-primary text-xs sm:text-sm font-bold flex items-center justify-center gap-1.5 w-full sm:w-auto px-4 py-2" style="height:38px; border-radius:10px;">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Generate Payroll</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Draft Pending (Harmonious Executive Warning Banner) -->
    <?php if ($hasPendingDraft): ?>
    <?php 
        $firstDraft = null;
        foreach ($payrollList as $item) {
            if ($item['status'] === 'draf') { $firstDraft = $item; break; }
        }
    ?>
    <div class="payroll-warning-banner">
        <div class="flex items-center gap-3 min-w-0 flex-1">
            <div class="payroll-warning-icon">
                <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="payroll-warning-title">
                    Ada draf payroll aktif yang belum disetujui<?= $firstDraft ? ' (' . htmlspecialchars($firstDraft['nomor_referensi']) . ')' : '' ?>.
                </div>
                <div class="payroll-warning-desc">
                    Selesaikan pemeriksaan, otorisasi pembayaran kas, atau hapus draf sebelum membuat periode baru.
                </div>
            </div>
        </div>
        <?php if ($firstDraft): ?>
        <a href="<?= Router::url('/penggajian/preview?id=' . $firstDraft['id']) ?>" class="payroll-warning-btn">
            <span>Periksa Draf</span>
            <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- 2. 4 KPI STAT CARDS (Pola Dasbor Finansial Keren One) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Card 1: Total Payroll Run (Indigo) -->
        <div class="dashboard-kpi-card is-indigo">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">1. Total Periode</span>
                <div class="dashboard-stat-icon is-indigo">
                    <i data-lucide="layers"></i>
                </div>
            </div>
            <div class="font-mono text-lg sm:text-xl font-bold text-slate-900 dark:text-slate-100 mt-1">
                <?= number_format($totalRuns) ?> <span class="text-xs font-normal text-slate-400">periode</span>
            </div>
            <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold pt-1 border-t border-dashed border-slate-200 dark:border-slate-700 mt-1">
                <?= $approvedCount ?> disetujui
            </div>
        </div>

        <!-- Card 2: Total Gaji Disetujui (Emerald) -->
        <div class="dashboard-kpi-card is-emerald">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">2. Total Gaji Disetujui</span>
                <div class="dashboard-stat-icon is-emerald">
                    <i data-lucide="check-check"></i>
                </div>
            </div>
            <div class="font-mono text-lg sm:text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 truncate">
                <?= Format::rupiah($totalGajiDisetujui) ?>
            </div>
            <div class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 font-medium pt-1 border-t border-dashed border-slate-200 dark:border-slate-700 mt-1">
                Akumulasi pengeluaran kas
            </div>
        </div>

        <!-- Card 3: Rerata per Periode (Sky Blue) -->
        <div class="dashboard-kpi-card is-sky">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">3. Rerata per Periode</span>
                <div class="dashboard-stat-icon is-sky">
                    <i data-lucide="trending-up"></i>
                </div>
            </div>
            <div class="font-mono text-lg sm:text-xl font-bold text-sky-600 dark:text-sky-400 mt-1 truncate">
                <?= Format::rupiah($avgPerRun) ?>
            </div>
            <div class="text-[11px] text-sky-600/80 dark:text-sky-400/80 font-medium pt-1 border-t border-dashed border-slate-200 dark:border-slate-700 mt-1">
                Estimasi rata-rata per siklus
            </div>
        </div>

        <!-- Card 4: Status Siklus Engine (Amber) -->
        <div class="dashboard-kpi-card is-amber">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">4. Siklus Penggajian</span>
                <div class="dashboard-stat-icon is-amber">
                    <i data-lucide="<?= $hasPendingDraft ? 'clock' : 'cpu' ?>"></i>
                </div>
            </div>
            <div class="font-mono text-base sm:text-lg font-bold <?= $hasPendingDraft ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-slate-100' ?> mt-1 flex items-center gap-1.5">
                <?php if ($hasPendingDraft): ?>
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    <span>Draf Aktif</span>
                <?php else: ?>
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Siap Siklus Baru</span>
                <?php endif; ?>
            </div>
            <div class="text-[11px] text-amber-600/80 dark:text-amber-400/80 font-medium pt-1 border-t border-dashed border-slate-200 dark:border-slate-700 mt-1">
                Auto anti-double pay aktif
            </div>
        </div>
    </div>

    <!-- 3. FILTER & SEARCH DOCK -->
    <div class="payroll-filter-dock">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Filter Pills Group -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Status Pills -->
                <div class="tab-pill-group">
                    <button type="button" 
                            @click="statusFilter = 'all'" 
                            class="tab-pill-btn" 
                            :class="statusFilter === 'all' ? 'is-active' : ''">
                        <span>Semua Status</span>
                        <span class="tab-pill-counter"><?= count($payrollList) ?></span>
                    </button>
                    <button type="button" 
                            @click="statusFilter = 'draf'" 
                            class="tab-pill-btn" 
                            :class="statusFilter === 'draf' ? 'is-active-amber' : ''">
                        <span>Draf</span>
                        <span class="tab-pill-counter" x-text="draftCount"></span>
                    </button>
                    <button type="button" 
                            @click="statusFilter = 'disetujui'" 
                            class="tab-pill-btn" 
                            :class="statusFilter === 'disetujui' ? 'is-active-emerald' : ''">
                        <span>Disetujui</span>
                        <span class="tab-pill-counter" x-text="approvedCount"></span>
                    </button>
                </div>

                <!-- Tipe Pills -->
                <div class="tab-pill-group hidden sm:inline-flex">
                    <button type="button" 
                            @click="typeFilter = 'all'" 
                            class="tab-pill-btn" 
                            :class="typeFilter === 'all' ? 'is-active' : ''">
                        <span>Semua Tipe</span>
                    </button>
                    <button type="button" 
                            @click="typeFilter = 'borongan'" 
                            class="tab-pill-btn" 
                            :class="typeFilter === 'borongan' ? 'is-active-amber' : ''">
                        <span>Borongan</span>
                    </button>
                    <button type="button" 
                            @click="typeFilter = 'bulanan'" 
                            class="tab-pill-btn" 
                            :class="typeFilter === 'bulanan' ? 'is-active-sky' : ''">
                        <span>Bulanan</span>
                    </button>
                    <button type="button" 
                            @click="typeFilter = 'gabungan'" 
                            class="tab-pill-btn" 
                            :class="typeFilter === 'gabungan' ? 'is-active-purple' : ''">
                        <span>Gabungan</span>
                    </button>
                </div>
            </div>

            <!-- Instant Search Box -->
            <div class="payroll-search-box">
                <i data-lucide="search" class="payroll-search-icon"></i>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari no. ref atau nama payroll..." 
                       class="payroll-search-input">
            </div>
        </div>
    </div>

    <!-- 4. RIWAYAT & SIKLUS PENGGAJIAN TABLE -->
    <div class="payroll-table-card">
        <!-- Table Header Info -->
        <div class="payroll-table-header">
            <div class="flex items-center gap-3">
                <div style="width:34px;height:34px;border-radius:10px;background:rgba(99,102,241,0.1);color:#4f46e5;display:flex;align-items:center;justify-content:center;border:1px solid rgba(99,102,241,0.25);flex-shrink:0;">
                    <i data-lucide="layers" style="width:17px;height:17px;"></i>
                </div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                    Riwayat &amp; Siklus Penggajian
                </h2>
            </div>
            <span class="payroll-table-counter">
                Menampilkan <strong class="text-slate-900 dark:text-slate-100" x-text="filteredList.length"></strong> periode
            </span>
        </div>

        <?php if (empty($payrollList)): ?>
        <!-- Empty State Khusus saat belum pernah ada payroll sama sekali -->
        <div class="payroll-empty-state">
            <div class="payroll-empty-icon">
                <i data-lucide="calculator"></i>
            </div>
            <h3 class="payroll-empty-title">Belum Ada Siklus Penggajian</h3>
            <p class="payroll-empty-desc">
                Mulai perhitungan upah borongan dan gaji pokok bulanan karyawan secara otomatis dengan menekan tombol di bawah.
            </p>
            <?php if (Auth::hasPermission('hr.payroll_manage')): ?>
            <a href="<?= Router::url('/penggajian/create') ?>" 
               class="btn btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs sm:text-sm font-bold shadow-sm" 
               style="height: 40px; border-radius: 10px; margin: 0 auto;">
                <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i>
                <span>Generate Payroll Pertama</span>
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>

        <!-- Desktop Table View -->
        <div class="hidden sm:block overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 1080px; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 165px; min-width: 155px;" class="cell-nowrap">No. Referensi</th>
                        <th style="min-width: 230px;">Nama Payroll</th>
                        <th style="width: 220px; min-width: 200px;" class="cell-nowrap">Periode</th>
                        <th style="width: 120px; min-width: 110px;" class="cell-center cell-nowrap">Tipe</th>
                        <th style="width: 140px; min-width: 130px;" class="cell-center cell-nowrap">Karyawan</th>
                        <th style="width: 150px; min-width: 140px;" class="cell-right cell-nowrap">Total Gaji</th>
                        <th style="width: 125px; min-width: 115px;" class="cell-center cell-nowrap">Status</th>
                        <th style="width: 140px; min-width: 130px; padding-right: 20px;" class="cell-right cell-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <template x-for="p in filteredList" :key="p.id">
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            <!-- 1. No. Referensi -->
                            <td class="cell-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md font-mono text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700" x-text="p.nomor_referensi"></span>
                            </td>

                            <!-- 2. Nama Payroll -->
                            <td>
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-[13px] leading-snug" x-text="p.nama_payroll"></div>
                            </td>

                            <!-- 3. Periode -->
                            <td class="cell-nowrap">
                                <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 font-medium">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                    <span x-text="p.periode_awal_fmt + ' s/d ' + p.periode_akhir_fmt"></span>
                                </div>
                            </td>

                            <!-- 4. Tipe Payroll -->
                            <td class="cell-center cell-nowrap">
                                <template x-if="p.tipe_penggajian === 'borongan' || p.tipe_penggajian === 'mingguan'">
                                    <span class="badge-tipe-borongan">Borongan</span>
                                </template>
                                <template x-if="p.tipe_penggajian === 'bulanan'">
                                    <span class="badge-tipe-bulanan">Bulanan</span>
                                </template>
                                <template x-if="p.tipe_penggajian === 'gabungan'">
                                    <span class="badge-tipe-gabungan">Gabungan</span>
                                </template>
                            </td>

                            <!-- 5. Karyawan Terbayar / Total -->
                            <td class="cell-center cell-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700 shadow-2xs">
                                    <i data-lucide="users" style="width: 13px; height: 13px;" class="text-slate-400 shrink-0"></i>
                                    <span class="whitespace-nowrap">
                                        <strong class="font-bold text-slate-900 dark:text-slate-100 font-mono" x-text="p.total_karyawan_terbayar"></strong>
                                        <span class="text-slate-400 font-normal"> / </span>
                                        <span class="font-mono text-slate-500" x-text="p.total_karyawan"></span>
                                        <span class="text-[11px] text-slate-400 font-normal ml-0.5">Org</span>
                                    </span>
                                </span>
                            </td>

                            <!-- 6. Total Gaji -->
                            <td class="cell-right cell-currency">
                                <span class="font-bold text-sm" 
                                      :class="p.total_gaji > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500'" 
                                      x-text="p.total_gaji_fmt"></span>
                            </td>

                            <!-- 7. Status -->
                            <td class="cell-center cell-nowrap">
                                <template x-if="p.status === 'draf'">
                                    <span class="badge-status-draf">
                                        <span class="dot-pulse"></span>
                                        <span>Draf</span>
                                    </span>
                                </template>
                                <template x-if="p.status === 'disetujui'">
                                    <span class="badge-status-disetujui">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                        <span>Disetujui</span>
                                    </span>
                                </template>
                                <template x-if="p.status === 'dibayarkan'">
                                    <span class="badge-status-dibayarkan">
                                        <i data-lucide="check-check" class="w-3 h-3"></i>
                                        <span>Dibayarkan</span>
                                    </span>
                                </template>
                            </td>

                            <!-- 8. Aksi -->
                            <td class="cell-right cell-nowrap" style="padding-right: 20px;">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <a :href="'<?= Router::url('/penggajian/preview?id=') ?>' + p.id" 
                                       class="payroll-btn-periksa"
                                       :title="p.status === 'draf' ? 'Periksa dan Edit Draf Payroll' : 'Lihat Rincian Payroll'">
                                        <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                        <span x-text="p.status === 'draf' ? 'Periksa' : 'Detail'"></span>
                                    </a>
                                    <template x-if="p.status !== 'draf'">
                                        <a :href="'<?= Router::url('/penggajian/rekap-pdf?run_id=') ?>' + p.id" 
                                           target="_blank" 
                                           class="payroll-btn-pdf" 
                                           title="Cetak Rekap PDF">
                                            <i data-lucide="printer" style="width: 14px; height: 14px;"></i>
                                        </a>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="block sm:hidden divide-y divide-slate-200 dark:divide-slate-700">
            <template x-for="p in filteredList" :key="'mob-' + p.id">
                <div class="p-3.5 space-y-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-slate-100 text-sm" x-text="p.nama_payroll"></div>
                            <div class="inline-flex items-center px-2 py-0.5 rounded font-mono text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 mt-1" x-text="p.nomor_referensi"></div>
                        </div>
                        <div>
                            <template x-if="p.status === 'draf'">
                                <span class="badge-status-draf">
                                    <span class="dot-pulse"></span>
                                    <span>Draf</span>
                                </span>
                            </template>
                            <template x-if="p.status === 'disetujui'">
                                <span class="badge-status-disetujui">
                                    <i data-lucide="check" class="w-3 h-3"></i>
                                    <span>Disetujui</span>
                                </span>
                            </template>
                            <template x-if="p.status === 'dibayarkan'">
                                <span class="badge-status-dibayarkan">
                                    <i data-lucide="check-check" class="w-3 h-3"></i>
                                    <span>Dibayarkan</span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <div class="text-xs text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span x-text="p.periode_awal_fmt + ' - ' + p.periode_akhir_fmt"></span>
                    </div>

                    <div class="flex items-center justify-between text-xs bg-slate-50 dark:bg-slate-900/50 p-2.5 rounded-lg border border-slate-100 dark:border-slate-800">
                        <div>
                            <div class="text-[10.5px] text-slate-400">Tipe &amp; Karyawan</div>
                            <div class="font-medium text-slate-700 dark:text-slate-300 mt-0.5 flex items-center gap-1.5">
                                <template x-if="p.tipe_penggajian === 'borongan' || p.tipe_penggajian === 'mingguan'">
                                    <span class="badge-tipe-borongan">Borongan</span>
                                </template>
                                <template x-if="p.tipe_penggajian === 'bulanan'">
                                    <span class="badge-tipe-bulanan">Bulanan</span>
                                </template>
                                <template x-if="p.tipe_penggajian === 'gabungan'">
                                    <span class="badge-tipe-gabungan">Gabungan</span>
                                </template>
                                <span class="text-slate-500 font-mono">(<span x-text="p.total_karyawan_terbayar"></span>/<span x-text="p.total_karyawan"></span>)</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10.5px] text-slate-400">Total Gaji</div>
                            <div class="font-bold font-mono text-sm mt-0.5" 
                                 :class="p.total_gaji > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500'" 
                                 x-text="p.total_gaji_fmt"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <a :href="'<?= Router::url('/penggajian/preview?id=') ?>' + p.id" 
                           class="payroll-btn-periksa">
                            <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                            <span x-text="p.status === 'draf' ? 'Periksa Draf' : 'Lihat Detail'"></span>
                        </a>
                        <template x-if="p.status !== 'draf'">
                            <a :href="'<?= Router::url('/penggajian/rekap-pdf?run_id=') ?>' + p.id" 
                               target="_blank" 
                               class="payroll-btn-pdf" 
                               title="Rekap PDF">
                                <i data-lucide="printer" style="width: 14px; height: 14px;"></i>
                            </a>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Filter Empty State (Saat ada data tapi pencarian tidak ketemu) -->
        <div x-show="payrollList.length > 0 && filteredList.length === 0" class="p-8 text-center text-slate-500">
            <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 mb-3">
                <i data-lucide="search-x" class="w-6 h-6"></i>
            </div>
            <p class="font-bold text-sm text-slate-700 dark:text-slate-300">Tidak ada periode cocok</p>
            <p class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau tab filter yang aktif.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>
