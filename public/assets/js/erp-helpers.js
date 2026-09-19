/**
 * KEREN SNACK ERP — Shared Universal Frontend Helpers
 * File: public/assets/js/erp-helpers.js
 * 
 * Provides robust currency formatting, unformatting, and icon initialization
 * across Alpine.js components and browser scripts.
 */
(function (window) {
    'use strict';

    /**
     * Parse any input (number, string with Rupiah/dots/commas, null, undefined) safely to numeric float
     */
    function toValidNumber(val) {
        if (val === null || val === undefined || val === '' || val === false) return 0;
        if (typeof val === 'number') return isNaN(val) ? 0 : val;

        var str = String(val).trim();
        var isNegative = str.startsWith('-') || str.indexOf('-') !== -1;
        str = str.replace(/[^0-9.,]/g, '');
        if (!str) return 0;

        if (str.indexOf('.') !== -1 && str.indexOf(',') !== -1) {
            if (str.lastIndexOf(',') > str.lastIndexOf('.')) {
                // Indonesian standard: 15.000,50 -> dot is thousand, comma is decimal
                str = str.replace(/\./g, '').replace(',', '.');
            } else {
                // English standard: 15,000.50 -> comma is thousand, dot is decimal
                str = str.replace(/,/g, '');
            }
        } else if (str.indexOf('.') !== -1) {
            var parts = str.split('.');
            if (parts.length > 2 || (parts.length === 2 && parts[1].length === 3 && parts[0].length > 0)) {
                str = str.replace(/\./g, '');
            }
        } else if (str.indexOf(',') !== -1) {
            var cparts = str.split(',');
            if (cparts.length > 2 || (cparts.length === 2 && cparts[1].length === 3)) {
                str = str.replace(/,/g, '');
            } else {
                str = str.replace(',', '.');
            }
        }

        var parsed = parseFloat(str);
        if (isNaN(parsed)) return 0;
        return isNegative ? -Math.abs(parsed) : Math.abs(parsed);
    }

    /**
     * Format number to Indonesian Thousand Separator without prefix (e.g. 15000 -> "15.000")
     */
    function formatRupiahNumber(val) {
        var num = toValidNumber(val);
        var isNegative = num < 0;
        var absNum = Math.abs(num);
        var formatted = '';
        try {
            formatted = new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 0
            }).format(absNum);
        } catch (e) {
            formatted = String(Math.round(absNum)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        return isNegative ? '-' + formatted : formatted;
    }

    /**
     * Format number to Rupiah string (e.g. 15000 -> "Rp 15.000" or -15000 -> "-Rp 15.000")
     */
    function formatRupiah(val, withPrefix) {
        if (withPrefix === undefined) withPrefix = true;
        var num = toValidNumber(val);
        var isNegative = num < 0;
        var formatted = formatRupiahNumber(Math.abs(num));
        if (isNegative) {
            return withPrefix ? '-Rp ' + formatted : '-' + formatted;
        }
        return withPrefix ? 'Rp ' + formatted : formatted;
    }

    /**
     * Parse formatted currency string back to pure integer/float (e.g. "Rp 15.000" -> 15000)
     */
    function unformatRupiah(str) {
        return toValidNumber(str);
    }

    /**
     * Safely refresh Lucide icons with existence and type guard
     */
    function refreshIcons(rootElement) {
        if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
            try {
                if (rootElement && rootElement instanceof Element) {
                    lucide.createIcons({ root: rootElement });
                } else {
                    lucide.createIcons();
                }
            } catch (err) {
                console.warn('[erp-helpers] Lucide refresh warning:', err);
            }
        }
    }

    // Built-in SVG Icons for instant zero-dependency rendering in Dialogs
    var dialogSvgs = {
        'trash-2': '<svg class="confirm-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>',
        'alert-triangle': '<svg class="confirm-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        'alert-circle': '<svg class="confirm-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
        'info': '<svg class="confirm-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
        'help-circle': '<svg class="confirm-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        'check-circle-2': '<svg class="confirm-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>',
        'x': '<svg style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
        'user-check': '<svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>'
    };

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getDialogIconSvg(name, type) {
        if (name && dialogSvgs[name]) return dialogSvgs[name];
        var defaultIcons = {
            danger: 'trash-2',
            warning: 'alert-triangle',
            info: 'info',
            primary: 'help-circle',
            success: 'check-circle-2'
        };
        var def = defaultIcons[type] || 'info';
        if (dialogSvgs[def]) return dialogSvgs[def];
        return '<i data-lucide="' + (name || def) + '"></i>';
    }

    /**
     * Modern Promise-based Confirmation Dialog
     */
    function AppConfirm(options) {
        return new Promise(function(resolve) {
            var opts = typeof options === 'string' ? { message: options } : (options || {});
            var title = escapeHtml(opts.title || 'Konfirmasi Tindakan');
            var message = escapeHtml(opts.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?');
            var submessage = escapeHtml(opts.submessage || '');
            var accountInfo = escapeHtml(opts.accountInfo || '');
            var confirmText = escapeHtml(opts.confirmText || 'Konfirmasi');
            var cancelText = escapeHtml(opts.cancelText || 'Batal');
            var type = opts.type || 'danger';
            var iconName = opts.icon || null;
            var showCancelBtn = opts.showCancelBtn !== false;
            var showCloseBtn = opts.showCloseBtn !== undefined ? Boolean(opts.showCloseBtn) : !showCancelBtn;
            var defaultFocus = opts.defaultFocus || 'confirm';

            var existing = document.getElementById('app-confirm-overlay');
            if (existing) existing.remove();

            var overlay = document.createElement('div');
            overlay.id = 'app-confirm-overlay';
            overlay.className = 'confirm-overlay';

            overlay.innerHTML =
                '<div class="confirm-modal" style="max-height: 90vh; overflow-y: auto;" role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title">' +
                    '<div class="confirm-modal-body">' +
                        '<div class="confirm-header-row">' +
                            '<div class="confirm-icon-box confirm-icon-' + type + '">' +
                                getDialogIconSvg(iconName, type) +
                            '</div>' +
                            (showCloseBtn ? (
                                '<button type="button" id="confirm-btn-close" class="confirm-close-btn" aria-label="Tutup dialog" title="Tutup">' +
                                    dialogSvgs['x'] +
                                '</button>'
                            ) : '') +
                        '</div>' +
                        '<h3 id="confirm-dialog-title" class="confirm-title">' + title + '</h3>' +
                        '<p class="confirm-message">' + message + '</p>' +
                        (accountInfo ? (
                            '<div class="confirm-account-pill">' +
                                dialogSvgs['user-check'] +
                                '<span>Akun Aktif: <strong>' + accountInfo + '</strong></span>' +
                            '</div>'
                        ) : '') +
                        (submessage ? (
                            '<div class="confirm-subnotice">' +
                                dialogSvgs['alert-circle'] +
                                '<span>' + submessage + '</span>' +
                            '</div>'
                        ) : '') +
                    '</div>' +
                    '<div class="confirm-modal-footer">' +
                        (showCancelBtn ? (
                            '<button type="button" id="confirm-btn-cancel" class="confirm-btn-cancel">' +
                                '<span>' + cancelText + '</span>' +
                            '</button>'
                        ) : '') +
                        '<button type="button" id="confirm-btn-ok" class="confirm-btn-action btn-action-' + type + '">' +
                            '<span>' + confirmText + '</span>' +
                        '</button>' +
                    '</div>' +
                '</div>';

            document.body.appendChild(overlay);
            document.body.classList.add('modal-open');
            if (window.PopupManager) window.PopupManager.freeze(overlay);

            var modalEl = overlay.querySelector('.confirm-modal');
            var btnCancel = overlay.querySelector('#confirm-btn-cancel');
            var btnOk = overlay.querySelector('#confirm-btn-ok');
            var btnClose = overlay.querySelector('#confirm-btn-close');

            requestAnimationFrame(function() {
                overlay.style.opacity = '1';
                modalEl.style.transform = 'scale(1) translateY(0)';
                if (defaultFocus === 'cancel' && btnCancel) {
                    btnCancel.focus();
                } else if (btnOk) {
                    btnOk.focus();
                }
            });

            var isClosed = false;
            function closeDialog(result) {
                if (isClosed) return;
                isClosed = true;
                if (window.PopupManager) window.PopupManager.unfreeze(overlay);
                overlay.style.opacity = '0';
                modalEl.style.transform = 'scale(0.95) translateY(6px)';
                document.removeEventListener('keydown', handleKey);
                document.body.classList.remove('modal-open');
                setTimeout(function() {
                    if (overlay.parentNode) overlay.remove();
                    resolve(result);
                }, 180);
            }

            function handleKey(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeDialog(false);
                } else if (e.key === 'Enter' && document.activeElement !== btnCancel) {
                    e.preventDefault();
                    closeDialog(true);
                }
            }

            document.addEventListener('keydown', handleKey);
            if (btnCancel) btnCancel.addEventListener('click', function() { closeDialog(false); });
            if (btnOk) btnOk.addEventListener('click', function() { closeDialog(true); });
            if (btnClose) btnClose.addEventListener('click', function() { closeDialog(false); });
        });
    }

    /**
     * Modern Promise-based Alert Dialog
     */
    function AppAlert(options) {
        return new Promise(function(resolve) {
            var opts = typeof options === 'string' ? { message: options } : (options || {});
            var title = escapeHtml(opts.title || 'Informasi Sistem');
            var message = escapeHtml(opts.message || '');
            var submessage = escapeHtml(opts.submessage || '');
            var buttonText = escapeHtml(opts.buttonText || 'Mengerti');
            var type = opts.type || 'info';
            var iconName = opts.icon || null;
            var showCloseBtn = opts.showCloseBtn !== false;

            var existing = document.getElementById('app-confirm-overlay');
            if (existing) existing.remove();

            var overlay = document.createElement('div');
            overlay.id = 'app-confirm-overlay';
            overlay.className = 'confirm-overlay';

            overlay.innerHTML =
                '<div class="confirm-modal" style="max-height: 90vh; overflow-y: auto;" role="dialog" aria-modal="true" aria-labelledby="alert-dialog-title">' +
                    '<div class="confirm-modal-body">' +
                        '<div class="confirm-header-row">' +
                            '<div class="confirm-icon-box confirm-icon-' + type + '">' +
                                getDialogIconSvg(iconName, type) +
                            '</div>' +
                            (showCloseBtn ? (
                                '<button type="button" id="alert-btn-close" class="confirm-close-btn" aria-label="Tutup dialog" title="Tutup">' +
                                    dialogSvgs['x'] +
                                '</button>'
                            ) : '') +
                        '</div>' +
                        '<h3 id="alert-dialog-title" class="confirm-title">' + title + '</h3>' +
                        '<p class="confirm-message">' + message + '</p>' +
                        (submessage ? (
                            '<div class="confirm-subnotice">' +
                                dialogSvgs['info'] +
                                '<span>' + submessage + '</span>' +
                            '</div>'
                        ) : '') +
                    '</div>' +
                    '<div class="confirm-modal-footer">' +
                        '<button type="button" id="alert-btn-ok" class="confirm-btn-action btn-action-' + (type === 'danger' ? 'danger' : 'primary') + '">' +
                            '<span>' + buttonText + '</span>' +
                        '</button>' +
                    '</div>' +
                '</div>';

            document.body.appendChild(overlay);
            document.body.classList.add('modal-open');
            if (window.PopupManager) window.PopupManager.freeze(overlay);

            var modalEl = overlay.querySelector('.confirm-modal');
            var btnOk = overlay.querySelector('#alert-btn-ok');
            var btnClose = overlay.querySelector('#alert-btn-close');

            requestAnimationFrame(function() {
                overlay.style.opacity = '1';
                modalEl.style.transform = 'scale(1) translateY(0)';
                if (btnOk) btnOk.focus();
            });

            var isClosed = false;
            function closeDialog() {
                if (isClosed) return;
                isClosed = true;
                if (window.PopupManager) window.PopupManager.unfreeze(overlay);
                overlay.style.opacity = '0';
                modalEl.style.transform = 'scale(0.95) translateY(6px)';
                document.removeEventListener('keydown', handleKey);
                document.body.classList.remove('modal-open');
                setTimeout(function() {
                    if (overlay.parentNode) overlay.remove();
                    resolve(true);
                }, 180);
            }

            function handleKey(e) {
                if (e.key === 'Escape' || e.key === 'Enter') {
                    e.preventDefault();
                    closeDialog();
                }
            }

            document.addEventListener('keydown', handleKey);
            if (btnOk) btnOk.addEventListener('click', closeDialog);
            if (btnClose) btnClose.addEventListener('click', closeDialog);
        });
    }

    // Export to global window scope
    window.formatRupiah = formatRupiah;
    window.unformatRupiah = unformatRupiah;
    window.formatRupiahNumber = formatRupiahNumber;
    window.refreshIcons = refreshIcons;

    // ERP Universal Branded Dialogs
    window.AppConfirm = AppConfirm;
    window.AppAlert = AppAlert;
    window.confirmModal = AppConfirm;
    window.alertModal = AppAlert;
    window.showConfirm = AppConfirm;
    window.showAlert = AppAlert;
    window.AppDialog = {
        confirm: AppConfirm,
        alert: AppAlert
    };

    // Universal Form Double-Submit Protection & Loading State for Non-Ajax Forms
    if (typeof document !== 'undefined') {
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (!form || !(form instanceof HTMLFormElement)) return;
            if (form.hasAttribute('data-no-disable')) return;
            var method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method === 'GET') return;

            var submitBtn = form.querySelector('button[type="submit"]:not([data-no-disable])');
            if (submitBtn && !submitBtn.disabled) {
                setTimeout(function() {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('is-submitting', 'opacity-70', 'pointer-events-none');
                }, 20);
            }
        });
    }

})(window);
