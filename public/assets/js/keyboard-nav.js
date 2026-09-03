/**
 * Global Keyboard Navigation for Forms & POS - Keren Snack ERP
 * - Enter: Move to the next input with class .enter-nav (Auto Add Row on last field)
 * - Shift+Enter: Move to the previous input
 * - ArrowRight / ArrowLeft: Move forward/backward across form fields
 * - ArrowDown / ArrowUp: Excel-style vertical column navigation in table rows
 * - Space: Open dropdown (.sd-trigger or native select)
 * - Ctrl+Enter: Submit form immediately
 * - Ctrl+Delete / Ctrl+Backspace: Delete current row (.btn-remove-row)
 * - F2: Quick Add Row
 */

let globalTargetForm = null;

(function() {
    'use strict';

    // Focus an element safely
    const focusElement = (el) => {
        if (!el) return;
        try {
            if (el.classList.contains('sd-trigger')) {
                el.focus();
                if (window.getSelection) { window.getSelection().removeAllRanges(); }
            } else {
                el.focus();
                if (el.tagName && el.tagName.toLowerCase() === 'input' && (el.type === 'text' || el.type === 'number' || el.type === 'search' || !el.type)) {
                    if (typeof el.select === 'function') el.select();
                }
            }
        } catch (e) {
            // Ignore focus errors on hidden elements
        }
    };

    // Helper to get visible nav elements without duplicates
    const getVisibleNavElements = (form) => {
        if (!form) return [];
        const seen = new Set();
        const result = [];

        const allCandidates = form.querySelectorAll('.enter-nav');
        allCandidates.forEach(el => {
            if (el.disabled || el.readOnly) return;

            // Jika select yang sudah diubah jadi custom searchable select
            if (el.tagName && el.tagName.toLowerCase() === 'select' && el.dataset.searchableInit === 'true') {
                const wrapper = el.nextElementSibling || (el.parentElement ? el.parentElement.querySelector('.sd-wrapper') : null);
                if (wrapper && wrapper.classList.contains('sd-wrapper')) {
                    const sdTrigger = wrapper.querySelector('.sd-trigger');
                    if (sdTrigger && sdTrigger.offsetWidth > 0 && !seen.has(sdTrigger)) {
                        seen.add(sdTrigger);
                        result.push(sdTrigger);
                    }
                }
                return;
            }

            // Jika custom trigger
            if (el.classList.contains('sd-trigger')) {
                if (el.offsetWidth > 0 && el.offsetHeight > 0 && !seen.has(el)) {
                    seen.add(el);
                    result.push(el);
                }
                return;
            }

            // Elemen standar (input, native select, textarea, button)
            if (el.offsetWidth > 0 && el.offsetHeight > 0 && !seen.has(el)) {
                seen.add(el);
                result.push(el);
            }
        });

        return result;
    };

    // Helper to get current nav element from activeElement
    const getCurrentNavElement = (activeElement) => {
        if (!activeElement) return null;

        if (activeElement.classList.contains('enter-nav')) {
            return activeElement;
        }

        if (activeElement.classList.contains('sd-search')) {
            const dropdown = activeElement.closest('.sd-dropdown');
            if (dropdown && dropdown._sdTrigger) {
                return dropdown._sdTrigger;
            }
        } else if (activeElement.classList.contains('sd-trigger')) {
            return activeElement;
        }

        // Check if inside a searchable trigger wrapper
        const wrapper = activeElement.closest('.sd-wrapper');
        if (wrapper) {
            const trigger = wrapper.querySelector('.sd-trigger');
            if (trigger) return trigger;
        }

        return null;
    };

    // Maju ke elemen berikutnya
    const moveNextNavElement = (fromEl) => {
        if (!fromEl) return;
        const form = fromEl.closest('form') || globalTargetForm || document.querySelector('form[data-add-row-btn]') || document.querySelector('form');
        if (!form) return;

        const navElements = getVisibleNavElements(form);
        const currentIndex = navElements.indexOf(fromEl);

        if (currentIndex > -1) {
            if (currentIndex < navElements.length - 1) {
                focusElement(navElements[currentIndex + 1]);
            } else {
                // Di elemen terakhir: Cek apakah form memiliki tombol Tambah Baris
                const addRowBtnSelector = form.getAttribute('data-add-row-btn');
                if (addRowBtnSelector) {
                    const addRowBtn = form.querySelector(addRowBtnSelector) || document.querySelector(addRowBtnSelector);
                    if (addRowBtn) {
                        addRowBtn.click();
                        
                        // Polling singkat menunggu DOM / Alpine.js render baris baru
                        let attempts = 0;
                        const checkNewRow = () => {
                            attempts++;
                            if (typeof window.initSearchableSelects === 'function') {
                                window.initSearchableSelects(form);
                            }
                            const updatedNavs = getVisibleNavElements(form);
                            if (updatedNavs.length > navElements.length) {
                                focusElement(updatedNavs[currentIndex + 1]);
                            } else if (attempts < 8) {
                                setTimeout(checkNewRow, 40);
                            }
                        };
                        setTimeout(checkNewRow, 40);
                    }
                }
            }
        }
    };

    // Mundur ke elemen sebelumnya
    const movePrevNavElement = (fromEl) => {
        if (!fromEl) return;
        const form = fromEl.closest('form') || globalTargetForm || document.querySelector('form[data-add-row-btn]') || document.querySelector('form');
        if (!form) return;

        const navElements = getVisibleNavElements(form);
        const currentIndex = navElements.indexOf(fromEl);

        if (currentIndex > 0) {
            if (typeof window.closeAllSdDropdowns === 'function') {
                window.closeAllSdDropdowns();
            }
            focusElement(navElements[currentIndex - 1]);
        }
    };

    // Navigasi Vertikal dalam Tabel (Excel Style ArrowUp / ArrowDown)
    const moveVerticalGrid = (currentEl, direction) => {
        const row = currentEl.closest('tr, .item-row');
        if (!row) return false;

        const targetRow = direction > 0 ? row.nextElementSibling : row.previousElementSibling;
        if (!targetRow) return false;

        // Cari inputs di baris sekarang dan baris target
        const currentInputs = Array.from(row.querySelectorAll('.enter-nav')).filter(el => el.offsetWidth > 0);
        const targetInputs  = Array.from(targetRow.querySelectorAll('.enter-nav')).filter(el => el.offsetWidth > 0);

        if (!currentInputs.length || !targetInputs.length) return false;

        const colIndex = currentInputs.indexOf(currentEl);
        const targetEl = (colIndex !== -1 && targetInputs[colIndex]) ? targetInputs[colIndex] : targetInputs[0];

        if (targetEl) {
            focusElement(targetEl);
            return true;
        }
        return false;
    };

    // Expose Global Helpers
    window.focusElement = focusElement;
    window.getVisibleNavElements = getVisibleNavElements;
    window.getCurrentNavElement = getCurrentNavElement;
    window.moveNextNavElement = moveNextNavElement;
    window.movePrevNavElement = movePrevNavElement;

    // UI Banner Initializer
    window.initKeyboardNavUI = () => {
        const generateInfoHtml = (hasAddRow) => {
            let enterText = hasAddRow ? 'Maju &amp; Tambah Baris' : 'Maju';
            let deleteHtml = hasAddRow ? `<kbd class="kbd-badge">Ctrl+Del</kbd> Hapus Baris &nbsp;&bull;&nbsp;` : '';
            
            return `
                <div class="keyboard-nav-info">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                        <div style="display:flex;align-items:center;gap:8px;font-weight:700;font-size:12.5px;color:var(--color-primary);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            <span>Mode Navigasi Keyboard Cepat Aktif</span>
                        </div>
                    </div>
                    <div class="keyboard-nav-keys" style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.8;">
                        <kbd class="kbd-badge">Enter</kbd> ${enterText} &nbsp;&bull;&nbsp;
                        <kbd class="kbd-badge">&rarr;</kbd> Maju &nbsp;&bull;&nbsp;
                        <kbd class="kbd-badge">&larr;</kbd> Mundur &nbsp;&bull;&nbsp;
                        <kbd class="kbd-badge">&uarr;&darr;</kbd> Baris Atas/Bawah &nbsp;&bull;&nbsp;
                        <kbd class="kbd-badge">Spasi</kbd> Buka Dropdown &nbsp;&bull;&nbsp;
                        <kbd class="kbd-badge">Shift+Enter</kbd> Mundur &nbsp;&bull;&nbsp;
                        ${deleteHtml}
                        <kbd class="kbd-badge">Ctrl+Enter</kbd> Simpan Transaksi
                    </div>
                </div>
            `;
        };

        const formsWithAddRow = document.querySelectorAll('form[data-add-row-btn]');
        if (formsWithAddRow.length > 0) {
            formsWithAddRow.forEach(f => {
                if (!f.querySelector('.keyboard-nav-info') && !f.classList.contains('no-kbd-banner')) {
                    let container = f.querySelector('.modal-body') || f.querySelector('.card-body') || f.querySelector('.card') || f;
                    container.insertAdjacentHTML('afterbegin', generateInfoHtml(true));
                }
                if (!globalTargetForm) globalTargetForm = f;
            });
        }
    };

    // DOM Ready
    document.addEventListener('DOMContentLoaded', function() {
        window.initKeyboardNavUI();
    });

    // Global Keydown Handler
    document.addEventListener('keydown', function(e) {
        if (e.defaultPrevented) return;
        
        // 1. F2 Quick Add Row
        if (e.key === 'F2') {
            const form = globalTargetForm || document.querySelector('form[data-add-row-btn]');
            if (form) {
                const addRowSelector = form.getAttribute('data-add-row-btn');
                const addBtn = form.querySelector(addRowSelector) || document.querySelector(addRowSelector);
                if (addBtn) {
                    e.preventDefault();
                    addBtn.click();
                    setTimeout(() => {
                        if (typeof window.initSearchableSelects === 'function') window.initSearchableSelects(form);
                        const navs = getVisibleNavElements(form);
                        if (navs.length > 0) focusElement(navs[navs.length - 1]);
                    }, 60);
                    return;
                }
            }
        }

        // 2. Ctrl + Enter untuk Submit Form
        if (e.ctrlKey && e.key === 'Enter') {
            const activeElement = document.activeElement;
            let form = activeElement ? activeElement.closest('form') : null;
            if (!form && activeElement && activeElement.classList.contains('sd-search')) {
                form = globalTargetForm || document.querySelector('form');
            }
            if (!form) form = globalTargetForm || document.querySelector('form');

            if (form) {
                e.preventDefault();
                if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                
                const submitBtn = form.querySelector('button[type="submit"], button.btn-save, input[type="submit"], #btnSubmitOrder');
                if (submitBtn) {
                    submitBtn.click();
                } else {
                    form.submit();
                }
                return;
            }
        }

        // 3. Ctrl + Delete / Ctrl + Backspace untuk Hapus Baris Aktif
        if (e.ctrlKey && (e.key === 'Delete' || e.key === 'Backspace')) {
            const activeElement = document.activeElement;
            if (activeElement) {
                const navElement = getCurrentNavElement(activeElement);
                if (navElement) {
                    const row = navElement.closest('tr, .row, .item-row');
                    if (row) {
                        const removeBtn = row.querySelector('.btn-remove-row, .remove-row-btn, button[title*="Hapus"], button[aria-label*="Hapus"]');
                        if (removeBtn) {
                            e.preventDefault();
                            const form = row.closest('form') || globalTargetForm;
                            if (form) {
                                const navElements = getVisibleNavElements(form);
                                const currentIndex = navElements.indexOf(navElement);
                                if (currentIndex > 0) {
                                    focusElement(navElements[currentIndex - 1]);
                                }
                            }
                            removeBtn.click();
                            return;
                        }
                    }
                }
            }
        }

        // 4. Spasi untuk Buka Custom Dropdown
        if (e.key === ' ' && !e.ctrlKey && !e.shiftKey && !e.altKey) {
            const activeElement = document.activeElement;
            if (activeElement && activeElement.tagName.toLowerCase() !== 'input' && activeElement.tagName.toLowerCase() !== 'textarea') {
                if (activeElement.classList.contains('sd-trigger') || activeElement.classList.contains('enter-nav')) {
                    e.preventDefault();
                    e.stopPropagation();
                    activeElement.click();
                    return;
                }
            }
        }

        // 5. Shift + Enter untuk Navigasi Mundur
        if (e.key === 'Enter' && e.shiftKey && !e.ctrlKey && !e.altKey) {
            const activeElement = document.activeElement;
            if (activeElement) {
                e.preventDefault();
                const navElement = getCurrentNavElement(activeElement);
                if (navElement) {
                    movePrevNavElement(navElement);
                    return;
                }
            }
        }

        // 6. Enter Standalone (Maju ke Field Berikutnya)
        if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.altKey) {
            const activeElement = document.activeElement;
            if (!activeElement) return;

            // Jika dalam modal popup yang punya aksi default khusus (misal barcode scan atau dropdown search box)
            if (activeElement.id === 'posBarcodeInput' || activeElement.classList.contains('sd-search') || activeElement.closest('.dropdown-menu-searchable')) {
                return;
            }

            const isTextarea = activeElement.tagName.toLowerCase() === 'textarea';
            const isPlainButton = (activeElement.tagName.toLowerCase() === 'button' || activeElement.type === 'button' || activeElement.type === 'submit') && !activeElement.classList.contains('enter-nav');

            if (isTextarea || isPlainButton) return;

            const navElement = getCurrentNavElement(activeElement);
            if (navElement) {
                if (navElement.tagName.toLowerCase() === 'button' && (navElement.getAttribute('data-nav') === 'customer' || navElement.getAttribute('data-nav') === 'product')) {
                    e.preventDefault();
                    navElement.click();
                    return;
                }
                e.preventDefault();
                moveNextNavElement(navElement);
            }
        }

        // 7. ArrowRight / ArrowLeft untuk Navigasi Antar Kolom
        if ((e.key === 'ArrowRight' || e.key === 'ArrowLeft') && !e.ctrlKey && !e.altKey && !e.shiftKey) {
            const activeElement = document.activeElement;
            if (!activeElement) return;

            const isSdTrigger   = activeElement.classList.contains('sd-trigger');
            const isSdSearch    = activeElement.classList.contains('sd-search');
            const isInput       = activeElement.tagName.toLowerCase() === 'input' && !isSdSearch;
            const isSelect      = activeElement.tagName.toLowerCase() === 'select';

            let shouldIntercept = false;

            if (isSdTrigger || isSelect) {
                shouldIntercept = true;
            } else if (isSdSearch) {
                shouldIntercept = (activeElement.value === '');
            } else if (isInput) {
                const len      = activeElement.value ? activeElement.value.length : 0;
                const selStart = activeElement.selectionStart;
                const selEnd   = activeElement.selectionEnd;
                if (e.key === 'ArrowRight' && selStart === len && selEnd === len) {
                    shouldIntercept = true;
                } else if (e.key === 'ArrowLeft' && selStart === 0 && selEnd === 0) {
                    shouldIntercept = true;
                }
            }

            if (!shouldIntercept) return;

            const navElement = getCurrentNavElement(activeElement);
            if (!navElement) return;

            if (e.key === 'ArrowRight') {
                e.preventDefault();
                moveNextNavElement(navElement);
            } else {
                e.preventDefault();
                movePrevNavElement(navElement);
            }
        }

        // 8. ArrowUp / ArrowDown untuk Navigasi Baris (Grid Excel Style)
        if ((e.key === 'ArrowUp' || e.key === 'ArrowDown') && !e.ctrlKey && !e.altKey && !e.shiftKey) {
            const activeElement = document.activeElement;
            if (!activeElement) return;

            // Jika dropdown sedang terbuka, biarkan event ditangani oleh searchable-select
            if (activeElement.classList.contains('sd-search') || document.querySelector('.sd-dropdown.open')) {
                return;
            }

            const navElement = getCurrentNavElement(activeElement);
            if (navElement) {
                const handled = moveVerticalGrid(navElement, e.key === 'ArrowDown' ? 1 : -1);
                if (handled) {
                    e.preventDefault();
                }
            }
        }
    });

})();