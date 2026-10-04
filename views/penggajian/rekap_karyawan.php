<?php
/**
 * views/penggajian/rekap_karyawan.php
 * Rekap Riwayat Penggajian, Kehadiran, Kasbon, & Tabungan Per Karyawan Keren One ERP
 * 100% Selaras DNA Desain, Variatif & Harmonis, Responsif Desktop & Mobile
 */
use App\Helpers\Format;
use App\Core\Router;

ob_start();

function getRekapInitials(string $name): string {
    $words = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach ($words as $w) {
        if (!empty($w)) {
            $initials .= mb_strtoupper(mb_substr($w, 0, 1));
        }
        if (mb_strlen($initials) >= 2) break;
    }
    return $initials ?: 'KR';
}

function getRekapAvatarColor(string $name): array {
    $colors = [
        ['bg' => 'rgba(16, 185, 129, 0.12)', 'text' => '#047857', 'border' => 'rgba(16, 185, 129, 0.28)'], // Emerald
        ['bg' => 'rgba(79, 70, 229, 0.1)',   'text' => '#4338ca', 'border' => 'rgba(79, 70, 229, 0.25)'],  // Indigo
        ['bg' => 'rgba(2, 132, 199, 0.1)',   'text' => '#0284c7', 'border' => 'rgba(2, 132, 199, 0.25)'],  // Sky Blue
        ['bg' => 'rgba(217, 119, 6, 0.12)',  'text' => '#b45309', 'border' => 'rgba(217, 119, 6, 0.28)'],  // Warm Amber
        ['bg' => 'rgba(147, 51, 234, 0.1)',  'text' => '#7e22ce', 'border' => 'rgba(147, 51, 234, 0.25)'], // Violet
        ['bg' => 'rgba(13, 148, 136, 0.12)', 'text' => '#0f766e', 'border' => 'rgba(13, 148, 136, 0.28)'], // Teal
        ['bg' => 'rgba(225, 29, 72, 0.1)',   'text' => '#be123c', 'border' => 'rgba(225, 29, 72, 0.25)'],  // Rose
    ];
    $idx = abs(crc32($name)) % count($colors);
    return $colors[$idx];
}

$avatarTheme = $karyawan ? getRekapAvatarColor($karyawan['nama_karyawan'] ?? '') : ['bg'=>'#f1f5f9','text'=>'#475569','border'=>'#cbd5e1'];

// Hitung total akumulasi gaji bersih tahun ini
$totalGajiTahunIni = 0.0;
$totalKasbonDipotongTahunIni = 0.0;
foreach ($payrollHistory as $ph) {
    $totalGajiTahunIni += (float)$ph['gaji_bersih_diterima'];
    $totalKasbonDipotongTahunIni += (float)$ph['total_potongan_kasbon'];
}
?>

<style>
/* ==========================================================================
   Rekap Karyawan DNA Styling & Responsive Utilities
   ========================================================================== */

/* 0. KPI Stat Cards */
.rekap-stat-card {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 14px;
    padding: 13px 15px;
    display: flex;
    align-items: center;
    gap: 13px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.rekap-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border-color: var(--color-hairline-strong, #cbd5e1);
}
.dark .rekap-stat-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}
.dark .rekap-stat-card:hover {
    border-color: #475569;
}

/* Stat Icon Box (Pastel Badges) */
.rekap-stat-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    min-height: 42px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-sizing: border-box;
}
.rekap-stat-icon svg {
    width: 20px;
    height: 20px;
    display: block;
}

.rekap-stat-icon.is-rose {
    background: rgba(225, 29, 72, 0.1);
    color: #be123c;
    border: 1px solid rgba(225, 29, 72, 0.25);
}
.dark .rekap-stat-icon.is-rose {
    background: rgba(225, 29, 72, 0.18);
    color: #fb7185;
    border-color: rgba(225, 29, 72, 0.35);
}

.rekap-stat-icon.is-emerald {
    background: rgba(16, 185, 129, 0.1);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.25);
}
.dark .rekap-stat-icon.is-emerald {
    background: rgba(16, 185, 129, 0.18);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.35);
}

.rekap-stat-icon.is-indigo {
    background: rgba(99, 102, 241, 0.1);
    color: #4f46e5;
    border: 1px solid rgba(99, 102, 241, 0.25);
}
.dark .rekap-stat-icon.is-indigo {
    background: rgba(99, 102, 241, 0.18);
    color: #a5b4fc;
    border-color: rgba(99, 102, 241, 0.35);
}

.rekap-stat-icon.is-sky {
    background: rgba(2, 132, 199, 0.1);
    color: #0284c7;
    border: 1px solid rgba(2, 132, 199, 0.25);
}
.dark .rekap-stat-icon.is-sky {
    background: rgba(2, 132, 199, 0.18);
    color: #38bdf8;
    border-color: rgba(2, 132, 199, 0.35);
}

