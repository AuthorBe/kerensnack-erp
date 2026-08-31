/**
 * Global Keyboard Navigation for Forms - Keren Snack ERP
 * - Enter: Move to the next input with class .enter-nav
 * - Spasi: Open dropdown (.sd-trigger, native select)
 * - Shift+Enter or Backspace (if empty): Move to the previous input
 * - Ctrl+Enter: Submit the form
 * - Ctrl+Delete: Delete the current row (.btn-remove-row)
 * - Auto Add Row: If Enter is pressed on the last .enter-nav and form has data-add-row-btn, click it.
 */

let globalTargetForm = null;

document.addEventListener('DOMContentLoaded', function() {
    
    window.initKeyboardNavUI = () => {
        const generateInfoHtml = (hasAddRow) => {
            let enterText = hasAddRow ? 'Maju &amp; Tambah Baris' : 'Maju';
            let deleteHtml = hasAddRow ? `<kbd class="kbd-badge">Ctrl+Del</kbd> Hapus Baris &nbsp;&bull;&nbsp;` : '';
            
            return `
                <div class="keyboard-nav-info">
                    <div style="display:flex;align-items:center;gap:8px;font-weight:700;font-size:12.5px;color:var(--color-primary);margin-bottom:4px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        <span>Mode Navigasi Keyboard Cepat Aktif</span>
                    </div>
                    <div class="keyboard-nav-keys" style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.8;">
                        <kbd class="kbd-badge">Enter</kbd> ${enterText} &nbsp;&bull;&nbsp;
                        <kbd class="kbd-badge">&rarr;</kbd> Maju &nbsp;&bull;&nbsp;
                        <kbd class="kbd-badge">&larr;</kbd> Mundur &nbsp;&bull;&nbsp;
                        <kbd class="kbd-badge">Spasi</kbd> Buka Dropdown &nbsp;&bull;&nbsp;
                        <kbd class="kbd-badge">Shift+Enter</kbd> / <kbd class="kbd-badge">Backspace</kbd> Mundur &nbsp;&bull;&nbsp;
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
                    let container = f.querySelector('.modal-body') || f.querySelector('.card') || f;
                    container.insertAdjacentHTML('afterbegin', generateInfoHtml(true));
                }
                if (!globalTargetForm) globalTargetForm = f;
            });
        }
    };

    window.initKeyboardNavUI();

    // Focus an element safely
    const focusElement = (el) => {
        if (!el) return;
        if (el.classList.contains('sd-trigger')) {
            el.focus();
            if (window.getSelection) { window.getSelection().removeAllRanges(); }
        } else {
            el.focus();
            if (el.tagName.toLowerCase() === 'input' && (el.type === 'text' || el.type === 'number')) {
                el.select();
            }
        }
    };

    // Helper to get visible nav elements
    const getVisibleNavElements = (form) => {
        const result = [];
        form.querySelectorAll('.enter-nav').forEach(el => {
            if (el.disabled || el.readOnly) return;

            // Jika select sudah diubah jadi .sd-trigger
            if (el.tagName.toLowerCase() === 'select' && el.dataset.searchableInit === 'true') {
                const wrapper = el.nextSibling;
                if (wrapper && wrapper.classList && wrapper.classList.contains('sd-wrapper')) {
                    const sdTrigger = wrapper.querySelector('.sd-trigger');
                    if (sdTrigger && sdTrigger.offsetWidth > 0) {
                        result.push(sdTrigger);
                        return;
                    }
                }
                return;
            }

            if (el.offsetWidth > 0 && el.offsetHeight > 0) {
                result.push(el);
            }
        });
        return result;
    };

    // Helper to get current nav element from active element
    const getCurrentNavElement = (activeElement) => {
        if (!activeElement) return null;

        if (activeElement.classList.contains('enter-nav')) {
            return activeElement;
        }

        if (activeElement.classList.contains('sd-search')) {
            const dropdown = activeElement.closest('.sd-dropdown');
            if (dropdown && dropdown._sdTrigger) {
                const sdTrigger = dropdown._sdTrigger;
                if (sdTrigger.classList.contains('enter-nav')) {
                    return sdTrigger;
                }
            }
        } else if (activeElement.classList.contains('sd-trigger')) {
            return activeElement;
        }

        return null;
    };

    // Maju ke elemen berikutnya
    const moveNextNavElement = (fromEl) => {
        if (!fromEl) return;
        const form = fromEl.closest('form') || globalTargetForm || document.querySelector('form');
        if (!form) return;
        const navElements = getVisibleNavElements(form);
        const currentIndex = navElements.indexOf(fromEl);
        if (currentIndex > -1) {
            if (currentIndex < navElements.length - 1) {
                focusElement(navElements[currentIndex + 1]);
            } else {
                const addRowBtnSelector = form.getAttribute('data-add-row-btn');
                if (addRowBtnSelector) {
                    const addRowBtn = form.querySelector(addRowBtnSelector);
                    if (addRowBtn) {
                        addRowBtn.click();
                        setTimeout(() => {
                            const newNavElements = getVisibleNavElements(form);
                            if (newNavElements.length > navElements.length) {
                                focusElement(newNavElements[currentIndex + 1]);
                            }
                        }, 80);
                    }
                }
            }
        }
    };

    window.focusElement = focusElement;
    window.getVisibleNavElements = getVisibleNavElements;
    window.getCurrentNavElement = getCurrentNavElement;
    window.moveNextNavElement = moveNextNavElement;
    
    // Keydown listeners
    document.addEventListener('keydown', function(e) {
        
        // Auto-focus ke input pertama jika Enter ditekan di luar form
        if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.altKey) {
            const activeElement = document.activeElement;
            const isInputMode = activeElement && (
                activeElement.tagName.toLowerCase() === 'input' || 
                activeElement.tagName.toLowerCase() === 'select' || 
                activeElement.tagName.toLowerCase() === 'textarea' || 
                activeElement.tagName.toLowerCase() === 'button' ||
                activeElement.classList.contains('sd-trigger') ||
                activeElement.classList.contains('sd-search')
            );
            
            if (!isInputMode) {
                const navElements = Array.from(document.querySelectorAll('.enter-nav')).filter(el => {
                    if (el.tagName.toLowerCase() === 'select' && el.dataset.searchableInit === 'true') {
                        const wrapper = el.nextSibling;
                        if (wrapper && wrapper.classList && wrapper.classList.contains('sd-wrapper')) {
                            const sdTrigger = wrapper.querySelector('.sd-trigger');
                            return sdTrigger && sdTrigger.offsetWidth > 0;
                        }
                        return false;
                    }
                    return el.offsetWidth > 0 && el.offsetHeight > 0;
                });
                if (navElements.length > 0) {
                    e.preventDefault();
                    const firstEl = navElements[0];
                    if (firstEl.tagName.toLowerCase() === 'select' && firstEl.dataset.searchableInit === 'true') {
                        const wrapper = firstEl.nextSibling;
                        const sdTrigger = wrapper && wrapper.querySelector('.sd-trigger');
                        focusElement(sdTrigger || firstEl);
                    } else {
                        focusElement(firstEl);
                    }
                    return;
                }
            }
        }
        
        // Ctrl + Enter untuk Submit Form
        if (e.ctrlKey && e.key === 'Enter') {
            const activeElement = document.activeElement;
            if (activeElement) {
                let form = activeElement.closest('form');
                if (!form && activeElement.classList.contains('sd-search')) {
                    form = globalTargetForm || document.querySelector('form');
                }
                
                if (form) {
                    e.preventDefault();
                    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    
                    const submitBtn = form.querySelector('button[type="submit"], button.btn-save, input[type="submit"]');
                    if (submitBtn) {
                        submitBtn.click();
                    } else {
                        form.submit();
                    }
                    return;
                }
            }
        }
        
        // Ctrl + Delete untuk Hapus Baris
        if (e.ctrlKey && e.key === 'Delete') {
            const activeElement = document.activeElement;
            if (activeElement) {
                const navElement = getCurrentNavElement(activeElement);
                if (navElement) {
                    const row = navElement.closest('tr, .row, .item-row');
                    if (row) {
                        const removeBtn = row.querySelector('.btn-remove-row, .remove-row-btn');
                        if (removeBtn) {
                            e.preventDefault();
                            const form = row.closest('form');
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
        
        // Spasi untuk Buka Custom Dropdown
        if (e.key === ' ' && !e.ctrlKey && !e.shiftKey && !e.altKey) {
            const activeElement = document.activeElement;
            if (activeElement && activeElement.tagName.toLowerCase() !== 'input' && activeElement.tagName.toLowerCase() !== 'textarea') {
                if (activeElement.classList.contains('sd-trigger')) {
                    e.preventDefault();
                    e.stopPropagation();
                    activeElement.click();
                    return;
                }
            }
        }
        
        // Backspace atau Shift + Enter untuk Navigasi Mundur
        if ((e.key === 'Backspace' || (e.key === 'Enter' && e.shiftKey)) && !e.ctrlKey && !e.altKey) {
            const activeElement = document.activeElement;
            if (activeElement) {
                if (activeElement.classList.contains('sd-search')) {
                    if (e.key === 'Backspace' && activeElement.value !== '') {
                        return;
                    }
                }
                
                const navElement = getCurrentNavElement(activeElement);
                if (navElement) {
                    const isSelect = navElement.tagName.toLowerCase() === 'select';
                    const isCheckbox = activeElement.type === 'checkbox' || activeElement.type === 'radio';
                    const isEmpty = isCheckbox || (('value' in activeElement) ? (activeElement.value === '' || activeElement.value === '0') : true);
                    const isSpan = activeElement.tagName.toLowerCase() === 'span' || activeElement.classList.contains('sd-trigger');
                    
                    if (e.key === 'Enter' || isSelect || (isEmpty && !isSpan) || (e.key === 'Backspace' && isSpan) || activeElement.classList.contains('sd-search')) {
                        if (e.key === 'Enter') e.preventDefault();
                        
                        const form = navElement.closest('form') || globalTargetForm || document.querySelector('form');
                        if (form) {
                            const navElements = getVisibleNavElements(form);
                            const currentIndex = navElements.indexOf(navElement);
                            
                            if (currentIndex > 0) {
                                e.preventDefault();
                                if (typeof window.closeAllSdDropdowns === 'function') {
                                    window.closeAllSdDropdowns();
                                }
                                focusElement(navElements[currentIndex - 1]);
                            }
                        }
                    }
                }
            }
        }
        
        // Enter Standalone (Maju ke Field Berikutnya)
        if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.altKey) {
            const activeElement = document.activeElement;
            if (!activeElement) return;
            
            if (activeElement.classList.contains('sd-search')) {
                return;
            }
            
            const isTextarea = activeElement.tagName.toLowerCase() === 'textarea';
            const isButton = activeElement.tagName.toLowerCase() === 'button' || activeElement.type === 'button' || activeElement.type === 'submit';
            
            if (isTextarea || isButton) {
                return;
            }
            
            const form = activeElement.closest('form');
            if (form) {
                e.preventDefault(); 
                const navElement = getCurrentNavElement(activeElement);
                if (navElement) {
                    moveNextNavElement(navElement);
                }
            }
        }

        // ArrowRight / ArrowLeft untuk Navigasi Cepat
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
                shouldIntercept = activeElement.value === '';
            } else if (isInput) {
                const len      = activeElement.value.length;
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

            const form = navElement.closest('form') || globalTargetForm || document.querySelector('form');
            if (!form) return;

            const navElements  = getVisibleNavElements(form);
            const currentIndex = navElements.indexOf(navElement);
            if (currentIndex === -1) return;

            if (e.key === 'ArrowRight' && currentIndex < navElements.length - 1) {
                e.preventDefault();
                if (typeof window.closeAllSdDropdowns === 'function') window.closeAllSdDropdowns();
                focusElement(navElements[currentIndex + 1]);
            } else if (e.key === 'ArrowLeft' && currentIndex > 0) {
                e.preventDefault();
                if (typeof window.closeAllSdDropdowns === 'function') window.closeAllSdDropdowns();
                focusElement(navElements[currentIndex - 1]);
            }
        }
    });

});