<?php
declare(strict_types=1);

/**
 * views/deliveries/print.php
 * Mendelegasikan render ke Template Canonical Dokumen Hybrid (Faktur & Surat Jalan Gabungan)
 * Menghilangkan duplikasi kode HTML & CSS antara faktur dan surat jalan.
 */

if (!isset($order) && isset($delivery)) {
    $order = $delivery;
    if (empty($order['id']) && !empty($delivery['pesanan_id'])) {
        $order['id'] = $delivery['pesanan_id'];
    }
}

$backUrl = $backUrl ?? (\App\Core\Auth::can('deliveries.view_all') ? \App\Core\Router::url('/deliveries') : \App\Core\Router::url('/driver-deliveries'));
require ROOT_PATH . '/views/customer_orders/nota_reguler.php';
