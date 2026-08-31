<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * app/Controllers/SalesOrderController.php
 * Backward-compatible alias extending CustomerOrderController.
 */
class SalesOrderController extends CustomerOrderController
{
    // Mewarisi seluruh fungsi CustomerOrderController secara transparan
}
