/**
 * Global Searchable Select Component for Keren Snack ERP
 * Mengubah elemen <select class="searchable-select"> standar menjadi custom searchable dropdown modern.
 * Mendukung <optgroup>, data-badge, navigasi keyboard (.enter-nav), integrasi Alpine.js & HTMX.
 */

(function() {
    'use strict';

    // ─── Satu mousedown listener GLOBAL untuk semua dropdown ────────────────
    document.addEventListener('mousedown', function(e) {
        document.querySelectorAll('.sd-dropdown.open').forEach(function(dropdown) {
            const trigger = dropdown._sdTrigger;
            if (trigger && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
                trigger.classList.remove('open');
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // ─── Public API: tutup semua sd-dropdown yang terbuka ───────────────────
    window.closeAllSdDropdowns = function() {
        document.querySelectorAll('.sd-dropdown.open').forEach(function(dropdown) {
            const trigger = dropdown._sdTrigger;
            dropdown.classList.remove('open');
            if (trigger) {
                trigger.classList.remove('open');
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    };

    // ─── Helper: Bersihkan dropdown yatim piatu di body saat DOM swap ────────
    function cleanupOrphanDropdowns() {
        document.querySelectorAll('body > .sd-dropdown').forEach(function(dropdown) {
            if (dropdown._sdTrigger && !document.body.contains(dropdown._sdTrigger)) {
                dropdown.remove();
            }
        });
    }

    /**
     * Transform satu <select> menjadi searchable dropdown.
     */
    function transformSelect(selectEl) {
        if (!selectEl || selectEl.dataset.searchableInit === "true") return;
        selectEl.dataset.searchableInit = "true";
        selectEl.style.display = 'none';

        // Baca opsi & grup
        const optionsData = [];
        let placeholderText = '-- Pilih --';

        Array.from(selectEl.children).forEach(child => {
            if (child.tagName.toLowerCase() === 'optgroup') {
                const groupName = child.getAttribute('label') || '';
                Array.from(child.children).forEach(opt => {
                    if (opt.tagName.toLowerCase() === 'option') {
                        optionsData.push({
                            id:       opt.value,
                            name:     opt.textContent.trim(),
                            group:    groupName,
                            badge:    opt.dataset.badge || null,
                            selected: opt.selected,
                        });
                        if (opt.selected && opt.value !== '') {
                            placeholderText = opt.textContent.trim();
                        }
                    }
                });
            } else if (child.tagName.toLowerCase() === 'option') {
                const isPlaceholder = child.value === '';
                if (isPlaceholder && child.textContent.trim()) {
                    placeholderText = child.textContent.trim();
                }
                optionsData.push({
                    id:       child.value,
                    name:     child.textContent.trim(),
                    group:    '',
                    badge:    child.dataset.badge || null,
                    selected: child.selected,
                });
            }
        });

        // Cari item terpilih awal
        const selectedOpt = optionsData.find(o => o.selected && o.id !== '');
        const initialText = selectedOpt ? selectedOpt.name : placeholderText;
        const isInitialPlaceholder = !selectedOpt;

        // Buat wrapper & trigger
        const wrapper = document.createElement('div');
        wrapper.className = 'sd-wrapper';

        const isLg = selectEl.classList.contains('form-select-lg') || selectEl.classList.contains('sd-lg');
        const hasEnterNav = selectEl.classList.contains('enter-nav');

        const trigger = document.createElement('div');
        trigger.className = ('sd-trigger' + (isLg ? ' sd-lg' : '') + (hasEnterNav ? ' enter-nav' : '')).trim();
        trigger.tabIndex = 0;
        trigger.setAttribute('role', 'combobox');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger._sdSelect = selectEl;

        const valueSpan = document.createElement('span');
        valueSpan.className = ('sd-value' + (isInitialPlaceholder ? ' sd-placeholder' : '')).trim();
        valueSpan.textContent = initialText;

        const arrowIcon = document.createElement('span');
        arrowIcon.className = 'sd-arrow-icon';
        arrowIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="sd-arrow"><path d="m6 9 6 6 6-6"/></svg>`;

        trigger.appendChild(valueSpan);
        trigger.appendChild(arrowIcon);

        // Buat dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'sd-dropdown';
        dropdown.setAttribute('role', 'listbox');
        dropdown._sdTrigger = trigger;
        dropdown._sdSelect  = selectEl;

        const searchWrap = document.createElement('div');
        searchWrap.className = 'sd-search-wrap';

        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'sd-search';
        searchInput.placeholder = 'Ketik untuk mencari...';
        searchInput.autocomplete = 'off';
        searchInput.tabIndex = -1;

        searchWrap.appendChild(searchInput);

        const listEl = document.createElement('div');
        listEl.className = 'sd-list';

        dropdown.appendChild(searchWrap);
        dropdown.appendChild(listEl);

        wrapper._sdSelect = selectEl;

        // Sisipkan UI ke DOM
        wrapper.appendChild(trigger);
        selectEl.parentNode.insertBefore(wrapper, selectEl.nextSibling);
        document.body.appendChild(dropdown);

        // AbortController untuk cleanup scroll/resize saat elemen dihapus
        const abortCtrl = new AbortController();
        const abortSignal = abortCtrl.signal;

        let focusedIdx = -1;

        function renderList(query) {
            const q = (query || '').toLowerCase().trim();
            listEl.innerHTML = '';
            focusedIdx = -1;

            const hasGroup = optionsData.some(d => d.group);

            if (!hasGroup) {
                const filtered = optionsData.filter(d => !q || d.name.toLowerCase().includes(q));
                if (!filtered.length) {
                    listEl.innerHTML = `<div class="sd-empty">Tidak ditemukan</div>`;
                    return;
                }
                filtered.forEach((item, i) => {
                    if (item.id === '') return;
                    listEl.appendChild(buildOption(item, i));
                });
            } else {
                const groups = {};
                optionsData.forEach(d => {
                    if (d.id === '') return;
                    if (q && !d.name.toLowerCase().includes(q)) return;
                    const g = d.group || 'Lainnya';
                    if (!groups[g]) groups[g] = [];
                    groups[g].push(d);
                });

                let totalCount = 0;
                let idx = 0;
                Object.keys(groups).forEach(gName => {
                    const groupLabel = document.createElement('div');
                    groupLabel.className = 'sd-group-label';
                    groupLabel.textContent = gName;
                    listEl.appendChild(groupLabel);
                    groups[gName].forEach(item => {
                        listEl.appendChild(buildOption(item, idx++));
                        totalCount++;
                    });
                });

                if (!totalCount) {
                    listEl.innerHTML = `<div class="sd-empty">Tidak ditemukan</div>`;
                }
            }
        }

        function buildOption(item, idx) {
            const opt = document.createElement('div');
            opt.className = 'sd-option';
            if (selectEl.value === item.id) opt.classList.add('selected');
            opt.setAttribute('role', 'option');
            opt.dataset.id   = item.id;
            opt.dataset.name = item.name;
            opt.dataset.idx  = idx;

            let inner = `<span>${item.name}</span>`;
            if (item.badge) inner += `<span class="sd-badge">${item.badge}</span>`;
            opt.innerHTML = inner;

            opt.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selectItem(item.id, item.name);
            });
            return opt;
        }

        function selectItem(id, name) {
            valueSpan.textContent = name;
            valueSpan.classList.remove('sd-placeholder');
            closeDropdown();

            // Update nilai elemen <select> asli
            selectEl.value = id;

            // Trigger event change & input native agar listener di luar (Alpine.js / JS) jalan
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
            selectEl.dispatchEvent(new Event('input', { bubbles: true }));
            if (typeof selectEl.onchange === 'function') {
                selectEl.onchange();
            }

            // Pindah fokus navigasi keyboard jika ada
            if (typeof window.moveNextNavElement === 'function') {
                setTimeout(() => window.moveNextNavElement(trigger), 50);
            }
        }

        function positionDropdown() {
            const rect = trigger.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            const dropH = Math.min(300, dropdown.scrollHeight || 300);
            const goUp = spaceBelow < dropH + 8 && rect.top > dropH + 8;

            dropdown.style.width = rect.width + 'px';
            dropdown.style.left  = (rect.left + window.scrollX) + 'px';

            if (goUp) {
                dropdown.style.top    = '';
                dropdown.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
            } else {
                dropdown.style.top    = (rect.bottom + window.scrollY + 4) + 'px';
                dropdown.style.bottom = '';
            }
        }

        function openDropdown() {
            // Tutup semua dropdown lain
            document.querySelectorAll('.sd-dropdown.open').forEach(function(d) {
                if (d !== dropdown) {
                    d.classList.remove('open');
                    const t = d._sdTrigger;
                    if (t) { t.classList.remove('open'); t.setAttribute('aria-expanded', 'false'); }
                }
            });

            dropdown.classList.add('open');
            trigger.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            searchInput.value = '';
            renderList('');
            positionDropdown();
            setTimeout(() => searchInput.focus(), 50);
        }

        function closeDropdown() {
            dropdown.classList.remove('open');
            trigger.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        function moveFocus(dir) {
            const opts = listEl.querySelectorAll('.sd-option');
            if (!opts.length) return;
            opts.forEach(o => o.classList.remove('focused'));
            focusedIdx = Math.max(0, Math.min(opts.length - 1, focusedIdx + dir));
            opts[focusedIdx].classList.add('focused');
            opts[focusedIdx].scrollIntoView({ block: 'nearest' });
        }

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.contains('open') ? closeDropdown() : openDropdown();
        });
        trigger.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                openDropdown();
            }
        });
        searchInput.addEventListener('input', function() { renderList(searchInput.value); });
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown')       { e.preventDefault(); moveFocus(1); }
            else if (e.key === 'ArrowUp')    { e.preventDefault(); moveFocus(-1); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                const focused = listEl.querySelector('.sd-option.focused');
                if (focused) selectItem(focused.dataset.id, focused.dataset.name);
            }
            else if (e.key === 'Escape')     { e.stopPropagation(); closeDropdown(); trigger.focus(); }
        });

        window.addEventListener('scroll', function() {
            if (dropdown.classList.contains('open')) positionDropdown();
        }, { capture: true, passive: true, signal: abortSignal });
        window.addEventListener('resize', function() {
            if (dropdown.classList.contains('open')) positionDropdown();
        }, { signal: abortSignal });

        // Cleanup listener saat wrapper dihapus
        const cleanupObserver = new MutationObserver(function() {
            if (!document.body.contains(wrapper)) {
                abortCtrl.abort();
                dropdown.remove();
                cleanupObserver.disconnect();
            }
        });
        cleanupObserver.observe(document.body, { childList: true, subtree: true });
    }

    /**
     * Inisialisasi semua <select class="searchable-select"> di kontainer.
     */
    window.initSearchableSelects = function(container) {
        cleanupOrphanDropdowns();
        const root = container || document;
        const selects = root.querySelectorAll('select.searchable-select, select[data-searchable="true"]');
        selects.forEach(transformSelect);
    };

    document.addEventListener('DOMContentLoaded', function() { window.initSearchableSelects(); });

})();