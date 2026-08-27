<?php
use App\Helpers\Flash;

$flash = Flash::get();
if (!$flash) return;

$iconMap = [
    'success' => 'check-circle',
    'error'   => 'x-circle',
    'warning' => 'alert-triangle',
    'info'    => 'info',
];
$icon = $iconMap[$flash['type']] ?? 'info';
?>
<!-- Toast Container -->
<div class="toast-container" x-data="{
    show: true,
    type: '<?= htmlspecialchars($flash['type']) ?>',
    message: <?= json_encode($flash['message']) ?>,
    dismissTimer: null,
    init() {
        this.dismissTimer = setTimeout(() => this.dismiss(), 5000);
    },
    dismiss() {
        clearTimeout(this.dismissTimer);
        this.show = false;
    }
}" x-show="show"
   x-transition:enter="transition ease-out duration-200"
   x-transition:enter-start="opacity-0 translate-x-3"
   x-transition:enter-end="opacity-100 translate-x-0"
   x-transition:leave="transition ease-in duration-150"
   x-transition:leave-start="opacity-100 translate-x-0"
   x-transition:leave-end="opacity-0 translate-x-3"
   x-cloak>

    <div :class="`toast toast-${type}`">
        <i data-lucide="<?= htmlspecialchars($icon) ?>" class="toast-icon"></i>
        <span class="toast-msg" x-text="message"></span>
        <button class="toast-close" @click="dismiss()" aria-label="Tutup">
            <i data-lucide="x"></i>
        </button>
    </div>
</div>
