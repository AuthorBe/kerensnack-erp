<?php
/**
 * views/penggajian/rekap_karyawan_pdf.php
 * Dokumen Resmi: Rekapitulasi Riwayat Karyawan (A4 Portrait, Dompdf)
 * Struktur: Kop surat -> Judul & nomor dokumen -> Identitas -> Ringkasan ->
 *           Rincian (gaji, presensi, kasbon, tabungan) -> Pengesahan -> Footer halaman.
 */
use App\Helpers\Format;
use App\Helpers\PrintDocumentHelper;

$bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$bulanLabel = static function (string $ym) use ($bulanNames): string {
    $p = explode('-', $ym);
    return ($bulanNames[(int)($p[1] ?? 0)] ?? $ym) . ' ' . ($p[0] ?? '');
};

$e = static fn($v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

$namaPt     = $company['nama'] ?? 'KEREN SNACK INDONESIA';
$nomorDok   = 'REK-KRY/' . (int)$tahun . '/' . strtoupper(substr(str_replace('-', '', (string)$karyawan['id']), 0, 6));
$tglCetak   = Format::tanggalIndo(date('Y-m-d')) . ', ' . date('H:i') . ' WIB';
$tipeLabel  = ucfirst((string)($karyawan['tipe_penggajian'] ?? 'bulanan'));

// Logo perusahaan disematkan sebagai data URI via helper
$logoSrc = PrintDocumentHelper::getLogoSrc($company);

// Agregat
$totGaji = 0.0; $totKasbonPot = 0.0; $totHadirGaji = 0;
foreach ($payrollHistory as $ph) {
    $totGaji      += (float)$ph['gaji_bersih_diterima'];
    $totKasbonPot += (float)$ph['total_potongan_kasbon'];
    $totHadirGaji += (int)$ph['hari_hadir'];
}
$totAbs = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'libur' => 0, 'alpa' => 0, 'lembur' => 0.0];
foreach ($absensiMonthly as $am) {
    foreach (['hadir', 'izin', 'sakit', 'libur', 'alpa'] as $k) { $totAbs[$k] += (int)($am[$k] ?? 0); }
    $totAbs['lembur'] += (float)$am['total_lembur'];
}
$totSetor = 0.0; $totTarik = 0.0;
foreach ($tabunganList as $tl) {
    if ($tl['tipe'] === 'deposit') { $totSetor += (float)$tl['jumlah']; } else { $totTarik += (float)$tl['jumlah']; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= $e($nomorDok) ?> - Rekap Riwayat Karyawan <?= $e($karyawan['nama_karyawan']) ?></title>
<style>
    @page { margin: 34mm 16mm 22mm 16mm; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 8.6pt; color: #111827; line-height: 1.4; margin: 0; padding: 0; }

    /* Kop surat berulang di setiap halaman */
    #kop { position: fixed; top: -28mm; left: 0; right: 0; height: 24mm; }
    #kop table { width: 100%; border-collapse: collapse; }
    .kop-name { font-size: 14pt; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
    .kop-tag  { font-size: 7.8pt; color: #4b5563; font-style: italic; margin-top: 1px; }
    .kop-addr { font-size: 7.6pt; color: #4b5563; margin-top: 2px; line-height: 1.35; }
    .kop-rule { border-top: 2px solid #881337; border-bottom: 0.7px solid #d1d5db; height: 2px; margin-top: 6px; }

    /* Footer berulang */
    #foot { position: fixed; bottom: -16mm; left: 0; right: 0; height: 12mm; font-size: 7.2pt; color: #6b7280; border-top: 0.7px solid #d1d5db; padding-top: 3px; }
    #foot table { width: 100%; border-collapse: collapse; }

    .doc-title { text-align: center; margin: 2px 0 8px 0; }
    .doc-title h1 { font-size: 13pt; margin: 0; letter-spacing: 1px; text-transform: uppercase; color: #111827; }
    .doc-title .no { font-size: 8.4pt; color: #4b5563; margin-top: 2px; }

    .sec { font-size: 8.8pt; font-weight: bold; color: #111827; background: #e5e7eb; border-left: 3px solid #374151; padding: 4px 8px; margin: 13px 0 5px 0; text-transform: uppercase; letter-spacing: 0.5px; page-break-after: avoid; }

    table.kv { width: 100%; border-collapse: collapse; }
    table.kv td { padding: 2.6px 4px; vertical-align: top; border-bottom: 0.5px solid #e5e7eb; }
    table.kv td.l { width: 22%; color: #4b5563; }
    table.kv td.c { width: 2%; }
    table.kv td.v { width: 26%; font-weight: bold; }

    table.sum { width: 100%; border-collapse: separate; border-spacing: 4px 0; margin: 0 -4px; }
    table.sum td { width: 25%; border: 0.7px solid #d1d5db; border-top: 2px solid #374151; padding: 6px 8px; background: #f9fafb; }
    table.sum .lb { font-size: 7pt; color: #4b5563; text-transform: uppercase; letter-spacing: 0.3px; }
    table.sum .vl { font-size: 10.5pt; font-weight: bold; margin-top: 2px; }

    table.dt { width: 100%; border-collapse: collapse; font-size: 8pt; }
    table.dt th { background: #e5e7eb; border: 0.7px solid #9ca3af; padding: 4.5px 5px; font-size: 7.4pt; text-transform: uppercase; text-align: left; color: #111827; }
    table.dt td { border: 0.5px solid #d1d5db; padding: 3.8px 5px; vertical-align: top; }
    table.dt tr { page-break-inside: avoid; }
    table.dt tfoot td, table.dt tr.tot td { background: #e5e7eb; font-weight: bold; border-top: 1px solid #374151; }
    table.dt tr.even td { background: #f9fafb; }
    .r { text-align: right; } .c { text-align: center; } .b { font-weight: bold; } .m { font-family: 'Helvetica', 'Arial', sans-serif; font-variant-numeric: tabular-nums; }
    .muted { color: #6b7280; } .pos { color: #111827; } .neg { color: #111827; }
    .empty { text-align: center; color: #6b7280; padding: 8px; font-style: italic; }

    .note { font-size: 7.6pt; color: #4b5563; margin-top: 8px; border-left: 3px solid #6b7280; padding: 3px 8px; background: #f9fafb; }

    table.sign { width: 100%; border-collapse: collapse; margin-top: 18px; page-break-inside: avoid; }
    table.sign td { width: 33.33%; text-align: center; vertical-align: top; font-size: 8.2pt; padding: 0 6px; }
    .sign-space { height: 52px; }
    .sign-line { border-top: 0.8px solid #111827; margin: 0 14px; padding-top: 2px; font-weight: bold; }
    .sign-role { font-size: 7.4pt; color: #4b5563; }
</style>
</head>
<body>

<!-- KOP SURAT -->
<div id="kop">
    <table>
        <tr>
            <?php if ($logoSrc !== ''): ?>
            <td style="width: 16mm; vertical-align: middle; padding-right: 3.5mm;">
                <img src="<?= $logoSrc ?>" style="height: 14mm; max-width: 14mm; object-fit: contain; display: block;">
            </td>
            <?php endif; ?>
            <td style="vertical-align: middle;">
                <div class="kop-name"><?= $e(mb_strtoupper($namaPt)) ?></div>
                <?php if (!empty($company['tagline'])): ?><div class="kop-tag"><?= $e($company['tagline']) ?></div><?php endif; ?>
                <div class="kop-addr">
                    <?= $e($company['alamat'] ?? '') ?><br>
                    Telp: <?= $e($company['telepon'] ?? '-') ?> &nbsp;|&nbsp; Email: <?= $e($company['email'] ?? '-') ?><?php if (!empty($company['website'])): ?> &nbsp;|&nbsp; <?= $e($company['website']) ?><?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
    <div class="kop-rule"></div>
</div>

<!-- FOOTER (nomor halaman diberi oleh script di bawah) -->
<div id="foot">
    <table>
        <tr>
            <td style="width: 60%;"><?= $e($nomorDok) ?> &nbsp;|&nbsp; Dokumen rahasia &ndash; hanya untuk keperluan internal perusahaan</td>
            <td style="width: 40%; text-align: right;">Dicetak: <?= $e($tglCetak) ?></td>
        </tr>
    </table>
</div>
<!-- DOMPDF_PAGE_NUMBERS -->

<!-- JUDUL -->
<div class="doc-title">
    <h1>Rekapitulasi Riwayat Karyawan</h1>
    <div class="no">Nomor: <?= $e($nomorDok) ?> &nbsp;&bull;&nbsp; Periode Laporan: Tahun <?= (int)$tahun ?></div>
</div>

<!-- A. IDENTITAS -->
<div class="sec">A. Identitas Karyawan</div>
<table class="kv">
    <tr>
        <td class="l">Nama Lengkap</td><td class="c">:</td><td class="v"><?= $e($karyawan['nama_karyawan']) ?></td>
        <td class="l">Bank</td><td class="c">:</td><td class="v"><?= $e($karyawan['bank_nama'] ?? '-') ?></td>
    </tr>
    <tr>
        <td class="l">NIK</td><td class="c">:</td><td class="v"><?= $e($karyawan['nik'] ?? '-') ?></td>
        <td class="l">No. Rekening</td><td class="c">:</td><td class="v m"><?= $e($karyawan['bank_nomor_rekening'] ?? '-') ?></td>
    </tr>
    <tr>
        <td class="l">Posisi / Jabatan</td><td class="c">:</td><td class="v"><?= $e($karyawan['posisi'] ?? '-') ?></td>
        <td class="l">Atas Nama</td><td class="c">:</td><td class="v"><?= $e($karyawan['bank_atas_nama'] ?? '-') ?></td>
    </tr>
    <tr>
        <td class="l">Tipe Penggajian</td><td class="c">:</td><td class="v"><?= $e($tipeLabel) ?></td>
        <td class="l">Gaji Pokok / Tunjangan</td><td class="c">:</td><td class="v m"><?= Format::rupiah((float)($karyawan['gaji_pokok_bulanan'] ?? 0)) ?> / <?= Format::rupiah((float)($karyawan['tunjangan_bulanan'] ?? 0)) ?></td>
    </tr>
    <tr>
        <td class="l">Uang Hadir Harian</td><td class="c">:</td><td class="v m"><?= Format::rupiah((float)($karyawan['uang_kehadiran_harian'] ?? 0)) ?></td>
        <td class="l"></td><td class="c"></td><td class="v"></td>
    </tr>
</table>

<!-- B. RINGKASAN -->
<div class="sec">B. Ringkasan Tahun <?= (int)$tahun ?></div>
<table class="sum">
    <tr>
        <td><div class="lb">Periode Gaji</div><div class="vl"><?= count($payrollHistory) ?> periode</div></td>
        <td><div class="lb">Total Gaji Bersih</div><div class="vl m pos"><?= Format::rupiah($totGaji) ?></div></td>
        <td><div class="lb">Sisa Kasbon Aktif</div><div class="vl m neg"><?= Format::rupiah($sisaKasbon) ?></div></td>
        <td><div class="lb">Saldo Tabungan</div><div class="vl m"><?= Format::rupiah($saldoTabungan) ?></div></td>
    </tr>
</table>

<!-- C. RIWAYAT GAJI -->
<div class="sec">C. Riwayat Pembayaran Gaji</div>
<table class="dt">
    <thead>
        <tr>
            <th class="c" style="width: 22px;">No</th>
            <th>No. Referensi</th>
            <th>Periode</th>
            <th class="c">Hadir</th>
            <th class="r">Pot. Kasbon</th>
            <th class="r">Gaji Bersih</th>
            <th class="c">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($payrollHistory)): ?>
        <tr><td colspan="7" class="empty">Tidak ada riwayat penggajian pada tahun <?= (int)$tahun ?>.</td></tr>
        <?php else: $no = 1; foreach ($payrollHistory as $ph): ?>
        <tr class="<?= $no % 2 === 0 ? 'even' : '' ?>">
            <td class="c"><?= $no++ ?></td>
            <td class="m" style="font-size: 7.4pt;"><?= $e($ph['nomor_referensi']) ?><?php if (!empty($ph['nama_payroll'])): ?><br><span class="muted" style="font-family: Helvetica;"><?= $e($ph['nama_payroll']) ?></span><?php endif; ?></td>
            <td><?= $e(Format::tanggalIndo($ph['periode_awal'], false, true)) ?> &ndash; <?= $e(Format::tanggalIndo($ph['periode_akhir'], false, true)) ?></td>
            <td class="c"><?= (int)$ph['hari_hadir'] ?> hr</td>
            <td class="r m"><?= Format::rupiah((float)$ph['total_potongan_kasbon']) ?></td>
            <td class="r m b"><?= Format::rupiah((float)$ph['gaji_bersih_diterima']) ?></td>
            <td class="c"><?= $e(ucfirst((string)$ph['status_payroll'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="tot">
            <td colspan="3" class="r">TOTAL TAHUN <?= (int)$tahun ?></td>
            <td class="c"><?= $totHadirGaji ?> hr</td>
            <td class="r m"><?= Format::rupiah($totKasbonPot) ?></td>
            <td class="r m"><?= Format::rupiah($totGaji) ?></td>
            <td></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- D. PRESENSI -->
<div class="sec">D. Rekapitulasi Kehadiran Bulanan</div>
<table class="dt">
    <thead>
        <tr>
            <th>Bulan</th>
            <th class="c">Hadir</th><th class="c">Izin</th><th class="c">Sakit</th><th class="c">Libur</th><th class="c">Alpa</th>
            <th class="r">Lembur (Rp)</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($absensiMonthly)): ?>
        <tr><td colspan="7" class="empty">Belum ada data kehadiran pada tahun <?= (int)$tahun ?>.</td></tr>
        <?php else: $i = 0; foreach ($absensiMonthly as $am): $i++; ?>
        <tr class="<?= $i % 2 === 0 ? 'even' : '' ?>">
            <td class="b"><?= $e($bulanLabel((string)$am['bulan'])) ?></td>
            <td class="c b"><?= (int)$am['hadir'] ?></td>
            <td class="c"><?= (int)$am['izin'] ?></td>
            <td class="c"><?= (int)$am['sakit'] ?></td>
            <td class="c"><?= (int)($am['libur'] ?? 0) ?></td>
            <td class="c <?= (int)$am['alpa'] > 0 ? 'neg b' : '' ?>"><?= (int)$am['alpa'] ?></td>
            <td class="r m"><?= Format::rupiah((float)$am['total_lembur']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="tot">
            <td>TOTAL</td>
            <td class="c"><?= $totAbs['hadir'] ?></td><td class="c"><?= $totAbs['izin'] ?></td><td class="c"><?= $totAbs['sakit'] ?></td>
            <td class="c"><?= $totAbs['libur'] ?></td><td class="c"><?= $totAbs['alpa'] ?></td>
            <td class="r m"><?= Format::rupiah($totAbs['lembur']) ?></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- E. KASBON -->
<div class="sec">E. Riwayat Kasbon / Pinjaman</div>
<table class="dt">
    <thead>
        <tr>
            <th>Tanggal Pengajuan</th>
            <th class="r">Total Pinjaman</th>
            <th class="r">Potongan / Periode</th>
            <th class="r">Sisa Pinjaman</th>
            <th class="c">Status</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($kasbonList)): ?>
        <tr><td colspan="6" class="empty">Tidak ada pengajuan kasbon pada tahun <?= (int)$tahun ?>.</td></tr>
        <?php else: $i = 0; foreach ($kasbonList as $kb): $i++; ?>
        <tr class="<?= $i % 2 === 0 ? 'even' : '' ?>">
            <td><?= $e(Format::tanggalIndo($kb['tanggal_pengajuan'])) ?></td>
            <td class="r m"><?= Format::rupiah((float)$kb['total_pinjaman']) ?></td>
            <td class="r m"><?= Format::rupiah((float)$kb['potongan_per_periode']) ?></td>
            <td class="r m b"><?= Format::rupiah((float)$kb['sisa_pinjaman']) ?></td>
            <td class="c"><?= $e(ucfirst((string)$kb['status_kasbon'])) ?></td>
            <td><?= $e($kb['keterangan'] ?: '-') ?></td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<!-- F. TABUNGAN -->
<div class="sec">F. Mutasi Tabungan Karyawan</div>
<table class="dt">
    <thead>
        <tr>
            <th style="width: 80px;">Tanggal</th>
            <th class="c" style="width: 60px;">Jenis</th>
            <th class="r" style="width: 95px;">Jumlah</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($tabunganList)): ?>
        <tr><td colspan="4" class="empty">Tidak ada mutasi tabungan pada tahun <?= (int)$tahun ?>.</td></tr>
        <?php else: $i = 0; foreach ($tabunganList as $tl): $i++; $dep = $tl['tipe'] === 'deposit'; ?>
        <tr class="<?= $i % 2 === 0 ? 'even' : '' ?>">
            <td><?= $e(Format::tanggalIndo($tl['tanggal'])) ?></td>
            <td class="c <?= $dep ? 'pos' : 'neg' ?> b"><?= $dep ? 'Setoran' : 'Penarikan' ?></td>
            <td class="r m b <?= $dep ? 'pos' : 'neg' ?>"><?= ($dep ? '+' : '-') . Format::rupiah((float)$tl['jumlah']) ?></td>
            <td><?= $e($tl['keterangan'] ?: '-') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="tot">
            <td colspan="2" class="r">Total Setoran / Penarikan</td>
            <td class="r m" colspan="2"><span class="pos">+<?= Format::rupiah($totSetor) ?></span> &nbsp;/&nbsp; <span class="neg">-<?= Format::rupiah($totTarik) ?></span></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="note">
    Dokumen ini disusun otomatis berdasarkan data pada sistem KEREN ONE ERP per <?= $e($tglCetak) ?>.
    Seluruh nominal dinyatakan dalam Rupiah (IDR). Dokumen bersifat rahasia dan hanya diperuntukkan bagi pihak berwenang.
</div>

<!-- PENGESAHAN -->
<table class="sign">
    <tr>
        <td>
            <div class="sign-role">Dibuat oleh,</div>
            <div class="sign-space"></div>
            <div class="sign-line">&nbsp;</div>
            <div class="sign-role">Admin HRD / Keuangan</div>
        </td>
        <td>
            <div class="sign-role">Diperiksa oleh,</div>
            <div class="sign-space"></div>
            <div class="sign-line">&nbsp;</div>
            <div class="sign-role">Manajer Operasional</div>
        </td>
        <td>
            <div class="sign-role">Disetujui oleh,</div>
            <div class="sign-space"></div>
            <div class="sign-line">&nbsp;</div>
            <div class="sign-role">Pimpinan Perusahaan</div>
        </td>
    </tr>
</table>

</body>
</html>
