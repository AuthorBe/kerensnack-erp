<?php
use App\Helpers\Flash;

$flash = Flash::get();
?>
<!-- GLOBAL TOAST NOTIFICATION CONTAINER (Utility container for JS toasts) -->
<div id="toast-container" class="toast-container" style="z-index: 9999999 !important;"></div>

<script>
    window.__FLASH__ = <?= $flash ? json_encode([
        'type' => $flash['type'],
        'message' => strip_tags($flash['message']),
        'raw_message' => $flash['message']
    ]) : 'null' ?>;
</script>

<script>
/**
 * Global Antigravity Toast Notification Engine
 * Bulletproof SVG Icons (0% dependency on Lucide initialization delay)
 */
window.showToast = function(message, type = 'info', duration = 4800) {
    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    var normalizedType = (type === 'danger' || type === 'error') ? 'error' : type;
    var svgs = {
        success: '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>',
        error:   '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6"></path><path d="m9 9 6 6"></path></svg>',
        warning: '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        info:    '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
    };

    var toastEl = document.createElement('div');
    toastEl.className = 'toast toast-' + normalizedType;
    toastEl.style.animation = 'toastSlideIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards';
    toastEl.innerHTML = (svgs[normalizedType] || svgs.info) + 
        '<span class="toast-msg">' + message + '</span>' +
        '<button class="toast-close" onclick="this.closest(\'.toast\').remove()" aria-label="Tutup">' +
            '<svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                '<line x1="18" y1="6" x2="6" y2="18"></line>' +
                '<line x1="6" y1="6" x2="18" y2="18"></line>' +
            '</svg>' +
        '</button>';

    container.appendChild(toastEl);

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
    success: function(msg, dur) {
        if (window.AppAction && typeof window.AppAction.success === 'function') {
            window.AppAction.success(msg);
        } else {
            window.showToast(msg, 'success', dur || 4800);
        }
    },
    error: function(msg, dur) {
        if (window.AppAction && typeof window.AppAction.error === 'function') {
            window.AppAction.error(msg);
        } else {
            window.showToast(msg, 'error', dur || 5000);
        }
    },
    warning: function(msg, dur) { window.showToast(msg, 'warning', dur || 4800); },
    info: function(msg, dur) { window.showToast(msg, 'info', dur || 4200); }
};
</script>