/* Avatar Large */
.rekap-avatar-lg {
    width: 48px;
    height: 48px;
    min-width: 48px;
    min-height: 48px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.rekap-stat-card.is-rose { border-left: 3.5px solid #e11d48; }
.rekap-stat-card.is-emerald { border-left: 3.5px solid #10b981; }
.rekap-stat-card.is-indigo { border-left: 3.5px solid #6366f1; }
.rekap-stat-card.is-sky { border-left: 3.5px solid #0284c7; }

/* Action button styles */
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

.btn-launch-emerald {
    background: #059669;
    color: #ffffff;
    border: 1px solid #047857;
    box-shadow: 0 1px 2px rgba(5, 150, 105, 0.25);
    transition: all 0.15s ease;
}
.btn-launch-emerald:hover {
    background: #047857;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(5, 150, 105, 0.3);
}

.btn-secondary-clean {
    background: var(--color-canvas, #ffffff);
    color: var(--color-ink-primary, #1e293b);
    border: 1px solid var(--color-hairline-strong, #cbd5e1);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    transition: all 0.15s ease;
}
.btn-secondary-clean:hover {
    background: var(--color-canvas-soft, #f8fafc);
    border-color: #94a3b8;
}
.dark .btn-secondary-clean {
    background: #1e293b;
    color: #f1f5f9;
    border-color: #475569;
}
.dark .btn-secondary-clean:hover {
    background: #334155;
}

/* Badges */
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
}
</style>

<div class="space-y-4">
    <!-- 1. PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-200 dark:border-indigo-800">
                <i data-lucide="user-check" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="flex items-center gap-2 text-xs font-mono text-slate-500">
                    <a href="<?= Router::url('/penggajian') ?>" class="hover:underline text-indigo-600 dark:text-indigo-400">Penggajian</a>
                    <span>/</span>
                    <span>Rekap Riwayat Karyawan</span>
                </div>
                <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2 mt-0.5">
                    Rekap Riwayat Karyawan
                </h1>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:flex items-center gap-2 w-full sm:w-auto justify-end">
            <a href="<?= Router::url('/penggajian') ?>" class="btn btn-secondary-clean text-xs font-bold flex items-center justify-center gap-1.5 w-full sm:w-auto px-3.5 py-2 rounded-lg" style="height:38px;">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali</span>
            </a>
            <?php if (!empty($karyawanId)): ?>
            <a href="<?= Router::url('/penggajian/rekap/karyawan/pdf?karyawan_id=' . $karyawanId . '&tahun=' . $tahun) ?>" target="_blank" class="btn btn-launch-emerald text-xs font-bold flex items-center justify-center gap-1.5 w-full sm:w-auto px-4 py-2 rounded-lg" style="height:38px;">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak PDF</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. FILTER DOCK -->
    <div class="bg-white dark:bg-slate-800 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
        <form method="GET" action="<?= Router::url('/penggajian/rekap/karyawan') ?>" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <div class="sm:col-span-6">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Pilih Karyawan</label>
                <select name="karyawan_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <?php foreach ($karyawanList as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $k['id'] === $karyawanId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama_karyawan']) ?> &bull; <?= htmlspecialchars($k['posisi'] ?? '-') ?> (<?= ucfirst($k['tipe_penggajian']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:col-span-3">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Tahun Laporan</label>
                <select name="tahun" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 3; $y--): ?>
                    <option value="<?= $y ?>" <?= $y === $tahun ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="btn btn-secondary-clean w-full py-2 px-3 text-xs font-bold rounded-lg flex items-center justify-center gap-1.5" style="height:38px;">
                    <i data-lucide="filter" class="w-3.5 h-3.5 text-slate-500"></i>
                    <span>Terapkan Filter</span>
                </button>
            </div>
        </form>
    </div>

    <?php if ($karyawan): ?>
    <!-- 3. PROFIL KARYAWAN & METRICS -->
    <div class="bg-white dark:bg-slate-800 p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
        <!-- Identity Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-700 pb-4">
            <div class="flex items-center gap-3">
                <div class="rekap-avatar-lg" style="background: <?= $avatarTheme['bg'] ?>; color: <?= $avatarTheme['text'] ?>; border: 1.5px solid <?= $avatarTheme['border'] ?>;">
                    <?= getRekapInitials($karyawan['nama_karyawan']) ?>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100">
                            <?= htmlspecialchars($karyawan['nama_karyawan']) ?>
                        </h2>
                        <span class="<?= ($karyawan['tipe_penggajian'] ?? '') === 'borongan' ? 'badge-tipe-borongan' : 'badge-tipe-bulanan' ?>">
                            <?= ucfirst($karyawan['tipe_penggajian'] ?? 'Bulanan') ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Posisi: <strong class="text-slate-700 dark:text-slate-300"><?= htmlspecialchars($karyawan['posisi'] ?? '-') ?></strong> &bull; NIK: <?= htmlspecialchars($karyawan['nik'] ?? '-') ?>
                    </p>
                </div>
            </div>

            <div class="text-xs text-slate-500 bg-slate-50 dark:bg-slate-900/50 px-3 py-2 rounded-lg border border-slate-100 dark:border-slate-800">
                <span class="block text-[10.5px] uppercase font-bold tracking-wider text-slate-400">Periode Evaluasi</span>
                <span class="font-bold text-slate-700 dark:text-slate-300">Tahun Buku <?= $tahun ?></span>
            </div>
        </div>

        <!-- 4 KPI Stat Cards Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Card 1: Sisa Kasbon Aktif -->
            <div class="rekap-stat-card is-rose">
                <div class="rekap-stat-icon is-rose">
                    <i data-lucide="wallet"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Sisa Kasbon Aktif</div>
                    <div class="text-lg sm:text-xl font-bold font-mono text-rose-600 dark:text-rose-400 mt-0.5">
                        <?= Format::rupiah($sisaKasbon) ?>
                    </div>
                </div>
            </div>

            <!-- Card 2: Saldo Tabungan -->
            <div class="rekap-stat-card is-emerald">
                <div class="rekap-stat-icon is-emerald">
                    <i data-lucide="piggy-bank"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Saldo Tabungan</div>
                    <div class="text-lg sm:text-xl font-bold font-mono text-emerald-700 dark:text-emerald-300 mt-0.5">
                        <?= Format::rupiah($saldoTabungan) ?>
                    </div>
                </div>
            </div>

            <!-- Card 3: Frekuensi Penggajian -->
            <div class="rekap-stat-card is-indigo">
                <div class="rekap-stat-icon is-indigo">
                    <i data-lucide="layers"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Penggajian Tahun <?= $tahun ?></div>
                    <div class="text-lg sm:text-xl font-bold font-mono text-slate-900 dark:text-slate-100 mt-0.5">
                        <?= count($payrollHistory) ?> <span class="text-xs font-normal text-slate-400">periode</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: Total Gaji Bersih Tahun Ini -->
            <div class="rekap-stat-card is-sky">
                <div class="rekap-stat-icon is-sky">
                    <i data-lucide="check-check"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Gaji Bersih Diterima</div>
                    <div class="text-lg sm:text-xl font-bold font-mono text-sky-600 dark:text-sky-400 mt-0.5">
                        <?= Format::rupiah($totalGajiTahunIni) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank & Master Pay Data Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1 text-xs border-t border-slate-200 dark:border-slate-700">
            <div>
                <span class="text-slate-400 block text-[11px]">Bank & Rekening:</span>
                <span class="font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($karyawan['bank_nama'] ?? '-') ?> &bull; <?= htmlspecialchars($karyawan['bank_nomor_rekening'] ?? '-') ?></span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px]">Atas Nama Rekening:</span>
                <span class="font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($karyawan['bank_atas_nama'] ?? '-') ?></span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px]">Uang Hadir Harian:</span>
                <span class="font-semibold font-mono text-slate-800 dark:text-slate-200"><?= Format::rupiah((float)$karyawan['uang_kehadiran_harian']) ?></span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px]">Gaji Pokok / Tunjangan:</span>
                <span class="font-semibold font-mono text-slate-800 dark:text-slate-200"><?= Format::rupiah((float)$karyawan['gaji_pokok_bulanan']) ?> / <?= Format::rupiah((float)$karyawan['tunjangan_bulanan']) ?></span>
            </div>
        </div>
    </div>

    <!-- 4. RIWAYAT PENGGAJIAN TAHUN INI -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <i data-lucide="wallet" class="w-4 h-4 text-slate-500"></i>
                <span>Riwayat Penggajian Tahun <?= $tahun ?></span>
            </h3>
            <span class="text-xs text-slate-500 font-mono font-medium"><?= count($payrollHistory) ?> Periode Tercatat</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3">No. Referensi</th>
                        <th class="px-4 py-3">Nama Payroll</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3 text-center">Hadir</th>
                        <th class="px-4 py-3 text-right">Pot. Kasbon</th>
                        <th class="px-4 py-3 text-right font-bold text-emerald-700 dark:text-emerald-400">Gaji Bersih</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Slip</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($payrollHistory)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                            Belum ada riwayat penggajian pada tahun <?= $tahun ?>.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($payrollHistory as $ph): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-750 transition">
                        <td class="px-4 py-3 font-mono font-bold text-xs text-slate-800 dark:text-slate-200"><?= htmlspecialchars($ph['nomor_referensi']) ?></td>
                        <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($ph['nama_payroll'] ?: $ph['nomor_referensi']) ?></td>
                        <td class="px-4 py-3 text-xs text-slate-500"><?= Format::tanggalIndo($ph['periode_awal']) ?> - <?= Format::tanggalIndo($ph['periode_akhir']) ?></td>
                        <td class="px-4 py-3 text-center font-medium"><?= $ph['hari_hadir'] ?> hr</td>
                        <td class="px-4 py-3 text-right font-mono text-rose-600 dark:text-rose-400"><?= Format::rupiah((float)$ph['total_potongan_kasbon']) ?></td>
                        <td class="px-4 py-3 text-right font-mono font-bold text-emerald-700 dark:text-emerald-300"><?= Format::rupiah((float)$ph['gaji_bersih_diterima']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                <?= ucfirst($ph['status_payroll']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= Router::url('/penggajian/slip?run_id=' . $ph['penggajian_id'] . '&rincian_id=' . $ph['id']) ?>" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 rounded-lg transition border border-blue-200/50 dark:border-blue-800/50">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                <span>Slip</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. PRESENSI BULANAN & MUTASI TABUNGAN GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Rekap Presensi Bulanan -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <i data-lucide="calendar" class="w-4 h-4 text-slate-500"></i>
                    <span>Rekap Presensi Bulanan (<?= $tahun ?>)</span>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 uppercase font-bold text-slate-500 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="px-3 py-2.5">Bulan</th>
                            <th class="px-3 py-2.5 text-center text-emerald-600">Hadir</th>
                            <th class="px-3 py-2.5 text-center text-blue-600">Izin</th>
                            <th class="px-3 py-2.5 text-center text-amber-600">Sakit</th>
                            <th class="px-3 py-2.5 text-center text-sky-600">Libur</th>
                            <th class="px-3 py-2.5 text-center text-rose-600">Alpa</th>
                            <th class="px-3 py-2.5 text-right">Lembur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php if (empty($absensiMonthly)): ?>
                        <tr><td colspan="7" class="px-3 py-6 text-center text-slate-400">Belum ada catatan absensi tahun ini.</td></tr>
                        <?php else: ?>
                        <?php foreach ($absensiMonthly as $am): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-750 transition">
                            <td class="px-3 py-2.5 font-semibold text-slate-800 dark:text-slate-200"><?= date('F Y', strtotime($am['bulan'] . '-01')) ?></td>
                            <td class="px-3 py-2.5 text-center font-bold text-emerald-600"><?= $am['hadir'] ?></td>
                            <td class="px-3 py-2.5 text-center text-blue-600"><?= $am['izin'] ?></td>
                            <td class="px-3 py-2.5 text-center text-amber-600"><?= $am['sakit'] ?></td>
                            <td class="px-3 py-2.5 text-center text-sky-600"><?= $am['libur'] ?? 0 ?></td>
                            <td class="px-3 py-2.5 text-center text-rose-600"><?= $am['alpa'] ?></td>
                            <td class="px-3 py-2.5 text-right font-mono text-slate-700 dark:text-slate-300"><?= Format::rupiah((float)$am['total_lembur']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mutasi Tabungan Karyawan -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <i data-lucide="piggy-bank" class="w-4 h-4 text-slate-500"></i>
                    <span>Mutasi Tabungan Karyawan</span>
                </h3>
                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300 font-mono">Saldo: <?= Format::rupiah($saldoTabungan) ?></span>
            </div>
            <div class="overflow-x-auto max-h-80">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 uppercase font-bold text-slate-500 border-b border-slate-200 dark:border-slate-700 sticky top-0">
                        <tr>
                            <th class="px-3 py-2.5">Tanggal</th>
                            <th class="px-3 py-2.5 text-center">Tipe</th>
                            <th class="px-3 py-2.5 text-right">Nominal</th>
                            <th class="px-3 py-2.5">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php if (empty($tabunganList)): ?>
                        <tr><td colspan="4" class="px-3 py-6 text-center text-slate-400">Belum ada mutasi tabungan tahun ini.</td></tr>
                        <?php else: ?>
                        <?php foreach ($tabunganList as $tl): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-750 transition">
                            <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300 whitespace-nowrap"><?= Format::tanggalIndo($tl['tanggal']) ?></td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold <?= $tl['tipe'] === 'deposit' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' ?>">
                                    <?= $tl['tipe'] === 'deposit' ? 'Setor' : 'Tarik' ?>
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-bold whitespace-nowrap <?= $tl['tipe'] === 'deposit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>">
                                <?= ($tl['tipe'] === 'deposit' ? '+' : '-') . Format::rupiah((float)$tl['jumlah']) ?>
                            </td>
                            <td class="px-3 py-2.5 text-slate-500 truncate max-w-xs"><?= htmlspecialchars($tl['keterangan'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
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
