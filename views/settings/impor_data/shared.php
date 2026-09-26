<?php
declare(strict_types=1);

/**
 * KEREN SNACK ERP — Modul Impor & Sinkronisasi Data
 * Berkas Konfigurasi Bersama & Helper Pemformatan Diff (shared.php)
 */

if (!function_exists('ks_format_diff_val')) {
    function ks_format_diff_val(string $key, $val): string {
        if ($val === null || $val === '' || $val === '—') {
            return '<span class="italic text-slate-400 dark:text-slate-500 font-mono text-[11px]">(Kosong)</span>';
        }
        if (is_bool($val)) {
            return $val 
                ? '<span class="inline-flex items-center gap-1 font-bold text-emerald-600 dark:text-emerald-400"><i data-lucide="check" class="w-3.5 h-3.5"></i> Aktif</span>' 
                : '<span class="inline-flex items-center gap-1 font-bold text-rose-600 dark:text-rose-400"><i data-lucide="x" class="w-3.5 h-3.5"></i> Nonaktif</span>';
        }
        if ($key === 'jenis_kelamin') {
            $g = strtoupper(trim((string)$val));
            return (in_array($g, ['P', 'PEREMPUAN', 'WANITA'], true))
                ? '<span class="inline-flex items-center gap-1 font-bold text-pink-600 dark:text-pink-400">🧕 Perempuan</span>'
                : '<span class="inline-flex items-center gap-1 font-bold text-blue-600 dark:text-blue-400">👨‍💼 Laki-laki</span>';
        }
        if (in_array(strtolower((string)$val), ['aktif', 'nonaktif'], true)) {
            return strtolower((string)$val) === 'aktif'
                ? '<span class="inline-flex items-center gap-1 font-bold text-emerald-600 dark:text-emerald-400"><i data-lucide="check" class="w-3.5 h-3.5"></i> Aktif</span>' 
                : '<span class="inline-flex items-center gap-1 font-bold text-rose-600 dark:text-rose-400"><i data-lucide="x" class="w-3.5 h-3.5"></i> Nonaktif</span>';
        }
        $currencyKeys = ['harga_pokok_pembelian', 'upah_per_bungkus', 'upah_manual', 'plafon_piutang', 'harga_jual_per_pcs', 'harga_jual', 'harga', 'hpp', 'gaji_pokok_bulanan', 'uang_kehadiran_harian', 'tunjangan_bulanan'];
        if (in_array($key, $currencyKeys, true) && is_numeric($val)) {
            return 'Rp ' . number_format((float)$val, 0, ',', '.');
        }
        return htmlspecialchars((string)$val);
    }
}

