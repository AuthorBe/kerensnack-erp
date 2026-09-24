<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\PdfExport;
use App\Helpers\PrintDocumentHelper;
use App\Helpers\ExcelExport;
use Database;
use Throwable;

/**
 * app/Controllers/OrderDocumentController.php
 * Pengendali Khusus Render Dokumen Cetak & Export Pesanan Pelanggan:
 * PDF Picking List, PDF Invoice, Export Excel Faktur, Export Excel Laporan Pesanan.
 * Didecoupling dari CustomerOrderController untuk merampingkan Controller Transaksi Inti (TASK-015 / Fase 4).
 */
class OrderDocumentController extends Controller
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Cetak Lembar Ambil Barang (Picking / Packing List) untuk Staf Gudang (HTML View)
     */
    public function printPickingList(): void
    {
        Auth::requirePermission(['orders.po_print', 'orders.po_view_all', 'orders.po_view_assigned']);

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID Pesanan tidak valid.');
            $this->redirect('/customer-orders/po-list');
            return;
        }

        try {
            $order = Database::fetchOne("
                SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                       pel.sales_driver_id as pelanggan_sales_id,
                       COALESCE(k_sj.nama_karyawan, k_p.nama_karyawan) as nama_driver,
                       COALESCE(k_sj.nomor_polisi_kendaraan, k_p.nomor_polisi_kendaraan) as nopol_driver,
                       w.nama_wilayah, w.kode_rute,
                       sj.nomor_surat_jalan
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.surat_jalan sj ON sj.pesanan_id = p.id
                LEFT JOIN public.v_karyawan_info k_p ON p.sales_driver_id = k_p.id
                LEFT JOIN public.v_karyawan_info k_sj ON sj.sales_driver_id = k_sj.id
                LEFT JOIN public.wilayah w ON COALESCE(sj.rute_wilayah_id, pel.wilayah_id) = w.id
                WHERE p.id = :id
            ", ['id' => $id]);

            if (!$order) {
                $this->flashError('Pesanan tidak ditemukan.');
                $this->redirect('/customer-orders/po-list');
                return;
            }

            if (!Auth::can('orders.po_view_all')) {
                $myEmpId = Auth::employeeId();
                if ($order['sales_driver_id'] !== $myEmpId && ($order['pelanggan_sales_id'] ?? null) !== $myEmpId) {
                    $this->flashError('Akses Ditolak: Anda hanya dapat mencetak picking list untuk pesanan/toko binaan Anda.');
                    $this->redirect('/customer-orders/po-list');
                    return;
                }
            }

            $items = Database::fetchAll("
                SELECT ip.*, it.nama_item, it.kode_sku, it.stok_fisik_saat_ini, it.satuan_dasar
                FROM public.item_pesanan ip
                JOIN public.item it ON ip.item_id = it.id
                WHERE ip.pesanan_id = :id
                ORDER BY it.nama_item ASC
            ", ['id' => $id]);

            $this->view('customer_orders.picking_list', [
                'pageTitle' => 'Picking List #' . $order['nomor_nota'],
                'order' => $order,
                'items' => $items
            ]);

        } catch (Throwable $e) {
            $this->flashError("Gagal memuat Lembar Ambil Barang: " . $e->getMessage());
            $this->redirect('/customer-orders/po-list');
        }
    }

    /**
     * Unduh Faktur Pesanan dalam Format PDF (Dompdf Library)
     */
    public function invoicePdf(): void
    {
        Auth::requirePermission(['orders.view_all', 'orders.view_assigned', 'orders.print_invoice']);
        $id = $this->input('id');
        $format = $this->input('format', 'standard');
        if (empty($id)) {
            $this->flashError('ID Pesanan tidak valid.');
            $this->redirect('/customer-orders');
            return;
        }

        try {
            $order = Database::fetchOne("
                SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                       pel.sales_driver_id as pelanggan_sales_id,
                       k.nama_karyawan as nama_sales,
                       ak.nama_akun as nama_akun_kas
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                LEFT JOIN public.akun_kas ak ON p.akun_kas_id = ak.id
                WHERE p.id = :id
            ", ['id' => $id]);

            if (!$order) {
                $this->flashError('Faktur pesanan tidak ditemukan.');
                $this->redirect('/customer-orders');
                return;
            }

            if (!Auth::can('orders.view_all')) {
                $myEmpId = Auth::employeeId();
                if ($order['sales_driver_id'] !== $myEmpId && ($order['pelanggan_sales_id'] ?? null) !== $myEmpId) {
                    $this->flashError('Akses Ditolak: Anda hanya dapat mengunduh faktur untuk toko binaan Anda.');
                    $this->redirect('/customer-orders');
                    return;
                }
            }

            $items = Database::fetchAll("
                SELECT ip.*, i.nama_item, i.kode_sku, i.satuan_dasar, gp.nama_grup
                FROM public.item_pesanan ip
                JOIN public.item i ON ip.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE ip.pesanan_id = :id
                ORDER BY ip.dibuat_pada ASC
            ", ['id' => $id]);

            ob_start();
            extract(['order' => $order, 'items' => $items, 'isPdf' => true, 'formatMode' => $format]);
            require ROOT_PATH . '/views/customer_orders/invoice.php';
            $html = ob_get_clean();

            $cleanNota = preg_replace('/[^A-Za-z0-9]/', ' ', (string)$order['nomor_nota']);
            $cleanNota = trim(preg_replace('/\s+/', ' ', $cleanNota));
            PrintDocumentHelper::downloadPdf($html, "Faktur {$cleanNota}", $format);
        } catch (Throwable $e) {
            $this->flashError('Gagal membuat PDF: ' . $e->getMessage());
            $this->redirect('/customer-orders/invoice?id=' . urlencode((string)$id));
        }
    }

    /**
     * Unduh Faktur Rincian Item dalam Format Excel (PhpSpreadsheet Library)
     */
    public function invoiceExcel(): void
    {
        Auth::requirePermission(['orders.view_all', 'orders.view_assigned', 'orders.print_invoice']);
        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID Pesanan tidak valid.');
            $this->redirect('/customer-orders');
            return;
        }

        try {
            $order = Database::fetchOne("
                SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                       pel.sales_driver_id as pelanggan_sales_id,
                       k.nama_karyawan as nama_sales
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.id = :id
            ", ['id' => $id]);

            if (!$order) {
                $this->flashError('Faktur pesanan tidak ditemukan.');
                $this->redirect('/customer-orders');
                return;
            }

            if (!Auth::can('orders.view_all')) {
                $myEmpId = Auth::employeeId();
                if ($order['sales_driver_id'] !== $myEmpId && ($order['pelanggan_sales_id'] ?? null) !== $myEmpId) {
                    $this->flashError('Akses Ditolak: Anda hanya dapat mengunduh faktur untuk toko binaan Anda.');
                    $this->redirect('/customer-orders');
                    return;
                }
            }

            $items = Database::fetchAll("
                SELECT ip.*, i.nama_item, i.kode_sku, i.satuan_dasar, gp.nama_grup
                FROM public.item_pesanan ip
                JOIN public.item i ON ip.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE ip.pesanan_id = :id
                ORDER BY ip.dibuat_pada ASC
            ", ['id' => $id]);

            // Filter out bonus items for customer-facing invoice Excel
            $items = array_values(array_filter($items, fn($it) => empty($it['is_bonus'])));

            $headers = ['No', 'Kode SKU', 'Nama Produk Snack', 'Kategori Kemasan', 'Harga Satuan (Rp)', 'Qty (Pcs)', 'Diskon (Rp)', 'Subtotal (Rp)'];
            $rows = [];
            $no = 1;
            foreach ($items as $it) {
                $rows[] = [
                    $no++,
                    $it['kode_sku'] ?? '-',
                    $it['nama_item'] ?? '-',
                    $it['nama_grup'] ?? '-',
                    (float)($it['harga_satuan'] ?? 0),
                    (int)($it['kuantitas_satuan_dasar'] ?? 0),
                    (float)($it['diskon_nominal'] ?? 0),
                    (float)($it['subtotal'] ?? 0)
                ];
            }

            // Tambahkan baris total
            $rows[] = ['', '', '', '', '', '', 'Total Bruto (Rp):', (float)($order['total_bruto'] ?? 0)];
            $rows[] = ['', '', '', '', '', '', 'Total Diskon (Rp):', (float)($order['total_diskon'] ?? 0)];
            $rows[] = ['', '', '', '', '', '', 'TOTAL NETTO (Rp):', (float)($order['total_netto'] ?? 0)];
            $rows[] = ['', '', '', '', '', '', 'Telah Dibayar (Rp):', (float)($order['total_dibayar'] ?? 0)];
            $rows[] = ['', '', '', '', '', '', 'Sisa Tagihan (Rp):', (float)($order['sisa_tagihan'] ?? 0)];

            $cleanNota = preg_replace('/[^A-Za-z0-9]/', ' ', (string)$order['nomor_nota']);
            $cleanNota = trim(preg_replace('/\s+/', ' ', $cleanNota));
            ExcelExport::download("Faktur {$cleanNota}.xlsx", $headers, $rows, "Faktur {$cleanNota}");
        } catch (Throwable $e) {
            $this->flashError('Gagal export Excel: ' . $e->getMessage());
            $this->redirect('/customer-orders/invoice?id=' . urlencode((string)$id));
        }
    }

    /**
     * Export Seluruh Daftar Pesanan ke File Excel (PhpSpreadsheet)
     */
    public function exportExcel(): void
    {
        Auth::requirePermission(['orders.view_all', 'orders.view_assigned', 'orders.print_invoice']);

        try {
            $startDate = $this->input('start_date', date('Y-m-01'));
            $endDate = $this->input('end_date', date('Y-m-d'));
            $pelangganId = $this->input('pelanggan_id');
            $statusBayar = $this->input('status_pembayaran');
            $tipeTransaksi = $this->input('tipe_transaksi');
            $q = trim((string)$this->input('q', ''));

            $sql = "
                SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.is_konsinyasi,
                       k.nama_karyawan as nama_sales
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.tanggal_pesanan >= :start AND p.tanggal_pesanan <= :end
            ";
            $params = ['start' => $startDate, 'end' => $endDate];

            // Scope Check: Jika hanya punya hak lihat pesanan toko binaan (orders.view_assigned)
            if (!Auth::can('orders.view_all')) {
                $myEmpId = Auth::employeeId();
                if ($myEmpId) {
                    $sql .= " AND (p.sales_driver_id = :my_emp_id OR pel.sales_driver_id = :my_emp_id)";
                    $params['my_emp_id'] = $myEmpId;
                } else {
                    $sql .= " AND 1=0";
                }
            }

            if (!empty($pelangganId)) {
                $sql .= " AND p.pelanggan_id = :pelanggan_id";
                $params['pelanggan_id'] = $pelangganId;
            }
            if (!empty($statusBayar) && $statusBayar !== 'semua') {
                $sql .= " AND p.status_pembayaran = :status_bayar";
                $params['status_bayar'] = $statusBayar;
            }
            if (!empty($tipeTransaksi) && $tipeTransaksi !== 'semua') {
                if ($tipeTransaksi === 'beli_putus') {
                    $sql .= " AND p.catatan ILIKE '%Beli putus%'";
                } elseif ($tipeTransaksi === 'reguler') {
                    $sql .= " AND (p.catatan NOT ILIKE '%Beli putus%' OR p.catatan IS NULL) AND p.tipe_pembayaran != 'konsinyasi' AND (p.is_tagihan = TRUE OR p.is_tagihan IS NULL)";
                } elseif ($tipeTransaksi === 'konsinyasi') {
                    $sql .= " AND (p.tipe_pembayaran = 'konsinyasi' OR p.is_tagihan = FALSE)";
                }
            }
            if (!empty($q)) {
                $sql .= " AND (p.nomor_nota ILIKE :q OR pel.nama_toko ILIKE :q OR pel.kode_pelanggan ILIKE :q OR p.catatan ILIKE :q)";
                $params['q'] = "%{$q}%";
            }

            $sql .= " ORDER BY p.tanggal_pesanan DESC, p.dibuat_pada DESC";
            $orders = Database::fetchAll($sql, $params);

            $headers = ['No', 'Nomor Nota', 'Tanggal', 'Kode Toko', 'Nama Toko Pelanggan', 'Sales / PIC', 'Tipe Pembayaran', 'Total Bruto (Rp)', 'Total Diskon (Rp)', 'Total Netto (Rp)', 'Dibayar (Rp)', 'Sisa Tagihan (Rp)', 'Status Bayar', 'Status Proses'];
            $rows = [];
            $no = 1;
            foreach ($orders as $o) {
                $rows[] = [
                    $no++,
                    $o['nomor_nota'],
                    date('d/m/Y', strtotime($o['tanggal_pesanan'])),
                    $o['kode_pelanggan'] ?? '-',
                    $o['nama_toko'],
                    $o['nama_sales'] ?? 'Armada / Toko',
                    ucfirst(str_replace('_', ' ', (string)$o['tipe_pembayaran'])),
                    (float)$o['total_bruto'],
                    (float)$o['total_diskon'],
                    (float)$o['total_netto'],
                    (float)$o['total_dibayar'],
                    (float)$o['sisa_tagihan'],
                    strtoupper(str_replace('_', ' ', (string)$o['status_pembayaran'])),
                    strtoupper(str_replace('_', ' ', (string)$o['status_pemrosesan']))
                ];
            }

            $cleanStart = str_replace('-', ' ', $startDate);
            $cleanEnd = str_replace('-', ' ', $endDate);
            ExcelExport::download("Daftar Pesanan {$cleanStart} sd {$cleanEnd}.xlsx", $headers, $rows, "Daftar Pesanan");
        } catch (Throwable $e) {
            $this->flashError('Gagal export data pesanan: ' . $e->getMessage());
            $this->redirect('/customer-orders');
        }
    }

    /**
     * Unduh Lembar Ambil Barang (Picking List) dalam Format PDF (Dompdf Library)
     */
    public function pickingListPdf(): void
    {
        Auth::requirePermission(['orders.po_print', 'orders.po_view_all', 'orders.po_view_assigned']);

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID Pesanan tidak valid.');
            $this->redirect('/customer-orders/po-list');
            return;
        }

        try {
            $order = Database::fetchOne("
                SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                       pel.sales_driver_id as pelanggan_sales_id,
                       COALESCE(k_sj.nama_karyawan, k_p.nama_karyawan) as nama_driver,
                       COALESCE(k_sj.nomor_polisi_kendaraan, k_p.nomor_polisi_kendaraan) as nopol_driver,
                       w.nama_wilayah, w.kode_rute,
                       sj.nomor_surat_jalan
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.surat_jalan sj ON sj.pesanan_id = p.id
                LEFT JOIN public.v_karyawan_info k_p ON p.sales_driver_id = k_p.id
                LEFT JOIN public.v_karyawan_info k_sj ON sj.sales_driver_id = k_sj.id
                LEFT JOIN public.wilayah w ON COALESCE(sj.rute_wilayah_id, pel.wilayah_id) = w.id
                WHERE p.id = :id
            ", ['id' => $id]);

            if (!$order) {
                $this->flashError('Pesanan tidak ditemukan.');
                $this->redirect('/customer-orders/po-list');
                return;
            }

            if (!Auth::can('orders.po_view_all')) {
                $myEmpId = Auth::employeeId();
                if ($order['sales_driver_id'] !== $myEmpId && ($order['pelanggan_sales_id'] ?? null) !== $myEmpId) {
                    $this->flashError('Akses Ditolak: Anda hanya dapat mengunduh picking list untuk pesanan/toko binaan Anda.');
                    $this->redirect('/customer-orders/po-list');
                    return;
                }
            }

            $items = Database::fetchAll("
                SELECT ip.*, it.nama_item, it.kode_sku, it.stok_fisik_saat_ini, it.satuan_dasar
                FROM public.item_pesanan ip
                JOIN public.item it ON ip.item_id = it.id
                WHERE ip.pesanan_id = :id
                ORDER BY it.nama_item ASC
            ", ['id' => $id]);

            ob_start();
            extract(['pageTitle' => 'Picking List #' . $order['nomor_nota'], 'order' => $order, 'items' => $items, 'isPdf' => true]);
            require ROOT_PATH . '/views/customer_orders/picking_list.php';
            $html = ob_get_clean();

            $cleanNota = preg_replace('/[^A-Za-z0-9]/', ' ', (string)$order['nomor_nota']);
            $cleanNota = trim(preg_replace('/\s+/', ' ', $cleanNota));
            PdfExport::download($html, "Picking List {$cleanNota}.pdf", 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->flashError('Gagal membuat PDF Picking List: ' . $e->getMessage());
            $this->redirect('/customer-orders/picking-list?id=' . urlencode((string)$id));
        }
    }

    /**
     * Batch export multiple PO item lists into a single consolidated PDF document.
     */
    public function batchPickingListPdf(): void
    {
        Auth::requirePermission(['orders.po_print', 'orders.po_view_all', 'orders.po_view_assigned']);

        try {
            $idsParam = $this->input('ids');
            $orderIdsInput = $this->input('order_ids');
            $tab = $this->input('tab', 'pending');
            $q = trim((string)$this->input('q', ''));
            $pelangganId = $this->input('pelanggan_id', '');
            $sort = strtolower(trim((string)$this->input('sort', 'terbaru')));
            if ($sort !== 'terlama') {
                $sort = 'terbaru';
            }

            $targetIds = [];
            if (!empty($idsParam)) {
                $targetIds = array_filter(array_map('trim', explode(',', (string)$idsParam)));
            } elseif (!empty($orderIdsInput) && is_array($orderIdsInput)) {
                $targetIds = array_filter(array_map('trim', $orderIdsInput));
            }

            $sql = "
                SELECT p.id, p.nomor_nota, p.tanggal_pesanan, p.total_bruto, p.total_diskon, p.total_netto,
                       p.status_pembayaran, p.status_pemrosesan, p.catatan, p.dibuat_pada,
                       pel.id as pelanggan_id, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                       (SELECT COUNT(*) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_sku,
                       (SELECT COALESCE(SUM(kuantitas_satuan_dasar), 0) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_pcs
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                WHERE p.status_pembayaran != 'dibatalkan'
            ";

            $params = [];

            // Permission scoping for sales/driver
            if (!Auth::can('orders.po_view_all')) {
                $myEmpId = Auth::employeeId();
                if ($myEmpId) {
                    $sql .= " AND (p.sales_driver_id = :my_emp_id OR pel.sales_driver_id = :my_emp_id)";
                    $params['my_emp_id'] = $myEmpId;
                } else {
                    $sql .= " AND 1=0";
                }
            }

            if (!empty($targetIds)) {
                $placeholders = [];
                foreach ($targetIds as $idx => $tId) {
                    $key = 'target_id_' . $idx;
                    $placeholders[] = ':' . $key;
                    $params[$key] = $tId;
                }
                $sql .= " AND p.id IN (" . implode(',', $placeholders) . ")";
            } else {
                // Filter by tab and criteria
                if ($tab === 'pending') {
                    $sql .= " AND p.status_pemrosesan = 'po'";
                } elseif ($tab === 'ready') {
                    $sql .= " AND p.status_pemrosesan IN ('siap_dikirim', 'siap_kirim')";
                } elseif ($tab === 'failed') {
                    $sql .= " AND p.status_pemrosesan = 'gagal_dikirim'";
                }

                if (!empty($pelangganId)) {
                    $sql .= " AND p.pelanggan_id = :pelanggan_id";
                    $params['pelanggan_id'] = $pelangganId;
                }

                if (!empty($q)) {
                    $sql .= " AND (p.nomor_nota ILIKE :q OR pel.nama_toko ILIKE :q OR pel.kode_pelanggan ILIKE :q OR p.catatan ILIKE :q)";
                    $params['q'] = "%{$q}%";
                }
            }

            if ($sort === 'terlama') {
                $sql .= " ORDER BY p.tanggal_pesanan ASC, p.dibuat_pada ASC, p.id ASC";
            } else {
                $sql .= " ORDER BY p.tanggal_pesanan DESC, p.dibuat_pada DESC, p.id DESC";
            }
            $orders = Database::fetchAll($sql, $params);

            if (empty($orders)) {
                $this->flashError('Tidak ada data PO yang sesuai untuk diunduh sebagai PDF.');
                $this->redirect('/customer-orders/po-list');
                return;
            }

            // Batch fetch items for all selected orders
            $orderIds = array_column($orders, 'id');
            $itemParams = [];
            $itemPlaceholders = [];
            foreach ($orderIds as $idx => $oId) {
                $key = 'ord_id_' . $idx;
                $itemPlaceholders[] = ':' . $key;
                $itemParams[$key] = $oId;
            }

            $rawItems = Database::fetchAll("
                SELECT ip.*, it.nama_item, it.kode_sku, it.stok_fisik_saat_ini, it.satuan_dasar, it.barcode
                FROM public.item_pesanan ip
                JOIN public.item it ON ip.item_id = it.id
                WHERE ip.pesanan_id IN (" . implode(',', $itemPlaceholders) . ")
                ORDER BY it.nama_item ASC
            ", $itemParams);

            $itemsByOrder = [];
            foreach ($rawItems as $ri) {
                $itemsByOrder[$ri['pesanan_id']][] = $ri;
            }

            foreach ($orders as &$ord) {
                $ord['items'] = $itemsByOrder[$ord['id']] ?? [];
            }
            unset($ord);

            ob_start();
            extract([
                'pageTitle' => 'Batch Item Pesanan PO (' . count($orders) . ' Nota)',
                'orders' => $orders,
                'isPdf' => true
            ]);
            require ROOT_PATH . '/views/customer_orders/batch_picking_list.php';
            $html = ob_get_clean();

            $dateSuffix = date('Ymd Hi');
            $countSuffix = count($orders);
            PdfExport::download($html, "Batch PO {$countSuffix} Nota {$dateSuffix}.pdf", 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->flashError('Gagal membuat PDF Batch PO: ' . $e->getMessage());
            $this->redirect('/customer-orders/po-list');
        }
    }
}
