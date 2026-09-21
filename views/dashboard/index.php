<?php
/**
 * views/dashboard/index.php
 * Shell Utama Dasbor Terpersonalisasi (Personalized Universal Dashboard)
 * Mengadopsi Pola Desain Kanonikal dari /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Helpers\Format;

ob_start();

$hour = (int)date('H');
if ($hour < 11) {
    $greeting = 'Selamat Pagi';
} elseif ($hour < 15) {
    $greeting = 'Selamat Siang';
} elseif ($hour < 18) {
    $greeting = 'Selamat Sore';
} else {
    $greeting = 'Selamat Malam';
}

$activeRoleLabels = [
    'developer' => 'Developer Engine',
    'owner'     => 'Owner / Eksekutif',
    'admin'     => 'Administrator Operasional',
    'mandor'    => 'Mandor Produksi & Gudang',
    'sales'     => 'Salesman & Konsinyasi',
    'driver'    => 'Pengemudi & Logistik Armada'
];

$roleHeaderIcons = [
    'developer' => ['icon' => 'terminal', 'color' => 'is-blue',   'accent' => '#2563eb', 'tag' => 'Developer Portal Hub'],
    'owner'     => ['icon' => 'crown',    'color' => 'is-amber',  'accent' => '#f59e0b', 'tag' => 'Executive Command Center'],
    'admin'     => ['icon' => 'shield-check', 'color' => 'is-blue','accent' => '#2563eb', 'tag' => 'Operational Store Hub'],
    'mandor'    => ['icon' => 'factory',  'color' => 'is-emerald','accent' => '#10b981', 'tag' => 'Factory & Inventory Hub'],
    'sales'     => ['icon' => 'store',    'color' => 'is-purple', 'accent' => '#a855f7', 'tag' => 'Sales B2B & Consignment'],
    'driver'    => ['icon' => 'truck',    'color' => 'is-cyan',   'accent' => '#06b6d4', 'tag' => 'Logistics Fleet & Delivery'],
];

$meta = $roleHeaderIcons[$activeRole] ?? [
    'icon'   => 'layout-dashboard', 
    'color'  => 'is-blue', 
    'accent' => '#2563eb', 
    'tag'    => 'Universal Dashboard'
];
?>

<div class="space-y-5 pb-20">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (Pola Kanonikal dari /owner)                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon <?= $meta['color'] ?>">
                <i data-lucide="<?= $meta['icon'] ?>"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:<?= $meta['accent'] ?>;"></span>
                    <span><?= $meta['tag'] ?></span>
                    <span style="display:inline-flex;align-items:center;gap:4px;background:rgba(16,185,129,0.12);color:var(--color-success);padding:2px 7px;border-radius:20px;font-size:9px;font-weight:700;letter-spacing:0.04em;border:1px solid rgba(16,185,129,0.2);">
                        <span style="width:5px;height:5px;border-radius:50%;background:var(--color-success);animation:pulse 1.5s infinite;flex-shrink:0;"></span>
                        ONLINE
                    </span>
                </div>
                <h1 class="page-title"><?= $greeting ?>, <?= htmlspecialchars($user['nama_lengkap'] ?? 'Pengguna') ?> 👋</h1>
                <p class="page-subtitle">
                    Bertugas sebagai <strong style="color:var(--color-ink);"><?= htmlspecialchars($activeRoleLabels[$activeRole] ?? ucfirst($activeRole)) ?></strong> &bull; <?= Format::tanggal(date('Y-m-d')) ?>
                </p>
            </div>
        </div>

        <div class="page-header-actions" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <a href="<?= Router::url('/profile') ?>" class="btn btn-secondary btn-sm" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;">
                <i data-lucide="user-cog" style="width:14px;height:14px;"></i>
                <span>Profil Akun</span>
            </a>
            <?php if ($activeRole === 'owner'): ?>
            <a href="<?= Router::url('/owner') ?>" class="btn btn-primary btn-sm" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;">
                <i data-lucide="crown" style="width:14px;height:14px;"></i>
                <span>Owner Executive Hub</span>
            </a>
            <?php elseif ($activeRole === 'developer'): ?>
            <a href="<?= Router::url('/developer') ?>" class="btn btn-primary btn-sm" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;">
                <i data-lucide="terminal" style="width:14px;height:14px;"></i>
                <span>Developer Portal</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. DEVELOPER LIVE ROLE PREVIEW SWITCHER (Gaya Tab Switcher /owner)        -->
    <!-- ========================================================================= -->
    <?php if ($isDeveloper): ?>
    <div class="card p-3 sm:p-3.5 rounded-xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-xs font-bold" style="color:var(--color-ink);">
                <i data-lucide="eye" style="width:15px;height:15px;color:var(--color-primary);"></i>
                <span>Pratinjau Mode Peran:</span>
                <span class="badge badge-primary font-mono text-[10px]" style="padding:2px 7px;">
                    <?= strtoupper($activeRole) ?>
                </span>
            </div>

            <div class="no-scrollbar" style="display:flex;gap:6px;overflow-x:auto;-webkit-overflow-scrolling:touch;">
                <?php
                $previewOptions = [
                    'developer' => ['label' => 'Developer', 'icon' => 'terminal'],
                    'owner'     => ['label' => 'Owner',     'icon' => 'crown'],
                    'admin'     => ['label' => 'Admin',     'icon' => 'shield-check'],
                    'mandor'    => ['label' => 'Mandor',    'icon' => 'factory'],
                    'sales'     => ['label' => 'Sales',     'icon' => 'store'],
                    'driver'    => ['label' => 'Driver',    'icon' => 'truck'],
                ];
                foreach ($previewOptions as $rKey => $rMeta):
                    $isActive = ($activeRole === $rKey);
                ?>
                <a href="<?= Router::url('/dashboard?preview_role=' . $rKey) ?>" 
                   class="<?= $isActive ? 'btn btn-primary btn-sm' : 'btn btn-secondary btn-sm' ?>"
                   style="display:inline-flex;align-items:center;gap:6px;font-weight:800;padding:6px 12px;border-radius:8px;font-size:12px;white-space:nowrap;flex-shrink:0;">
                    <i data-lucide="<?= $rMeta['icon'] ?>" style="width:13px;height:13px;"></i>
                    <span><?= $rMeta['label'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- 3. ROLE-SPECIFIC CONTENT PARTIAL                                          -->
    <!-- ========================================================================= -->
    <div>
        <?php
        $partialPath = __DIR__ . '/partials/' . $activeRole . '.php';
        if (file_exists($partialPath)) {
            require $partialPath;
        } else {
            require __DIR__ . '/partials/admin.php';
        }
        ?>
    </div>

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