if (!function_exists('ks_format_diff_label')) {
    function ks_format_diff_label(string $key): string {
        $labels = [
            'kode_sku'                => 'Kode SKU',
            'nama_item'               => 'Nama Produk',
            'satuan_dasar'            => 'Satuan Dasar',
            'harga_pokok_pembelian'   => 'HPP Pokok',
            'upah_per_bungkus'        => 'Upah Borongan',
            'upah_manual'             => 'Upah Borongan Manual',
            'stok_minimum_peringatan' => 'Stok Minimum Peringatan',
            'status_jual'             => 'Status Jual',
            'status_aktif'            => 'Status Master Data',
            'kode_merek'              => 'Kode Merek',
            'nama_merek'              => 'Nama Merek Dagang',
            'kode_pelanggan'          => 'Kode Pelanggan',
            'nama_toko'               => 'Nama Toko / Mitra',
            'nama_pemilik'            => 'Nama Pemilik',
            'alamat_lengkap'          => 'Alamat Lengkap',
            'nomor_whatsapp'          => 'No WhatsApp',
            'nama_kontak'             => 'Kontak PIC',
            'plafon_piutang'          => 'Plafon Piutang',
            'is_konsinyasi'           => 'Tipe Toko',
            'tipe_pembayaran_default' => 'Metode Bayar Default',
            'harga_jual_per_pcs'      => 'Harga Jual / Pcs',
            'harga_jual_pcs'          => 'Harga Jual / Pcs',
            'level_harga'             => 'Level Harga',
            'nama_grup'               => 'Nama Grup Produk',
            'kode_grup'               => 'Kode Grup',
            'kode_rute'               => 'Kode Rute Distribusi',
            'nama_wilayah'            => 'Nama Wilayah / Rute',
            'provinsi'                => 'Provinsi',
            'kota_kabupaten'          => 'Kota / Kabupaten',
            'sub_wilayah'             => 'Cakupan Area',
            'nama_pemasok'            => 'Nama Pemasok',
            'kode_pemasok'            => 'Kode Pemasok',
            'termin_bayar'            => 'Termin Bayar',
            'nama_bank'               => 'Nama Bank',
            'nomor_rekening'          => 'No. Rekening',
            'atas_nama_rekening'      => 'Atas Nama Rekening',
            'catatan'                 => 'Catatan',
            'diskon_persen_default'   => 'Diskon Default (%)',
            'diskon_nominal_default'  => 'Diskon Nominal Default',
            'nik'                     => 'NIK Karyawan',
            'nama_lengkap'            => 'Nama Lengkap Karyawan',
            'nama_panggilan'          => 'Nama Panggilan',
            'jenis_kelamin'           => 'Jenis Kelamin',
            'tanggal_lahir'           => 'Tanggal Lahir',
            'tanggal_bergabung'       => 'Tanggal Bergabung',
            'nomor_polisi_kendaraan'  => 'Plat Nomor Kendaraan',
            'grup_id'                 => 'Relasi Grup Produk',
            'wilayah_id'              => 'Relasi Wilayah',
            'kelompok_borongan_id'    => 'Relasi Upah Borongan',
            'pemasok_utama_id'        => 'Relasi Pemasok Utama',
            'sales_pembina_id'        => 'Sales Pembina',
            'sales_driver_id'         => 'Sales Pembina',
            'display_sales'           => 'Sales Pembina',
            'nama_sales'              => 'Sales Pembina',
            'sales_pembina'           => 'Sales Pembina',
            'telepon'                 => 'Nomor Telepon',
            'email'                   => 'Alamat Email',
            'keterangan'              => 'Keterangan Tambahan',
        ];
        return $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
    }
}

if (!function_exists('ks_get_error_solution_hint')) {
    function ks_get_error_solution_hint(?string $errMsg): string {
        if (empty($errMsg)) {
            return 'Periksa kembali data pada baris bersangkutan di file Excel Anda.';
        }
        if (stripos($errMsg, 'masih kosong') !== false || stripos($errMsg, 'Fase 1') !== false || stripos($errMsg, 'Fase 2') !== false) {
            return 'Entitas induk belum tersedia di sistem. Ikuti urutan setup pada Panduan Teknis (Roadmap 4 Fase) dan impor master data prasyarat terlebih dahulu.';
        }
        if (stripos($errMsg, 'sales') !== false && (stripos($errMsg, 'karyawan') !== false || stripos($errMsg, 'posisi') !== false)) {
            return 'Pastikan staf yang ditugaskan sudah terdaftar di Master Karyawan dengan posisi "sales". Anda bisa menggunakan NIK atau nama lengkap karyawan.';
        }
        if (stripos($errMsg, 'tidak ditemukan di sistem') !== false || stripos($errMsg, 'tidak ditemukan di master') !== false || stripos($errMsg, 'tidak terdaftar') !== false) {
            return 'Pastikan data master relasi terkait (Grup Produk/Wilayah/Pemasok) sudah terdaftar di sistem, atau sesuaikan ejaan nama di file Excel Anda.';
        }
        if (stripos($errMsg, 'duplikasi') !== false) {
            return 'Kode atau nama tersebut sudah terpakai pada baris lain. Pastikan setiap entri memiliki kode unik.';
        }
        if (stripos($errMsg, 'kosong') !== false || stripos($errMsg, 'wajib diisi') !== false) {
            return 'Kolom ini merupakan atribut wajib. Buka file Excel dan pastikan nilai pada kolom tersebut sudah terisi.';
        }
        if (stripos($errMsg, 'harus lebih besar') !== false || stripos($errMsg, 'tidak valid') !== false) {
            return 'Format atau batasan nilai angka belum sesuai. Periksa panduan tipe data pada template.';
        }
        return 'Buka file Excel Anda, perbaiki kesalahan pada baris yang ditandai, lalu unggah kembali.';
    }
}

