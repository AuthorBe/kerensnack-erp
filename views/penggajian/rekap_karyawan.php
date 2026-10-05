<?php
/**
 * views/penggajian/rekap_karyawan.php
 * Rekap Riwayat Penggajian, Kehadiran, Kasbon, & Tabungan Per Karyawan Keren One ERP
 * Mengikuti Design System (erp-ui-design): page-header, stat-card, data-table, badge.
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
        ['bg' => 'rgba(16, 185, 129, 0.12)', 'text' => '#047857', 'border' => 'rgba(16, 185, 129, 0.28)'],
        ['bg' => 'rgba(79, 70, 229, 0.1)',   'text' => '#4338ca', 'border' => 'rgba(79, 70, 229, 0.25)'],
        ['bg' => 'rgba(2, 132, 199, 0.1)',   'text' => '#0284c7', 'border' => 'rgba(2, 132, 199, 0.25)'],
        ['bg' => 'rgba(217, 119, 6, 0.12)',  'text' => '#b45309', 'border' => 'rgba(217, 119, 6, 0.28)'],
        ['bg' => 'rgba(147, 51, 234, 0.1)',  'text' => '#7e22ce', 'border' => 'rgba(147, 51, 234, 0.25)'],
        ['bg' => 'rgba(13, 148, 136, 0.12)', 'text' => '#0f766e', 'border' => 'rgba(13, 148, 136, 0.28)'],
        ['bg' => 'rgba(225, 29, 72, 0.1)',   'text' => '#be123c', 'border' => 'rgba(225, 29, 72, 0.25)'],
    ];
    return $colors[abs(crc32($name)) % count($colors)];
}

function rekapBulanIndo(string $ym): string {
    static $names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $parts = explode('-', $ym);
    $m = (int)($parts[1] ?? 0);
    return ($names[$m] ?? $ym) . ' ' . ($parts[0] ?? '');
}

function rekapStatusBadge(string $status): string {
    $s = strtolower($status);
    if (in_array($s, ['dibayarkan', 'dibayar', 'paid', 'selesai'], true)) return 'badge-success';
    if (in_array($s, ['disetujui', 'approved'], true)) return 'badge-info';
    if (in_array($s, ['draf', 'draft'], true)) return 'badge-muted';
    if (in_array($s, ['batal', 'dibatalkan', 'ditolak'], true)) return 'badge-danger';
    return 'badge-secondary';
}

$avatarTheme = $karyawan ? getRekapAvatarColor($karyawan['nama_karyawan'] ?? '') : ['bg' => '#f1f5f9', 'text' => '#475569', 'border' => '#cbd5e1'];

$totalGajiTahunIni = 0.0;
$totalKasbonDipotongTahunIni = 0.0;
$totalHariHadir = 0;
foreach ($payrollHistory as $ph) {
    $totalGajiTahunIni += (float)$ph['gaji_bersih_diterima'];
    $totalKasbonDipotongTahunIni += (float)$ph['total_potongan_kasbon'];
    $totalHariHadir += (int)$ph['hari_hadir'];
}
$tipePenggajian = $karyawan['tipe_penggajian'] ?? 'bulanan';
?>

<style>
/* Rekap Karyawan: hanya aksen khusus halaman; komponen utama memakai app.css */
.rekap-avatar-lg {
    width: 48px; height: 48px; min-width: 48px;
    border-radius: 12px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 800; letter-spacing: -0.02em;
}
.rekap-info-grid dt { font-size: 11px; color: var(--color-ink-mute); margin-bottom: 2px; }
.rekap-info-grid dd { font-size: 13px; font-weight: 600; color: var(--color-ink); margin: 0; word-break: break-word; }
.rekap-section-title { font-size: 13px; font-weight: 700; color: var(--color-ink); display: flex; align-items: center; gap: 8px; }
.rekap-section-title svg { width: 16px; height: 16px; color: var(--color-ink-mute); }
.rekap-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;
    padding: 12px 16px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas);
}
.data-table tfoot td { font-weight: 700; background: var(--color-canvas-soft); border-top: 1px solid var(--color-hairline); }
.rekap-scroll-y { max-height: 340px; overflow-y: auto; }
.rekap-scroll-y thead th { position: sticky; top: 0; z-index: 1; }
.rekap-filter { display: flex; flex-direction: column; align-items: stretch; gap: 12px; padding: 12px; }
.rekap-filter-year { width: 100%; }
.rekap-filter-reset { justify-content: center; }
.rekap-year-badge { align-self: flex-start; }
@media (min-width: 640px) {
    .rekap-filter { flex-direction: row; align-items: flex-end; padding: 16px; gap: 12px; }
    .rekap-filter-year { width: 160px; flex-shrink: 0; }
    .rekap-filter-reset { flex-shrink: 0; }
    .rekap-year-badge { align-self: center; }
}
.rekap-spin svg { animation: rekap-spin 0.9s linear infinite; }
@keyframes rekap-spin { to { transform: rotate(360deg); } }
.rekap-header-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.btn.is-loading { opacity: 0.8; pointer-events: none; }

