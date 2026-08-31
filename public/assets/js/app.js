/**
 * KEREN SNACK ERP — Ultra-Fast Zero-Freeze Global JavaScript
 * Optimized for Mobile & Desktop Performance (60 FPS Native Touch)
 * Version: 3.5 | 2026
 */

(function () {
  'use strict';

  const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

  /* =====================================================================
     1. THEME ENGINE (Zero-Flash Light/Dark)
     ===================================================================== */
  const ThemeEngine = {
    STORAGE_KEY: 'ksnack_theme',

    get() {
      return localStorage.getItem(this.STORAGE_KEY) || 'light';
    },

    set(theme) {
      localStorage.setItem(this.STORAGE_KEY, theme);
      document.cookie = `${this.STORAGE_KEY}=${theme}; path=/; max-age=31536000`;
      this.apply(theme);
    },

    apply(theme) {
      if (theme === 'light') {
        document.documentElement.classList.remove('dark');
      } else {
        document.documentElement.classList.add('dark');
      }
    },

    toggle() {
      const current = this.get();
      const next = current === 'dark' ? 'light' : 'dark';
      this.set(next);
      return next;
    },

    isDark() {
      return this.get() === 'dark';
    }
  };

  // Ensure sync
  ThemeEngine.apply(ThemeEngine.get());
  window.ThemeEngine = ThemeEngine;

  /* =====================================================================
     2. SIDEBAR CONTROLLER (Safe Mobile & Desktop Navigation)
     ===================================================================== */
  const SidebarCtrl = {
    sidebar: null,
    overlay: null,

    init() {
      this.sidebar = document.getElementById('app-sidebar');
      this.overlay = document.getElementById('sidebar-overlay');

      // Restore scroll position
      const savedScroll = sessionStorage.getItem('sidebar_scroll');
      const navEl = this.sidebar?.querySelector('.sidebar-nav');
      if (navEl && savedScroll) {
        navEl.scrollTop = parseInt(savedScroll, 10) || 0;
      }
    },

    open() {
      if (!this.sidebar) this.sidebar = document.getElementById('app-sidebar');
      if (!this.overlay) this.overlay = document.getElementById('sidebar-overlay');

      this.sidebar?.classList.add('is-open');
      if (this.overlay) {
        this.overlay.style.display = 'block';
        requestAnimationFrame(() => this.overlay.classList.add('is-visible'));
      }
      document.body.classList.add('sidebar-open');
      document.documentElement.classList.add('sidebar-open');
    },

    close() {
      if (!this.sidebar) this.sidebar = document.getElementById('app-sidebar');
      if (!this.overlay) this.overlay = document.getElementById('sidebar-overlay');

      this.sidebar?.classList.remove('is-open');
      if (this.overlay) {
        this.overlay.classList.remove('is-visible');
        setTimeout(() => { 
          if (this.overlay && !document.body.classList.contains('sidebar-open')) {
            this.overlay.style.display = 'none'; 
          }
        }, 200);
      }
      document.body.classList.remove('sidebar-open');
      document.documentElement.classList.remove('sidebar-open');
    }
  };

  window.SidebarCtrl = SidebarCtrl;
  window.openSidebar = () => SidebarCtrl.open();
  window.closeSidebar = () => SidebarCtrl.close();

  /* =====================================================================
     3. LUCIDE ICONS (Debounced & Scoped)
     ===================================================================== */
  let iconTimer = null;
  function initIcons(target) {
    if (typeof lucide === 'undefined') return;
    if (target && target.nodeType === 1) {
      lucide.createIcons({ el: target });
      return;
    }
    // Global debounced call
    cancelAnimationFrame(iconTimer);
    iconTimer = requestAnimationFrame(() => {
      lucide.createIcons();
    });
  }

  window.initIcons = initIcons;
  window.refreshIcons = initIcons;

  /* =====================================================================
     4. DESKTOP-ONLY PREFETCH ENGINE (No Mobile Network Choking)
     ===================================================================== */
  const PrefetchEngine = {
    prefetched: new Set(),
    timer: null,
    DELAY_MS: 120,

    getValidUrl(target) {
      const a = target.closest?.('a');
      if (!a) return null;

      const href = a.getAttribute('href');
      if (!href) return null;
      if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return null;
      if (a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-no-prefetch')) return null;

      try {
        const url = new URL(href, window.location.href);
        if (url.origin !== window.location.origin) return null;
        if (url.href === window.location.href) return null;
        if (/\.(pdf|xlsx|xls|csv|zip|png|jpg|jpeg|gif|svg|mp3|mp4)$/i.test(url.pathname)) return null;
        return url.href;
      } catch (_) {
        return null;
      }
    },

    prefetch(url) {
      if (!url || this.prefetched.has(url)) return;
      this.prefetched.add(url);
      const link = document.createElement('link');
      link.rel = 'prefetch';
      link.href = url;
      link.as = 'document';
      document.head.appendChild(link);
    },

    init() {
      // Never run prefetch on mobile / touch devices to preserve memory & bandwidth
      if (isTouchDevice || !('fetch' in window)) return;

      document.addEventListener('mouseover', (e) => {
        const url = this.getValidUrl(e.target);
        if (!url) return;
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.prefetch(url), this.DELAY_MS);
      }, { passive: true });

      document.addEventListener('mouseout', () => {
        clearTimeout(this.timer);
      }, { passive: true });
    }
  };

  /* =====================================================================
     5. TOAST NOTIFICATION HELPERS
     ===================================================================== */
  const Toast = {
    container: null,

    getContainer() {
      if (!this.container) {
        this.container = document.getElementById('toast-container');
      }
      return this.container;
    },

    show(message, type = 'success', duration = 4000) {
      const container = this.getContainer();
      if (!container) return;

      const icons = {
        success: 'check-circle',
        error:   'x-circle',
        warning: 'alert-triangle',
        info:    'info'
      };

      const el = document.createElement('div');
      el.className = `toast toast-${type}`;
      el.style.cssText = 'opacity:0; transform:translateY(-8px); transition:opacity 0.2s ease, transform 0.2s ease;';
      el.innerHTML = `
        <i data-lucide="${icons[type] || 'info'}" class="toast-icon"></i>
        <span class="toast-msg">${message}</span>
        <button class="toast-close" type="button" onclick="this.closest('.toast').remove()">
          <i data-lucide="x"></i>
        </button>`;

      container.appendChild(el);
      if (typeof lucide !== 'undefined') lucide.createIcons({ el });

      requestAnimationFrame(() => {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
      });

      if (duration > 0) {
        setTimeout(() => {
          el.style.opacity = '0';
          el.style.transform = 'translateY(-8px)';
          setTimeout(() => el.remove(), 200);
        }, duration);
      }
    },

    success(msg, duration) { this.show(msg, 'success', duration); },
    error(msg, duration)   { this.show(msg, 'error', duration); },
    warning(msg, duration) { this.show(msg, 'warning', duration); },
    info(msg, duration)    { this.show(msg, 'info', duration); }
  };

  window.AppToast = Toast;
  window.toast = Toast;

  /* =====================================================================
     6. APP CONFIRMATION DIALOG (Modern Minimalist Modal)
     ===================================================================== */
  const AppConfirm = (options) => {
    return new Promise((resolve) => {
      let opts = typeof options === 'string' ? { message: options } : (options || {});

      const {
        title = 'Konfirmasi Tindakan',
        message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
        confirmText = 'Konfirmasi',
        cancelText = 'Batal',
        type = 'danger',
        icon = null
      } = opts;

      const existing = document.getElementById('app-confirm-overlay');
      if (existing) existing.remove();

      const typeIcons = {
        danger: 'trash-2',
        warning: 'alert-triangle',
        info: 'info',
        primary: 'help-circle'
      };

      const iconName = icon || typeIcons[type] || 'alert-triangle';
      const typeButtonClass = {
        danger: 'btn-danger',
        warning: 'btn-warning',
        info: 'btn-primary',
        primary: 'btn-primary'
      }[type] || 'btn-danger';

      const iconBadgeColors = {
        danger: 'background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.25);',
        warning: 'background: rgba(245, 158, 11, 0.12); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.25);',
        info: 'background: rgba(59, 130, 246, 0.12); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.25);',
        primary: 'background: rgba(62, 207, 142, 0.14); color: #059669; border: 1px solid rgba(62, 207, 142, 0.3);'
      }[type] || 'background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.25);';

      const overlay = document.createElement('div');
      overlay.id = 'app-confirm-overlay';
      overlay.className = 'confirm-overlay';
      overlay.style.cssText = `
        position: fixed; inset: 0; z-index: 99999;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center;
        padding: 16px; opacity: 0; transition: opacity 0.18s ease;
      `;

      overlay.innerHTML = `
        <div class="confirm-modal" style="
          background: var(--color-canvas);
          border: 1px solid var(--color-hairline);
          border-radius: var(--rounded-xl);
          box-shadow: var(--shadow-3);
          width: 100%; max-width: 410px;
          overflow: hidden;
          transform: scale(0.96) translateY(6px);
          transition: transform 0.18s ease;
        ">
          <div style="padding: 22px 24px 18px;">
            <div style="display: flex; align-items: flex-start; gap: 14px;">
              <div style="width: 40px; height: 40px; border-radius: var(--rounded-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; ${iconBadgeColors}">
                <i data-lucide="${iconName}" style="width: 20px; height: 20px;"></i>
              </div>
              <div style="flex: 1; min-width: 0;">
                <h3 style="font-size: 15px; font-weight: 700; color: var(--color-ink); margin: 0 0 6px 0; line-height: 1.35;">
                  ${title}
                </h3>
                <p style="font-size: 13px; color: var(--color-ink-mute); margin: 0; line-height: 1.5;">
                  ${message}
                </p>
              </div>
            </div>
          </div>

          <div style="
            padding: 13px 20px;
            background: var(--color-canvas-soft);
            border-top: 1px solid var(--color-hairline);
            display: flex; align-items: center; justify-content: flex-end; gap: 10px;
          ">
            <button type="button" id="confirm-btn-cancel" class="btn btn-secondary" style="padding: 7px 14px; font-size: 12.5px; font-weight: 600;">
              ${cancelText}
            </button>
            <button type="button" id="confirm-btn-ok" class="btn ${typeButtonClass}" style="padding: 7px 16px; font-size: 12.5px; font-weight: 700;">
              ${confirmText}
            </button>
          </div>
        </div>
      `;

      document.body.appendChild(overlay);
      if (typeof lucide !== 'undefined') lucide.createIcons({ el: overlay });

      const modalEl = overlay.querySelector('.confirm-modal');
      const btnCancel = overlay.querySelector('#confirm-btn-cancel');
      const btnOk = overlay.querySelector('#confirm-btn-ok');

      requestAnimationFrame(() => {
        overlay.style.opacity = '1';
        modalEl.style.transform = 'scale(1) translateY(0)';
        btnOk.focus();
      });

      const closeDialog = (result) => {
        overlay.style.opacity = '0';
        modalEl.style.transform = 'scale(0.96) translateY(4px)';
        document.removeEventListener('keydown', handleKey);
        setTimeout(() => {
          overlay.remove();
          resolve(result);
        }, 160);
      };

      const handleKey = (e) => {
        if (e.key === 'Escape') {
          e.preventDefault();
          closeDialog(false);
        } else if (e.key === 'Enter' && document.activeElement !== btnCancel) {
          e.preventDefault();
          closeDialog(true);
        }
      };

      document.addEventListener('keydown', handleKey);
      btnCancel.addEventListener('click', () => closeDialog(false));
      btnOk.addEventListener('click', () => closeDialog(true));
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeDialog(false);
      });
    });
  };

  window.AppConfirm = AppConfirm;
  window.confirmModal = AppConfirm;

  /* =====================================================================
     7. ZERO-OVERHEAD EVENT-DELEGATED RUPIAH FORMATTER (No MutationObserver)
     ===================================================================== */
  const RupiahFormatter = {
    format(val, prefix = '') {
      if (val === null || val === undefined || val === '') return '';
      let numVal;
      if (typeof val === 'number') {
        numVal = Math.round(val);
      } else {
        const strVal = String(val).trim();
        // If string contains decimal from DB like "15000.00"
        if (/^\d+\.\d{1,2}$/.test(strVal)) {
          numVal = Math.round(parseFloat(strVal));
        } else {
          const cleanStr = strVal.replace(/[^0-9]/g, '');
          if (!cleanStr) return '';
          numVal = parseInt(cleanStr, 10) || 0;
        }
      }
      const formatted = String(numVal).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      return prefix ? `${prefix} ${formatted}` : formatted;
    },

    unformat(val) {
      if (val === null || val === undefined || val === '') return 0;
      if (typeof val === 'number') return Math.round(val);
      const strVal = String(val).trim();
      if (/^\d+\.\d{1,2}$/.test(strVal)) {
        return Math.round(parseFloat(strVal));
      }
      const clean = strVal.replace(/[^0-9]/g, '');
      return parseInt(clean, 10) || 0;
    },

    handleInput(e) {
      const input = e.target;
      if (!input || !input.matches) return;
      if (!input.matches('input.input-rupiah, input.input-currency, input[data-rupiah], input[data-currency]')) return;

      const originalValue = input.value;
      const cursorPosition = input.selectionStart || 0;
      const digitsBeforeCursor = originalValue.slice(0, cursorPosition).replace(/[^0-9]/g, '').length;

      const rawNumbers = originalValue.replace(/[^0-9]/g, '');
      const formatted = RupiahFormatter.format(rawNumbers);
      input.value = formatted;

      let newCursorPos = 0;
      let digitCount = 0;
      for (let i = 0; i < formatted.length; i++) {
        if (/[0-9]/.test(formatted[i])) digitCount++;
        if (digitCount === digitsBeforeCursor) {
          newCursorPos = i + 1;
          break;
        }
      }
      if (digitsBeforeCursor === 0) newCursorPos = 0;
      input.setSelectionRange(newCursorPos, newCursorPos);

      input.dispatchEvent(new CustomEvent('rupiah-change', {
        bubbles: true,
        detail: {
          raw: RupiahFormatter.unformat(formatted),
          formatted: formatted
        }
      }));
    },

    init() {
      // Zero MutationObserver! Pure single event-listener on document
      document.addEventListener('input', this.handleInput, { passive: true });
    }
  };

  window.RupiahFormatter = RupiahFormatter;
  window.formatRupiah = (val) => 'Rp ' + Number(val || 0).toLocaleString('id-ID');
  window.formatRupiahNumber = (val) => RupiahFormatter.format(val);
  window.unformatRupiah = (val) => RupiahFormatter.unformat(val);

  /* =====================================================================
     8. DESKTOP-ONLY GRAB-TO-SCROLL (Touch Devices Use 100% Native Momentum)
     ===================================================================== */
  const TableGrabScroll = {
    init() {
      if (isTouchDevice) return; // Never intercept touch devices

      let activeContainer = null;
      let startX = 0;
      let scrollLeft = 0;
      let isDragging = false;

      document.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        if (e.target.closest('input, button, a, select, textarea, label, [role="button"], .modal-box')) return;

        const container = e.target.closest('.table-scroll, .overflow-x-auto');
        if (!container || container.scrollWidth <= container.clientWidth) return;

        activeContainer = container;
        isDragging = false;
        container.classList.add('is-grabbing');
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
      });

      document.addEventListener('mousemove', (e) => {
        if (!activeContainer) return;
        e.preventDefault();
        const x = e.pageX - activeContainer.offsetLeft;
        const walk = (x - startX) * 1.3;
        if (Math.abs(walk) > 4) isDragging = true;
        activeContainer.scrollLeft = scrollLeft - walk;
      });

      const stopDrag = () => {
        if (activeContainer) {
          activeContainer.classList.remove('is-grabbing');
          activeContainer = null;
          setTimeout(() => { isDragging = false; }, 60);
        }
      };

      document.addEventListener('mouseup', stopDrag);
      document.addEventListener('mouseleave', stopDrag);

      document.addEventListener('click', (e) => {
        if (isDragging) {
          e.preventDefault();
          e.stopPropagation();
        }
      }, true);
    }
  };

  /* =====================================================================
     9. SAFARI / IOS BFCACHE PROTECTION
     ===================================================================== */
  window.addEventListener('pageshow', (e) => {
    if (e.persisted) window.location.reload();
  });

  /* =====================================================================
     10. INITIALIZATION ON DOM READY & CSRF INTERCEPTOR
     ===================================================================== */
  if (typeof window.fetch === 'function') {
    const originalFetch = window.fetch;
    window.fetch = function (resource, init = {}) {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      if (token) {
        init.headers = init.headers || {};
        if (init.headers instanceof Headers) {
          if (!init.headers.has('X-CSRF-TOKEN')) init.headers.set('X-CSRF-TOKEN', token);
        } else if (Array.isArray(init.headers)) {
          if (!init.headers.some(([k]) => k.toLowerCase() === 'x-csrf-token')) {
            init.headers.push(['X-CSRF-TOKEN', token]);
          }
        } else {
          if (!init.headers['X-CSRF-TOKEN']) init.headers['X-CSRF-TOKEN'] = token;
        }
      }
      return originalFetch.call(this, resource, init);
    };
  }

  // Global CSRF & Double-Submit Interceptor
  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form && form.tagName === 'FORM' && form.method && form.method.toUpperCase() === 'POST') {
      if (!form.querySelector('input[name="csrf_token"]')) {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'csrf_token';
          input.value = token;
          form.appendChild(input);
        }
      }

      // Prevent double submit
      const submitBtn = form.querySelector('button[type="submit"]:not([disabled])');
      if (submitBtn && !form.hasAttribute('data-no-disable')) {
        setTimeout(() => {
          submitBtn.disabled = true;
          submitBtn.style.opacity = '0.7';
          submitBtn.style.cursor = 'not-allowed';
        }, 20);
      }
    }
  }, true);

  /* =====================================================================
     9. PROGRESSIVE WEB APP (PWA) SERVICE WORKER REGISTRATION
     ===================================================================== */
  const PWAEngine = {
    init() {
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          const swPath = (window.APP_BASE_PATH || '') + '/sw.js';
          navigator.serviceWorker.register(swPath).then((reg) => {
            // SW active
          }).catch(() => {
            navigator.serviceWorker.register('./sw.js').catch(() => {});
          });
        });
      }
    }
  };

  function onReady(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  onReady(() => {
    initIcons();
    SidebarCtrl.init();
    PrefetchEngine.init();
    RupiahFormatter.init();
    TableGrabScroll.init();
    PWAEngine.init();
  });

})();


