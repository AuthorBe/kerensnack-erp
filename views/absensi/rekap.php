<?php
use App\Core\Router;
use App\Helpers\Format;

ob_start();
?>

<div class="space-y-5">
    <!-- PAGE HEADER -->
    <div class="page-header flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="page-header-content">
            <div class="flex items-center gap-3">
                <div class="header-icon-box bg-rose-50 dark:bg-rose-950/40 text-rose-900 dark:text-rose-300 p-2.5 rounded-xl border border-rose-200/60 dark:border-rose-800/40">
                    <i data-lucide="file-spreadsheet" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="page-title text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100"><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="page-subtitle text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Periode: <span class="font-semibold text-rose-900 dark:text-rose-400"><?= Format::tanggalIndo($tglAwal) ?></span> s/d <span class="font-semibold text-rose-900 dark:text-rose-400"><?= Format::tanggalIndo($tglAkhir) ?></span>
                    </p>
                </div>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2 flex-wrap w-full sm:w-auto">
            <a href="<?= Router::url('/absensi') ?>" class="btn btn-secondary text-xs flex-1 sm:flex-initial justify-center">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali</span>
            </a>
            <button type="button" 
                    onclick="downloadRekapPdf('<?= Router::url('/absensi/rekap/pdf?' . http_build_query(['tanggal_awal' => $tglAwal, 'tanggal_akhir' => $tglAkhir, 'tipe_gaji' => $tipeGaji, 'karyawan_id' => $karyawanId])) ?>')"
                    class="btn btn-primary text-xs flex items-center gap-1.5 flex-1 sm:flex-initial justify-center cursor-pointer">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Unduh PDF</span>
            </button>
        </div>
    </div>

    <!-- FILTER CARD -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
        <form method="GET" action="<?= Router::url('/absensi/rekap') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="form-label text-xs">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" value="<?= htmlspecialchars($tglAwal) ?>" class="form-input form-input-sm text-xs rounded-lg">
            </div>
            <div>
                <label class="form-label text-xs">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($tglAkhir) ?>" class="form-input form-input-sm text-xs rounded-lg">
            </div>
            <div>
                <label class="form-label text-xs">Tipe Karyawan</label>
                <select name="tipe_gaji" class="form-select form-select-sm text-xs rounded-lg">
                    <option value="semua" <?= $tipeGaji === 'semua' ? 'selected' : '' ?>>Semua Tipe</option>
                    <option value="borongan" <?= $tipeGaji === 'borongan' ? 'selected' : '' ?>>Borongan</option>
                    <option value="bulanan" <?= $tipeGaji === 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                </select>
            </div>
            <div>
                <label class="form-label text-xs">Karyawan</label>
                <select name="karyawan_id" class="form-select form-select-sm text-xs rounded-lg">
                    <option value="">Semua Karyawan</option>
                    <?php foreach ($karyawanList as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $karyawanId === $k['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_karyawan']) ?> (<?= ucfirst($k['tipe_penggajian']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-secondary w-full text-xs flex items-center justify-center gap-1.5 h-[34px]">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Terapkan Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- KPI SUMMARY -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-3 shadow-xs">
            <div class="text-[11px] text-slate-400">Total Karyawan</div>
            <div class="text-base font-bold font-mono text-slate-800 dark:text-slate-100 mt-0.5"><?= $totalKaryawan ?></div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-3 shadow-xs">
            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">Total Hari Hadir</div>
            <div class="text-base font-bold font-mono text-emerald-700 dark:text-emerald-300 mt-0.5"><?= $totalHadir ?></div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-3 shadow-xs">
            <div class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">Total Izin / Sakit</div>
            <div class="text-base font-bold font-mono text-amber-700 dark:text-amber-300 mt-0.5"><?= $totalIzinSakit ?></div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-3 shadow-xs">
            <div class="text-[11px] text-rose-600 dark:text-rose-400 font-medium">Total Alpa</div>
            <div class="text-base font-bold font-mono text-rose-700 dark:text-rose-300 mt-0.5"><?= $totalAlpa ?></div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-3 shadow-xs">
            <div class="text-[11px] text-slate-400">Total Lembur Bulanan</div>
            <div class="text-base font-bold font-mono text-slate-800 dark:text-slate-100 mt-0.5"><?= Format::rupiah($totalLembur) ?></div>
        </div>
    </div>

    <!-- DATA TABLE -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="table-wrapper overflow-x-auto">
            <table class="data-table w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/30 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4 min-w-[180px]">Nama Karyawan</th>
                        <th class="py-3 px-4 w-28 text-center">Tipe Gaji</th>
                        <th class="py-3 px-4 text-center text-emerald-600 dark:text-emerald-400">Hadir</th>
                        <th class="py-3 px-4 text-center text-amber-600 dark:text-amber-400">Izin</th>
                        <th class="py-3 px-4 text-center text-amber-600 dark:text-amber-400">Sakit</th>
                        <th class="py-3 px-4 text-center text-sky-600 dark:text-sky-400">Libur</th>
                        <th class="py-3 px-4 text-center text-rose-600 dark:text-rose-400">Alpa</th>
                        <th class="py-3 px-4 text-center">Telat</th>
                        <th class="py-3 px-4 text-right min-w-[130px]">Lembur (Rp)</th>
                        <th class="py-3 px-4 text-center w-28">% Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    <?php if (empty($rekapData)): ?>
                        <tr>
                            <td colspan="11" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                Tidak ada data rekapitulasi kehadiran untuk filter periode ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rekapData as $idx => $r): 
                            $totalHariAktif = $r['hari_hadir'] + $r['hari_izin'] + $r['hari_sakit'] + $r['hari_alpa'];
                            $persenHadir = ($totalHariAktif > 0) ? round(($r['hari_hadir'] / $totalHariAktif) * 100, 1) : 0;
                        ?>
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="py-3 px-4 text-center font-mono text-slate-400"><?= $idx + 1 ?></td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($r['nama_karyawan']) ?></div>
                                <div class="text-[11px] text-slate-400 capitalize"><?= htmlspecialchars($r['posisi'] ?? '-') ?></div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="badge <?= $r['tipe_penggajian'] === 'borongan' ? 'badge-neutral' : 'badge-info' ?> text-[10px] py-0.5 px-2">
                                    <?= ucfirst($r['tipe_penggajian']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-semibold text-emerald-600 dark:text-emerald-400"><?= $r['hari_hadir'] ?></td>
                            <td class="py-3 px-4 text-center font-mono text-amber-600"><?= $r['hari_izin'] ?></td>
                            <td class="py-3 px-4 text-center font-mono text-amber-600"><?= $r['hari_sakit'] ?></td>
                            <td class="py-3 px-4 text-center font-mono text-sky-600"><?= $r['hari_libur'] ?></td>
                            <td class="py-3 px-4 text-center font-mono font-semibold text-rose-600"><?= $r['hari_alpa'] ?></td>
                            <td class="py-3 px-4 text-center font-mono text-slate-500"><?= $r['hari_telat'] ?></td>
                            <td class="py-3 px-4 text-right font-mono"><?= Format::rupiah((float)$r['total_lembur_nominal']) ?></td>
                            <td class="py-3 px-4 text-center">
                                <div class="font-mono font-semibold <?= $persenHadir >= 90 ? 'text-emerald-600' : ($persenHadir >= 75 ? 'text-amber-600' : 'text-rose-600') ?>">
                                    <?= $persenHadir ?>%
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function downloadRekapPdf(url) {
    if (window.AppAction) {
        window.AppAction.show('Menyiapkan Dokumen PDF...', 'Mengompilasi data rekapitulasi kehadiran periode ini...');
    }

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error('Server mengembalikan kode error (' + response.status + ').');
        }

        const blob = await response.blob();
        if (blob.size === 0) {
            throw new Error('Ukuran berkas PDF kosong.');
        }

        let filename = 'Rekapitulasi Kehadiran (<?= date('d M Y', strtotime($tglAwal)) . ($tglAwal !== $tglAkhir ? ' sd ' . date('d M Y', strtotime($tglAkhir)) : '') ?>).pdf';
        const disposition = response.headers.get('content-disposition');
        if (disposition && disposition.indexOf('filename=') !== -1) {
            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
            if (matches != null && matches[1]) {
                filename = matches[1].replace(/['"]/g, '');
            }
        }

        const blobUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = blobUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        
        setTimeout(() => {
            window.URL.revokeObjectURL(blobUrl);
            a.remove();
        }, 1000);

        if (window.AppAction) {
            await window.AppAction.success('Unduhan Berhasil! ✨', 'Berkas PDF rekapitulasi kehadiran berhasil diunduh.', 1500);
        }

    } catch (err) {
        if (window.AppAction) {
            await window.AppAction.error('Gagal Mengunduh Dokumen!', err.message || 'Terjadi kesalahan saat memproses unduhan PDF.', 3000);
        } else {
            alert('Gagal mengunduh: ' + err.message);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>