/* Combobox karyawan */
.rekap-combo-list {
    position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 50;
    max-height: min(320px, 55vh); overflow-y: auto;
    background: var(--color-canvas, #fff); border: 1px solid var(--color-hairline);
    border-radius: 8px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12); padding: 4px;
}
.rekap-combo-item {
    display: flex; align-items: center; gap: 8px; width: 100%; text-align: left;
    padding: 8px 10px; border-radius: 6px; background: transparent; border: 0; cursor: pointer;
}
.rekap-combo-item.is-active { background: var(--color-canvas-soft); }
.rekap-combo-item.is-selected .rekap-combo-name { color: var(--color-primary); }
.rekap-combo-name { display: block; font-size: 13px; font-weight: 600; color: var(--color-ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rekap-combo-meta { display: block; font-size: 11px; color: var(--color-ink-mute); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rekap-combo-empty { padding: 14px 10px; text-align: center; font-size: 12px; color: var(--color-ink-mute); }

/* Kartu riwayat penggajian untuk layar kecil */
.rekap-pay-card { padding: 12px 14px; border-top: 1px solid var(--color-hairline); }
.rekap-pay-card:first-child { border-top: 0; }
.rekap-pay-row { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; font-size: 12px; padding: 2px 0; }
.rekap-pay-row > span:first-child { color: var(--color-ink-mute); flex-shrink: 0; }
.rekap-pay-row > span:last-child { text-align: right; min-width: 0; word-break: break-word; }

@media (max-width: 639px) {
    .rekap-header-actions { width: 100%; display: grid; grid-template-columns: 1fr 1fr; }
    .rekap-header-actions .btn { width: 100%; justify-content: center; }
    .rekap-header-actions > :only-child { grid-column: 1 / -1; }
    .rekap-combo-list { position: fixed; left: 12px; right: 12px; top: auto; bottom: 12px; max-height: 60vh; z-index: 70; }
    .stat-card-value { font-size: 18px; }
}
</style>

<div class="space-y-5" style="padding-bottom:80px;" x-data="rekapKaryawanApp()" x-init="init()">

    <!-- 1. PAGE HEADER -->
    <div class="page-header flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-indigo" style="flex-shrink:0;">
                <i data-lucide="user-check"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#4f46e5;"></span>
                    <a href="<?= Router::url('/penggajian') ?>" style="color:inherit;">Penggajian</a>
                    <span>/</span>
                    <span>Rekap Karyawan</span>
                </div>
                <h1 class="page-title">Rekap Riwayat Karyawan</h1>
                <p class="page-subtitle">Riwayat gaji, kehadiran, kasbon, dan tabungan per karyawan.</p>
            </div>
        </div>

        <div class="page-header-actions rekap-header-actions">
            <a href="<?= Router::url('/penggajian') ?>" class="btn btn-secondary" style="height:38px;">
                <i data-lucide="arrow-left"></i>
                <span>Kembali</span>
            </a>
            <?php if (!empty($karyawan)): ?>
            <a href="<?= Router::url('/penggajian/rekap/karyawan/pdf?karyawan_id=' . urlencode((string)$karyawanId) . '&tahun=' . (int)$tahun) ?>"
               @click="startDownload($event)"
               class="btn btn-primary" style="height:38px;" download>
                <i data-lucide="download"></i>
                
                <span>Unduh PDF</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. FILTER BAR -->
    <div class="card" style="padding:0; position:relative; z-index:20;">
        <form method="GET" x-ref="filterForm" action="<?= Router::url('/penggajian/rekap/karyawan') ?>"
              class="rekap-filter" style="background-color:var(--color-canvas); border-radius:inherit;">
            <input type="hidden" name="karyawan_id" :value="selectedId">

            <!-- Combobox karyawan dengan pencarian -->
            <div class="flex-1 min-w-0 relative" @click.outside="open = false" @keydown.escape.window="open = false">
                <label class="form-label" for="rekapKaryawanSearch">Karyawan</label>
                <div class="form-input-icon relative">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input id="rekapKaryawanSearch" x-ref="search" type="text" autocomplete="off" role="combobox"
                           :aria-expanded="open" aria-controls="rekapKaryawanList"
                           class="form-input" style="height:38px; font-size:13px; padding-right:64px;"
                           :placeholder="selectedLabel || 'Cari &amp; pilih karyawan...'"
                           x-model="query"
                           @focus="openList()" @input="open = true; highlight = 0"
                           @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                           @keydown.enter.prevent="pickHighlighted()">
                    <button type="button" x-cloak x-show="query" @click="query = ''; $refs.search && $refs.search.focus()"
                            class="btn btn-ghost btn-xs" title="Hapus pencarian"
                            style="position:absolute; right:34px; top:50%; transform:translateY(-50%); padding:4px;">
                        <i data-lucide="x" style="width:14px; height:14px;"></i>
                    </button>
                    <button type="button" @click="open ? open = false : openList()" tabindex="-1"
                            class="btn btn-ghost btn-xs" title="Buka daftar karyawan"
                            style="position:absolute; right:6px; top:50%; transform:translateY(-50%); padding:4px;">
                        <i data-lucide="chevrons-up-down" style="width:14px; height:14px;"></i>
                    </button>
                </div>
                <div id="rekapKaryawanList" role="listbox" x-cloak x-show="open" x-transition.opacity.duration.100ms class="rekap-combo-list custom-scrollbar">
                    <template x-for="(k, i) in filtered" :key="k.id">
                        <button type="button" role="option" :aria-selected="k.id === selectedId"
                                class="rekap-combo-item" :class="{ 'is-active': i === highlight, 'is-selected': k.id === selectedId }"
                                @mouseenter="highlight = i" @click="pick(k)">
                            <span class="min-w-0 flex-1">
                                <span class="rekap-combo-name" x-text="k.nama"></span>
                                <span class="rekap-combo-meta" x-text="(k.posisi || '-') + ' • ' + k.tipe"></span>
                            </span>
                            <svg x-show="k.id === selectedId" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-primary); flex-shrink:0;"><path d="M20 6 9 17l-5-5"/></svg>
                        </button>
                    </template>
                    <div x-show="filtered.length === 0" class="rekap-combo-empty">Karyawan tidak ditemukan</div>
                </div>
            </div>

            <div class="rekap-filter-year">
                <label class="form-label" for="rekapTahun">Tahun Laporan</label>
                <select id="rekapTahun" name="tahun" onchange="this.form.submit()" class="form-input font-mono" style="height:38px; font-size:13px;">
                    <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 3; $y--): ?>
                    <option value="<?= $y ?>" <?= $y === (int)$tahun ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <?php if (!empty($karyawan)): ?>
            <a href="<?= Router::url('/penggajian/rekap/karyawan?tahun=' . (int)$tahun) ?>" class="btn btn-secondary rekap-filter-reset" style="height:38px;" title="Kosongkan pilihan karyawan">
                <i data-lucide="rotate-ccw"></i>
                <span>Reset</span>
            </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($karyawan): ?>

    <!-- 3. PROFIL KARYAWAN -->
    <div class="card" style="padding:16px;">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="border-bottom:1px solid var(--color-hairline); padding-bottom:16px;">
            <div class="flex items-center gap-3 min-w-0">
                <div class="rekap-avatar-lg" style="background: <?= $avatarTheme['bg'] ?>; color: <?= $avatarTheme['text'] ?>; border: 1px solid <?= $avatarTheme['border'] ?>;">
                    <?= htmlspecialchars(getRekapInitials($karyawan['nama_karyawan'])) ?>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 style="font-size:16px; font-weight:700; color:var(--color-ink);"><?= htmlspecialchars($karyawan['nama_karyawan']) ?></h2>
                        <span class="<?= $tipePenggajian === 'borongan' ? 'badge-tipe-borongan' : 'badge-tipe-bulanan' ?>"><?= ucfirst($tipePenggajian) ?></span>
                    </div>
                    <p style="font-size:12px; color:var(--color-ink-mute); margin-top:2px;">
                        <?= htmlspecialchars($karyawan['posisi'] ?? '-') ?> &bull; NIK: <span class="font-mono"><?= htmlspecialchars($karyawan['nik'] ?? '-') ?></span>
                    </p>
                </div>
            </div>
            <span class="badge badge-mono rekap-year-badge">Tahun Buku <?= (int)$tahun ?></span>
        </div>

        <!-- KPI -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3" style="padding-top:16px;">
            <div class="stat-card" style="display:flex; align-items:center; gap:14px;">
                <div class="stat-card-icon" style="background:rgba(225,29,72,0.1); color:#e11d48;"><i data-lucide="wallet"></i></div>
                <div class="min-w-0">
                    <div class="stat-card-label">Sisa Kasbon Aktif</div>
                    <div class="stat-card-value font-mono" style="color:#e11d48;"><?= Format::rupiah($sisaKasbon) ?></div>
                </div>
            </div>
            <div class="stat-card" style="display:flex; align-items:center; gap:14px;">
                <div class="stat-card-icon" style="background:rgba(16,185,129,0.1); color:#10b981;"><i data-lucide="coins"></i></div>
                <div class="min-w-0">
                    <div class="stat-card-label">Saldo Tabungan</div>
                    <div class="stat-card-value font-mono" style="color:#10b981;"><?= Format::rupiah($saldoTabungan) ?></div>
                </div>
            </div>
            <div class="stat-card" style="display:flex; align-items:center; gap:14px;">
                <div class="stat-card-icon" style="background:rgba(37,99,235,0.1); color:var(--color-primary);"><i data-lucide="layers"></i></div>
                <div class="min-w-0">
                    <div class="stat-card-label">Penggajian <?= (int)$tahun ?></div>
                    <div class="stat-card-value font-mono"><?= count($payrollHistory) ?> <span class="text-xs font-normal" style="color:var(--color-ink-mute);">periode</span></div>
                </div>
            </div>
            <div class="stat-card" style="display:flex; align-items:center; gap:14px;">
                <div class="stat-card-icon" style="background:rgba(2,132,199,0.1); color:#0284c7;"><i data-lucide="check-check"></i></div>
                <div class="min-w-0">
                    <div class="stat-card-label">Total Gaji Bersih</div>
                    <div class="stat-card-value font-mono" style="color:#0284c7;"><?= Format::rupiah($totalGajiTahunIni) ?></div>
                </div>
            </div>
        </div>

        <!-- Data bank & master gaji -->
        <dl class="rekap-info-grid grid grid-cols-2 lg:grid-cols-4" style="border-top:1px solid var(--color-hairline); margin-top:16px; padding-top:16px; gap:12px 16px;">
            <div>
                <dt>Bank &amp; Rekening</dt>
                <dd><?= htmlspecialchars($karyawan['bank_nama'] ?? '-') ?> &bull; <span class="font-mono"><?= htmlspecialchars($karyawan['bank_nomor_rekening'] ?? '-') ?></span></dd>
            </div>
            <div>
                <dt>Atas Nama Rekening</dt>
                <dd><?= htmlspecialchars($karyawan['bank_atas_nama'] ?? '-') ?></dd>
            </div>
            <div>
                <dt>Uang Hadir Harian</dt>
                <dd class="font-mono"><?= Format::rupiah((float)($karyawan['uang_kehadiran_harian'] ?? 0)) ?></dd>
            </div>
            <div>
                <dt>Gaji Pokok / Tunjangan</dt>
                <dd class="font-mono"><?= Format::rupiah((float)($karyawan['gaji_pokok_bulanan'] ?? 0)) ?> / <?= Format::rupiah((float)($karyawan['tunjangan_bulanan'] ?? 0)) ?></dd>
            </div>
        </dl>
    </div>

    <!-- 4. RIWAYAT PENGGAJIAN -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="rekap-card-head">
            <div class="rekap-section-title">
                <i data-lucide="wallet"></i>
                <span>Riwayat Penggajian Tahun <?= (int)$tahun ?></span>
            </div>
            <span class="badge badge-mono"><?= count($payrollHistory) ?> periode tercatat</span>
        </div>
        <div class="relative overflow-x-auto custom-scrollbar hidden sm:block">
            <table class="data-table" style="min-width: 760px;">
                <thead>
                    <tr>
                        <th class="cell-nowrap">No. Referensi</th>
                        <th>Nama Payroll</th>
                        <th class="cell-nowrap">Periode</th>
                        <th class="cell-center">Hadir</th>
                        <th class="cell-right">Pot. Kasbon</th>
                        <th class="cell-right">Gaji Bersih</th>
                        <th class="cell-center">Status</th>
                        <th class="cell-center" style="width:90px;">Slip</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payrollHistory)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:40px 20px; color:var(--color-ink-mute);">
                            <i data-lucide="inbox" style="width:36px; height:36px; margin:0 auto 8px auto; opacity:0.4;"></i>
                            <div style="font-weight:600; font-size:13px;">Belum ada riwayat penggajian pada tahun <?= (int)$tahun ?></div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($payrollHistory as $ph): ?>
                    <tr>
                        <td class="cell-nowrap"><span class="badge badge-mono"><?= htmlspecialchars($ph['nomor_referensi']) ?></span></td>
                        <td><strong class="text-sm" style="color:var(--color-ink);"><?= htmlspecialchars($ph['nama_payroll'] ?: $ph['nomor_referensi']) ?></strong></td>
                        <td class="cell-nowrap font-mono" style="font-size:12px; color:var(--color-ink-mute);"><?= Format::tanggalIndo($ph['periode_awal']) ?> - <?= Format::tanggalIndo($ph['periode_akhir']) ?></td>
                        <td class="cell-center font-mono"><?= (int)$ph['hari_hadir'] ?> hr</td>
                        <td class="cell-right font-mono" style="color:#e11d48;"><?= Format::rupiah((float)$ph['total_potongan_kasbon']) ?></td>
                        <td class="cell-right font-mono font-bold" style="color:#10b981;"><?= Format::rupiah((float)$ph['gaji_bersih_diterima']) ?></td>
                        <td class="cell-center"><span class="badge <?= rekapStatusBadge((string)$ph['status_payroll']) ?>"><?= htmlspecialchars(ucfirst($ph['status_payroll'])) ?></span></td>
                        <td class="cell-center cell-nowrap">
                            <a href="<?= Router::url('/penggajian/slip?run_id=' . urlencode((string)$ph['penggajian_id']) . '&rincian_id=' . urlencode((string)$ph['id'])) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" title="Lihat Slip Gaji">
                                <i data-lucide="file-text" style="width:14px; height:14px;"></i>
                                <span>Slip</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($payrollHistory)): ?>
                <tfoot>
                    <tr>
                        <td colspan="3" class="cell-right">Total <?= (int)$tahun ?></td>
                        <td class="cell-center font-mono"><?= $totalHariHadir ?> hr</td>
                        <td class="cell-right font-mono" style="color:#e11d48;"><?= Format::rupiah($totalKasbonDipotongTahunIni) ?></td>
                        <td class="cell-right font-mono" style="color:#10b981;"><?= Format::rupiah($totalGajiTahunIni) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- Tampilan kartu (ponsel) -->
        <div class="sm:hidden">
            <?php if (empty($payrollHistory)): ?>
            <div style="text-align:center; padding:32px 16px; color:var(--color-ink-mute); font-weight:600; font-size:13px;">
                Belum ada riwayat penggajian pada tahun <?= (int)$tahun ?>
            </div>
            <?php else: foreach ($payrollHistory as $ph): ?>
            <div class="rekap-pay-card">
                <div class="flex items-center justify-between gap-2" style="margin-bottom:6px;">
                    <span class="badge badge-mono" style="max-width:60%; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($ph['nomor_referensi']) ?></span>
                    <span class="badge <?= rekapStatusBadge((string)$ph['status_payroll']) ?>"><?= htmlspecialchars(ucfirst($ph['status_payroll'])) ?></span>
                </div>
                <div style="font-size:13px; font-weight:700; color:var(--color-ink);"><?= htmlspecialchars($ph['nama_payroll'] ?: $ph['nomor_referensi']) ?></div>
                <div class="rekap-pay-row"><span>Periode</span><span class="font-mono"><?= Format::tanggalIndo($ph['periode_awal'], false, true) ?> - <?= Format::tanggalIndo($ph['periode_akhir'], false, true) ?></span></div>
                <div class="rekap-pay-row"><span>Hadir</span><span class="font-mono"><?= (int)$ph['hari_hadir'] ?> hr</span></div>
                <div class="rekap-pay-row"><span>Pot. Kasbon</span><span class="font-mono" style="color:#e11d48;"><?= Format::rupiah((float)$ph['total_potongan_kasbon']) ?></span></div>
                <div class="rekap-pay-row"><span>Gaji Bersih</span><span class="font-mono font-bold" style="color:#10b981; font-size:14px;"><?= Format::rupiah((float)$ph['gaji_bersih_diterima']) ?></span></div>
                <a href="<?= Router::url('/penggajian/slip?run_id=' . urlencode((string)$ph['penggajian_id']) . '&rincian_id=' . urlencode((string)$ph['id'])) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm w-full mt-2" style="justify-content:center;">
                    <i data-lucide="file-text"></i><span>Lihat Slip</span>
                </a>
            </div>
            <?php endforeach; ?>
            <div class="rekap-pay-card" style="background:var(--color-canvas-soft);">
                <div class="rekap-pay-row"><span style="font-weight:700;">Total Gaji Bersih</span><span class="font-mono font-bold" style="color:#10b981;"><?= Format::rupiah($totalGajiTahunIni) ?></span></div>
                <div class="rekap-pay-row"><span>Total Pot. Kasbon</span><span class="font-mono" style="color:#e11d48;"><?= Format::rupiah($totalKasbonDipotongTahunIni) ?></span></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 5. PRESENSI BULANAN & MUTASI TABUNGAN -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Rekap Presensi Bulanan -->
        <div class="card" style="padding:0; overflow:hidden;">
            <div class="rekap-card-head">
                <div class="rekap-section-title">
                    <i data-lucide="calendar"></i>
                    <span>Rekap Presensi Bulanan (<?= (int)$tahun ?>)</span>
                </div>
            </div>
            <div class="relative overflow-x-auto custom-scrollbar">
                <table class="data-table" style="min-width: 480px;">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="cell-center" style="color:#10b981;">Hadir</th>
                            <th class="cell-center" style="color:#2563eb;">Izin</th>
                            <th class="cell-center" style="color:#f59e0b;">Sakit</th>
                            <th class="cell-center" style="color:#0284c7;">Libur</th>
                            <th class="cell-center" style="color:#ef4444;">Alpa</th>
                            <th class="cell-right">Lembur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($absensiMonthly)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:32px 20px; color:var(--color-ink-mute);">
                                <i data-lucide="calendar-x" style="width:32px; height:32px; margin:0 auto 8px auto; opacity:0.4;"></i>
                                <div style="font-weight:600; font-size:13px;">Belum ada catatan absensi tahun ini</div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($absensiMonthly as $am): ?>
                        <tr>
                            <td class="cell-nowrap"><strong style="color:var(--color-ink);"><?= htmlspecialchars(rekapBulanIndo((string)$am['bulan'])) ?></strong></td>
                            <td class="cell-center font-mono font-bold" style="color:#10b981;"><?= (int)$am['hadir'] ?></td>
                            <td class="cell-center font-mono"><?= (int)$am['izin'] ?></td>
                            <td class="cell-center font-mono"><?= (int)$am['sakit'] ?></td>
                            <td class="cell-center font-mono"><?= (int)($am['libur'] ?? 0) ?></td>
                            <td class="cell-center font-mono" style="<?= (int)$am['alpa'] > 0 ? 'color:#ef4444; font-weight:700;' : '' ?>"><?= (int)$am['alpa'] ?></td>
                            <td class="cell-right font-mono"><?= Format::rupiah((float)$am['total_lembur']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mutasi Tabungan -->
        <div class="card" style="padding:0; overflow:hidden;">
            <div class="rekap-card-head">
                <div class="rekap-section-title">
                    <i data-lucide="coins"></i>
                    <span>Mutasi Tabungan Karyawan</span>
                </div>
                <span class="badge badge-success font-mono">Saldo: <?= Format::rupiah($saldoTabungan) ?></span>
            </div>
            <div class="relative overflow-x-auto custom-scrollbar rekap-scroll-y">
                <table class="data-table" style="min-width: 440px;">
                    <thead>
                        <tr>
                            <th class="cell-nowrap">Tanggal</th>
                            <th class="cell-center">Tipe</th>
                            <th class="cell-right">Nominal</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tabunganList)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding:32px 20px; color:var(--color-ink-mute);">
                                <i data-lucide="coins" style="width:32px; height:32px; margin:0 auto 8px auto; opacity:0.4;"></i>
                                <div style="font-weight:600; font-size:13px;">Belum ada mutasi tabungan tahun ini</div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($tabunganList as $tl): $isDeposit = $tl['tipe'] === 'deposit'; ?>
                        <tr>
                            <td class="cell-nowrap font-mono" style="font-size:12px; color:var(--color-ink-mute);"><?= Format::tanggalIndo($tl['tanggal']) ?></td>
                            <td class="cell-center"><span class="badge <?= $isDeposit ? 'badge-success' : 'badge-danger' ?>"><?= $isDeposit ? 'Setor' : 'Tarik' ?></span></td>
                            <td class="cell-right cell-nowrap font-mono font-bold" style="color:<?= $isDeposit ? '#10b981' : '#ef4444' ?>;">
                                <?= ($isDeposit ? '+' : '-') . Format::rupiah((float)$tl['jumlah']) ?>
                            </td>
                            <td style="color:var(--color-ink-mute); max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($tl['keterangan'] ?? '-') ?>"><?= htmlspecialchars($tl['keterangan'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Empty state: belum ada karyawan dipilih -->
    <div class="card" style="text-align:center; padding:48px 20px; color:var(--color-ink-mute);">
        <i data-lucide="user-search" style="width:40px; height:40px; margin:0 auto 10px auto; opacity:0.4;"></i>
        <?php if (!empty($karyawanId)): ?>
        <div style="font-weight:600; font-size:14px; color:var(--color-ink);">Karyawan tidak ditemukan</div>
        <div style="font-size:12px; margin-top:4px;">Data karyawan yang dipilih tidak tersedia. Silakan pilih karyawan lain.</div>
        <?php else: ?>
        <div style="font-weight:600; font-size:14px; color:var(--color-ink);">Belum ada karyawan dipilih</div>
        <div style="font-size:12px; margin-top:4px;">Cari dan pilih karyawan pada kolom di atas untuk menampilkan rekap riwayatnya.</div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function rekapKaryawanApp() {
    return {
        items: <?= json_encode(array_map(static fn($k) => [
            'id'    => (string)$k['id'],
            'nama'  => (string)$k['nama_karyawan'],
            'posisi'=> (string)($k['posisi'] ?? ''),
            'tipe'  => ucfirst((string)($k['tipe_penggajian'] ?? '')),
        ], $karyawanList), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        selectedId: <?= json_encode((string)($karyawan['id'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        query: '',
        open: false,
        highlight: 0,
        downloading: false,

        init() {
            this.$nextTick(() => window.refreshIcons && window.refreshIcons());
        },
        get selected() {
            return this.items.find(i => i.id === this.selectedId) || null;
        },
        get selectedLabel() {
            return this.selected ? this.selected.nama + ' • ' + (this.selected.posisi || '-') : '';
        },
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) return this.items;
            return this.items.filter(i => (i.nama + ' ' + i.posisi + ' ' + i.tipe).toLowerCase().includes(q));
        },
        openList() {
            this.open = true;
            this.highlight = Math.max(0, this.filtered.findIndex(i => i.id === this.selectedId));
            this.$nextTick(() => { this.scrollToHighlight(); window.refreshIcons && window.refreshIcons(); });
        },
        move(step) {
            if (!this.open) { this.openList(); return; }
            const n = this.filtered.length;
            if (!n) return;
            this.highlight = (this.highlight + step + n) % n;
            this.$nextTick(() => this.scrollToHighlight());
        },
        scrollToHighlight() {
            const el = document.querySelector('#rekapKaryawanList .rekap-combo-item.is-active');
            if (el) el.scrollIntoView({ block: 'nearest' });
        },
        pickHighlighted() {
            const k = this.filtered[this.highlight];
            if (k) this.pick(k);
        },
        pick(k) {
            this.selectedId = k.id;
            this.open = false;
            this.query = '';
            this.$nextTick(() => this.$refs.filterForm.submit());
        },
        async startDownload(ev) {
            ev.preventDefault();
            if (this.downloading) return;
            const url = ev.currentTarget.href;
            this.downloading = true;
            if (window.AppAction) window.AppAction.show('Menyiapkan dokumen PDF...', 'Mohon tunggu, unduhan akan dimulai otomatis');
            try {
                const res = await fetch(url, { credentials: 'same-origin' });
                const type = (res.headers.get('Content-Type') || '').toLowerCase();
                if (!res.ok || type.indexOf('pdf') === -1) throw new Error('Respons bukan PDF');
                const blob = await res.blob();
                const cd = res.headers.get('Content-Disposition') || '';
                const m = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(cd);
                const name = m ? decodeURIComponent(m[1]) : 'Rekap_Karyawan.pdf';
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = name;
                document.body.appendChild(a);
                a.click();
                setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 1000);
                if (window.AppAction) window.AppAction.success('PDF berhasil diunduh', '', 1400);
            } catch (e) {
                if (window.AppAction) window.AppAction.error('Gagal menyiapkan PDF', 'Silakan coba lagi.', 2400);
                else if (window.toast) window.toast.error('Gagal menyiapkan PDF.');
            } finally {
                this.downloading = false;
            }
        }
    };
}
document.addEventListener('DOMContentLoaded', () => {
    if (typeof refreshIcons === 'function') refreshIcons();
    else if (window.lucide) lucide.createIcons();
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>