if (!function_exists('rm_format_cell_value')) {
    function rm_format_cell_value($val, string $type = 'text'): string {
        if ($val === null || $val === '' || $val === '—') {
            return '<span class="text-slate-400 dark:text-slate-500 italic text-xs">—</span>';
        }
        if ($type === 'boolean' || is_bool($val)) {
            $b = is_bool($val) ? $val : (in_array(strtolower((string)$val), ['1', 'true', 'aktif', 'ya'], true));
            return $b
                ? '<span class="badge badge-success text-[11px] font-bold">Aktif</span>'
                : '<span class="badge badge-danger text-[11px] font-bold">Nonaktif</span>';
        }
        if ($type === 'gender') {
            $g = strtoupper(trim((string)$val));
            return (in_array($g, ['P', 'PEREMPUAN', 'WANITA'], true))
                ? '<span class="badge text-[11px] font-bold" style="color:#db2777;background:rgba(219,39,119,0.1);border:1px solid rgba(219,39,119,0.3);">🧕 Perempuan</span>'
                : '<span class="badge text-[11px] font-bold" style="color:#2563eb;background:rgba(37,99,235,0.1);border:1px solid rgba(37,99,235,0.3);">👨‍💼 Laki-laki</span>';
        }
        if ($type === 'konsinyasi') {
            $isKonsin = is_bool($val) ? $val : (str_contains(strtolower((string)$val), 'konsin') || $val == '1');
            return $isKonsin
                ? '<span class="badge badge-primary text-[11px] font-bold">Konsinyasi</span>'
                : '<span class="badge badge-secondary text-[11px] font-bold">Reguler</span>';
        }
        if ($type === 'currency' && is_numeric($val)) {
            return '<span class="font-mono font-semibold text-slate-800 dark:text-slate-200">Rp ' . number_format((float)$val, 0, ',', '.') . '</span>';
        }
        return htmlspecialchars((string)$val);
    }
}

if (!function_exists('ks_get_row_val')) {
    function ks_get_row_val(array $arr, string $key) {
        if (array_key_exists($key, $arr)) return $arr[$key];
        // Dynamic aliases and relational field fallbacks
        if ($key === 'display_grup' && isset($arr['nama_grup'])) return $arr['nama_grup'];
        if ($key === 'display_wilayah' && isset($arr['nama_wilayah'])) return $arr['nama_wilayah'];
        if ($key === 'display_sales' && (isset($arr['display_sales']) || isset($arr['nama_sales']))) return $arr['display_sales'] ?? $arr['nama_sales'];
        if ($key === 'display_pemasok' && isset($arr['nama_pemasok'])) return $arr['nama_pemasok'];
        if ($key === 'display_kelompok_borongan' && isset($arr['nama_kelompok'])) return $arr['nama_kelompok'];
        if ($key === 'display_merek' && (isset($arr['merek']) || isset($arr['nama_merek']))) return $arr['merek'] ?? $arr['nama_merek'];
        if ($key === 'alamat' && isset($arr['alamat_lengkap'])) return $arr['alamat_lengkap'];
        if ($key === 'alamat_lengkap' && isset($arr['alamat'])) return $arr['alamat'];
        if ($key === 'telepon' && isset($arr['nomor_whatsapp'])) return $arr['nomor_whatsapp'];
        if ($key === 'nomor_whatsapp' && isset($arr['nomor_telepon'])) return $arr['nomor_telepon'];
        if ($key === 'nomor_whatsapp' && isset($arr['telepon'])) return $arr['telepon'];
        if ($key === 'harga_jual_per_pcs' && isset($arr['harga_jual_pcs'])) return $arr['harga_jual_pcs'];
        if ($key === 'harga_jual_pcs' && isset($arr['harga_jual_per_pcs'])) return $arr['harga_jual_per_pcs'];
        if ($key === 'bank_nama' && isset($arr['nama_bank'])) return $arr['nama_bank'];
        if ($key === 'bank_nomor_rekening' && isset($arr['nomor_rekening'])) return $arr['nomor_rekening'];
        if ($key === 'bank_atas_nama' && isset($arr['atas_nama_rekening'])) return $arr['atas_nama_rekening'];
        if ($key === 'nama_bank' && isset($arr['bank_nama'])) return $arr['bank_nama'];
        if ($key === 'nomor_rekening' && isset($arr['bank_nomor_rekening'])) return $arr['bank_nomor_rekening'];
        if ($key === 'atas_nama_rekening' && isset($arr['bank_atas_nama'])) return $arr['bank_atas_nama'];
        return null;
    }
}

