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
    navEl: null,

    saveScroll() {
      if (this.navEl) {
        try {
          sessionStorage.setItem('sidebar_scroll', this.navEl.scrollTop);
        } catch (e) {}
      }
    },

    ensureActiveVisible() {
      if (!this.navEl) return;
      const activeEl = this.navEl.querySelector('.sidebar-link.is-active');
      if (!activeEl) return;

      const navRect = this.navEl.getBoundingClientRect();
      const activeRect = activeEl.getBoundingClientRect();

      // Check if active item is outside or partially cut off vertically
      const isAbove = activeRect.top < navRect.top;
      const isBelow = activeRect.bottom > navRect.bottom;

      if (isAbove || isBelow) {
        activeEl.scrollIntoView({ block: 'nearest', behavior: 'instant' });
        this.saveScroll();
      }
    },

    init() {
      this.sidebar = document.getElementById('app-sidebar');
      this.overlay = document.getElementById('sidebar-overlay');
      this.navEl = this.sidebar?.querySelector('.sidebar-nav');

      if (!this.navEl) return;

      // 1. Restore scroll position from session storage
      try {
        const savedScroll = sessionStorage.getItem('sidebar_scroll');
        if (savedScroll !== null) {
          this.navEl.scrollTop = parseInt(savedScroll, 10) || 0;
        }
      } catch (e) {}

      // 2. Ensure active menu item is visible inside the viewport
      this.ensureActiveVisible();

      // 3. Persist scroll position continuously using passive listener
      let scrollTimer = null;
      this.navEl.addEventListener('scroll', () => {
        if (!scrollTimer) {
          scrollTimer = requestAnimationFrame(() => {
            this.saveScroll();
            scrollTimer = null;
          });
        }
      }, { passive: true });

      // 4. Save immediately when clicking any link inside sidebar
      this.navEl.addEventListener('click', (e) => {
        if (e.target.closest('a')) {
          this.saveScroll();
        }
      });

      // 5. Save on page unload / page hide as final safeguard
      window.addEventListener('beforeunload', () => {
        this.saveScroll();
      });
      window.addEventListener('pagehide', () => {
        this.saveScroll();
      });
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

      // In mobile mode, ensure active link is visible inside opened drawer
      setTimeout(() => this.ensureActiveVisible(), 50);
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
  /* =====================================================================
     6. APP CONFIRMATION & ALERT DIALOG (Modern, High-Clarity, Responsive)
     ===================================================================== */
  const AppConfirm = (options) => {
    return new Promise((resolve) => {
      let opts = typeof options === 'string' ? { message: options } : (options || {});

      const {
        title = 'Konfirmasi Tindakan',
        message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
        submessage = '',
        accountInfo = null,
        confirmText = 'Konfirmasi',
        cancelText = 'Batal',
        type = 'danger',
        icon = null,
        confirmIcon = null,
        cancelIcon = null,
        showCancelBtn = true,
        showCloseBtn = (opts.showCloseBtn !== undefined ? Boolean(opts.showCloseBtn) : !showCancelBtn),
        defaultFocus = 'confirm'
      } = opts;

      const existing = document.getElementById('app-confirm-overlay');
      if (existing) existing.remove();

      const typeIcons = {
        danger: 'trash-2',
        warning: 'alert-triangle',
        info: 'info',
        primary: 'help-circle',
        success: 'check-circle-2'
      };

      const iconName = icon || typeIcons[type] || 'alert-triangle';

      const overlay = document.createElement('div');
      overlay.id = 'app-confirm-overlay';
      overlay.className = 'confirm-overlay';

      overlay.innerHTML = `
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title">
          <div class="confirm-modal-body">
            <div class="confirm-header-row">
              <div class="confirm-icon-box confirm-icon-${type}">
                <i data-lucide="${iconName}"></i>
              </div>
              ${showCloseBtn ? `
              <button type="button" id="confirm-btn-close" class="confirm-close-btn" aria-label="Tutup dialog" title="Tutup">
                <i data-lucide="x"></i>
              </button>` : ''}
            </div>

            <h3 id="confirm-dialog-title" class="confirm-title">${title}</h3>
            <p class="confirm-message">${message}</p>

            ${accountInfo ? `
            <div class="confirm-account-pill">
              <i data-lucide="user-check"></i>
              <span>Akun Aktif: <strong>${accountInfo}</strong></span>
            </div>` : ''}

            ${submessage ? `
            <div class="confirm-subnotice">
              <i data-lucide="alert-circle"></i>
              <span>${submessage}</span>
            </div>` : ''}
          </div>

          <div class="confirm-modal-footer">
            ${showCancelBtn ? `
            <button type="button" id="confirm-btn-cancel" class="confirm-btn-cancel">
              ${cancelIcon ? `<i data-lucide="${cancelIcon}"></i>` : ''}
              <span>${cancelText}</span>
            </button>` : ''}
            <button type="button" id="confirm-btn-ok" class="confirm-btn-action btn-action-${type}">
              ${confirmIcon ? `<i data-lucide="${confirmIcon}"></i>` : ''}
              <span>${confirmText}</span>
            </button>
          </div>
        </div>
      `;

      document.body.appendChild(overlay);
      if (window.PopupManager) window.PopupManager.freeze(overlay);
      if (typeof lucide !== 'undefined') lucide.createIcons({ el: overlay });

      const modalEl = overlay.querySelector('.confirm-modal');
      const btnCancel = overlay.querySelector('#confirm-btn-cancel');
      const btnOk = overlay.querySelector('#confirm-btn-ok');
      const btnClose = overlay.querySelector('#confirm-btn-close');

      requestAnimationFrame(() => {
        overlay.style.opacity = '1';
        modalEl.style.transform = 'scale(1) translateY(0)';
        if (defaultFocus === 'cancel' && btnCancel) {
          btnCancel.focus();
        } else if (btnOk) {
          btnOk.focus();
        }
      });

      const closeDialog = (result) => {
        if (window.PopupManager) window.PopupManager.unfreeze(overlay);
        overlay.style.opacity = '0';
        modalEl.style.transform = 'scale(0.95) translateY(6px)';
        document.removeEventListener('keydown', handleKey);
        setTimeout(() => {
          overlay.remove();
          resolve(result);
        }, 180);
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
      if (btnClose) btnClose.addEventListener('click', () => closeDialog(false));
    });
  };

  const AppAlert = (options) => {
    return new Promise((resolve) => {
      let opts = typeof options === 'string' ? { message: options } : (options || {});
      const {
        title = 'Informasi Sistem',
        message = '',
        submessage = '',
        buttonText = 'Mengerti',
        type = 'info',
        icon = null,
        buttonIcon = null
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

      const overlay = document.createElement('div');
      overlay.id = 'app-confirm-overlay';
      overlay.className = 'confirm-overlay';

      overlay.innerHTML = `
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="alert-dialog-title">
          <div class="confirm-modal-body">
            <div class="confirm-header-row">
              <div class="confirm-icon-box confirm-icon-${type}">
                <i data-lucide="${iconName}"></i>
              </div>
              <button type="button" id="alert-btn-close" class="confirm-close-btn" aria-label="Tutup dialog" title="Tutup">
                <i data-lucide="x"></i>
              </button>
            </div>

            <h3 id="alert-dialog-title" class="confirm-title">${title}</h3>
            <p class="confirm-message">${message}</p>

            ${submessage ? `
            <div class="confirm-subnotice">
              <i data-lucide="info"></i>
              <span>${submessage}</span>
            </div>` : ''}
          </div>

          <div class="confirm-modal-footer">
            <button type="button" id="alert-btn-ok" class="confirm-btn-action btn-action-${type === 'danger' ? 'danger' : 'primary'}">
              ${buttonIcon ? `<i data-lucide="${buttonIcon}"></i>` : ''}
              <span>${buttonText}</span>
            </button>
          </div>
        </div>
      `;

      document.body.appendChild(overlay);
      if (typeof lucide !== 'undefined') lucide.createIcons({ el: overlay });

      const modalEl = overlay.querySelector('.confirm-modal');
      const btnOk = overlay.querySelector('#alert-btn-ok');
      const btnClose = overlay.querySelector('#alert-btn-close');

      requestAnimationFrame(() => {
        overlay.style.opacity = '1';
        modalEl.style.transform = 'scale(1) translateY(0)';
        btnOk.focus();
      });

      const closeDialog = () => {
        overlay.style.opacity = '0';
        modalEl.style.transform = 'scale(0.95) translateY(6px)';
        document.removeEventListener('keydown', handleKey);
        setTimeout(() => {
          overlay.remove();
          resolve(true);
        }, 180);
      };

      const handleKey = (e) => {
        if (e.key === 'Escape' || e.key === 'Enter') {
          e.preventDefault();
          closeDialog();
        }
      };

      document.addEventListener('keydown', handleKey);
      btnOk.addEventListener('click', () => closeDialog());
      if (btnClose) btnClose.addEventListener('click', () => closeDialog());
    });
  };

  // Expose globally to window
  window.AppConfirm = window.AppConfirm || AppConfirm;
  window.AppAlert = window.AppAlert || AppAlert;
  window.confirmModal = window.AppConfirm;
  window.alertModal = window.AppAlert;
  window.showConfirm = window.AppConfirm;
  window.showAlert = window.AppAlert;
  window.AppDialog = window.AppDialog || {
    confirm: window.AppConfirm,
    alert: window.AppAlert
  };

  // Global Declarative data-confirm form submit interceptor
  /* =====================================================================
     6. APP CONFIRMATION DIALOG & SMART ACTION TEXT RESOLVER
     ===================================================================== */
  function getSmartActionText(form) {
    if (!form) return 'Menyimpan data...';
    try {
      let customText = (typeof form.getAttribute === 'function') ? form.getAttribute('data-action-text') : null;
      if (customText) return customText;

      let formAction = '';
      if (typeof form.getAttribute === 'function') {
        formAction = (form.getAttribute('action') || '').toLowerCase();
      } else if (typeof form.action === 'string') {
        formAction = form.action.toLowerCase();
      }

      let formId = '';
      if (typeof form.getAttribute === 'function') {
        formId = (form.getAttribute('id') || '').toLowerCase();
      } else if (typeof form.id === 'string') {
        formId = form.id.toLowerCase();
      }
      
      if (formAction.includes('/login') || formId.includes('login')) {
        return 'Memverifikasi akun...';
      } else if (formAction.includes('/logout') || formId.includes('logout')) {
        return 'Keluar sistem...';
      } else if (formAction.includes('toggle-status')) {
        return 'Mengubah status akun pengguna...';
      } else if (formAction.includes('preview') || formAction.includes('pratinjau') || formId.includes('preview')) {
        return 'Membaca berkas & menganalisis pratinjau...';
      } else if ((formAction.includes('confirm') || formId.includes('confirm')) && (formAction.includes('impor') || formId.includes('sync'))) {
        return 'Menerapkan sinkronisasi data...';
      } else if (formAction.includes('impor') || formAction.includes('import')) {
        return 'Memproses impor data...';
      } else if (formAction.includes('delete') || formAction.includes('hapus') || formId.includes('delete') || formId.includes('hapus')) {
        return 'Menghapus data...';
      } else if (formAction.includes('update') || formAction.includes('edit')) {
        return 'Memperbarui data...';
      } else if (formAction.includes('approve') || formAction.includes('setujui')) {
        return 'Menyetujui data...';
      } else if (formAction.includes('reject') || formAction.includes('tolak')) {
        return 'Menolak data...';
      } else if (formAction.includes('pay') || formAction.includes('bayar')) {
        return 'Memproses pembayaran...';
      } else if (formAction.includes('adjust') || formAction.includes('penyesuaian')) {
        return 'Menyesuaikan stok...';
      } else if (formAction.includes('waste') || formAction.includes('rusak')) {
        return 'Mencatat barang rusak...';
      } else if (formAction.includes('transfer')) {
        return 'Mentransfer dana kas...';
      } else {
        return 'Menyimpan data...';
      }
    } catch (e) {
      console.warn('[app.js] Error resolving smart action text:', e);
      return 'Menyimpan data...';
    }
  }

  function getSmartActionSubtext(form) {
    if (!form) return '';
    try {
      let customSubtext = (typeof form.getAttribute === 'function') ? form.getAttribute('data-action-subtext') : null;
      if (customSubtext) return customSubtext;

      let formAction = '';
      if (typeof form.getAttribute === 'function') {
        formAction = (form.getAttribute('action') || '').toLowerCase();
      } else if (typeof form.action === 'string') {
        formAction = form.action.toLowerCase();
      }

      if (formAction.includes('preview') || formAction.includes('pratinjau')) {
        return 'Mempersiapkan pratinjau perbandingan data...';
      } else if (formAction.includes('confirm') && (formAction.includes('impor') || formAction.includes('sync'))) {
        return 'Menjalankan transaksi database secara atomic...';
      }
      return '';
    } catch (e) {
      return '';
    }
  }

  // Global Native form.submit() & requestSubmit() Monkey-Patch (Intersepsi seluruh submit form via script / Alpine / modal)
  const _nativeFormSubmit = HTMLFormElement.prototype.submit;
  HTMLFormElement.prototype.submit = function() {
    try {
      if (!this.classList.contains('no-loader') && this.getAttribute('target') !== '_blank') {
        const method = (this.getAttribute('method') || 'GET').toUpperCase();
        const wantsAction = this.hasAttribute('data-action-text') || this.getAttribute('data-loader') === 'action';
        if (wantsAction) {
          if (typeof AppSkeleton !== 'undefined') AppSkeleton.hide();
          const text = getSmartActionText(this);
          const subtext = getSmartActionSubtext(this);
          if (typeof AppAction !== 'undefined') AppAction.show(text, subtext);
          try {
            sessionStorage.setItem('app_action_triggered', 'true');
            if (method === 'GET') sessionStorage.setItem('app_action_dismiss_on_load', 'true');
          } catch (e) {}
        } else if (method === 'GET') {
          if (typeof AppAction !== 'undefined') AppAction.hide();
          if (typeof AppSkeleton !== 'undefined') AppSkeleton.show('Memuat data...');
        } else {
          if (typeof AppSkeleton !== 'undefined') AppSkeleton.hide();
          const text = getSmartActionText(this);
          const subtext = getSmartActionSubtext(this);
          if (typeof AppAction !== 'undefined') AppAction.show(text, subtext);
          try { sessionStorage.setItem('app_action_triggered', 'true'); } catch (e) {}
        }
      }
    } catch (e) {
      console.warn('[app.js] Error in form.submit interceptor:', e);
    }
    return _nativeFormSubmit.apply(this, arguments);
  };

  if (typeof HTMLFormElement.prototype.requestSubmit === 'function') {
    const _nativeRequestSubmit = HTMLFormElement.prototype.requestSubmit;
    HTMLFormElement.prototype.requestSubmit = function(submitter) {
      try {
        if (!this.classList.contains('no-loader') && this.getAttribute('target') !== '_blank') {
          const method = (this.getAttribute('method') || 'GET').toUpperCase();
          const wantsAction = this.hasAttribute('data-action-text') || this.getAttribute('data-loader') === 'action';
          if (wantsAction || method !== 'GET') {
            if (typeof AppSkeleton !== 'undefined') AppSkeleton.hide();
            const text = getSmartActionText(this);
            const subtext = getSmartActionSubtext(this);
            if (typeof AppAction !== 'undefined') AppAction.show(text, subtext);
            try {
              sessionStorage.setItem('app_action_triggered', 'true');
              if (method === 'GET') sessionStorage.setItem('app_action_dismiss_on_load', 'true');
            } catch (e) {}
          } else if (method === 'GET') {
            if (typeof AppAction !== 'undefined') AppAction.hide();
            if (typeof AppSkeleton !== 'undefined') AppSkeleton.show('Memuat data...');
          }
        }
      } catch (e) {
        console.warn('[app.js] Error in form.requestSubmit interceptor:', e);
      }
      return _nativeRequestSubmit.apply(this, arguments);
    };
  }

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
        if (typeof AppSkeleton !== 'undefined') AppSkeleton.hide();
        const customText = getSmartActionText(form);
        const customSubtext = getSmartActionSubtext(form);
        if (typeof AppAction !== 'undefined') AppAction.show(customText, customSubtext);
        try { sessionStorage.setItem('app_action_triggered', 'true'); } catch (err) {}
        
        // Defensive: Jika form terlepas dari DOM (misal modal tertutup/terdestroy oleh Alpine),
        // pasang kembali ke document.body agar browser tidak membatalkan submit
        if (!form.isConnected) {
          form.style.display = 'none';
          document.body.appendChild(form);
        }
        
        _nativeFormSubmit.call(form);
      }
    }
  });

  // Global Logout Confirmation Function & Interceptor
  const confirmLogout = async (customUrl = null) => {
    const logoutUrl = customUrl || (window.KSNACK_SESSION?.logoutUrl) || ((window.APP_BASE_PATH || '') + '/logout');
    
    // Ambil identitas akun yang sedang aktif
    const userName = window.KSNACK_AUTH_USER?.name || document.querySelector('.sidebar-user-name')?.textContent?.trim() || '';
    const userRole = window.KSNACK_AUTH_USER?.role || document.querySelector('.sidebar-user-role')?.textContent?.trim() || '';
    const accountInfo = (userName && userRole) ? `${userName} (${userRole})` : (userName || null);

    const confirmed = await AppConfirm({
      title: 'Konfirmasi Keluar',
      message: 'Apakah Anda yakin ingin keluar dari sistem Keren One?',
      submessage: 'Seluruh sesi kerja aktif Anda pada perangkat ini akan diakhiri. Pastikan pekerjaan atau transaksi yang sedang berjalan telah selesai.',
      accountInfo: accountInfo,
      confirmText: 'Ya, Keluar',
      cancelText: 'Batal',
      type: 'danger',
      icon: 'log-out',
      confirmIcon: 'log-out',
      cancelIcon: null,
      showCloseBtn: false,
      defaultFocus: 'confirm'
    });

    if (confirmed) {
      if (typeof AppSkeleton !== 'undefined') AppSkeleton.hide();
      if (typeof AppAction !== 'undefined') AppAction.show('Mengakhiri sesi sistem...');
      try { sessionStorage.setItem('app_action_triggered', 'true'); } catch (err) {}
      window.location.href = logoutUrl;
    }
    return confirmed;
  };

  // Intercept any click on logout trigger or link targeting /logout
  document.addEventListener('click', async (e) => {
    const logoutTrigger = e.target.closest('a[href$="/logout"], a[href*="/logout?"], [data-action="logout"]');
    if (!logoutTrigger) return;

    // Jika trigger berasal dari modal inactivity timeout (#ksnack-session-warning-modal) atau ber-atribut data-instant-logout:
    // Jangan munculkan pop up konfirmasi ganda, langsung eksekusi logout instan
    if (logoutTrigger.closest('#ksnack-session-warning-modal') || logoutTrigger.id === 'ksnack-session-logout-btn' || logoutTrigger.hasAttribute('data-instant-logout')) {
      return;
    }

    e.preventDefault();
    e.stopPropagation();

    const targetUrl = logoutTrigger.getAttribute('href') || null;
    await confirmLogout(targetUrl);
  }, true);

  // Declarative click confirmation for non-form elements (a[data-confirm], button[data-confirm]:not([type="submit"]))
  document.addEventListener('click', async (e) => {
    const trigger = e.target.closest('a[data-confirm], button[data-confirm]:not([type="submit"])');
    if (!trigger) return;

    // Abaikan jika trigger adalah logout yang sudah ditangani di atas
    if (trigger.matches('a[href$="/logout"], a[href*="/logout?"], [data-action="logout"]')) return;

    e.preventDefault();
    e.stopPropagation();

    const message = trigger.getAttribute('data-confirm');
    const title = trigger.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
    const type = trigger.getAttribute('data-confirm-type') || 'danger';
    const icon = trigger.getAttribute('data-confirm-icon') || null;
    const confirmText = trigger.getAttribute('data-confirm-btn') || 'Konfirmasi';
    const cancelText = trigger.getAttribute('data-confirm-cancel') || 'Batal';

    const confirmed = await AppConfirm({
      title,
      message,
      type,
      icon,
      confirmText,
      cancelText
    });

    if (confirmed) {
      if (trigger.tagName.toLowerCase() === 'a' && trigger.href) {
        if (typeof AppSkeleton !== 'undefined') AppSkeleton.hide();
        const actionText = trigger.getAttribute('data-action-text') || 'Memproses...';
        if (typeof AppAction !== 'undefined') AppAction.show(actionText);
        try { sessionStorage.setItem('app_action_triggered', 'true'); } catch (err) {}
        window.location.href = trigger.href;
      }
    }
  }, true);

  window.AppConfirm = AppConfirm;
  window.confirmModal = AppConfirm;
  window.confirmLogout = confirmLogout;
  window.AppAlert = AppAlert;
  window.alertModal = AppAlert;

  /* =====================================================================
     6.1 SKELETON SCREEN & ACTION PROCESSING DUAL-ENGINE
     ===================================================================== */
  
  // 1. AppSkeleton: Khusus untuk Navigasi Antar Halaman, Filter, & Refresh
  const AppSkeleton = {
    safetyTimer: null,

    show(text = 'Memuat halaman...') {
      // Jika proses aksi CRUD sedang aktif, JANGAN timpa dengan skeleton halaman
      if (typeof AppAction !== 'undefined' && AppAction.isActive()) return;

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

  // 2. AppAction: Khusus untuk Simpan Data, Update, Hapus, Checkout POS & Mutasi Data (Dual-Ring Glow -> Morph Checkmark / Error X)
  const AppAction = {
    safetyTimer: null,
    _active: false,

    isActive() {
      if (this._active) return true;
      try {
        return sessionStorage.getItem('app_action_triggered') === 'true';
      } catch (e) {
        return false;
      }
    },

    _eventsInited: false,

    _freeze() {
      try {
        document.documentElement.classList.add('action-loader-frozen');
        document.body.classList.add('action-loader-frozen');
        if (typeof PopupManager !== 'undefined' && typeof PopupManager.applyFreeze === 'function') {
          PopupManager.applyFreeze();
        }
      } catch (e) {}
    },

    _unfreeze() {
      try {
        document.documentElement.classList.remove('action-loader-frozen');
        document.body.classList.remove('action-loader-frozen');
        if (typeof PopupManager !== 'undefined' && typeof PopupManager.removeFreeze === 'function' && (!PopupManager.activePopups || PopupManager.activePopups.size === 0)) {
          PopupManager.removeFreeze();
        }
      } catch (e) {}
    },

    _initEvents() {
      if (this._eventsInited) return;
      const loader = document.getElementById('app-action-loader');
      if (!loader) return;
      this._eventsInited = true;

      // Kunci scroll & touch pada background saat modal aktif
      loader.addEventListener('wheel', (e) => {
        if (loader.classList.contains('is-active')) {
          e.preventDefault();
        }
      }, { passive: false });

      loader.addEventListener('touchmove', (e) => {
        if (loader.classList.contains('is-active')) {
          e.preventDefault();
        }
      }, { passive: false });

      // Pop up tidak boleh ditutup dengan mengklik ruang kosong / backdrop
      loader.addEventListener('click', (e) => {
        if (e.target === loader) {
          e.preventDefault();
          e.stopPropagation();
          const card = loader.querySelector('.action-loader-card');
          if (card) {
            card.classList.remove('action-card-nudge');
            void card.offsetWidth; // trigger reflow
            card.classList.add('action-card-nudge');
          }
        }
      });
    },

    show(text = 'Menyimpan data...', subtext = '') {
      this._active = true;
      this._freeze();
      this._initEvents();

      try {
        sessionStorage.setItem('app_action_triggered', 'true');
      } catch (e) {}

      // Sembunyikan page skeleton seketika agar Action Loader mendapat prioritas 100%
      if (typeof AppSkeleton !== 'undefined') {
        AppSkeleton.hide();
      }

      const loader = document.getElementById('app-action-loader');
      const textEl = document.getElementById('app-action-loader-text');
      const subtextEl = document.getElementById('app-action-loader-subtext');
      if (!loader) return;

      clearTimeout(this.safetyTimer);
      loader.classList.remove('is-success', 'is-error');

      if (textEl && text) {
        textEl.textContent = text;
      }
      if (subtextEl) {
        if (subtext) {
          subtextEl.textContent = subtext;
          subtextEl.style.display = 'block';
        } else {
          subtextEl.style.display = 'none';
        }
      }

      loader.classList.add('is-active');

      this.safetyTimer = setTimeout(() => {
        this.hide();
      }, 15000);
    },

    success(text = 'Berhasil Disimpan! ✨', subtext = '', duration = 1500) {
      // Jika argumen kedua adalah angka (duration), sesuaikan untuk backwards compatibility
      if (typeof subtext === 'number') {
        duration = subtext;
        subtext = '';
      }

      this._active = false;
      this._freeze();
      this._initEvents();

      try {
        sessionStorage.removeItem('app_action_triggered');
      } catch (e) {}

      return new Promise((resolve) => {
        const loader = document.getElementById('app-action-loader');
        const textEl = document.getElementById('app-action-loader-text');
        const subtextEl = document.getElementById('app-action-loader-subtext');
        if (!loader) {
          this._unfreeze();
          resolve();
          return;
        }

        clearTimeout(this.safetyTimer);
        loader.classList.remove('is-error');

        if (textEl && text) {
          textEl.textContent = text;
        }
        if (subtextEl) {
          if (subtext) {
            subtextEl.textContent = subtext;
            subtextEl.style.display = 'block';
          } else {
            subtextEl.style.display = 'none';
          }
        }

        loader.classList.add('is-active', 'is-success');

        // Pop up tidak ditutup dengan klik ruang kosong; hanya ditutup otomatis sesuai durasi
        setTimeout(() => {
          this.hide();
          resolve();
        }, duration);
      });
    },

    error(text = 'Gagal memproses data!', subtext = '', duration = 2200) {
      if (typeof subtext === 'number') {
        duration = subtext;
        subtext = '';
      }

      // Hitung durasi membaca dinamis agar pesan error panjang sempat terbaca dengan nyaman
      const totalLen = (text ? String(text).length : 0) + (subtext ? String(subtext).length : 0);
      const calculatedDuration = Math.max(duration, Math.min(8000, 2400 + totalLen * 35));
      const finalDuration = duration > 2200 ? duration : calculatedDuration;

      this._active = false;
      this._freeze();
      this._initEvents();

      try {
        sessionStorage.removeItem('app_action_triggered');
      } catch (e) {}

      return new Promise((resolve) => {
        const loader = document.getElementById('app-action-loader');
        const textEl = document.getElementById('app-action-loader-text');
        const subtextEl = document.getElementById('app-action-loader-subtext');
        if (!loader) {
          this._unfreeze();
          resolve();
          return;
        }

        clearTimeout(this.safetyTimer);
        loader.classList.remove('is-success');

        if (textEl && text) {
          textEl.textContent = text;
        }
        if (subtextEl) {
          if (subtext) {
            subtextEl.textContent = subtext;
            subtextEl.style.display = 'block';
          } else {
            subtextEl.style.display = 'none';
          }
        }

        loader.classList.add('is-active', 'is-error');

        // Pop up tidak ditutup dengan klik ruang kosong; hanya ditutup otomatis sesuai durasi
        setTimeout(() => {
          this.hide();
          resolve();
        }, finalDuration);
      });
    },

    fail(text, subtext, duration) {
      return this.error(text, subtext, duration);
    },

    hide() {
      clearTimeout(this.safetyTimer);
      this._unfreeze();
      const loader = document.getElementById('app-action-loader');
      if (loader) {
        loader.classList.remove('is-active');
        setTimeout(() => {
          loader.classList.remove('is-success', 'is-error');
          const subtextEl = document.getElementById('app-action-loader-subtext');
          if (subtextEl) subtextEl.style.display = 'none';
        }, 250);
      }
    }
  };

  // Smart Parser: Mengubah pesan server mentah menjadi Judul Elegan + Subtitle Kartu Tengah
  function formatSmartFeedback(rawMsg, isSuccess = true) {
    if (!rawMsg) {
      return {
        title: isSuccess ? 'Berhasil Disimpan! ✨' : 'Terjadi Kesalahan!',
        subtext: ''
      };
    }
    const msg = String(rawMsg).trim();
    const lower = msg.toLowerCase();

    if (isSuccess) {
      let title = 'Berhasil Diproses! ✨';
      let subtext = '';

      if (lower.includes('selamat datang') || lower.includes('login') || lower.includes('berhasil masuk')) {
        title = 'Login Berhasil! 🎉';
        subtext = msg;
      } else if (lower.includes('toko') || lower.includes('pelanggan')) {
        if (lower.includes('beli putus') || (lower.includes('faktur') && lower.includes('konsinyasi'))) {
          title = 'Faktur Beli Putus Diterbitkan! 🧾';
          const notaMatch = msg.match(/(?:Faktur Beli Putus|Nota|Faktur)\s*#?([A-Z0-9\-_]+)/i);
          subtext = notaMatch ? `${notaMatch[0]} berhasil diterbitkan. Lihat di Pesanan Pelanggan (/customer-orders).` : 'Faktur beli putus sisa konsinyasi berhasil diterbitkan di Pesanan Pelanggan.';
        } else if (lower.includes('retur') || lower.includes('gudang pusat') || (lower.includes('stok') && lower.includes('konsinyasi'))) {
          title = 'Stok Konsinyasi Diretur! 📦';
          const qtyMatch = msg.match(/(\d+(?:\.\d+)?)\s*pcs/i);
          subtext = qtyMatch ? `${qtyMatch[0]} stok konsinyasi ditarik kembali ke gudang pusat.` : 'Stok konsinyasi telah diretur ke gudang pusat.';
        } else if (lower.includes('dihapus') || lower.includes('delete') || lower.includes('hapus')) {
          title = 'Toko Pelanggan Dihapus! 🗑️';
        } else if (lower.includes('ditambahkan') || lower.includes('tambah') || lower.includes('baru')) {
          title = 'Toko Pelanggan Ditambahkan! ✨';
        } else if (lower.includes('diperbarui') || lower.includes('diubah') || lower.includes('update') || lower.includes('simpan')) {
          title = 'Data Toko Diperbarui! ✨';
        } else {
          title = 'Data Toko Disimpan! ✨';
        }
      } else if (lower.includes('wilayah') || lower.includes('rute')) {
        if (lower.includes('dihapus') || lower.includes('delete') || lower.includes('hapus')) {
          title = 'Wilayah Berhasil Dihapus! 🗑️';
        } else if (lower.includes('ditambahkan') || lower.includes('baru')) {
          title = 'Wilayah Berhasil Ditambahkan! ✨';
        } else {
          title = 'Data Wilayah Diperbarui! ✨';
        }
      } else if (lower.includes('grup pelanggan')) {
        if (lower.includes('dihapus') || lower.includes('delete') || lower.includes('hapus')) {
          title = 'Grup Pelanggan Dihapus! 🗑️';
        } else if (lower.includes('ditambahkan') || lower.includes('baru')) {
          title = 'Grup Pelanggan Ditambahkan! ✨';
        } else {
          title = 'Grup Pelanggan Diperbarui! ✨';
        }
      } else if (lower.includes('surat jalan') || lower.includes('surat_jalan')) {
        if (lower.includes('disetujui') || lower.includes('approved') || lower.includes('diberangkatkan')) {
          title = 'Surat Jalan Disetujui! ✨';
          subtext = 'Armada / driver dapat memulai pengiriman.';
        } else if (lower.includes('dimulai')) {
          title = 'Pengiriman Dimulai! 🚚';
          subtext = 'Driver dalam perjalanan menuju lokasi pelanggan.';
        } else if (lower.includes('selesai') || lower.includes('diselesaikan')) {
          title = 'Pengiriman Selesai! 🎉';
          subtext = 'Pesanan telah berhasil diterima pelanggan.';
        } else if (lower.includes('diperbarui') || lower.includes('diubah') || lower.includes('update')) {
          title = 'Surat Jalan Diperbarui! ✨';
          subtext = 'Status dan data pengiriman berhasil diperbarui.';
        } else {
          title = 'Surat Jalan Berhasil Dibuat! ✨';
          const sjMatch = msg.match(/SJ-[A-Z0-9\-]+/i);
          subtext = sjMatch ? `Nomor ${sjMatch[0]} siap untuk proses pengiriman.` : 'Dokumen surat jalan telah berhasil dibuat.';
        }
      } else if ((lower.includes('po') || lower.includes('pesanan') || lower.includes('order')) && (lower.includes('disiapkan') || lower.includes('siap dikirim') || lower.includes('siap kirim'))) {
        title = 'PO Berhasil Disiapkan! ✨';
        subtext = 'Stok fisik gudang telah terpotong.';
      } else if ((lower.includes('po') || lower.includes('pesanan') || lower.includes('purchase order') || lower.includes('antrean')) && (lower.includes('terbit') || lower.includes('diterbitkan') || lower.includes('masuk ke antrean'))) {
        title = 'PO Berhasil Diterbitkan! ✨';
        subtext = 'Pesanan masuk ke antrean daftar PO gudang.';
      } else if (lower.includes('checkout') || lower.includes('transaksi')) {
        title = 'Transaksi Berhasil! ✨';
      } else if (lower.includes('dihapus') || lower.includes('delete')) {
        title = 'Data Berhasil Dihapus! ✨';
      } else if (lower.includes('diperbarui') || lower.includes('diubah') || lower.includes('update')) {
        title = 'Data Berhasil Diperbarui! ✨';
      } else if (lower.includes('disimpan') || lower.includes('ditambahkan') || lower.includes('dibuat') || lower.includes('simpan')) {
        title = 'Data Berhasil Disimpan! ✨';
      } else if (lower.includes('reset')) {
        title = 'Berhasil Direset! ✨';
      }

      // Ambil nomor nota atau referensi jika ada
      const poMatch = msg.match(/(?:PO|Nota|Faktur)\s*#?([A-Z0-9\-_]+)/i);
      if (poMatch && poMatch[0] && !subtext) {
        subtext = `${poMatch[0]} siap diproses.`;
      }

      if (!subtext && msg) {
        const cleanTitle = title.toLowerCase().replace(/[!✨🎉🚚📦🧾🗑️]/g, '').trim();
        if (lower !== cleanTitle && lower !== cleanTitle.replace(/^data\s+/, '')) {
          subtext = msg;
        }
      }

      if (subtext && subtext.length > 95) {
        subtext = subtext.substring(0, 92) + '...';
      }

      return { title, subtext };
    } else {
      // Gagal / Error
      let title = 'Gagal Memproses Data!';
      let subtext = msg;

      if (lower.includes('stok') && (lower.includes('kurang') || lower.includes('tidak cukup') || lower.includes('mencukupi') || lower.includes('defisit'))) {
        title = 'Stok Gudang Tidak Cukup!';
      } else if (lower.includes('ditolak') || lower.includes('melebihi')) {
        title = 'Transaksi Ditolak Sistem!';
      } else if (lower.includes('tidak ditemukan')) {
        title = 'Data Tidak Ditemukan!';
      } else if (lower.includes('terdaftar') || lower.includes('duplikat') || lower.includes('sudah digunakan')) {
        title = 'Data Sudah Digunakan!';
      } else if (lower.includes('izin') || lower.includes('akses') || lower.includes('dibatasi')) {
        title = 'Akses Dibatasi!';
      }

      if (subtext.length > 85) {
        subtext = subtext.substring(0, 82) + '...';
      }

      return { title, subtext };
    }
  }

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

    try {
      document.documentElement.classList.remove('has-pending-action');
    } catch (e) {}

    // Check whether a form/action was explicitly submitted (for page-navigation uses)
    let hadAction = false;
    let dismissOnLoad = false;
    try {
      hadAction = sessionStorage.getItem('app_action_triggered') === 'true';
      dismissOnLoad = sessionStorage.getItem('app_action_dismiss_on_load') === 'true';
      sessionStorage.removeItem('app_action_triggered');
      sessionStorage.removeItem('app_action_dismiss_on_load');
    } catch (e) {}

    const flash = window.__FLASH__;

    // Tampilkan Animasi di Tengah Layar (Gaya Kasir POS) untuk Seluruh Notifikasi Server
    if (flash && (flash.type === 'error' || flash.type === 'danger')) {
      AppSkeleton.hide();
      const feedback = formatSmartFeedback(flash.message, false);
      AppAction.error(feedback.title, feedback.subtext, 2200);
    } else if (flash && flash.type === 'success') {
      AppSkeleton.hide();
      const feedback = formatSmartFeedback(flash.message, true);
      const isLogin = feedback.title.toLowerCase().includes('login') || (flash.message && flash.message.toLowerCase().includes('selamat datang'));
      const duration = isLogin ? 1800 : 1500;
      AppAction.success(feedback.title, feedback.subtext, duration);
    } else if (flash && (flash.type === 'warning' || flash.type === 'info')) {
      AppSkeleton.hide();
      if (typeof window.showToast === 'function') {
        window.showToast(flash.message, flash.type, 4500);
      }
      setTimeout(() => { AppAction.hide(); }, 100);
    } else if (hadAction && dismissOnLoad) {
      // Untuk request GET (filter / cari dengan pop up loading), tutup loader langsung saat konten halaman siap
      AppSkeleton.hide();
      AppAction.hide();
    } else if (hadAction) {
      // Jika sebelumnya ada aksi form CRUD POST namun tidak ada flash message dari backend
      AppSkeleton.hide();
      AppAction.success('Aksi Berhasil Diproses! ✨', '', 1200);
    } else {
      setTimeout(() => {
        AppSkeleton.hide();
        AppAction.hide();
      }, 120);
    }
  };

  if (document.readyState === 'complete') {
    dismissInitialSkeleton();
  } else {
    window.addEventListener('load', dismissInitialSkeleton);
    document.addEventListener('DOMContentLoaded', () => {
      setTimeout(dismissInitialSkeleton, 60);
    });
  }

  // Restore on bfcache (Back/Forward navigation)
  window.addEventListener('pageshow', (event) => {
    AppSkeleton.hide();
    if (!AppAction.isActive() && !window.__FLASH__) {
      AppAction.hide();
    }
  });

  // Intercept Refresh / Page Reload (F5, Ctrl+R, Reload button)
  window.addEventListener('beforeunload', () => {
    // Jika proses aksi CRUD (AppAction) sedang aktif atau ada submit in-flight, JANGAN PERNAH timpa dengan AppSkeleton!
    if (AppAction.isActive()) {
      const pageLoader = document.getElementById('app-page-skeleton') || document.getElementById('app-page-loader');
      if (pageLoader) {
        pageLoader.classList.remove('is-active');
      }
      return;
    }
    const loader = document.getElementById('app-page-skeleton') || document.getElementById('app-page-loader');
    if (loader) {
      loader.classList.add('is-active');
    }
  });

  // Intercept standard internal links -> Trigger Skeleton Screen (Navigasi Antar Halaman)
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

        const wantsAction = link.getAttribute('data-loader') === 'action' || link.hasAttribute('data-action-text');
        if (wantsAction) {
          const text = link.getAttribute('data-action-text') || 'Memuat data...';
          AppSkeleton.hide();
          AppAction.show(text);
          try {
            sessionStorage.setItem('app_action_triggered', 'true');
            sessionStorage.setItem('app_action_dismiss_on_load', 'true');
          } catch (err) {}
          return;
        }

        AppAction.hide();
        AppSkeleton.show('Memuat halaman...');
      }
    } catch (err) {}
  });

  // Intercept standard form submissions -> Trigger Action Blur Processing Loader (POST) or Skeleton (GET)
  document.addEventListener('submit', (e) => {
    try {
      const form = e.target;
      if (!form || !form.tagName || form.tagName.toLowerCase() !== 'form') return;
      if (e.defaultPrevented || form.classList.contains('no-loader') || form.getAttribute('target') === '_blank') {
        return;
      }

      // Form dengan data-confirm ditangani secara terpisah oleh listener data-confirm
      if (form.hasAttribute('data-confirm')) {
        return;
      }

      // Validasi form HTML5 bawaan: jika belum valid, jangan jalankan loader
      if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
        return;
      }

      const method = (form.getAttribute('method') || 'GET').toUpperCase();
      const wantsAction = form.hasAttribute('data-action-text') || form.getAttribute('data-loader') === 'action';
      
      // 1. Form dengan deklarasi eksplisit Action Loader (data-action-text atau data-loader="action")
      if (wantsAction) {
        if (typeof AppSkeleton !== 'undefined') AppSkeleton.hide();
        const customText = getSmartActionText(form);
        if (typeof AppAction !== 'undefined') AppAction.show(customText);
        try {
          sessionStorage.setItem('app_action_triggered', 'true');
          if (method === 'GET') {
            sessionStorage.setItem('app_action_dismiss_on_load', 'true');
          }
        } catch (err) {}
        return;
      }

      // 2. Form GET standar (Filter, Pencarian, Parameter Laporan) -> Trigger Page Transition Skeleton
      if (method === 'GET') {
        if (typeof AppAction !== 'undefined') AppAction.hide();
        if (typeof AppSkeleton !== 'undefined') AppSkeleton.show('Memuat data...');
        return;
      }

      // 3. Form POST / PUT / DELETE (Operasi CRUD) -> Trigger AppAction Processing Loader
      if (typeof AppSkeleton !== 'undefined') AppSkeleton.hide();
      const customText = getSmartActionText(form);
      if (typeof AppAction !== 'undefined') AppAction.show(customText);
      try {
        sessionStorage.setItem('app_action_triggered', 'true');
      } catch (err) {}
    } catch (err) {
      console.warn('[app.js] Error in submit event listener:', err);
    }
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
      if (!input.matches('input.input-rupiah, input.input-currency, input.format-rupiah, input.currency-input, input[data-rupiah], input[data-currency], input[data-type="currency"]')) return;

      // Jika input awalnya bertipe number, ubah ke text numeric agar browser mengizinkan titik pemisah ribuan
      if (input.type === 'number') {
        try {
          input.type = 'text';
          input.inputMode = 'numeric';
        } catch (err) {}
      }

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

      // Simpan raw value di dataset
      input.dataset.rawValue = RupiahFormatter.unformat(formatted);

      input.dispatchEvent(new CustomEvent('rupiah-change', {
        bubbles: true,
        detail: {
          raw: RupiahFormatter.unformat(formatted),
          formatted: formatted
        }
      }));
    },

    handleFocus(e) {
      const input = e.target;
      if (!input || !input.matches) return;
      if (!input.matches('input.input-rupiah, input.input-currency, input.format-rupiah, input.currency-input, input[data-rupiah], input[data-currency], input[data-type="currency"]')) return;

      if (input.type === 'number') {
        try {
          input.type = 'text';
          input.inputMode = 'numeric';
          if (input.value) {
            input.value = RupiahFormatter.format(input.value);
          }
        } catch (err) {}
      }
    },

    init() {
      // Pure delegated event listeners on document
      document.addEventListener('input', this.handleInput, { passive: true });
      document.addEventListener('focusin', this.handleFocus, { passive: true });
    }
  };

  window.RupiahFormatter = RupiahFormatter;
  window.formatRupiah = (val) => 'Rp ' + Number(val || 0).toLocaleString('id-ID');
  window.formatRupiahNumber = (val) => RupiahFormatter.format(val);
  window.unformatRupiah = (val) => RupiahFormatter.unformat(val);
  window.attachRupiahMask = (el) => {
    if (!el) return;
    el.classList.add('input-rupiah');
    if (el.type === 'number') {
      try { el.type = 'text'; el.inputMode = 'numeric'; } catch (e) {}
    }
    if (el.value) el.value = RupiahFormatter.format(el.value);
  };

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
        const table = target.closest('table');
        if (table) {
          let parent = table.parentElement;
          while (parent && parent !== document.body) {
            if (parent.scrollWidth > parent.clientWidth + 2) {
              return parent;
            }
            parent = parent.parentElement;
          }
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
      window.addEventListener('load', updateScrollableContainers, { passive: true });
      document.addEventListener('alpine:initialized', () => {
        setTimeout(updateScrollableContainers, 120);
      });

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
      init = init || {};
      init.headers = init.headers || {};
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      if (init.headers instanceof Headers) {
        if (token && !init.headers.has('X-CSRF-TOKEN')) init.headers.set('X-CSRF-TOKEN', token);
        if (!init.headers.has('X-Requested-With')) init.headers.set('X-Requested-With', 'XMLHttpRequest');
      } else if (Array.isArray(init.headers)) {
        if (token && !init.headers.some(([k]) => k.toLowerCase() === 'x-csrf-token')) {
          init.headers.push(['X-CSRF-TOKEN', token]);
        }
        if (!init.headers.some(([k]) => k.toLowerCase() === 'x-requested-with')) {
          init.headers.push(['X-Requested-With', 'XMLHttpRequest']);
        }
      } else {
        if (token && !init.headers['X-CSRF-TOKEN']) init.headers['X-CSRF-TOKEN'] = token;
        if (!init.headers['X-Requested-With']) init.headers['X-Requested-With'] = 'XMLHttpRequest';
      }
      return originalFetch.call(this, resource, init);
    };
  }

  // Global CSRF & Double-Submit Interceptor
  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form && form.tagName === 'FORM' && form.method && form.method.toUpperCase() === 'POST') {
      const csrfInput = form.querySelector('input[name="csrf_token"]');
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      if (!csrfInput) {
        if (token) {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'csrf_token';
          input.value = token;
          form.appendChild(input);
        }
      } else if ((!csrfInput.value || !csrfInput.value.trim()) && token) {
        csrfInput.value = token;
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
     9. PROGRESSIVE WEB APP (PWA) & NETWORK GUARD ENGINE
     ===================================================================== */
  const PWAEngine = {
    _deferredInstallPrompt: null,
    _swRegistration: null,

    init() {
      this.initNetworkGuard();
      this.initInstallPrompt();
      this.initServiceWorker();
    },

    // 9.1 Network Guard (Zero False-Positive Online/Offline Status)
    initNetworkGuard() {
      let offlineToastEl = null;

      const showNetworkToast = (isOnline) => {
        if (offlineToastEl) {
          offlineToastEl.remove();
          offlineToastEl = null;
        }

        const toast = document.createElement('div');
        toast.className = `pwa-network-toast ${isOnline ? 'is-online' : 'is-offline'}`;
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        
        toast.innerHTML = `
          <div class="pwa-toast-inner">
            <div class="pwa-toast-icon-wrap ${isOnline ? 'online' : 'offline'}">
              <span class="pwa-toast-dot ${isOnline ? 'online' : 'offline'}"></span>
            </div>
            <div class="pwa-toast-content">
              <div class="pwa-toast-title">${isOnline ? 'Koneksi Kembali Pulih' : 'Koneksi Internet Terputus'}</div>
              <div class="pwa-toast-msg">${isOnline ? 'Terhubung kembali ke server dengan lancar.' : 'Transaksi ditangguhkan sementara demi integritas data.'}</div>
            </div>
          </div>
        `;

        document.body.appendChild(toast);
        offlineToastEl = toast;

        // Animate entrance
        requestAnimationFrame(() => {
          toast.classList.add('is-visible');
        });

        // NOTIFIKASI OFFLINE TETAP TAMPIL SAMPAI SINYAL KEMBALI
        // Hanya auto-dismiss jika koneksi sudah online
        if (isOnline) {
          setTimeout(() => {
            if (offlineToastEl === toast) {
              toast.classList.remove('is-visible');
              setTimeout(() => {
                toast.remove();
                if (offlineToastEl === toast) offlineToastEl = null;
              }, 300);
            }
          }, 3500);
        }
      };

      // Realtime listeners
      window.addEventListener('offline', () => {
        if (!navigator.onLine) {
          showNetworkToast(false);
        }
      });

      window.addEventListener('online', () => {
        // Double check real connectivity before announcing online
        const basePath = window.APP_BASE_PATH || '';
        fetch(basePath + '/assets/favicon/favicon-96x96.png?_ping=' + Date.now(), { method: 'HEAD', cache: 'no-store' })
          .then(() => {
            showNetworkToast(true);
          })
          .catch(() => {
            if (navigator.onLine) {
              showNetworkToast(true);
            }
          });
      });

      // Form submission guard: prevent data corruption when offline
      document.addEventListener('submit', (e) => {
        if (!navigator.onLine) {
          const form = e.target;
          if (form && form.tagName === 'FORM') {
            e.preventDefault();
            e.stopPropagation();
            
            // Re-enable submit button if disabled
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.style.opacity = '1';
              submitBtn.style.cursor = 'pointer';
            }

            // Show alert toast
            showNetworkToast(false);
            if (typeof window.showToast === 'function') {
              window.showToast('Perangkat sedang offline. Mohon periksa koneksi internet sebelum mengirim formulir.', 'warning');
            }
          }
        }
      }, true);
    },

    // 9.2 Custom PWA Install Prompt & Dynamic Installation State Sync
    initInstallPrompt() {
      const isStandaloneMode = () => {
        return window.matchMedia('(display-mode: standalone)').matches 
          || window.matchMedia('(display-mode: fullscreen)').matches
          || window.matchMedia('(display-mode: minimal-ui)').matches
          || (window.navigator.standalone === true)
          || (document.referrer && document.referrer.indexOf('android-app://') === 0);
      };

      const syncInstallButtons = async () => {
        let isAppInstalled = isStandaloneMode();

        // Check navigator.getInstalledRelatedApps() for Chrome Android & Desktop
        if (!isAppInstalled && 'getInstalledRelatedApps' in navigator) {
          try {
            const relatedApps = await navigator.getInstalledRelatedApps();
            if (Array.isArray(relatedApps) && relatedApps.length > 0) {
              isAppInstalled = true;
            }
          } catch (e) {}
        }

        const installBtns = document.querySelectorAll('.pwa-install-trigger');
        installBtns.forEach((btn) => {
          if (isAppInstalled) {
            btn.style.setProperty('display', 'none', 'important');
            btn.setAttribute('aria-hidden', 'true');
          } else {
            btn.style.removeProperty('display');
            btn.removeAttribute('aria-hidden');
          }
        });

        if (isAppInstalled) {
          document.documentElement.classList.add('is-pwa-standalone');
        } else {
          document.documentElement.classList.remove('is-pwa-standalone');
        }
      };

      // Initial check
      syncInstallButtons();

      // Listen for browser install prompt readiness
      window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent default mini-infobar
        e.preventDefault();
        this._deferredInstallPrompt = e;
        syncInstallButtons();
      });

      // Delegate click on any PWA install trigger
      document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.pwa-install-trigger');
        if (trigger) {
          e.preventDefault();
          if (this._deferredInstallPrompt) {
            this._deferredInstallPrompt.prompt();
            this._deferredInstallPrompt.userChoice.then((choiceResult) => {
              if (choiceResult.outcome === 'accepted') {
                console.log('[PWA] User accepted install prompt');
                if (window.showToast) window.showToast('Aplikasi berhasil dipasang! 🎉', 'success');
                syncInstallButtons();
              }
              this._deferredInstallPrompt = null;
            });
          } else {
            // Open interactive installation guide modal for Android, iOS Safari, or Desktop
            if (typeof window.openPwaInstallModal === 'function') {
              window.openPwaInstallModal();
            } else {
              alert('Untuk memasang aplikasi: Pada Chrome Android tekan titik 3 lalu "Pasang Aplikasi", atau pada iPhone Safari tekan tombol Share lalu "Tambah ke Layar Utama".');
            }
          }
        }
      });

      // When app is installed, immediately hide all install buttons
      window.addEventListener('appinstalled', () => {
        console.log('[PWA] App installed successfully');
        this._deferredInstallPrompt = null;
        syncInstallButtons();
      });

      // Listen for display mode media query changes (e.g. opened in standalone or resized)
      try {
        window.matchMedia('(display-mode: standalone)').addEventListener('change', () => {
          syncInstallButtons();
        });
      } catch (e) {}
    },

    // 9.3 Service Worker Registration & Update Notification
    initServiceWorker() {
      if (!('serviceWorker' in navigator)) return;

      window.addEventListener('load', () => {
        const swPath = (window.APP_BASE_PATH || '') + '/sw.js';
        
        navigator.serviceWorker.register(swPath).then((reg) => {
          this._swRegistration = reg;

          // Check if new update is already waiting
          if (reg.waiting) {
            this.showUpdateBanner(reg.waiting);
          }

          // Check on update found
          reg.addEventListener('updatefound', () => {
            const newWorker = reg.installing;
            if (newWorker) {
              newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                  this.showUpdateBanner(newWorker);
                }
              });
            }
          });
        }).catch(() => {
          navigator.serviceWorker.register('./sw.js').catch(() => {});
        });

        // Reload window when worker activates after skipWaiting
        let refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', () => {
          if (!refreshing) {
            refreshing = true;
            window.location.reload();
          }
        });
      });
    },

    // 9.4 Update Notification Toast
    showUpdateBanner(worker) {
      if (document.getElementById('pwa-update-banner')) return;

      const banner = document.createElement('div');
      banner.id = 'pwa-update-banner';
      banner.className = 'pwa-update-toast is-visible';
      banner.innerHTML = `
        <div class="pwa-update-content">
          <div class="pwa-update-icon"><i data-lucide="sparkles"></i></div>
          <div class="pwa-update-text">
            <strong>Pembaruan Sistem Tersedia</strong>
            <span>Muat ulang untuk menikmati pembaruan versi terbaru.</span>
          </div>
          <button type="button" class="pwa-update-btn" id="pwa-reload-btn">
            Perbarui
          </button>
        </div>
      `;

      document.body.appendChild(banner);
      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons({ root: banner });
      }

      const reloadBtn = banner.querySelector('#pwa-reload-btn');
      if (reloadBtn) {
        reloadBtn.addEventListener('click', () => {
          reloadBtn.disabled = true;
          reloadBtn.textContent = 'Memperbarui...';
          if (worker) {
            worker.postMessage({ action: 'skipWaiting' });
          } else if (this._swRegistration && this._swRegistration.waiting) {
            this._swRegistration.waiting.postMessage({ action: 'skipWaiting' });
          } else {
            window.location.reload();
          }
        });
      }
    }
  };

  /* =====================================================================
     10. SESSION TIMEOUT ENGINE (Sliding Inactivity 1 Jam + Interactive Warning)
     ===================================================================== */
  const SessionTimeoutEngine = {
    _isTriggered: false,
    _intervalId: null,
    _lastActivityTime: Date.now(),
    _lastHeartbeatTime: Date.now(),
    _warningShown: false,
    _modalEl: null,
    _countdownEl: null,

    // Konfigurasi default (didukung sinkronisasi via window.KSNACK_SESSION)
    timeoutSeconds: 3600,      // 1 Jam (3600 detik)
    warningSeconds: 300,       // 5 Menit countdown sebelum logout otomatis
    heartbeatInterval: 300000, // 5 Menit (300.000 ms) antara heartbeat ping

    init() {
      if (!window.KSNACK_SESSION) return;

      if (window.KSNACK_SESSION.timeoutSeconds) {
        this.timeoutSeconds = parseInt(window.KSNACK_SESSION.timeoutSeconds, 10) || 3600;
      }
      if (window.KSNACK_SESSION.warningSeconds) {
        this.warningSeconds = parseInt(window.KSNACK_SESSION.warningSeconds, 10) || 300;
      }
      if (window.KSNACK_SESSION.heartbeatInterval) {
        this.heartbeatInterval = parseInt(window.KSNACK_SESSION.heartbeatInterval, 10) || 300000;
      }

      this._lastActivityTime = Date.now();
      this._lastHeartbeatTime = Date.now();

      // Pasang event listener aktivitas pengguna (Throttled & Passive)
      this._attachActivityListeners();

      // Bangun modal peringatan di DOM
      this._createWarningModal();

      // Evaluasi timer berkala tiap detik
      this._intervalId = setInterval(() => this._tick(), 1000);

      // Cek seketika saat user kembali membuka tab browser dari sleep/background
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
          this._tick();
        }
      });
    },

    _attachActivityListeners() {
      const activityEvents = ['mousedown', 'keydown', 'touchstart', 'scroll'];
      let lastRecorded = 0;

      const onUserActivity = () => {
        // JIKA warning modal sedang aktif/terbuka:
        // Jangan pernah perpanjang sesi otomatis lewat klik/scroll sembarangan!
        // Pengguna WAJIB secara sadar mengklik tombol "Lanjutkan Sesi".
        if (this._warningShown) {
          return;
        }

        const now = Date.now();
        // Throttle pencatatan aktivitas lokal maksimal 1x per 2 detik
        if (now - lastRecorded < 2000) return;
        lastRecorded = now;
        this._lastActivityTime = now;

        // Jika waktu sejak heartbeat terakhir sudah melebihi interval (5 menit), kirim heartbeat otomatis
        if (now - this._lastHeartbeatTime >= this.heartbeatInterval) {
          this.sendHeartbeat();
        }
      };

      activityEvents.forEach(evt => {
        window.addEventListener(evt, onUserActivity, { passive: true });
      });
    },

    _createWarningModal() {
      if (document.getElementById('ksnack-session-warning-modal')) {
        this._modalEl = document.getElementById('ksnack-session-warning-modal');
        this._countdownEl = document.getElementById('ksnack-session-countdown');
        return;
      }

      const logoutUrl = window.KSNACK_SESSION?.logoutUrl || ((window.APP_BASE_PATH || '') + '/logout?reason=timeout');

      const modalHtml = `
        <div id="ksnack-session-warning-modal" class="session-warning-backdrop modal-backdrop confirm-overlay" data-popup-backdrop="true" role="dialog" aria-modal="true" style="position:fixed;inset:0;background:rgba(9,13,22,0.85);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);z-index:999999;display:none;align-items:center;justify-content:center;padding:16px;opacity:0;transition:opacity 0.25s ease-out;">
          <style>
            .session-warning-box { background: #ffffff; color: #0f172a; border: 1px solid rgba(226,232,240,0.8); }
            .dark .session-warning-box { background: #0f172a !important; color: #f8fafc !important; border: 1px solid rgba(255,255,255,0.12) !important; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7) !important; }
            .dark .session-warning-box h3 { color: #f8fafc !important; }
            .dark .session-warning-box p { color: #94a3b8 !important; }
            .dark .session-warning-box #ksnack-session-logout-btn { color: #94a3b8 !important; }
            .dark .session-warning-box #ksnack-session-logout-btn:hover { color: #f8fafc !important; }
          </style>
          <div class="session-warning-box modal-box confirm-modal" style="border-radius:24px;max-width:420px;width:100%;padding:32px 28px;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);text-align:center;transform:scale(0.95);transition:transform 0.25s ease-out;position:relative;">
            <div style="width:60px;height:60px;margin:0 auto 16px;border-radius:50%;background:#fee2e2;color:#e11d48;display:flex;align-items:center;justify-content:center;">
              <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <h3 style="font-size:18px;font-weight:700;margin-bottom:8px;">Sesi Tidak Aktif</h3>
            <p style="font-size:13.5px;color:#64748b;line-height:1.5;margin-bottom:20px;">
              Tidak ada aktivitas selama beberapa waktu. Demi keamanan, sesi login Anda akan otomatis berakhir dalam:
            </p>
            <div style="font-family:inherit;font-variant-numeric:tabular-nums;font-size:36px;font-weight:800;color:#e11d48;margin-bottom:24px;letter-spacing:1px;" id="ksnack-session-countdown">
              05:00
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
              <button id="ksnack-session-extend-btn" type="button" style="width:100%;padding:12px 20px;border-radius:12px;background:#e11d48;color:#ffffff;font-weight:700;font-size:14px;border:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 14px rgba(225,29,72,0.35);transition:all 0.2s;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
                Lanjutkan Sesi
              </button>
              <button id="ksnack-session-logout-btn" type="button" data-instant-logout="true" style="width:100%;padding:10px 20px;border-radius:12px;background:transparent;color:#64748b;font-weight:600;font-size:13px;border:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all 0.15s;">
                Keluar Sekarang
              </button>
            </div>
          </div>
        </div>
      `;

      const div = document.createElement('div');
      div.innerHTML = modalHtml.trim();
      document.body.appendChild(div.firstElementChild);

      this._modalEl = document.getElementById('ksnack-session-warning-modal');
      this._countdownEl = document.getElementById('ksnack-session-countdown');

      const extendBtn = document.getElementById('ksnack-session-extend-btn');
      if (extendBtn) {
        extendBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          this.extendSession();
        });
      }

      const logoutBtn = document.getElementById('ksnack-session-logout-btn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          if (typeof AppSkeleton !== 'undefined') AppSkeleton.hide();
          if (typeof AppAction !== 'undefined') AppAction.show('Mengakhiri sesi sistem...');
          try { sessionStorage.setItem('app_action_triggered', 'true'); } catch (err) {}
          window.location.replace(logoutUrl);
        });
      }
    },

    _tick() {
      if (this._isTriggered) return;

      const idleSeconds = Math.floor((Date.now() - this._lastActivityTime) / 1000);
      const remainingSeconds = this.timeoutSeconds - idleSeconds;

      if (remainingSeconds <= 0) {
        this.handleTimeout();
        return;
      }

      if (remainingSeconds <= this.warningSeconds) {
        this._showWarning(remainingSeconds);
      } else if (this._warningShown) {
        this._hideWarning();
      }
    },

    _showWarning(remainingSeconds) {
      if (!this._modalEl) this._createWarningModal();
      if (!this._modalEl) return;

      this._warningShown = true;
      const mins = Math.floor(remainingSeconds / 60);
      const secs = remainingSeconds % 60;
      const formattedTime = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

      if (this._countdownEl) {
        this._countdownEl.textContent = formattedTime;
      }

      this._modalEl.style.display = 'flex';
      if (window.PopupManager) window.PopupManager.freeze(this._modalEl);
      document.body.classList.add('modal-open');

      requestAnimationFrame(() => {
        this._modalEl.style.opacity = '1';
        const box = this._modalEl.querySelector('.session-warning-box');
        if (box) box.style.transform = 'scale(1)';
      });
    },

    _hideWarning() {
      this._warningShown = false;
      if (!this._modalEl) return;

      if (window.PopupManager) window.PopupManager.unfreeze(this._modalEl);
      document.body.classList.remove('modal-open');

      this._modalEl.style.opacity = '0';
      const box = this._modalEl.querySelector('.session-warning-box');
      if (box) box.style.transform = 'scale(0.95)';
      setTimeout(() => {
        if (!this._warningShown && this._modalEl) {
          this._modalEl.style.display = 'none';
        }
      }, 250);
    },

    sendHeartbeat() {
      this._lastHeartbeatTime = Date.now();
      const heartbeatUrl = window.KSNACK_SESSION?.heartbeatUrl || ((window.APP_BASE_PATH || '') + '/api/auth/heartbeat');

      fetch(heartbeatUrl, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
      .then(res => {
        if (res.status === 401) {
          this.handleTimeout();
          return null;
        }
        return res.json();
      })
      .then(data => {
        if (data && data.success) {
          this._lastActivityTime = Date.now();
        }
      })
      .catch(() => {});
    },

    extendSession() {
      const defaultTimeout = window.KSNACK_SESSION?.timeoutSeconds || 3600;
      const defaultWarning = window.KSNACK_SESSION?.warningSeconds || 300;
      this.timeoutSeconds = defaultTimeout;
      this.warningSeconds = defaultWarning;
      this._lastActivityTime = Date.now();
      this._hideWarning();
      this.sendHeartbeat();

      const tVal = document.getElementById('sim-timeout-val');
      const wVal = document.getElementById('sim-warning-val');
      const sBadge = document.getElementById('sim-status-badge');
      if (tVal) tVal.textContent = `${defaultTimeout}s (1 Jam)`;
      if (wVal) wVal.textContent = `${defaultWarning}s (5 Menit)`;
      if (sBadge) {
        sBadge.textContent = 'Engine Online';
        sBadge.style.color = 'var(--color-success)';
      }
      if (typeof appendSimLog === 'function') {
        appendSimLog('✓ Sesi berhasil diperpanjang oleh pengguna. Modal ditutup dan masa aktif kembali ke 1 jam (3.600s).', 'success');
      }
    },

    handleTimeout() {
      if (this._isTriggered) return;
      this._isTriggered = true;
      if (this._intervalId) clearInterval(this._intervalId);

      this._hideWarning();

      if (typeof AppSkeleton !== 'undefined') AppSkeleton.hide();
      if (typeof AppAction !== 'undefined') AppAction.show('Sesi telah berakhir karena tidak ada aktivitas (1 jam)...');

      const logoutUrl = window.KSNACK_SESSION?.logoutUrl || ((window.APP_BASE_PATH || '') + '/logout?reason=timeout');
      window.location.replace(logoutUrl);
    }
  };

  window.SessionTimeoutEngine = SessionTimeoutEngine;

  /* =====================================================================
     CENTRALIZED POPUP FREEZE & BLUR MANAGER (window.PopupManager)
     ===================================================================== */
  const PopupManager = {
    activePopups: new Set(),
    isFrozen: false,
    observer: null,
    scrollCompensation: 0,

    POPUP_SELECTORS: [
      '.modal-backdrop',
      '.tagihan-modal-backdrop',
      '.tagihan-modal-overlay',
      '.receipt-backdrop',
      '.confirm-overlay',
      '.m3-payment-backdrop',
      '.popup-blur-backdrop',
      '[data-popup-backdrop]',
      '[role="dialog"]',
      '[aria-modal="true"]'
    ],

    EXCLUDED_SELECTORS: [
      '#app-page-loader',
      '#app-action-loader',
      '.app-page-loader',
      '.action-loader-card',
      '#sidebar-overlay',
      '.sidebar-overlay',
      '#toast-container',
      '.toast-container',
      '.toast',
      'template',
      '.sd-dropdown'
    ],

    isElementVisible(el) {
      if (!el || !(el instanceof Element) || !el.isConnected) return false;

      // Exclusions
      for (let i = 0; i < this.EXCLUDED_SELECTORS.length; i++) {
        if (el.matches(this.EXCLUDED_SELECTORS[i]) || el.closest(this.EXCLUDED_SELECTORS[i])) {
          return false;
        }
      }

      // Check x-cloak or hidden attribute
      if (el.hasAttribute('x-cloak') || el.hidden) return false;

      // Check inline style display: none
      if (el.style.display === 'none') return false;

      // Check computed style
      try {
        const cs = window.getComputedStyle(el);
        if (cs.display === 'none' || cs.visibility === 'hidden') {
          return false;
        }
      } catch (e) {
        return false;
      }

      // Must occupy some dimensions or have child dialog box
      return (el.offsetWidth > 0 || el.offsetHeight > 0 || (typeof el.getClientRects === 'function' && el.getClientRects().length > 0));
    },

    freeze(popupEl) {
      if (popupEl && popupEl instanceof Element) {
        this.activePopups.add(popupEl);
      }
      this.applyFreeze();
    },

    unfreeze(popupEl) {
      if (popupEl && popupEl instanceof Element) {
        this.activePopups.delete(popupEl);
      }
      this.updateState();
    },

    applyFreeze() {
      if (this.isFrozen) return;
      this.isFrozen = true;

      // Calculate scrollbar compensation to prevent horizontal layout shift on Windows
      const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
      if (scrollbarWidth > 0) {
        this.scrollCompensation = scrollbarWidth;
        document.documentElement.style.setProperty('--ksnack-scrollbar-comp', `${scrollbarWidth}px`);
      } else {
        this.scrollCompensation = 0;
        document.documentElement.style.removeProperty('--ksnack-scrollbar-comp');
      }

      document.documentElement.classList.add('ksnack-popup-freeze');
      document.body.classList.add('ksnack-popup-freeze');

      try {
        window.dispatchEvent(new CustomEvent('ksnack:popup-open', { detail: { count: this.activePopups.size } }));
      } catch (e) {}
    },

    removeFreeze() {
      if (!this.isFrozen) return;
      this.isFrozen = false;
      this.scrollCompensation = 0;
      document.documentElement.style.removeProperty('--ksnack-scrollbar-comp');
      document.documentElement.classList.remove('ksnack-popup-freeze');
      document.body.classList.remove('ksnack-popup-freeze');

      try {
        window.dispatchEvent(new CustomEvent('ksnack:popup-close', { detail: { count: 0 } }));
      } catch (e) {}
    },

    updateState() {
      // Clean up disconnected or hidden popups from activePopups
      for (const el of this.activePopups) {
        if (!el.isConnected || !this.isElementVisible(el)) {
          this.activePopups.delete(el);
        }
      }

      if (this.activePopups.size > 0) {
        this.applyFreeze();
      } else {
        this.removeFreeze();
      }
    },

    isPopupOpen() {
      return this.activePopups.size > 0;
    },

    scan() {
      const selector = this.POPUP_SELECTORS.join(',');
      const candidates = document.querySelectorAll(selector);
      let foundVisible = false;

      candidates.forEach(el => {
        // Skip child dialog elements (e.g. .m3-dialog inside .modal-backdrop)
        if (el.matches('[role="dialog"]') && el.closest('.modal-backdrop, .confirm-overlay, .tagihan-modal-backdrop, .receipt-backdrop, .m3-payment-backdrop')) {
          return;
        }

        if (this.isElementVisible(el)) {
          this.activePopups.add(el);
          foundVisible = true;
        } else {
          this.activePopups.delete(el);
        }
      });

      this.updateState();
      return foundVisible;
    },

    init() {
      // 1. Initial scan
      this.scan();

      // 2. Setup MutationObserver for zero-boilerplate DOM reactivity
      if (typeof MutationObserver !== 'undefined' && document.body) {
        let timer = null;
        this.observer = new MutationObserver(() => {
          if (timer) return;
          timer = requestAnimationFrame(() => {
            this.scan();
            timer = null;
          });
        });

        this.observer.observe(document.body, {
          childList: true,
          subtree: true,
          attributes: true,
          attributeFilter: ['style', 'class', 'hidden', 'open', 'aria-hidden', 'x-cloak']
        });
      }

      // 3. Prevent touch rubber-banding on background when touching backdrop on mobile
      window.addEventListener('touchmove', (e) => {
        if (!this.isFrozen) return;
        const target = e.target;
        if (!target) return;

        const backdrop = target.closest(
          '.modal-backdrop, .tagihan-modal-backdrop, .tagihan-modal-overlay, .confirm-overlay, .receipt-backdrop, .m3-payment-backdrop, .popup-blur-backdrop, [data-popup-backdrop]'
        );
        if (!backdrop) return;

        const dialog = backdrop.querySelector(
          '.modal-box, .detail-modal-shell, .tagihan-modal-shell, .tagihan-modal-guide-shell, .skema-modal-box, .receipt-container, .m3-dialog, .confirm-modal, .action-loader-card, [role="dialog"], [aria-modal="true"], [data-modal-container], .card, form'
        ) || Array.from(backdrop.children).find(el => !['STYLE', 'SCRIPT', 'TEMPLATE'].includes(el.tagName));

        // If target is inside the modal dialog, allow normal scroll
        if (dialog && dialog.contains(target)) return;

        // If target is backdrop itself or outside modal content, freeze touch drag
        if (e.cancelable) {
          e.preventDefault();
        }
      }, { passive: false });

      // 4. Safe ESC listener to trigger rescan
      window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.isFrozen) {
          setTimeout(() => this.scan(), 60);
        }
      });

      // 5. Cegah penutupan modal saat mengklik ruang kosong (backdrop / background)
      // Seluruh pop up hanya dapat ditutup menggunakan tombol Batal atau Tutup eksplisit
      window.addEventListener('click', (e) => {
        const backdrop = e.target.closest(
          '.modal-backdrop, .tagihan-modal-backdrop, .tagihan-modal-overlay, .confirm-overlay, .receipt-backdrop, .m3-payment-backdrop, .popup-blur-backdrop, [data-popup-backdrop]'
        );
        if (!backdrop) return;

        // Cari elemen kotak dialog utama di dalam backdrop
        const dialog = backdrop.querySelector(
          '.modal-box, .detail-modal-shell, .tagihan-modal-shell, .tagihan-modal-guide-shell, .skema-modal-box, .receipt-container, .m3-dialog, .confirm-modal, .action-loader-card, [role="dialog"], [aria-modal="true"], [data-modal-container], .card, form'
        ) || Array.from(backdrop.children).find(el => !['STYLE', 'SCRIPT', 'TEMPLATE'].includes(el.tagName));

        // Jika klik berada di dalam kotak dialog (tombol Tutup/Batal, input, link, tab), IZINKAN NORMAL
        if (dialog && dialog.contains(e.target)) {
          return;
        }

        // Jika target klik di ruang kosong backdrop (di luar dialog modal), batalkan event
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        return false;
      }, true);
    }
  };

  window.PopupManager = PopupManager;
  window.ModalFreezeManager = PopupManager;

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
    SessionTimeoutEngine.init();
    PopupManager.init();
  });

})();


