/**
 * KEREN SNACK ERP — Global JavaScript
 * Consolidates: Lucide icons, theme toggle, sidebar, hover prefetch
 * Version: 3.0 | 2026
 */

(function () {
  'use strict';

  /* =====================================================================
     1. THEME ENGINE (Zero-Flash Light/Dark)
     ===================================================================== */
  const ThemeEngine = {
    STORAGE_KEY: 'ksnack_theme',

    get() {
      return localStorage.getItem(this.STORAGE_KEY) || 'dark';
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

  // Apply immediately on script load (inline script handles before CSS, this ensures sync)
  ThemeEngine.apply(ThemeEngine.get());

  // Expose to window for Alpine.js / inline handlers
  window.ThemeEngine = ThemeEngine;

  /* =====================================================================
     2. SIDEBAR CONTROLLER
     ===================================================================== */
  const SidebarCtrl = {
    sidebar: null,
    overlay: null,

    init() {
      this.sidebar = document.getElementById('app-sidebar');
      this.overlay = document.getElementById('sidebar-overlay');

      if (!this.sidebar) return;

      // Restore scroll position
      const savedScroll = sessionStorage.getItem('sidebar_scroll');
      const navEl = this.sidebar.querySelector('.sidebar-nav');
      if (navEl && savedScroll) {
        navEl.scrollTop = parseInt(savedScroll, 10) || 0;
      }
    },

    open() {
      if (!this.sidebar) return;
      this.sidebar.classList.add('is-open');
      if (this.overlay) {
        this.overlay.style.display = 'block';
        requestAnimationFrame(() => this.overlay.classList.add('is-visible'));
      }
      document.body.style.overflow = 'hidden';
    },

    close() {
      if (!this.sidebar) return;
      this.sidebar.classList.remove('is-open');
      if (this.overlay) {
        this.overlay.classList.remove('is-visible');
        setTimeout(() => { if (this.overlay) this.overlay.style.display = 'none'; }, 200);
      }
      document.body.style.overflow = '';
    },

    saveScroll() {
      const navEl = this.sidebar?.querySelector('.sidebar-nav');
      if (navEl) {
        sessionStorage.setItem('sidebar_scroll', navEl.scrollTop);
      }
    }
  };

  window.SidebarCtrl = SidebarCtrl;

  /* =====================================================================
     3. LUCIDE ICONS INITIALIZER
     ===================================================================== */
  function initIcons() {
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  }

  /* =====================================================================
     4. HOVER PREFETCH ENGINE (Sub-ms Instant Navigation)
     ===================================================================== */
  const PrefetchEngine = {
    prefetched: new Set(),
    timer: null,
    DELAY_MS: 65,

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
      if (!('fetch' in window)) return;

      document.addEventListener('mouseover', (e) => {
        const url = this.getValidUrl(e.target);
        if (!url) return;
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.prefetch(url), this.DELAY_MS);
      }, { passive: true });

      document.addEventListener('mouseout', () => {
        clearTimeout(this.timer);
      }, { passive: true });

      document.addEventListener('touchstart', (e) => {
        const url = this.getValidUrl(e.target);
        if (url) this.prefetch(url);
      }, { passive: true });

      // Save sidebar scroll on link click
      document.addEventListener('mousedown', (e) => {
        const link = e.target.closest('.sidebar-link');
        if (link) {
          const navEl = document.querySelector('.sidebar-nav');
          if (navEl) sessionStorage.setItem('sidebar_scroll', navEl.scrollTop);
        }
      }, { passive: true });
    }
  };

  /* =====================================================================
     5. TOAST / FLASH NOTIFICATION HELPERS
     ===================================================================== */
  const Toast = {
    container: null,

    getContainer() {
      if (!this.container) {
        this.container = document.getElementById('toast-container');
      }
      return this.container;
    },

    show(message, type = 'success', duration = 5000) {
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
      el.style.cssText = 'opacity:0; transform:translateX(12px); transition:opacity 0.2s,transform 0.2s;';
      el.innerHTML = `
        <i data-lucide="${icons[type] || 'info'}" class="toast-icon"></i>
        <span class="toast-msg">${message}</span>
        <button class="toast-close" onclick="this.closest('.toast').remove()">
          <i data-lucide="x"></i>
        </button>`;

      container.appendChild(el);
      if (typeof lucide !== 'undefined') lucide.createIcons({ el });

      requestAnimationFrame(() => {
        el.style.opacity = '1';
        el.style.transform = 'translateX(0)';
      });

      if (duration > 0) {
        setTimeout(() => {
          el.style.opacity = '0';
          el.style.transform = 'translateX(12px)';
          setTimeout(() => el.remove(), 200);
        }, duration);
      }
    }
  };

  window.AppToast = Toast;

  /* =====================================================================
     6. APP CONFIRMATION DIALOG (Modern Minimalist Modal)
     ===================================================================== */
  const AppConfirm = (options) => {
    return new Promise((resolve) => {
      let opts = {};
      if (typeof options === 'string') {
        opts = { message: options };
      } else {
        opts = options || {};
      }

      const {
        title = 'Konfirmasi Tindakan',
        message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
        confirmText = 'Konfirmasi',
        cancelText = 'Batal',
        type = 'danger', // 'danger' | 'warning' | 'info' | 'primary'
        icon = null
      } = opts;

      // Remove any existing confirm modal
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
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center;
        padding: 16px; opacity: 0; transition: opacity 0.18s cubic-bezier(0.16, 1, 0.3, 1);
      `;

      overlay.innerHTML = `
        <div class="confirm-modal" style="
          background: var(--color-canvas);
          border: 1px solid var(--color-hairline);
          border-radius: var(--rounded-xl);
          box-shadow: var(--shadow-3);
          width: 100%; max-width: 410px;
          overflow: hidden;
          transform: scale(0.95) translateY(6px);
          transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        ">
          <div style="padding: 22px 24px 18px;">
            <div style="display: flex; align-items: flex-start; gap: 14px;">
              <div style="width: 40px; height: 40px; border-radius: var(--rounded-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; ${iconBadgeColors}">
                <i data-lucide="${iconName}" style="width: 20px; height: 20px;"></i>
              </div>
              <div style="flex: 1; min-width: 0;">
                <h3 style="font-size: 15px; font-weight: 700; color: var(--color-ink); margin: 0 0 6px 0; line-height: 1.35; letter-spacing: -0.01em;">
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

      // Animate in
      requestAnimationFrame(() => {
        overlay.style.opacity = '1';
        modalEl.style.transform = 'scale(1) translateY(0)';
        btnOk.focus();
      });

      const closeDialog = (result) => {
        overlay.style.opacity = '0';
        modalEl.style.transform = 'scale(0.95) translateY(4px)';
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
     6. ALPINE.JS HELPERS (exposed for x-data bindings)
     ===================================================================== */
  window.appHelpers = {
    toggleTheme() {
      const next = ThemeEngine.toggle();
      // Re-render Lucide icons after theme change
      setTimeout(initIcons, 50);
      return next;
    },
    isDark() {
      return ThemeEngine.isDark();
    }
  };

  /* =====================================================================
     7. BFCACHE PROTECTION (Safari/iOS back-button)
     ===================================================================== */
  window.addEventListener('pageshow', (e) => {
    if (e.persisted) window.location.reload();
  });

  /* =====================================================================
     8. DOM READY INIT
     ===================================================================== */
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

    // Expose burger & overlay click handlers globally
    window.openSidebar  = () => SidebarCtrl.open();
    window.closeSidebar = () => SidebarCtrl.close();
  });

})();
