---
name: erp-ui-design
description: >-
  Must read before creating or editing KEREN ONE UI: files in views/**, public/assets/**, new pages,
  modal pop-ups, data tables, KPI cards, filter bars, CSS/Tailwind, theme colors, PWA notch, or standalone
  portal navigation such as /guide. Covers complete design system, page anatomy, modal DNA, M3 ban, and dialog helpers.
---

# Design Guidelines & UI/UX Standards for KEREN ONE (Design System)

> **Single Source of Truth (SSOT)**  
> All Agentic AIs and software engineers must strictly adhere to these guidelines when creating or updating pages, components, forms, tables, and modal pop-ups in the **KEREN ONE** repository and its ecosystem.

---

## 1. Design Philosophy & Brand DNA
KEREN ONE adopts the **Modern Enterprise Clean UI** design language guided by **Contextual Harmony & Peer-View Replication** paired with the **Google Chrome Dark Mode Standard**:
- **Contextual Harmony & Peer-View Replication (First Principle)**: Every UI modification MUST visually align with existing active pages of the same category in the repository (`views/products`, `views/customers`, `views/customer_orders`). Never invent arbitrary styles, force external UI dogmas, or construct components in isolation without referencing active views.
- **High Information Density**: Layouts are designed to be concise, dense, and space-efficient without wasted whitespace.
- **Clean & Crisp Typography**: Uses the **Inter** font family for interface copy and **JetBrains Mono** for numbers, transaction codes, currency, and dates.
- **Brand Identity Colors (Brand DNA)**:
  - PWA Status Bar / Notch / Header Accent: `#881337` (*Deep Maroon / Rose*)
  - Brand Accent Dark Mode: `#fb7185` (*Rose 400*)
  - Primary Call-to-Action (*CTA Primary*): `#2563eb` (Light Mode) / `#8ab4f8` (Dark Mode)
  - Status Success / Paid / Profit: `#10b981` (Emerald)
  - Status Warning / Due / Terms: `#f59e0b` (Amber)
  - Status Danger / Out of Stock / Delete: `#ef4444` (Red)
- **Borders & Rounded Hierarchy**:
  - **Cards, Modals, Panels, & Form Action Buttons**: Use 1px solid border (`var(--color-hairline)` / `border-slate-200` / `border-zinc-700`) with moderate rounded corners `rounded-lg` (8px - 12px) or `rounded-md`.
  - **Badge Counters, Status Tags, & Notification Pills**: **Must be sleek, smooth pills (`.badge-counter` / `rounded-full`)** with proportional padding and min-width so they never render as rigid square dice.
- **Iconography**: Always use **Lucide** icons (`data-lucide="..."`).

---

## 2. 🚫 STRICT PROHIBITIONS: Anti-Patterns
Applying Google Material Design 3 patterns or rigid artificial styling is strictly forbidden:
1. ❌ **Forbidden**: Using Floating Action Buttons (large circular FAB in bottom corners).
2. ❌ **Forbidden**: Using full-pill buttons or excessively rounded corners (`rounded-2xl`, `rounded-3xl`) for **primary form action buttons** (Save, Submit, Modal Actions). *(Exception: Numeric counter badges and status tags MUST be `rounded-full`)*.
3. ❌ **Forbidden**: Using heavy multi-level elevation shadows (*M3 surface container tonal palettes*).
4. ❌ **Forbidden**: Using legacy serif or Roboto fonts; `Inter` and `JetBrains Mono` are mandatory.
5. ❌ **Forbidden**: Building components from scratch or inventing isolated styles without inspecting and replicating the structure of existing reference files (*Golden Templates & Peer Views*).
6. ❌ **Forbidden**: Using native browser dialogs `alert()` or `confirm()`.
7. ❌ **Forbidden**: Using raw, unclassed `<button>` elements without standard button classes (`.btn`, `.btn-primary`, `.btn-ghost`) or dedicated segmented controls (`.segmented-track` + `.segmented-btn`). Because KEREN ONE uses pure CSS without full Tailwind preflight, raw `<button>` elements render default browser User-Agent styles (`border: 2px outset buttonface`), generating dated 3D beveled black borders.

---

## 3. Mandatory Protocol: Golden Peer-View Replication
Before creating or modifying any view file, AI agents **MUST** open and inspect at least one reference page corresponding to the module category to mirror class structure, proportions, margins, and color schemes:
- **Dashboard / Executive Summary Cards**: `views/dashboard/index.php` & `views/owner/index.php`
- **Master Data / Full Table + Filter Tabs + Pop-up Modals**: `views/products/index.php`, `views/customers/index.php` & `views/inventory/index.php`
- **Transactions / Invoices / Multi-item Builder**: `views/customer_orders/index.php` & `views/pos/index.php`
- **Form Management & Logistics**: `views/deliveries/index.php` & `views/purchases/index.php`

---

## 3.5. Badge & Numeric Counter Standard DNA
To prevent numeric counter badges from appearing as rigid, blocky dice, apply these element standards:
1. **Tab & Notification Counter Pills (`.badge-counter`)**:
   - Use the official `.badge-counter` class (defined in `public/assets/css/app.css`).
   - Smooth pill shape (`border-radius: var(--rounded-full)`), `min-width: 20px`, `height: 18px`, `padding: 0 6px`.
   - Example: `<span class="badge-counter" x-text="items.length"></span>`.
2. **Status Pills (Available / Low Stock / Out of Stock / Active)**:
   - Use `.badge.badge-success`, `.badge.badge-warning`, `.badge.badge-danger`.
   - Automatically `rounded-full` with padding `2.5px var(--space-sm)`.
3. **SKU & Transaction Code Monospace Box (`.badge-mono`)**:
   - Reserved strictly for SKU codes or invoice numbers that need monospace text inside a subtle rectangular frame: use `.badge.badge-mono` (`border-radius: var(--rounded-xs)` / 4px). Never use `.badge-mono` for tab counters!
4. **Precision Centering Invariant (Vertical & Horizontal)**:
   - Every badge element (`.badge`, `.badge-counter`, status pills, filter indicator chips) **MUST BE PERFECTLY CENTERED** both vertically and horizontally.
   - Always apply the precision flexbox pattern:
     ```css
     display: inline-flex;
     align-items: center;
     justify-content: center;
     line-height: 1;
     ```
   - Avoid browser default line-heights (such as 1.4–1.5) which cause text to sag below the badge boundary. When a badge includes a pulse dot or Lucide icon, always apply `flex-shrink: 0;` to ensure symmetrical vertical alignment.

---

## 4. Standard Page Anatomy (*Page Anatomy*)

Every primary view must be wrapped inside the standard container:
```html
<?php
use App\Core\Router;
use App\Core\Auth;
use App\Helpers\Format;
ob_start();
?>

<div x-data="featureApp()" x-init="init()" class="space-y-5 pb-20">

    <!-- 1. PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-blue"> <!-- is-blue | is-emerald | is-amber | is-cyan | is-violet -->
                <i data-lucide="layers"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#2563eb;"></span>
                    <span>Modul Operasional</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Judul Halaman' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Keterangan ringkas fungsi dan tujuan halaman' ?></p>
            </div>
        </div>

        <!-- Header Action Buttons (Right) -->
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <button @click="openAddModal()" class="btn btn-primary" style="height:38px;">
                <i data-lucide="plus"></i>
                <span>Tambah Data Baru</span>
            </button>
        </div>
    </div>

    <!-- 2. QUICK STATS KPI (Optional: if the module requires numeric summaries) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card" style="display:flex; align-items:center; gap:14px;">
            <div class="stat-card-icon" style="background:rgba(37,99,235,0.1); color:var(--color-primary);">
                <i data-lucide="boxes"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Data</div>
                <div class="stat-card-value font-mono" x-text="stats.total">0</div>
                <div class="text-xs text-slate-500 mt-0.5">Semua rekaman aktif</div>
            </div>
        </div>
    </div>

    <!-- 3. MAIN CARD & FILTER BAR -->
    <div class="card" style="padding:0; overflow:hidden;">
        <!-- Filter Bar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 sm:p-4 border-b"
             style="border-color:var(--color-hairline); background-color:var(--color-canvas);">
            <div class="flex items-center gap-2 flex-1 sm:max-w-md w-full">
                <!-- Debounced Search Input -->
                <div class="form-input-icon flex-1 relative">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="search" x-model.debounce.300ms="searchQuery"
                           placeholder="Cari data, kode, nama..."
                           autocomplete="off" class="form-input" style="height:38px; font-size:13px; padding-right:32px;">
                    <button type="button" x-cloak x-show="searchQuery" @click="searchQuery = ''"
                            class="btn btn-ghost btn-xs text-slate-400 hover:text-slate-600"
                            style="position:absolute; right:8px; top:50%; transform:translateY(-50%);">
                        <i data-lucide="x" style="width:14px; height:14px;"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="relative overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 860px;">
                <thead>
                    <tr>
                        <th style="width:110px;" class="cell-nowrap">Kode</th>
                        <th>Nama Entitas</th>
                        <th>Kategori</th>
                        <th class="cell-right">Nominal / Qty</th>
                        <th class="cell-center" style="width:100px;">Status</th>
                        <th class="cell-center" style="width:110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in filteredItems" :key="item.id">
                        <tr>
                            <td class="cell-nowrap">
                                <span class="badge badge-mono" x-text="item.kode"></span>
                            </td>
                            <td>
                                <strong class="text-sm" style="color:var(--color-ink);" x-text="item.nama"></strong>
                            </td>
                            <td x-text="item.kategori || '-'"></td>
                            <td class="cell-right font-mono font-bold" x-text="formatRupiah(item.nominal)"></td>
                            <td class="cell-center">
                                <span :class="item.status_aktif ? 'badge badge-success' : 'badge badge-danger'"
                                      x-text="item.status_aktif ? 'Aktif' : 'Nonaktif'"></span>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openEditModal(item)" class="btn btn-ghost btn-sm" title="Edit">
                                        <i data-lucide="edit-3" style="width:14px; height:14px;"></i>
                                    </button>
                                    <button @click="deleteItem(item)" class="btn btn-ghost btn-sm text-red-500 hover:text-red-700" title="Hapus">
                                        <i data-lucide="trash-2" style="width:14px; height:14px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <template x-if="filteredItems.length === 0">
                        <tr>
                            <td colspan="6" style="text-align:center; padding:40px 20px; color:var(--color-ink-mute);">
                                <i data-lucide="inbox" style="width:36px; height:36px; margin:0 auto 8px auto; opacity:0.4;"></i>
                                <div style="font-weight:600; font-size:13px;">Tidak ada data yang tersedia</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>
```

---

## 5. Modal & Pop-up DNA (*Universal Popup Standard*)

Every pop-up or modal form **must** adhere strictly to the standard KEREN ONE anatomical structure:

### Mandatory Modal Rules:
1. **Use `<template x-teleport="body">`**: Ensures z-index stacking is never trapped or overridden by parent containers.
2. **Backdrop & Box**: Use classes `.modal-backdrop` and `.modal-box` (with size variants `.modal-box-sm`, `.modal-box-md`, `.modal-box-lg`, `.modal-box-xl`).
3. **Mobile Handle Bar**: Always include `<div class="modal-handle"><div class="modal-handle-bar"></div></div>` at the very top of `.modal-box` so it functions smoothly as a bottom sheet drawer on mobile devices.
4. **Consistent Header**: 40x40px icon box with 12px rounded corners + distinct bold title + supportive subtitle + cross close button `.modal-close-x`.
5. **Responsive Action Footer**:
   - Desktop: "Batal" (Cancel) button on the left (`modal-btn-cancel-desktop`) and "Simpan" (Save) button on the right.
   - Mobile: Automatically transforms into a full-width action button comfortably reached by thumb (desktop cancel button is automatically hidden by CSS in `app.css`).

### Standard Modal Template Code:
```html
<template x-teleport="body">
    <div x-show="showModal" x-cloak class="modal-backdrop" @click="showModal = false">
        <div class="modal-box modal-box-md" @click.stop>
            
            <!-- Handle Bar for Mobile Bottom Sheet Drawer -->
            <div class="modal-handle"><div class="modal-handle-bar"></div></div>

            <!-- Modal Header -->
            <div class="modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div style="width:40px; height:40px; border-radius:12px; background:rgba(37,99,235,0.12); color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i data-lucide="box" style="width:20px; height:20px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="modal-title" x-text="isEdit ? 'Edit Data Entitas' : 'Tambah Entitas Baru'"></div>
                        <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">Lengkapi formulir di bawah ini dengan data valid.</div>
                    </div>
                </div>
                <button type="button" @click="showModal = false" class="modal-close-x" title="Tutup Modal">
                    <i data-lucide="x" style="width:18px; height:18px;"></i>
                </button>
            </div>

            <!-- Form & Body -->
            <form action="<?= Router::url('/modul/simpan') ?>" method="POST" @submit="submitForm($event)">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="id" :value="form.id">

                <div class="modal-body custom-scrollbar space-y-3.5">
                    <div>
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="nama" x-model="form.nama" required class="form-input" placeholder="Masukkan nama...">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Kategori *</label>
                            <select name="kategori_id" x-model="form.kategori_id" required class="form-input">
                                <option value="" disabled>-- Pilih Kategori --</option>
                                <option value="1">Kategori Utama</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Nominal (Rp)</label>
                            <input type="text" name="nominal" x-model="form.nominal" class="form-input font-mono" placeholder="0">
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" @click="showModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                    <button type="submit" class="btn btn-primary w-full sm:w-auto" style="display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                        <i data-lucide="save" style="width:16px; height:16px;"></i>
                        <span x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Data'"></span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>
```

---

## 6. Notification Standards, Dialog Confirmations & JS Alerts

Never use native browser `window.confirm()` or `alert()`, which degrade interface aesthetics. Always utilize the universal helpers provided in `public/assets/js/erp-helpers.js`:

### A. Dangerous Action / Deletion Confirmation (`AppConfirm`):
```javascript
const ok = await window.AppConfirm({
    title: 'Konfirmasi Hapus Data',
    message: 'Apakah Anda yakin ingin menghapus data "' + item.nama + '"?',
    submessage: 'Tindakan ini tidak dapat dibatalkan.',
    type: 'danger',           // 'danger' | 'warning' | 'primary' | 'info'
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal'
});

if (ok) {
    // Execute deletion logic
}
```

### B. Floating Toast Notifications (`window.toast`):
```javascript
window.toast.success('Data berhasil disimpan ke sistem.');
window.toast.error('Gagal memproses transaksi: Saldo kas tidak mencukupi.');
window.toast.warning('Stok barang di gudang mendekati batas minimum.');
window.toast.info('Pencarian data selesai dimuat.');
```

### C. System Alert Dialogs (`AppAlert`):
```javascript
await window.AppAlert({
    title: 'Batas Kuota Tercapai',
    message: 'Toko ini telah melebihi batas limit piutang belanja tempo.',
    type: 'warning',
    buttonText: 'Saya Mengerti'
});
```

---

## 7. Number Formatting Helpers & Lucide Icon Refresh
The application provides global helpers in `erp-helpers.js` readily available in Alpine.js and vanilla scripts:
- `formatRupiah(150000)` $\rightarrow$ `"Rp 150.000"`
- `formatRupiahNumber(150000)` $\rightarrow$ `"150.000"`
- `unformatRupiah("Rp 150.000")` $\rightarrow$ `150000`
- `refreshIcons()` $\rightarrow$ Call this whenever dynamic DOM is rendered via Alpine.js (`$nextTick(() => refreshIcons())`) so Lucide icons appear immediately without delay.

---

## 8. Rendering Performance & Transition Speed (Zero Delay)
- **Avoid Blurring on Large Containers**: Never apply `filter: blur(...)` directly to large DOM containers (`<header>`, `<main>`), as it strains GPU/CPU rendering, especially on mobile devices.
- **Use Hardware-Accelerated Backdrop Blur**: Use `backdrop-filter: blur(6px)` only on modal overlay backdrops (`.modal-backdrop`), which benefit from GPU hardware acceleration.

---

## 9. Smart Navigation for Standalone PWA Portals (*Smart Navigation*)
For standalone portal views or independent popups such as `/guide` or printable views:
- The close/back button **must** employ tiered fallback navigation:
  ```javascript
  if (window.opener || window.history.length <= 1) {
      window.close();
  } else {
      window.history.back();
  }
  // Fallback if navigation did not occur:
  setTimeout(() => { window.location.href = '/dashboard'; }, 300);
  ```
  This ensures users are never trapped or lose their active transaction context when the application runs as an installed PWA or desktop shortcut.

---

## 10. Key CSS Variables Summary (`app.css`)
| CSS Variable | Default Value | Primary Usage |
| :--- | :--- | :--- |
| `--color-primary` | `#2563eb` (Light) / `#8ab4f8` (Dark) | Primary CTA buttons, active state, main links |
| `--color-canvas` | `#ffffff` (Light) / `#303134` (Dark) | Background for cards, modals, headers |
| `--color-canvas-soft` | `#f1f5f9` (Light) / `#202124` (Dark) | App base background & secondary sub-panels |
| `--color-hairline` | `#e2e8f0` (Light) / `#3c4043` (Dark) | Table borders, row dividers, card edges |
| `--color-ink` | `#0f172a` (Light) / `#e8eaed` (Dark) | Heading text, primary titles, bold content |
| `--color-ink-mute` | `#64748b` (Light) / `#9aa0a6` (Dark) | Descriptive text, subtitles, placeholders |
| `--font-sans` | `'Inter', sans-serif` | Default font across the entire interface |
| `--font-mono` | `'JetBrains Mono', monospace` | Numbers, transaction codes, currency, dates |

---

## 11. Pre-flight UI Checklist (*UI Pre-flight Checklist*)
- [ ] Strictly follows peer-view replication: inspected and harmonized with existing active views in `views/**`.
- [ ] Zero M3 Anti-patterns: no FAB, no full-pill form action buttons, no Roboto font.
- [ ] Tab counters and notification numbers use `.badge-counter` (`rounded-full`), avoiding rigid square dice.
- [ ] No raw unclassed `<button>` tags; all buttons use `.btn` variants or `.segmented-btn` with explicit resets.
- [ ] Modal conforms to standard DNA (`x-teleport`, `.modal-backdrop`, `.modal-box`, `.modal-handle`, 40x40 header icon, responsive footer).
- [ ] All interactive dialogs use `AppConfirm()`, `AppAlert()`, `window.toast` (zero native `alert()`/`confirm()`).
- [ ] Monetary inputs and transaction codes use monospace typography (`font-mono`).
- [ ] `$nextTick(() => refreshIcons())` is invoked during dynamic Alpine.js DOM manipulations.
- [ ] Colors conform to light & dark mode specifications (`#881337` / `#fb7185`).
