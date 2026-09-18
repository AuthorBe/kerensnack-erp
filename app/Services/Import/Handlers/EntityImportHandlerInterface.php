<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use PDO;

/**
 * EntityImportHandlerInterface
 * Kontrak terpadu untuk setiap handler impor master data di Keren Snack ERP.
 */
interface EntityImportHandlerInterface
{
    /**
     * Kunci identifikasi tipe data (misal: 'customers', 'products', 'suppliers', dll.)
     */
    public function getEntityKey(): string;

    /**
     * Nama label ramah pengguna (misal: 'Toko Pelanggan')
     */
    public function getEntityLabel(): string;

    /**
     * Izin RBAC granular yang dibutuhkan (misal: 'master.customers_manage')
     */
    public function getRequiredPermission(): string;

    /**
     * Grup kolom kunci untuk auto-detect baris header pada file Excel
     */
    public function getRequiredHeaderGroups(): array;

    /**
     * Daftar judul header kolom untuk template Excel
     */
    public function getTemplateHeaders(): array;

    /**
     * Lebar masing-masing kolom template Excel
     */
    public function getTemplateWidths(): array;

    /**
     * Baris contoh dummy untuk template kosong
     */
    public function getTemplateExamples(): array;

    /**
     * Petunjuk pengisian untuk banner template Excel
     */
    public function getTemplateNotes(): array;

    /**
     * Ekspor seluruh data master terkini dari database dalam format template
     */
    public function getCurrentDataRows(PDO $pdo): array;

    /**
     * Memproses baris Excel menjadi array pratinjau diff (INSERT/UPDATE/FATAL/ERROR/DELETE)
     */
    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array;

    /**
     * Menerapkan pratinjau yang telah dikonfirmasi ke dalam database PostgreSQL
     * (Dipanggil di dalam transaksi aktif PDO)
     */
    public function applySync(array $previewList, PDO $pdo): array;
}