$entityColumnsConfig = [
    'customers' => [
        'code_key'   => 'kode_pelanggan',
        'code_label' => 'Kode Pelanggan',
        'name_key'   => 'nama_toko',
        'name_label' => 'Nama Toko / Mitra',
        'columns'    => [
            ['key' => 'nama_pemilik', 'label' => 'Nama Pemilik'],
            ['key' => 'display_grup', 'label' => 'Grup Pelanggan'],
            ['key' => 'display_wilayah', 'label' => 'Wilayah / Rute'],
            ['key' => 'display_sales', 'label' => 'Sales Pembina'],
            ['key' => 'is_konsinyasi', 'label' => 'Model Toko', 'type' => 'konsinyasi'],
            ['key' => 'plafon_piutang', 'label' => 'Plafon Piutang', 'type' => 'currency'],
            ['key' => 'tipe_pembayaran_default', 'label' => 'Tipe Bayar'],
            ['key' => 'nomor_whatsapp', 'label' => 'WhatsApp'],
            ['key' => 'status_aktif', 'label' => 'Status', 'type' => 'boolean'],
        ]
    ],
    'products' => [
        'code_key'   => 'kode_sku',
        'code_label' => 'Kode SKU',
        'name_key'   => 'nama_item',
        'name_label' => 'Nama Produk',
        'columns'    => [
            ['key' => 'display_grup', 'label' => 'Grup Produk'],
            ['key' => 'satuan_dasar', 'label' => 'Satuan'],
            ['key' => 'display_kelompok_borongan', 'label' => 'Kelompok Upah'],
            ['key' => 'display_pemasok', 'label' => 'Pemasok Utama'],
            ['key' => 'harga_pokok_pembelian', 'label' => 'HPP Pokok', 'type' => 'currency'],
            ['key' => 'stok_minimum_peringatan', 'label' => 'Stok Min'],
            ['key' => 'status_jual', 'label' => 'Status Jual', 'type' => 'boolean'],
            ['key' => 'status_aktif', 'label' => 'Status Master', 'type' => 'boolean'],
        ]
    ],
    'materials' => [
        'code_key'   => 'kode_sku',
        'code_label' => 'Kode SKU',
        'name_key'   => 'nama_item',
        'name_label' => 'Nama Bahan / Kemasan',
        'columns'    => [
            ['key' => 'tipe_item', 'label' => 'Tipe Bahan'],
            ['key' => 'satuan_dasar', 'label' => 'Satuan'],
            ['key' => 'harga_pokok_pembelian', 'label' => 'HPP Beli', 'type' => 'currency'],
            ['key' => 'display_pemasok', 'label' => 'Pemasok Utama'],
            ['key' => 'stok_minimum_peringatan', 'label' => 'Stok Min'],
            ['key' => 'status_aktif', 'label' => 'Status', 'type' => 'boolean'],
        ]
    ],
    'suppliers' => [
        'code_key'   => 'kode_pemasok',
        'code_label' => 'Kode Pemasok',
        'name_key'   => 'nama_pemasok',
        'name_label' => 'Nama Pemasok (Vendor)',
        'columns'    => [
            ['key' => 'nama_kontak', 'label' => 'Kontak PIC'],
            ['key' => 'display_wilayah', 'label' => 'Wilayah / Kota'],
            ['key' => 'alamat_lengkap', 'label' => 'Alamat Lengkap'],
            ['key' => 'nomor_whatsapp', 'label' => 'No. WhatsApp'],
            ['key' => 'termin_bayar', 'label' => 'Termin Bayar'],
            ['key' => 'nama_bank', 'label' => 'Bank'],
            ['key' => 'nomor_rekening', 'label' => 'No. Rekening'],
            ['key' => 'atas_nama_rekening', 'label' => 'Atas Nama Rekening'],
            ['key' => 'status_aktif', 'label' => 'Status', 'type' => 'boolean'],
        ]
    ],
    'employees' => [
        'code_key'   => 'nik',
        'code_label' => 'NIK',
        'name_key'   => 'nama_lengkap',
        'name_label' => 'Nama Karyawan',
        'columns'    => [
            ['key' => 'nama_panggilan', 'label' => 'Panggilan'],
            ['key' => 'jenis_kelamin', 'label' => 'Gender', 'type' => 'gender'],
            ['key' => 'tanggal_lahir', 'label' => 'Tgl Lahir'],
            ['key' => 'posisi', 'label' => 'Posisi / Tugas'],
            ['key' => 'tipe_penggajian', 'label' => 'Sistem Gaji'],
            ['key' => 'gaji_pokok_bulanan', 'label' => 'Gaji Pokok', 'type' => 'currency'],
            ['key' => 'uang_kehadiran_harian', 'label' => 'Uang Hadir', 'type' => 'currency'],
            ['key' => 'tunjangan_bulanan', 'label' => 'Tunjangan', 'type' => 'currency'],
            ['key' => 'nomor_whatsapp', 'label' => 'WhatsApp'],
            ['key' => 'tanggal_bergabung', 'label' => 'Tgl Gabung'],
            ['key' => 'bank_nama', 'label' => 'Bank'],
            ['key' => 'bank_nomor_rekening', 'label' => 'No. Rekening'],
            ['key' => 'status_aktif', 'label' => 'Status', 'type' => 'boolean'],
        ]
    ],
    'product_groups' => [
        'code_key'   => 'kode_grup',
        'code_label' => 'Kode Grup',
        'name_key'   => 'nama_grup',
        'name_label' => 'Nama Grup Produk',
        'columns'    => [
            ['key' => 'display_merek', 'label' => 'Merek'],
            ['key' => 'barcode_universal', 'label' => 'Barcode Universal'],
            ['key' => 'satuan_dasar', 'label' => 'Satuan Dasar'],
            ['key' => 'status_aktif', 'label' => 'Status', 'type' => 'boolean'],
        ]
    ],
    'customer_groups' => [
        'code_key'   => 'kode_grup',
        'code_label' => 'Kode Grup',
        'name_key'   => 'nama_grup',
        'name_label' => 'Nama Grup Pelanggan',
        'columns'    => [
            ['key' => 'default_level_harga', 'label' => 'Default Level'],
            ['key' => 'diskon_persen_default', 'label' => 'Diskon Default (%)'],
            ['key' => 'diskon_nominal_default', 'label' => 'Diskon Nominal', 'type' => 'currency'],
            ['key' => 'status_aktif', 'label' => 'Status', 'type' => 'boolean'],
        ]
    ],
    'territories' => [
        'code_key'   => 'kode_rute',
        'code_label' => 'Kode Rute',
        'name_key'   => 'nama_wilayah',
        'name_label' => 'Nama Wilayah / Rute',
        'columns'    => [
            ['key' => 'provinsi', 'label' => 'Provinsi'],
            ['key' => 'kota_kabupaten', 'label' => 'Kota / Kab'],
            ['key' => 'sub_wilayah', 'label' => 'Cakupan Area'],
            ['key' => 'status_aktif', 'label' => 'Status', 'type' => 'boolean'],
        ]
    ],
    'pricing_matrix' => [
        'code_key'   => 'level_harga',
        'code_label' => 'Level (1-30)',
        'name_key'   => 'nama_level',
        'name_label' => 'Nama Level Harga',
        'columns'    => [
            ['key' => 'display_grup', 'label' => 'Grup Produk'],
            ['key' => 'harga_jual_pcs', 'label' => 'Harga Jual / Pcs', 'type' => 'currency'],
        ]
    ],
    'piece_rates' => [
        'code_key'   => 'nama_kelompok',
        'code_label' => 'Nama Kelompok',
        'name_key'   => 'keterangan',
        'name_label' => 'Keterangan / Deskripsi',
        'columns'    => [
            ['key' => 'upah_per_bungkus', 'label' => 'Tarif Upah / Pcs', 'type' => 'currency'],
            ['key' => 'status_aktif', 'label' => 'Status', 'type' => 'boolean'],
        ]
    ],
    'brands' => [
        'code_key'   => 'kode_merek',
        'code_label' => 'Kode Merek',
        'name_key'   => 'nama_merek',
        'name_label' => 'Nama Merek Dagang',
        'columns'    => [
            ['key' => 'status_aktif', 'label' => 'Status', 'type' => 'boolean'],
        ]
    ]
];

