<?php
use App\Helpers\Flash;

$flash = Flash::get();
$iconMap = [
    'success' => 'check-circle',
    'error'   => 'x-circle',
    'warning' => 'alert-triangle',
    'info'    => 'info',
];
?>
<!-- GLOBAL TOAST NOTIFICATION CONTAINER -->
<div id="toast-container" class="toast-container">
    <?php if ($flash): ?>
    <div class="toast toast-<?= htmlspecialchars($flash['type']) ?>" id="php-flash-toast" style="animation: toastSlideIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;">
        <i data-lucide="<?= htmlspecialchars($iconMap[$flash['type']] ?? 'info') ?>" class="toast-icon"></i>
        <span class="toast-msg"><?= $flash['message'] ?></span>
        <button class="toast-close" onclick="this.closest('.toast').remove()" aria-label="Tutup">
            <i data-lucide="x"></i>
        </button>
    </div>
    <script>
        setTimeout(function() {
            var el = document.getElementById('php-flash-toast');
            if (el) {
                el.style.opacity = '0';
                el.style.transform = 'translateY(-8px) scale(0.96)';
                el.style.transition = 'all 0.2s ease';
                setTimeout(function() { el.remove(); }, 200);
            }
        }, 5000);
    </script>
    <?php endif; ?>
</div>

<script>
/**
 * Global Antigravity Toast Notification Engine
 * Usage:
 *   showToast('Pesan notifikasi', 'success'|'error'|'warning'|'info', 4500);
 *   toast.success('Berhasil disimpan!');
 *   toast.warning('Mohon pilih driver pengiriman.');
 */
window.showToast = function(message, type = 'info', duration = 4500) {
    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    var icons = {
        success: 'check-circle',
        error: 'x-circle',
        warning: 'alert-triangle',
        info: 'info'
    };

    var toastEl = document.createElement('div');
    toastEl.className = 'toast toast-' + type;
    toastEl.innerHTML = `
        <i data-lucide="${icons[type] || 'info'}" class="toast-icon"></i>
        <span class="toast-msg">${message}</span>
        <button class="toast-close" onclick="this.closest('.toast').remove()" aria-label="Tutup">
            <i data-lucide="x"></i>
        </button>
    `;

    container.appendChild(toastEl);

    if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
    }

    if (duration > 0) {
        setTimeout(function() {
            if (toastEl.parentNode) {
                toastEl.style.opacity = '0';
                toastEl.style.transform = 'translateY(-8px) scale(0.96)';
                toastEl.style.transition = 'all 0.2s ease';
                setTimeout(function() {
                    if (toastEl.parentNode) toastEl.remove();
                }, 200);
            }
        }, duration);
    }
};

window.toast = {
    success: function(msg, dur) { window.showToast(msg, 'success', dur || 4500); },
    error: function(msg, dur) { window.showToast(msg, 'error', dur || 5000); },
    warning: function(msg, dur) { window.showToast(msg, 'warning', dur || 4500); },
    info: function(msg, dur) { window.showToast(msg, 'info', dur || 4000); }
};
</script>
