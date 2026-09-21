<?php
declare(strict_types=1);

namespace App\Services\Import;

use App\Services\Import\Handlers\EntityImportHandlerInterface;
use App\Services\Import\Handlers\CustomerImportHandler;
use App\Services\Import\Handlers\CustomerGroupImportHandler;
use App\Services\Import\Handlers\TerritoryImportHandler;
use App\Services\Import\Handlers\SupplierImportHandler;
use App\Services\Import\Handlers\EmployeeImportHandler;
use App\Services\Import\Handlers\ProductGroupImportHandler;
use App\Services\Import\Handlers\ProductItemImportHandler;
use App\Services\Import\Handlers\MaterialItemImportHandler;
use App\Services\Import\Handlers\PricingMatrixImportHandler;
use App\Services\Import\Handlers\PieceRateImportHandler;
use App\Services\Import\Handlers\BrandImportHandler;
use App\Helpers\ActivityLog;
use PDO;
use Exception;
use RuntimeException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportProcessor
{
    /**
     * Daftar seluruh handler master data terdaftar
     * 
     * @return array<string, EntityImportHandlerInterface>
     */
    public static function getHandlers(): array
    {
        return [
            // Fase 1: Master Pondasi Independen (Zero Dependency)
            'brands'          => new BrandImportHandler(),
            'territories'     => new TerritoryImportHandler(),
            'customer_groups' => new CustomerGroupImportHandler(),
            'product_groups'  => new ProductGroupImportHandler(),
            'piece_rates'     => new PieceRateImportHandler(),

            // Fase 2: Sumber Daya, Vendor & Matriks Harga
            'employees'       => new EmployeeImportHandler(),
            'suppliers'       => new SupplierImportHandler(),
            'pricing_matrix'  => new PricingMatrixImportHandler(),

            // Fase 3: Katalog Inventori & Produksi
            'materials'       => new MaterialItemImportHandler(),
            'products'        => new ProductItemImportHandler(),

            // Fase 4: Jaringan Mitra & Toko
            'customers'       => new CustomerImportHandler(),
        ];
    }

    /**
     * Dapatkan handler berdasarkan kunci tipe data
     */
    public static function getHandler(string $type): ?EntityImportHandlerInterface
    {
        $handlers = self::getHandlers();
        return $handlers[$type] ?? null;
    }

    /**
     * Memproses file upload untuk menghasilkan pratinjau perbandingan (Diff)
     */
    public static function processPreview(string $tempFile, string $type, string $mode, PDO $pdo): array
    {
        $handler = self::getHandler($type);
        if (!$handler) {
            throw new RuntimeException("Tipe master data '{$type}' tidak valid atau belum didukung.");
        }

        $spreadsheet = IOFactory::load($tempFile);
        $candidateSheets = $spreadsheet->getAllSheets();

        // Multi-Layer Smart Sheet Resolver:
        // 1. Cari sheet data utama yang memiliki baris header sah (abaikan sheet bertitel Kamus/Referensi)
        $targetRows = null;
        $extractedHeader = null;

        foreach ($candidateSheets as $sheet) {
            $sheetTitle = strtolower(trim($sheet->getTitle()));
            if (str_contains($sheetTitle, 'kamus') || str_contains($sheetTitle, 'referensi') || str_contains($sheetTitle, 'panduan') || str_contains($sheetTitle, 'petunjuk')) {
                continue;
            }
            $rows = $sheet->toArray();
            if (count($rows) <= 1) {
                continue;
            }
            $extracted = SmartReader::extractSmartHeader($rows, $handler->getRequiredHeaderGroups());
            if ($extracted['index'] !== -1) {
                $targetRows = $rows;
                $extractedHeader = $extracted;
                break;
            }
        }

        // 2. Jika belum ditemukan (misal user mengubah nama sheet), periksa seluruh sheet
        if ($targetRows === null) {
            foreach ($candidateSheets as $sheet) {
                $rows = $sheet->toArray();
                if (count($rows) <= 1) {
                    continue;
                }
                $extracted = SmartReader::extractSmartHeader($rows, $handler->getRequiredHeaderGroups());
                if ($extracted['index'] !== -1) {
                    $targetRows = $rows;
                    $extractedHeader = $extracted;
                    break;
                }
            }
        }

        // 3. Fallback terakhir: gunakan Sheet Index 0 atau active sheet
        if ($targetRows === null) {
            $fallbackSheet = $spreadsheet->getSheet(0) ?? $spreadsheet->getActiveSheet();
            $targetRows = $fallbackSheet->toArray();
            $extractedHeader = SmartReader::extractSmartHeader($targetRows, $handler->getRequiredHeaderGroups());
        }

        if (count($targetRows) <= 1) {
            throw new RuntimeException("File kosong atau hanya berisi judul tanpa baris data.");
        }

        $header = $extractedHeader['header'];
        $headerIndex = $extractedHeader['index'];

        if ($headerIndex === -1) {
            throw new RuntimeException("Format kolom tidak dikenali. Kolom wajib untuk master " . $handler->getEntityLabel() . " tidak ditemukan pada berkas Excel yang diunggah.");
        }

        $rowsRaw = array_values(array_slice($targetRows, $headerIndex + 1));
        $rows = SmartReader::filterSmartDataRows($rowsRaw);

        if (empty($rows)) {
            throw new RuntimeException("Tidak ditemukan baris data yang valid di bawah baris header.");
        }

        // Jalankan pratinjau diff pada handler
        $previewList = $handler->previewRows($rows, $header, $pdo, $mode);

        // Simpan ke file temporary JSON untuk menghindari pembengkakan sesi PHP
        $previewJsonFile = sys_get_temp_dir() . '/ks_sync_preview_' . uniqid() . '.json';
        file_put_contents($previewJsonFile, json_encode($previewList, JSON_UNESCAPED_UNICODE));

        return [
            'preview_list'      => $previewList,
            'preview_json_file' => $previewJsonFile,
            'handler'           => $handler
        ];
    }

    /**
     * Menerapkan hasil pratinjau yang disetujui ke dalam database dalam satu transaksi utuh
     */
    public static function applySyncFromPreview(
        string $previewJsonFile, 
        string $type, 
        PDO $pdo,
        ?string $filename = null,
        ?string $mode = null
    ): array
    {
        $handler = self::getHandler($type);
        if (!$handler) {
            throw new RuntimeException("Tipe data tidak valid.");
        }

        if (!file_exists($previewJsonFile)) {
            throw new RuntimeException("Data pratinjau kedaluwarsa atau file temporary telah dibersihkan.");
        }

        $previewList = json_decode((string)file_get_contents($previewJsonFile), true);
        if (!is_array($previewList)) {
            throw new RuntimeException("Format data pratinjau korup.");
        }

        // Cek apakah masih ada baris ERROR atau FATAL konflik
        $hasBlocker = false;
        $blockerCount = 0;
        foreach ($previewList as $r) {
            $act = $r['action'] ?? '';
            $isFatal = !empty($r['is_fatal']);
            if ($act === 'ERROR' || $act === 'FATAL' || $isFatal) {
                $hasBlocker = true;
                $blockerCount++;
            }
        }

        if ($hasBlocker) {
            throw new RuntimeException("Terdapat {$blockerCount} baris data bermasalah (ERROR / FATAL). Tombol konfirmasi dikunci demi keamanan data. Harap perbaiki berkas Excel Anda terlebih dahulu sebelum menerapkan sinkronisasi.");
        }

        $pdo->beginTransaction();

        try {
            $stats = $handler->applySync($previewList, $pdo);

            // Pemetaan nama tabel riil database PostgreSQL
            $tableMap = [
                'brands'          => 'merek',
                'customers'       => 'pelanggan',
                'customer_groups' => 'grup_pelanggan',
                'territories'     => 'wilayah',
                'suppliers'       => 'pemasok',
                'employees'       => 'pengguna',
                'product_groups'  => 'grup_produk',
                'products'        => 'item',
                'materials'       => 'item',
                'pricing_matrix'  => 'grup_produk_harga_level',
                'piece_rates'     => 'kelompok_upah_borongan',
            ];
            $targetTable = $tableMap[$type] ?? $handler->getEntityKey();

            $modeLabel = ($mode === 'full_sync') ? 'Sinkronisasi Penuh (+ Hapus)' : 'Mode Aman (Upsert)';
            $fileInfo  = !empty($filename) ? " via berkas '{$filename}'" : '';

            // Audit Trail Log Rinci
            $logMsg = "Sinkronisasi massal {$handler->getEntityLabel()}{$fileInfo} [{$modeLabel}]: {$stats['insert']} INSERT, {$stats['update']} UPDATE, {$stats['delete']} DELETE, {$stats['deactivate']} DINONAKTIFKAN.";
            ActivityLog::log('master_data', 'SYNC', $logMsg, $targetTable);

            $pdo->commit();

            return [
                'stats'   => $stats,
                'handler' => $handler,
                'message' => $logMsg
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException("Gagal menerapkan sinkronisasi: " . $e->getMessage(), (int)$e->getCode(), $e);
        } finally {
            // Bersihkan file JSON preview
            if (file_exists($previewJsonFile)) {
                @unlink($previewJsonFile);
            }
        }
    }
}