// Metadata representasi visual 11 master data
$entityMeta = [
    'brands' => [
        'icon'      => 'tag',
        'badge'     => 'Merek Produk',
        'category'  => 'Produk & BOM',
        'color'     => '#d97706',
        'bg'        => 'rgba(217, 119, 6, 0.12)',
        'desc'      => 'Master merek dagang produk (Brand) yang menaungi grup kemasan.',
        'url'       => '/products'
    ],
    'territories' => [
        'icon'      => 'map-pin',
        'badge'     => 'Wilayah & Rute',
        'category'  => 'Logistik & Distribusi',
        'color'     => '#06b6d4',
        'bg'        => 'rgba(6, 182, 212, 0.12)',
        'desc'      => 'Zona operasional pengiriman sales driver dan rute distribusi toko.',
        'url'       => '/customers?tab=territories'
    ],
    'product_groups' => [
        'icon'      => 'package',
        'badge'     => 'Grup Kemasan',
        'category'  => 'Produk & BOM',
        'color'     => '#ec4899',
        'bg'        => 'rgba(236, 72, 153, 0.12)',
        'desc'      => 'Kategori kemasan universal, barcode universal grup, dan satuan dasar produk.',
        'url'       => '/products'
    ],
    'piece_rates' => [
        'icon'      => 'coins',
        'badge'     => 'Upah Borongan',
        'category'  => 'Produksi & Payroll',
        'color'     => '#0d9488',
        'bg'        => 'rgba(139, 92, 246, 0.12)',
        'desc'      => 'Kelompok tarif upah borongan kemas repacking per bungkus/pcs.',
        'url'       => '/products'
    ],
    'employees' => [
        'icon'      => 'user-check',
        'badge'     => 'Data Karyawan',
        'category'  => 'SDM & Keamanan',
        'color'     => '#8b5cf6',
        'bg'        => 'rgba(139, 92, 246, 0.12)',
        'desc'      => 'Master data profil karyawan, posisi/tugas operasional, dan skema penggajian.',
        'url'       => '/employees'
    ],
    'suppliers' => [
        'icon'      => 'truck',
        'badge'     => 'Pemasok (Vendor)',
        'category'  => 'Pengadaan & Stok',
        'color'     => '#f59e0b',
        'bg'        => 'rgba(245, 158, 11, 0.12)',
        'desc'      => 'Vendor bahan mentah curah, kemasan plastik, karton box, dan rekening bank.',
        'url'       => '/suppliers'
    ],
    'pricing_matrix' => [
        'icon'      => 'table-properties',
        'badge'     => 'Matriks 30 Level',
        'category'  => 'Penjualan & Pricing',
        'color'     => '#2563eb',
        'bg'        => 'rgba(37, 99, 235, 0.12)',
        'desc'      => 'Konfigurasi harga jual bertingkat 30 level harga per grup kemasan produk.',
        'url'       => '/pricing-matrix'
    ],
    'customer_groups' => [
        'icon'      => 'users',
        'badge'     => 'Grup Pelanggan',
        'category'  => 'Mitra & Penjualan',
        'color'     => '#3b82f6',
        'bg'        => 'rgba(59, 130, 246, 0.12)',
        'desc'      => 'Segmentasi tier pelanggan (Grosir, Ritel, Semi-Grosir) dan default level harga.',
        'url'       => '/customers?tab=customer_groups'
    ],
    'materials' => [
        'icon'      => 'layers',
        'badge'     => 'Bahan Baku & Kemas',
        'category'  => 'Produk & BOM',
        'color'     => '#ea580c',
        'bg'        => 'rgba(234, 88, 12, 0.12)',
        'desc'      => 'Bahan mentah curah repacking, kemasan plastik, bumbu, dan karton vendor.',
        'url'       => '/products'
    ],
    'products' => [
        'icon'      => 'boxes',
        'badge'     => 'Barang Jadi',
        'category'  => 'Produk & BOM',
        'color'     => '#059669',
        'bg'        => 'rgba(5, 150, 105, 0.12)',
        'desc'      => 'Katalog snack siap jual, SKU barcode, HPP pokok, dan tarif upah borongan.',
        'url'       => '/products'
    ],
    'customers' => [
        'icon'      => 'store',
        'badge'     => 'Pelanggan',
        'category'  => 'Mitra & Penjualan',
        'color'     => '#10b981',
        'bg'        => 'rgba(16, 185, 129, 0.12)',
        'desc'      => 'Katalog toko mitra konsinyasi & ritel reguler, plafon piutang, dan rute pembina.',
        'url'       => '/customers'
    ],
];
