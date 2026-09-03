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

  const AppAlert = (options) => {
    return new Promise((resolve) => {
      let opts = typeof options === 'string' ? { message: options } : (options || {});
      const {
        title = 'Informasi Sistem',
        message = '',
        buttonText = 'Mengerti',
        type = 'info',
        icon = null
      } = opts;

      const existing = document.getElementById('app-confirm-overlay');
      if (existing) existing.remove();

      const typeIcons = {
        danger: 'alert-circle',
        warning: 'alert-triangle',
        info: 'info',
        success: 'check-circle-2',
        primary: 'info'
      };

      const iconName = icon || typeIcons[type] || 'info';
      const iconBadgeColors = {
        danger: 'background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.25);',
        warning: 'background: rgba(245, 158, 11, 0.12); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.25);',
        info: 'background: rgba(59, 130, 246, 0.12); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.25);',
        success: 'background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25);',
        primary: 'background: rgba(37, 99, 235, 0.12); color: #2563eb; border: 1px solid rgba(37, 99, 235, 0.25);'
      }[type] || 'background: rgba(59, 130, 246, 0.12); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.25);';

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
                <p style="font-size: 13px; color: var(--color-ink-mute); margin: 0; line-height: 1.5; white-space: pre-line;">
                  ${message}
                </p>
              </div>
            </div>
          </div>

          <div style="
            padding: 13px 20px;
            background: var(--color-canvas-soft);
            border-top: 1px solid var(--color-hairline);
            display: flex; align-items: center; justify-content: flex-end;
          ">
            <button type="button" id="alert-btn-ok" class="btn btn-primary" style="padding: 7px 18px; font-size: 12.5px; font-weight: 700;">
              ${buttonText}
            </button>
          </div>
        </div>
      `;

      document.body.appendChild(overlay);
      if (typeof lucide !== 'undefined') lucide.createIcons({ el: overlay });

      const modalEl = overlay.querySelector('.confirm-modal');
      const btnOk = overlay.querySelector('#alert-btn-ok');

      requestAnimationFrame(() => {
        overlay.style.opacity = '1';
        modalEl.style.transform = 'scale(1) translateY(0)';
        btnOk.focus();
      });

      const closeDialog = () => {
        overlay.style.opacity = '0';
        modalEl.style.transform = 'scale(0.96) translateY(4px)';
        document.removeEventListener('keydown', handleKey);
        setTimeout(() => {
          overlay.remove();
          resolve(true);
        }, 160);
      };

      const handleKey = (e) => {
        if (e.key === 'Escape' || e.key === 'Enter') {
          e.preventDefault();
          closeDialog();
        }
      };

      document.addEventListener('keydown', handleKey);
      btnOk.addEventListener('click', () => closeDialog());
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeDialog();
      });
    });
  };

  // Global Declarative data-confirm form submit interceptor
  document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (form && form.hasAttribute && form.hasAttribute('data-confirm')) {
      e.preventDefault();
      const message = form.getAttribute('data-confirm');
      const title = form.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
      const type = form.getAttribute('data-confirm-type') || 'danger';
      const icon = form.getAttribute('data-confirm-icon') || null;
      const confirmText = form.getAttribute('data-confirm-btn') || 'Konfirmasi';
      const cancelText = form.getAttribute('data-confirm-cancel') || 'Batal';

      const confirmed = await AppConfirm({
        title,
        message,
        type,
        icon,
        confirmText,
        cancelText
      });

      if (confirmed) {
        form.removeAttribute('data-confirm'); // prevent infinite loop
        AppAction.show(form.getAttribute('data-action-text') || 'Memproses data...');
        form.submit();
      }
    }
  });

  window.AppConfirm = AppConfirm;
  window.confirmModal = AppConfirm;
  window.AppAlert = AppAlert;
  window.alertModal = AppAlert;

  /* =====================================================================
     6.1 SKELETON SCREEN & ACTION PROCESSING DUAL-ENGINE
     ===================================================================== */
  
  // 1. AppSkeleton: Khusus untuk Navigasi Antar Halaman & Refresh
  const AppSkeleton = {
    safetyTimer: null,

    show(text = 'Memuat halaman...') {
      const loader = document.getElementById('app-page-skeleton') || document.getElementById('app-page-loader');
      const textEl = document.getElementById('app-page-skeleton-text') || document.getElementById('app-page-loader-text');
      if (!loader) return;

      if (textEl && text) {
        textEl.textContent = text;
      }

      clearTimeout(this.safetyTimer);
      loader.classList.add('is-active');

      this.safetyTimer = setTimeout(() => {
        this.hide();
      }, 10000);
    },

    hide() {
      clearTimeout(this.safetyTimer);
      const loader = document.getElementById('app-page-skeleton') || document.getElementById('app-page-loader');
      if (loader) {
        loader.classList.remove('is-active');
      }
    }
  };

  // 2. AppAction: Khusus untuk Simpan Data, Update, Checkout POS & Mutasi Data (Dual-Ring Glow -> Morph Checkmark / Error X)
  const AppAction = {
    safetyTimer: null,

    show(text = 'Menyimpan data...') {
      try {
        sessionStorage.setItem('app_action_triggered', 'true');
      } catch (e) {}

      const loader = document.getElementById('app-action-loader');
      const textEl = document.getElementById('app-action-loader-text');
      if (!loader) return;

      clearTimeout(this.safetyTimer);
      loader.classList.remove('is-success', 'is-error');

      if (textEl && text) {
        textEl.textContent = text;
      }

      loader.classList.add('is-active');

      this.safetyTimer = setTimeout(() => {
        this.hide();
      }, 15000);
    },

    success(text = 'Berhasil Disimpan! ✨', duration = 850) {
      try {
        sessionStorage.removeItem('app_action_triggered');
      } catch (e) {}

      return new Promise((resolve) => {
        const loader = document.getElementById('app-action-loader');
        const textEl = document.getElementById('app-action-loader-text');
        if (!loader) {
          resolve();
          return;
        }

        clearTimeout(this.safetyTimer);
        loader.classList.remove('is-error');

        if (textEl && text) {
          textEl.textContent = text;
        }

        loader.classList.add('is-active', 'is-success');

        setTimeout(() => {
          this.hide();
          resolve();
        }, duration);
      });
    },

    error(text = 'Gagal memproses data!', duration = 1400) {
      try {
        sessionStorage.removeItem('app_action_triggered');
      } catch (e) {}

      return new Promise((resolve) => {
        const loader = document.getElementById('app-action-loader');
        const textEl = document.getElementById('app-action-loader-text');
        if (!loader) {
          resolve();
          return;
        }

        clearTimeout(this.safetyTimer);
        loader.classList.remove('is-success');

        if (textEl && text) {
          textEl.textContent = text;
        }

        loader.classList.add('is-active', 'is-error');

        setTimeout(() => {
          this.hide();
          resolve();
        }, duration);
      });
    },

    fail(text, duration) {
      return this.error(text, duration);
    },

    hide() {
      clearTimeout(this.safetyTimer);
      const loader = document.getElementById('app-action-loader');
      if (loader) {
        loader.classList.remove('is-active');
        setTimeout(() => {
          loader.classList.remove('is-success', 'is-error');
        }, 250);
      }
    }
  };

  // Global Exports
  window.AppSkeleton = AppSkeleton;
  window.AppAction = AppAction;
  window.ActionLoader = AppAction;
  window.AppLoading = AppSkeleton; // backwards-compatible

  // Lifecycle Handlers: Smooth Reveal & Dismiss on Page Ready / Refresh with Kinetic Morphing
  let isInitialSkeletonDismissed = false;
  const dismissInitialSkeleton = () => {
    if (isInitialSkeletonDismissed) return;
    isInitialSkeletonDismissed = true;

    // Check whether a form/action was explicitly submitted (for page-navigation uses)
    let hadAction = false;
    try {
      hadAction = sessionStorage.getItem('app_action_triggered') === 'true';
      sessionStorage.removeItem('app_action_triggered');
    } catch (e) {}

    const flash = window.__FLASH__;
    console.log('dismissInitialSkeleton: flash =', flash);

    // Always animate AppAction if there's a server flash — regardless of hadAction.
    // This covers: data-confirm forms, modal-submit forms, Alpine-rendered forms,
    // and any redirect from a backend action that produces a flash message.
    if (flash && (flash.type === 'error' || flash.type === 'danger')) {
      AppSkeleton.hide();
      // Suppress corner toast so the animated orb is the sole feedback channel
      _suppressToast();
      AppAction.error(flash.message || 'Terjadi Kesalahan!', 1600);
    } else if (flash && flash.type === 'success') {
      AppSkeleton.hide();
      _suppressToast();
      AppAction.success(flash.message || 'Berhasil! ✨', 1200);
    } else if (flash && (flash.type === 'warning' || flash.type === 'info')) {
      AppSkeleton.hide();
      // For warnings and info, keep the corner toast (they are informational, not action results)
      _showToast();
      setTimeout(() => { AppAction.hide(); }, 100);
    } else {
      setTimeout(() => {
        AppSkeleton.hide();
        AppAction.hide();
      }, 150);
    }
  };

  // Suppress the PHP-flash corner toast element so the kinetic orb is the sole feedback channel
  function _suppressToast() {
    try {
      const el = document.getElementById('php-flash-toast');
      if (el) {
        el.style.transition = 'none';
        el.style.opacity = '0';
        setTimeout(() => { if (el.parentNode) el.remove(); }, 10);
      }
    } catch (e) {}
  }

  // Re-show the PHP-flash corner toast for non-action feedback (info/warning)
  function _showToast() {
    try {
      const el = document.getElementById('php-flash-toast');
      if (el) {
        el.style.animation = 'toastSlideIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards';
        el.style.pointerEvents = '';
        el.style.opacity = '';
        // Auto-hide after 5 seconds
        setTimeout(() => {
          if (el.parentNode) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px) scale(0.96)';
            el.style.transition = 'all 0.2s ease';
            setTimeout(() => { if (el.parentNode) el.remove(); }, 200);
          }
        }, 5000);
      }
    } catch (e) {}
  }

  if (document.readyState === 'complete') {
    dismissInitialSkeleton();
  } else {
    window.addEventListener('load', dismissInitialSkeleton);
    document.addEventListener('DOMContentLoaded', () => {
      setTimeout(dismissInitialSkeleton, 80);
    });
  }

  // Restore on bfcache (Back/Forward navigation)
  window.addEventListener('pageshow', (event) => {
    AppSkeleton.hide();
    AppAction.hide();
  });

  // Intercept Refresh / Page Reload (F5, Ctrl+R, Reload button)
  window.addEventListener('beforeunload', () => {
    const loader = document.getElementById('app-page-skeleton') || document.getElementById('app-page-loader');
    if (loader) {
      loader.classList.add('is-active');
    }
  });

  // Intercept standard internal links -> Trigger Skeleton Screen
  document.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (!link) return;

    const href = link.getAttribute('href');
    const target = link.getAttribute('target');
    const isDownload = link.hasAttribute('download');
    const isNoLoader = link.classList.contains('no-loader') || link.getAttribute('data-no-loader') === 'true';
    const hasConfirm = link.hasAttribute('data-confirm') || link.getAttribute('onclick')?.includes('confirm');

    // Ignore anchors, javascript, mailto, tel, new tabs, downloads, confirms, or key modifiers
    if (!href || href.startsWith('#') || href.startsWith('javascript:') ||
        href.startsWith('mailto:') || href.startsWith('tel:') ||
        target === '_blank' || isDownload || isNoLoader || hasConfirm ||
        e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) {
      return;
    }

    try {
      const url = new URL(link.href, window.location.origin);
      if (url.origin === window.location.origin) {
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
          return;
        }
        AppSkeleton.show('Memuat halaman...');
      }
    } catch (err) {}
  });

  // Intercept standard form submissions -> Trigger Action Blur Processing Loader
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (e.defaultPrevented || !form || form.hasAttribute('data-confirm') || form.classList.contains('no-loader') || form.getAttribute('target') === '_blank') {
      return;
    }
    let customText = form.getAttribute('data-action-text');
    if (!customText) {
      const formAction = (form.getAttribute('action') || '').toLowerCase();
      const formId = (form.id || '').toLowerCase();
      if (formAction.includes('/login') || formId.includes('login')) {
        customText = 'Memverifikasi akun...';
      } else if (formAction.includes('/logout') || formId.includes('logout')) {
        customText = 'Keluar sistem...';
      } else if (formAction.includes('delete') || formAction.includes('hapus')) {
        customText = 'Menghapus data...';
      } else if (formAction.includes('update') || formAction.includes('edit')) {
        customText = 'Memperbarui data...';
      } else {
        customText = 'Menyimpan data...';
      }
    }
    AppAction.show(customText);
  });

  // Escape key cancels loaders in emergency
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      AppSkeleton.hide();
      AppAction.hide();
    }
  });

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
     8. GLOBAL MOUSE DRAG & GRAB-TO-SCROLL (Desktop & Touchscreen Laptops)
     ===================================================================== */
  const TableGrabScroll = {
    init() {
      let activeContainer = null;
      let startX = 0;
      let startY = 0;
      let scrollLeft = 0;
      let isDragging = false;
      let dragDistance = 0;

      // Find scrollable container for target
      const findScrollContainer = (target) => {
        if (!target || typeof target.closest !== 'function') return null;
        const container = target.closest('.table-scroll, .overflow-x-auto, .table-responsive, .custom-scrollbar, [data-table-scroll], .cat-scroll-track, .table-grab-container, [style*="overflow-x: auto"], [style*="overflow-x:auto"]');
        if (container && container.scrollWidth > container.clientWidth + 2) {
          return container;
        }
        return null;
      };

      // Auto mark all horizontally scrollable containers
      const updateScrollableContainers = () => {
        const candidates = document.querySelectorAll('.table-scroll, .overflow-x-auto, .table-responsive, .custom-scrollbar, [data-table-scroll], .cat-scroll-track, .table-grab-container, table');
        candidates.forEach(el => {
          const scrollParent = el.tagName === 'TABLE' ? el.parentElement : el;
          if (scrollParent && scrollParent.scrollWidth > scrollParent.clientWidth + 2) {
            scrollParent.classList.add('table-grab-container', 'is-scrollable');
          } else if (scrollParent) {
            scrollParent.classList.remove('is-scrollable');
          }
        });
      };

      updateScrollableContainers();
      window.addEventListener('resize', updateScrollableContainers, { passive: true });

      try {
        const observer = new MutationObserver(() => {
          updateScrollableContainers();
        });
        observer.observe(document.body, { childList: true, subtree: true });
      } catch (e) {}

      // Mouse Down
      document.addEventListener('mousedown', (e) => {
        // Only primary mouse button (left click)
        if (e.button !== 0) return;

        // Never intercept input typing or textareas or searchable select search box
        if (e.target.closest('input, textarea, select, .sd-search')) {
          return;
        }

        const container = findScrollContainer(e.target);
        if (!container) return;

        activeContainer = container;
        isDragging = false;
        dragDistance = 0;
        startX = e.clientX;
        startY = e.clientY;
        scrollLeft = container.scrollLeft;
      });

      // Mouse Move
      document.addEventListener('mousemove', (e) => {
        if (!activeContainer) return;

        // Verify left mouse button is still held down
        if ((e.buttons & 1) === 0) {
          stopDrag();
          return;
        }

        const deltaX = e.clientX - startX;
        const deltaY = e.clientY - startY;

        // Start dragging after 4px movement
        if (!isDragging && Math.abs(deltaX) > 4 && Math.abs(deltaX) > Math.abs(deltaY)) {
          isDragging = true;
          activeContainer.classList.add('is-grabbing');
          document.body.style.userSelect = 'none';
        }

        if (isDragging) {
          e.preventDefault();
          dragDistance += Math.abs(deltaX);
          activeContainer.scrollLeft = scrollLeft - deltaX;
        }
      });

      // Stop Drag
      const stopDrag = () => {
        if (activeContainer) {
          activeContainer.classList.remove('is-grabbing');
          document.body.style.userSelect = '';
          activeContainer = null;
          
          if (isDragging) {
            // Keep isDragging flag true momentarily to suppress immediate click event
            setTimeout(() => {
              isDragging = false;
              dragDistance = 0;
            }, 60);
          } else {
            isDragging = false;
            dragDistance = 0;
          }
        }
      };

      document.addEventListener('mouseup', stopDrag);
      window.addEventListener('blur', stopDrag);

      // Intercept and suppress accidental clicks after dragging
      document.addEventListener('click', (e) => {
        if (isDragging || dragDistance > 4) {
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


