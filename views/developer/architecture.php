<?php
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="devArchitectureApp()" x-init="init()" class="space-y-5 pb-16">

    <!-- ========================================================================= -->
    <!-- 1. DEVELOPER HERO & TELEMETRY BANNER (ADAPTIVE MINIMALIST)                -->
    <!-- ========================================================================= -->
    <div class="card p-4 sm:p-6" style="border-radius:18px;border:1px solid var(--color-hairline-strong);background:var(--color-canvas);box-shadow:var(--shadow-1);">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            
            <div class="flex items-start sm:items-center gap-3.5">
                <!-- Icon Box -->
                <div style="width:48px;height:48px;border-radius:14px;background:rgba(37,99,235,0.12);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(37,99,235,0.25);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="16" height="16" x="4" y="4" rx="2"></rect>
                        <rect width="6" height="6" x="9" y="9" rx="1"></rect>
                        <path d="M15 2v2"></path>
                        <path d="M15 20v2"></path>
                        <path d="M2 15h2"></path>
                        <path d="M2 9h2"></path>
                        <path d="M20 15h2"></path>
                        <path d="M20 9h2"></path>
                        <path d="M9 2v2"></path>
                        <path d="M9 20v2"></path>
                    </svg>
                </div>

                <div>
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="badge badge-primary" style="font-size:10px;font-weight:800;letter-spacing:0.04em;padding:2px 8px;">AI ARCHITECTURE STUDIO</span>
                        <span style="font-size:11px;font-weight:700;color:var(--color-success);display:inline-flex;align-items:center;gap:4px;">
                            <span style="width:6px;height:6px;border-radius:9999px;background:var(--color-success);display:inline-block;"></span>
                            Engine Online
                        </span>
                        <span class="badge badge-mono" style="font-size:10px;">PHP <?= htmlspecialchars($systemInfo['php_version'] ?? PHP_VERSION) ?></span>
                        <span class="badge badge-mono" style="font-size:10px;"><?= $systemInfo['total_tables'] ?? count($tables) ?> Tables (PostgreSQL 17)</span>
                        <span class="badge badge-mono" style="font-size:10px;"><?= $systemInfo['total_procedures'] ?? count($procedures) ?> Procedures &amp; RPC</span>
                        <span class="badge badge-mono" style="font-size:10px;"><?= $systemInfo['total_controllers'] ?? 24 ?> Controllers</span>
                        <span class="badge badge-mono" style="font-size:10px;"><?= $systemInfo['total_helpers'] ?? 13 ?> Helpers</span>
                        <span class="badge badge-mono" style="font-size:10px;"><?= $systemInfo['total_suites'] ?? 27 ?> Test Suites</span>
                        <span class="badge badge-mono" style="font-size:10px;">Cloudflare R2 Storage</span>
                    </div>
                    <h1 style="font-size:18px;font-weight:900;color:var(--color-ink);line-height:1.3;">
                        System Architecture Blueprint &amp; AI Studio
                    </h1>
                    <p style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
                        Peta arsitektur terverifikasi (100% Single Source of Truth) &amp; generator prompt konteks terpadu untuk AI Programming Logic.
                    </p>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="flex items-center gap-2 w-full md:w-auto">
                <button type="button" @click="copyFullSystemMap()" class="btn btn-secondary flex-1 md:flex-initial" style="font-size:12px;font-weight:700;height:38px;white-space:nowrap;border-radius:10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Salin Full Arsitektur</span>
                </button>

                <a href="<?= Router::url('/developer') ?>" class="btn btn-secondary flex-1 md:flex-initial" style="font-size:12px;font-weight:700;height:38px;border-radius:10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>Portal Developer</span>
                </a>
            </div>

        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. SEGMENTED TABS (MINIMALIST & TOUCH FRIENDLY)                           -->
    <!-- ========================================================================= -->
    <div class="no-scrollbar" style="display:flex;gap:8px;overflow-x:auto;padding-bottom:2px;-webkit-overflow-scrolling:touch;">
        
        <!-- Tab 1: AI Prompt Studio -->
        <button type="button" @click="activeTab = 'ai_copier'" 
                :style="activeTab === 'ai_copier' ? 'background:var(--color-primary);color:#ffffff;border-color:var(--color-primary);' : 'background:var(--color-canvas);color:var(--color-ink-secondary);border-color:var(--color-hairline);'"
                class="btn btn-sm" style="font-weight:800;font-size:12px;border-radius:10px;padding:8px 14px;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;transition:all 0.15s ease;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 8V4H8"></path>
                <rect width="16" height="12" x="4" y="8" rx="2"></rect>
                <path d="M2 14h2"></path>
                <path d="M20 14h2"></path>
                <path d="M15 13v2"></path>
                <path d="M9 13v2"></path>
            </svg>
            <span>AI Prompt Studio</span>
        </button>

        <!-- Tab 2: Struktur Folder & File -->
        <button type="button" @click="activeTab = 'tree_explorer'" 
                :style="activeTab === 'tree_explorer' ? 'background:var(--color-primary);color:#ffffff;border-color:var(--color-primary);' : 'background:var(--color-canvas);color:var(--color-ink-secondary);border-color:var(--color-hairline);'"
                class="btn btn-sm" style="font-weight:800;font-size:12px;border-radius:10px;padding:8px 14px;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;transition:all 0.15s ease;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
            <span>Struktur Direktori &amp; File</span>
        </button>

        <!-- Tab 3: Visual Topology -->
        <button type="button" @click="activeTab = 'topology'" 
                :style="activeTab === 'topology' ? 'background:var(--color-primary);color:#ffffff;border-color:var(--color-primary);' : 'background:var(--color-canvas);color:var(--color-ink-secondary);border-color:var(--color-hairline);'"
                class="btn btn-sm" style="font-weight:800;font-size:12px;border-radius:10px;padding:8px 14px;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;transition:all 0.15s ease;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="16" y="16" width="6" height="6" rx="1"></rect>
                <rect x="2" y="16" width="6" height="6" rx="1"></rect>
                <rect x="9" y="2" width="6" height="6" rx="1"></rect>
                <path d="M5 16v-3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3"></path>
                <path d="M12 12V8"></path>
            </svg>
            <span>Visual Topologi 3-Layer</span>
        </button>

        <!-- Tab 4: Sitemap -->
        <button type="button" @click="activeTab = 'sitemap'" 
                :style="activeTab === 'sitemap' ? 'background:var(--color-primary);color:#ffffff;border-color:var(--color-primary);' : 'background:var(--color-canvas);color:var(--color-ink-secondary);border-color:var(--color-hairline);'"
                class="btn btn-sm" style="font-weight:800;font-size:12px;border-radius:10px;padding:8px 14px;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;transition:all 0.15s ease;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"></polygon>
                <line x1="9" x2="9" y1="3" y2="18"></line>
                <line x1="15" x2="15" y1="6" y2="21"></line>
            </svg>
            <span>Peta Rute &amp; URL Endpoints</span>
        </button>

        <!-- Tab 5: Database -->
        <button type="button" @click="activeTab = 'database'" 
                :style="activeTab === 'database' ? 'background:var(--color-primary);color:#ffffff;border-color:var(--color-primary);' : 'background:var(--color-canvas);color:var(--color-ink-secondary);border-color:var(--color-hairline);'"
                class="btn btn-sm" style="font-weight:800;font-size:12px;border-radius:10px;padding:8px 14px;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;transition:all 0.15s ease;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"></path>
            </svg>
            <span>Database (<?= count($tables) ?> Tables &bull; <?= count($procedures) ?> Routines)</span>
        </button>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: INTERACTIVE AI PROMPT STUDIO (SUPER EASY FOR AI PROGRAMMING)       -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'ai_copier'" class="space-y-4">
        
        <!-- Configurator Card -->
        <div class="card p-4 sm:p-5 space-y-4" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
            
            <!-- Header -->
            <div class="pb-3 border-b space-y-1" style="border-color:var(--color-hairline);">
                <div class="flex items-center gap-2">
                    <span style="font-size:18px;">🤖</span>
                    <h2 style="font-size:16px;font-weight:900;color:var(--color-ink);">Interactive AI Prompt Studio &amp; Context Generator</h2>
                </div>
                <p style="font-size:12px;color:var(--color-ink-mute);line-height:1.5;">
                    Pilih modul &amp; tipe tugas, masukkan instruksi kustom, dan AI Studio akan otomatis membuat master prompt berkonteks presisi 100% sesuai realita proyek!
                </p>
            </div>

            <!-- 3-Step Interactive Configurator Form -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                
                <!-- Step 1: Target Module -->
                <div class="space-y-1.5">
                    <label style="display:block;font-size:12px;font-weight:800;color:var(--color-ink);">
                        1. Pilih Modul Target
                    </label>
                    <select x-model="selectedModule" @change="generatePrompt()" class="form-select w-full text-xs font-bold" style="height:42px;border-radius:10px;">
                        <option value="consignment_portal">📱 Konsinyasi Terpadu &amp; Sales Mobile</option>
                        <option value="delivery_driver">🚚 Driver Logistik &amp; Pengiriman</option>
                        <option value="owner_dashboard">👑 Owner Executive Command Center</option>
                        <option value="pos_cashier">🛒 Kasir POS &amp; Retail Penjualan</option>
                        <option value="customer_orders_b2b">📦 Pesanan Pelanggan &amp; Grosir B2B</option>
                        <option value="finance_cash">💰 Keuangan, Mutasi Kas &amp; Valuasi</option>
                        <option value="inventory_bom">🏭 Gudang, Opname Stok &amp; Komposisi BOM</option>
                        <option value="pricing_engine">🏷️ Matriks 30 Level Harga &amp; Tier Toko</option>
                        <option value="master_employees">👥 Karyawan (Sales vs Driver) &amp; Penggajian</option>
                        <option value="rbac_permissions">🔐 Pengguna, Peran &amp; 5-Tab RBAC</option>
                        <option value="import_data_engine">📥 Master Data Impor &amp; Diffing Reconciliation</option>
                        <option value="media_cloud_storage">☁️ Cloudflare R2 Media Proxy &amp; Local Disk Cache</option>
                        <option value="settings_audit">⚙️ Pengaturan Toko &amp; Audit Log Forensik</option>
                        <option value="developer_test_runner">🧪 Developer Portal &amp; Test Runner (27 Suites)</option>
                    </select>
                </div>

                <!-- Step 2: Task Intent -->
                <div class="space-y-1.5">
                    <label style="display:block;font-size:12px;font-weight:800;color:var(--color-ink);">
                        2. Tipe Tugas AI
                    </label>
                    <select x-model="taskType" @change="generatePrompt()" class="form-select w-full text-xs font-bold" style="height:42px;border-radius:10px;">
                        <option value="add_feature">✨ Tambah Fitur / Tombol / Alur Baru</option>
                        <option value="fix_bug">🐛 Perbaiki Bug / Glitch / Edge Case (Zero-Defect)</option>
                        <option value="redesign_ui">🎨 Polish &amp; Redesign UI/UX Modern Minimalis</option>
                        <option value="optimize_logic">⚡ Optimasi Logika Bisnis &amp; Stored Procedure RPC</option>
                        <option value="write_test">🧪 Buat Test Otomatisasi (Integration Test)</option>
                    </select>
                </div>

                <!-- Step 3: Custom Instruction -->
                <div class="space-y-1.5">
                    <label style="display:block;font-size:12px;font-weight:800;color:var(--color-ink);">
                        3. Instruksi Khusus (Opsional)
                    </label>
                    <input type="text" x-model="customNote" @input="generatePrompt()" placeholder="Contoh: Tambahkan tombol export PDF nota..." class="form-input text-xs" style="height:42px;border-radius:10px;">
                </div>

            </div>

        </div>

        <!-- Master AI Prompt Studio Output Card -->
        <div class="card p-4 sm:p-5 space-y-3.5" style="border-radius:16px;background:var(--color-canvas);border:1.5px solid var(--color-hairline-strong);box-shadow:var(--shadow-2);">
            
            <!-- Output Header -->
            <div class="flex items-center justify-between gap-2 pb-2.5 border-b" style="border-color:var(--color-hairline);">
                <div class="flex items-center gap-2">
                    <div style="width:28px;height:28px;border-radius:8px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                        </svg>
                    </div>
                    <div>
                        <h3 style="font-size:13.5px;font-weight:900;color:var(--color-ink);">Master AI Prompt (Siap Ditempel ke AI Assistant)</h3>
                    </div>
                </div>
                
                <span class="badge badge-success font-mono" style="font-size:10.5px;font-weight:700;">Live Auto-Generated</span>
            </div>

            <!-- Enlarged Code / Prompt Textarea -->
            <div style="position:relative;">
                <textarea x-model="generatedPrompt" 
                          class="form-input font-mono no-scrollbar" 
                          readonly 
                          style="width:100%;min-height:360px;height:380px;font-size:12px;line-height:1.65;background:var(--color-canvas-soft);color:var(--color-ink);border-radius:12px;padding:14px;border:1px solid var(--color-hairline);resize:vertical;"></textarea>
            </div>

            <!-- Prominent 1-Click Copy Button -->
            <div>
                <button type="button" 
                        @click="copyPrompt()" 
                        class="btn w-full py-3 sm:py-3.5" 
                        :style="copied ? 'background:#059669;color:#ffffff;border-color:#059669;' : 'background:var(--color-success);color:#ffffff;border-color:var(--color-success);'"
                        style="font-size:14px;font-weight:900;border-radius:12px;box-shadow:var(--shadow-1);display:flex;align-items:center;justify-content:center;gap:8px;transition:all 0.15s ease;">
                    <template x-if="!copied">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                        </svg>
                    </template>
                    <template x-if="copied">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </template>
                    <span x-text="copied ? 'BERHASIL DISALIN KE CLIPBOARD! ✅' : 'Salin Master Prompt AI (1-Click) 📋'"></span>
                </button>
            </div>

        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: INTERACTIVE DIRECTORY & FILE STRUCTURE EXPLORER                    -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'tree_explorer'" class="space-y-4">
        <div class="card p-4 sm:p-5 space-y-4" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-3 border-b" style="border-color:var(--color-hairline);">
                <div>
                    <h2 style="font-size:15px;font-weight:900;color:var(--color-ink);display:flex;align-items:center;gap:6px;">
                        <span>🌳</span>
                        <span>Interactive Directory &amp; File Architecture Explorer</span>
                    </h2>
                    <p style="font-size:12px;color:var(--color-ink-mute);margin-top:1px;">
                        Klik folder manapun untuk melihat rincian file di dalamnya beserta dokumentasi fungsinya secara lengkap!
                    </p>
                </div>
                <input type="text" x-model="searchFolder" placeholder="Cari folder / nama file..." class="form-input" style="height:36px;font-size:12px;max-width:240px;">
            </div>

            <!-- Grid Folder Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <template x-for="f in filteredFolders" :key="f.id">
                    <div @click="openFolder(f)" 
                         class="p-4 rounded-xl cursor-pointer hover:border-primary transition-all flex flex-col justify-between space-y-3"
                         style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        
                        <div class="space-y-2">
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center gap-2">
                                    <span style="font-size:20px;flex-shrink:0;" x-text="f.icon"></span>
                                    <h3 class="font-mono font-bold text-sm truncate" style="color:var(--color-ink);" x-text="f.name"></h3>
                                </div>
                                <div class="flex">
                                    <span class="badge badge-primary font-mono text-[9.5px]" x-text="f.badge"></span>
                                </div>
                            </div>
                            <p style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.4;" x-text="f.desc"></p>
                        </div>

                        <div class="pt-2 border-t" style="border-color:var(--color-hairline);">
                            <button type="button" class="btn btn-secondary w-full" style="font-size:11.5px;font-weight:800;border-radius:10px;height:38px;display:flex;align-items:center;justify-content:space-between;padding:0 12px;background:var(--color-canvas);border:1px solid var(--color-hairline-strong);color:var(--color-primary);">
                                <span class="flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                    <span>Buka Rincian File</span>
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                        </div>

                    </div>
                </template>
            </div>

        </div>
    </div>

    <!-- MODAL FOLDER INSPECTOR (RINCIAN FILE & PENJELASAN SINGKAT) -->
    <template x-teleport="body">
        <div x-show="selectedFolder !== null" 
             x-cloak 
             class="modal-backdrop" 
             style="position:fixed !important;inset:0 !important;width:100vw !important;height:100vh !important;height:100dvh !important;margin:0 !important;padding:16px !important;z-index:999999 !important;background:rgba(0,0,0,0.85) !important;backdrop-filter:blur(10px) !important;display:flex !important;align-items:center !important;justify-content:center !important;" 
             @keydown.escape.window="closeFolder()">
            
            <div class="modal-box space-y-4" 
                 style="width:100%;max-width:680px;max-height:88vh;max-height:88dvh;border-radius:20px;background:var(--color-canvas);border:1.5px solid var(--color-hairline-strong);box-shadow:0 25px 50px -12px rgba(0, 0, 0, 0.7);padding:20px;margin:auto;display:flex;flex-direction:column;position:relative;z-index:1000000;" 
                 @click.outside="closeFolder()">
                
                <template x-if="selectedFolder">
                    <div class="space-y-4 flex-1 flex flex-col min-h-0">
                        <!-- Modal Header -->
                        <div class="modal-header pb-3 border-b flex items-center justify-between gap-2 flex-shrink-0" style="border-color:var(--color-hairline);">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span style="font-size:24px;flex-shrink:0;" x-text="selectedFolder.icon"></span>
                                <div class="min-w-0">
                                    <h3 class="font-mono truncate" style="font-size:15px;font-weight:900;color:var(--color-ink);" x-text="selectedFolder.name"></h3>
                                    <p style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.3;" x-text="selectedFolder.desc"></p>
                                </div>
                            </div>
                            <button type="button" @click="closeFolder()" class="modal-close-btn flex-shrink-0" style="width:32px;height:32px;border-radius:8px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:center;color:var(--color-ink);cursor:pointer;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>

                        <!-- Search within folder -->
                        <div class="flex-shrink-0">
                            <input type="text" x-model="searchFileInModal" placeholder="Filter nama file dalam folder ini..." class="form-input text-xs" style="height:38px;border-radius:10px;">
                        </div>

                        <!-- Files List (Scrollable) -->
                        <div class="space-y-2 overflow-y-auto flex-1 min-h-0 pr-1 no-scrollbar" style="max-height:50vh;">
                            <template x-for="file in filteredModalFiles" :key="file.name">
                                <div class="p-3 rounded-xl space-y-1.5 transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-primary);flex-shrink:0;">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                            </svg>
                                            <span class="font-mono font-bold text-xs truncate" style="color:var(--color-ink);" x-text="file.name"></span>
                                        </div>
                                        <button type="button" @click="copyFilePath(selectedFolder.name + file.name)" class="btn btn-secondary btn-sm flex-shrink-0" style="font-size:10.5px;padding:3px 8px;border-radius:6px;">
                                            Salin Path
                                        </button>
                                    </div>
                                    <p style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.4;padding-left:22px;" x-text="file.desc"></p>
                                </div>
                            </template>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-3 border-t flex-shrink-0" style="border-color:var(--color-hairline);">
                            <button type="button" @click="copyFolderContext(selectedFolder)" class="btn btn-primary w-full py-3 font-bold text-xs" style="background:var(--color-primary);border-color:var(--color-primary);border-radius:10px;box-shadow:var(--shadow-1);">
                                <span>📋 Salin Ringkasan Seluruh File Folder Ini</span>
                            </button>
                        </div>

                    </div>
                </template>

            </div>
        </div>
    </template>

    <!-- ========================================================================= -->
    <!-- TAB 3: VISUAL TOPOLOGY & MODULE CARDS                                     -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'topology'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- LAYER 1: CLIENT FRONTEND -->
            <div class="card p-4 sm:p-5 space-y-3" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center gap-2.5 pb-2.5 border-b" style="border-color:var(--color-hairline);">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(245,158,11,0.12);color:#f59e0b;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="14" height="20" x="5" y="2" rx="2" ry="2"></rect>
                            <path d="M12 18h.01"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="badge badge-warning font-mono" style="font-size:9.5px;padding:1px 6px;">LAYER 01</span>
                            <h3 style="font-size:13px;font-weight:800;color:var(--color-ink);">Client &amp; Frontend UI</h3>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Responsive Web &amp; Mobile Interfaces</div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div @click="selectAndCopyModule('consignment_portal')" class="p-3 rounded-xl cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-center">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">📱 Konsinyasi &amp; Sales (`/consignment`)</div>
                            <span class="badge badge-mono text-[9.5px]">Pilih</span>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Opname rak toko, hitung laku instan, nota faktur, saldo rak real-time.</div>
                    </div>

                    <div @click="selectAndCopyModule('delivery_driver')" class="p-3 rounded-xl cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-center">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">🚚 Driver Logistik (`/deliveries`)</div>
                            <span class="badge badge-mono text-[9.5px]">Pilih</span>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Surat jalan pengiriman, proteksi anti-bocor harga, update status antar.</div>
                    </div>

                    <div @click="selectAndCopyModule('pos_cashier')" class="p-3 rounded-xl cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-center">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">🛒 Kasir POS (`/pos`)</div>
                            <span class="badge badge-mono text-[9.5px]">Pilih</span>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Penjualan ritel cepat, scan barcode universal, auto potong stok &amp; buku kas.</div>
                    </div>

                    <div @click="selectAndCopyModule('customer_orders_b2b')" class="p-3 rounded-xl cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-center">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">📦 Pesanan Grosir B2B (`/customer-orders`)</div>
                            <span class="badge badge-mono text-[9.5px]">Pilih</span>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">PO grosir, batch picking list PDF gudang, faktur tagihan &amp; piutang.</div>
                    </div>

                    <div @click="selectAndCopyModule('owner_dashboard')" class="p-3 rounded-xl cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-center">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">👑 Owner Command Center (`/owner`)</div>
                            <span class="badge badge-mono text-[9.5px]">Pilih</span>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Analitik profitabilitas, modal kerja, biaya upah pabrik, dan komisi sales.</div>
                    </div>

                    <div @click="selectAndCopyModule('developer_test_runner')" class="p-3 rounded-xl cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-center">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">🧪 Developer Hub (`/developer`)</div>
                            <span class="badge badge-mono text-[9.5px]">Pilih</span>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Portal arsitektur, diagnostik DB Supabase, dan <?= $systemInfo['total_suites'] ?? 27 ?> test suites runner.</div>
                    </div>
                </div>
            </div>

            <!-- LAYER 2: CONTROLLERS & CORE ENGINE -->
            <div class="card p-4 sm:p-5 space-y-3" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center gap-2.5 pb-2.5 border-b" style="border-color:var(--color-hairline);">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(37,99,235,0.12);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect>
                            <rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect>
                            <line x1="6" x2="6.01" y1="6" y2="6"></line>
                            <line x1="6" x2="6.01" y1="18" y2="18"></line>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="badge badge-primary font-mono" style="font-size:9.5px;padding:1px 6px;">LAYER 02</span>
                            <h3 style="font-size:13px;font-weight:800;color:var(--color-ink);">Core Engine &amp; MVC</h3>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">PHP 8.1+ Native Modular Architecture</div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">⚙️ `app/Core/` &amp; Enterprise Foundation</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Router.php (regex matcher), Auth.php (RBAC &amp; 1-Hour Session Guard), Controller.php (Base).</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">🕹️ 24 Business Controllers (`app/Controllers/*`)</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Consignment, Delivery, POS, Order, Owner, Cash, Inventory, ImportData, Media, Developer, dll.</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">🛠️ 13 Enterprise Helpers (`app/Helpers/*`)</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">PrintDocument, ExcelExport, PdfExport, ActivityLog, CSRF, Format, StockHelper, Upload, dll.</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">⚡ 4 Enterprise Services (`app/Services/*`)</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">TestRunnerService (27 Suites), R2StorageService, MediaCacheService, dan ImportProcessor Engine.</div>
                    </div>
                </div>
            </div>

            <!-- LAYER 3: POSTGRESQL SUPABASE & CLOUDFLARE R2 -->
            <div class="card p-4 sm:p-5 space-y-3" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center gap-2.5 pb-2.5 border-b" style="border-color:var(--color-hairline);">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                            <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="badge badge-success font-mono" style="font-size:9.5px;padding:1px 6px;">LAYER 03</span>
                            <h3 style="font-size:13px;font-weight:800;color:var(--color-ink);">PostgreSQL 17 &amp; Cloudflare R2</h3>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">45 Tabel Relasional, 18 Stored Procedures &amp; R2 Storage</div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">⚡ `fn_proses_kunjungan_konsinyasi`</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Kalkulasi barang laku otomatis, kerugian retur rusak, faktur tagihan riil.</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">⚡ `fn_catat_pembayaran_konsinyasi`</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Pelunasan piutang cicil/lunas, mutasi saldo akun kas &amp; buku kas aktif.</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">⚡ `fn_trg_proses_pengiriman_konsinyasi`</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Trigger potong stok fisik gudang &amp; tambah saldo rak saat barang sampai.</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">☁️ Cloudflare R2 Object Storage &amp; Cache</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Penyimpanan foto produk/bukti bayar S3-compatible + smart local cache.</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 4: PETA RUTE & SITEMAP DIRECTORY                                      -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'sitemap'" class="card p-0 overflow-hidden" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
        <div class="p-3.5 sm:p-4 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-2.5" style="border-color:var(--color-hairline);">
            <div>
                <h3 style="font-size:13px;font-weight:800;color:var(--color-ink);">Katalog Lengkap URL Endpoints, Controllers &amp; Views</h3>
                <p style="font-size:11px;color:var(--color-ink-mute);">Peta seluruh 65+ rute aktif terdaftar di public/index.php.</p>
            </div>
            <input type="text" x-model="searchRoute" placeholder="Cari endpoint / controller..." class="form-input" style="height:34px;font-size:12px;max-width:260px;">
        </div>

        <div class="overflow-x-auto no-scrollbar">
            <table class="data-table" style="font-size:12px;">
                <thead>
                    <tr>
                        <th style="min-width:160px;">URL Route</th>
                        <th style="width:100px;">Method</th>
                        <th style="min-width:200px;">Controller Action</th>
                        <th style="min-width:180px;">File View / Response</th>
                        <th style="min-width:130px;">Hak Akses</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in filteredRoutes" :key="r.url + r.method">
                        <tr>
                            <td class="font-mono font-bold" style="color:var(--color-primary);" x-text="r.url"></td>
                            <td><span class="badge badge-mono" style="font-size:10px;" x-text="r.method"></span></td>
                            <td class="font-mono" style="color:var(--color-ink);" x-text="r.action"></td>
                            <td class="font-mono" style="color:var(--color-ink-mute);" x-text="r.view"></td>
                            <td><span class="badge badge-info" style="font-size:10.5px;" x-text="r.role"></span></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 5: DATABASE TABLES & STORED PROCEDURES EXPLORER                       -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'database'" class="card p-4 space-y-3" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
        
        <!-- Header & Switcher -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2.5 border-b" style="border-color:var(--color-hairline);">
            <div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="dbViewMode = 'tables'" 
                            :class="dbViewMode === 'tables' ? 'btn-primary' : 'btn-secondary'" 
                            class="btn btn-sm text-xs font-bold py-1 px-2.5" style="border-radius:8px;">
                        🗄️ Tabel Database (<?= count($tables) ?>)
                    </button>
                    <button type="button" @click="dbViewMode = 'procedures'" 
                            :class="dbViewMode === 'procedures' ? 'btn-primary' : 'btn-secondary'" 
                            class="btn btn-sm text-xs font-bold py-1 px-2.5" style="border-radius:8px;">
                        ⚡ Stored Procedures &amp; RPC (<?= count($procedures) ?>)
                    </button>
                </div>
                <p style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;" x-text="dbViewMode === 'tables' ? '45 Tabel aktif skema public PostgreSQL Supabase lengkap dengan jumlah baris riil.' : '18 Stored Procedures & fungsi triggers aktif di PostgreSQL Supabase.'"></p>
            </div>
            <input type="text" x-model="searchTable" :placeholder="dbViewMode === 'tables' ? 'Cari nama tabel...' : 'Cari nama procedure...'" class="form-input" style="height:34px;font-size:12px;max-width:240px;">
        </div>

        <!-- Tables Grid View -->
        <div x-show="dbViewMode === 'tables'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 pt-1">
            <?php foreach ($tables as $t): ?>
            <div x-show="!searchTable || '<?= $t['table_name'] ?>'.toLowerCase().includes(searchTable.toLowerCase())"
                 @click="copyTableName('<?= $t['table_name'] ?>')"
                 class="p-2.5 rounded-xl flex items-center justify-between cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);font-size:11.5px;">
                <div class="min-w-0 pr-2">
                    <span class="font-mono font-bold block truncate" style="color:var(--color-ink);"><?= htmlspecialchars($t['table_name']) ?></span>
                    <span class="badge badge-mono text-[9px]" style="background:rgba(59,130,246,0.1);color:var(--color-primary);">
                        <?= number_format((int)($t['row_count'] ?? 0), 0, ',', '.') ?> baris
                    </span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-ink-mute);flex-shrink:0;">
                    <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                    <path d="M3 9h18"></path>
                    <path d="M3 15h18"></path>
                </svg>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Procedures Grid View -->
        <div x-show="dbViewMode === 'procedures'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 pt-1">
            <?php foreach ($procedures as $p): ?>
            <div x-show="!searchTable || '<?= $p['routine_name'] ?>'.toLowerCase().includes(searchTable.toLowerCase())"
                 @click="copyProcedureName('<?= $p['routine_name'] ?>')"
                 class="p-3 rounded-xl flex items-center justify-between cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);font-size:11.5px;">
                <div class="min-w-0 pr-2">
                    <span class="font-mono font-bold truncate block" style="color:var(--color-ink);"><?= htmlspecialchars($p['routine_name']) ?></span>
                    <span class="text-[10px] text-mute uppercase font-mono"><?= htmlspecialchars($p['routine_type']) ?> &bull; Public Schema</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-primary);flex-shrink:0;">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- FLOATING TOAST NOTIFICATION                                               -->
    <!-- ========================================================================= -->
    <div x-show="showToast" 
         x-cloak
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:999999;max-width:92vw;width:400px;"
         @click="showToast = false">
        <div style="background:var(--color-canvas);border:1.5px solid var(--color-success);border-radius:14px;padding:12px 16px;box-shadow:var(--shadow-3);display:flex;align-items:center;gap:12px;">
            <div style="width:32px;height:32px;border-radius:8px;background:rgba(16,185,129,0.15);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:900;color:var(--color-ink);" x-text="toastTitle"></div>
                <div style="font-size:11.5px;color:var(--color-ink-mute);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="toastMessage"></div>
            </div>
        </div>
    </div>

</div>

<script>
function devArchitectureApp() {
    return {
        activeTab: 'ai_copier',
        selectedModule: 'consignment_portal',
        taskType: 'add_feature',
        customNote: '',
        generatedPrompt: '',
        copied: false,
        copiedFull: false,
        searchRoute: '',
        searchTable: '',
        dbViewMode: 'tables',
        searchFolder: '',
        searchFileInModal: '',
        selectedFolder: null,
        showToast: false,
        toastTitle: '',
        toastMessage: '',
        toastTimeout: null,

        folders: [
            {
                id: 'app_controllers',
                name: 'app/Controllers/',
                badge: '24 Controllers',
                icon: '🕹️',
                desc: 'Otak pengendali alur request HTTP, validasi bisnis, otorisasi RBAC, dan routing seluruh modul sistem.',
                files: [
                    { name: 'ActivityLogController.php', desc: 'Audit jejak aktivitas sistem forensik, tracking IP address, user agent, filter modul & ekspor Excel.' },
                    { name: 'AuthController.php', desc: 'Manajemen login multi-role, verifikasi kredensial bcrypt, heartbeat sesi, proteksi brute force, dan logout.' },
                    { name: 'CashController.php', desc: 'Buku kas multi-akun, mutasi masuk/keluar, transfer kas/bank, rekonsiliasi kas, dan laporan cash flow.' },
                    { name: 'ConsignmentController.php', desc: 'Portal konsinyasi terpadu: stok rak fisik toko, opname rak, hitung laku otomatis, faktur tagihan, dan komisi sales.' },
                    { name: 'CustomerController.php', desc: 'Master data toko mitra pelanggan, grup tier harga, katalog item toko, dan rute wilayah logistik.' },
                    { name: 'CustomerOrderController.php', desc: 'Pesanan grosir/B2B (PO Pelanggan), invoice penjualan tempo/tunai, alokasi stok, dan status kirim.' },
                    { name: 'DeliveryController.php', desc: 'Manajemen surat jalan ekspedisi dan Driver Mobile (tugas antar tanpa bocor harga, update status, upload POD).' },
                    { name: 'DeveloperController.php', desc: 'Portal developer: visual blueprint arsitektur, diagnostik koneksi database Supabase, dan test runner.' },
                    { name: 'EmployeeController.php', desc: 'Master karyawan dengan pemisahan peran Sales (komisi %) vs Driver (nopol armada), kasbon, dan payroll.' },
                    { name: 'ImportDataController.php', desc: 'Master Data Impor & Diffing Engine: upload file Excel 10 entitas, deteksi diff, dan verifikasi full-sync.' },
                    { name: 'InventoryController.php', desc: 'Inventaris fisik gudang, kartu stok, penyesuaian stok, pencatatan waste, dan bulk stock opname.' },
                    { name: 'MediaController.php', desc: 'Media proxy Cloudflare R2: streaming file privat/publik, presigned upload URLs, dan local disk cache management.' },
                    { name: 'OrderDocumentController.php', desc: 'Unified Printing Engine: cetak PDF & Excel untuk Surat Jalan, Faktur, Batch Picking List, dan Struk POS.' },
                    { name: 'OwnerController.php', desc: 'Owner Executive Command Center: profitabilitas (omzet, HPP, laba), net working capital, dan performa sales.' },
                    { name: 'PermissionController.php', desc: '5-Tab Master RBAC: peran, permission matrix, user-level overrides, dan update hak akses massal.' },
                    { name: 'PosController.php', desc: 'Kasir ritel cepat: scan barcode universal, kalkulasi diskon otomatis, potong stok, dan mutasi kas.' },
                    { name: 'PricingController.php', desc: 'Matriks 30 level harga dinamis, grup produk, dan penentuan katalog harga khusus toko mitra.' },
                    { name: 'ProductController.php', desc: 'Master produk jadi, brand/merek, bahan baku, kemasan, resep komposisi BOM (komposisi_item), dan upah borongan.' },
                    { name: 'ProfileController.php', desc: 'Manajemen akun pengguna login, pembaruan data profil, dan ubah kata sandi.' },
                    { name: 'PurchaseController.php', desc: 'Pengadaan bahan baku ke vendor (PO Vendor), penerimaan fisik gudang, dan pembayaran hutang.' },
                    { name: 'SalesOrderController.php', desc: 'Controller alias kompatibilitas untuk transaksi pesanan penjualan grosir / B2B.' },
                    { name: 'SettingsController.php', desc: 'Pengaturan umum toko, identitas perusahaan, logo, rekening bank, dan konfigurasi master sistem.' },
                    { name: 'SupplierController.php', desc: 'Master vendor pemasok bahan baku mentah, bumbu racik, dan plastik kemasan snack.' },
                    { name: 'UserController.php', desc: 'Manajemen pengguna sistem: buat akun, ganti role (owner, admin, sales, driver, mandor, developer), aktivasi status.' }
                ]
            },
            {
                id: 'app_core',
                name: 'app/Core/',
                badge: '3 Core Engines',
                icon: '⚙️',
                desc: 'Komponen fondasi arsitektur MVC native, request dispatcher, dan guard keamanan.',
                files: [
                    { name: 'Router.php', desc: 'Engine routing regex cepat yang memetakan URL ke Controller dan memvalidasi token CSRF otomatis.' },
                    { name: 'Auth.php', desc: 'Guard autentikasi, sliding inactivity session 1 jam, dan RBAC multi-peran dengan requireDeveloper() eksklusif.' },
                    { name: 'Controller.php', desc: 'Base class controller induk yang menyediakan helper render view, json response, dan redirect.' }
                ]
            },
            {
                id: 'app_helpers',
                name: 'app/Helpers/',
                badge: '13 Helpers',
                icon: '🛠️',
                desc: 'Kumpulan fungsi pembantu untuk format data, dokumen cetak, keamanan, dan audit forensik.',
                files: [
                    { name: 'ActivityLog.php', desc: 'Pencatat riwayat audit forensik otomatis ke tabel log_aktivitas secara real-time.' },
                    { name: 'CashVoucher.php', desc: 'Generator template voucher kas masuk (BKM) dan kas keluar (BKK) bernomor resmi.' },
                    { name: 'CompanySetting.php', desc: 'Helper konfigurasi dinamis profil perusahaan dan parameter sistem dari pengaturan_sistem.' },
                    { name: 'CSRF.php', desc: 'Generator dan validator token Anti-CSRF untuk memproteksi form dan endpoint AJAX.' },
                    { name: 'DocumentNumber.php', desc: 'Generator format nomor urut dokumen otomatis (SJ-..., INV-..., PO-...).' },
                    { name: 'ExcelExport.php', desc: 'Engine generator spreadsheet Excel/XML untuk laporan omzet, stok, arus kas, dan piutang.' },
                    { name: 'Flash.php', desc: 'Manajer pesan notifikasi sesi pengguna (sukses, peringatan, error) antar request redirect.' },
                    { name: 'Format.php', desc: 'Format standar rupiah Indonesia (Rp), tanggal lokal, dan parsing satuan numerik.' },
                    { name: 'PaymentHelper.php', desc: 'Validasi dan alokasi pembayaran piutang pelanggan, faktur grosir, dan tagihan konsinyasi.' },
                    { name: 'PdfExport.php', desc: 'Engine rendering dokumen cetak format PDF untuk surat jalan, invoice, dan picking list.' },
                    { name: 'PrintDocumentHelper.php', desc: 'Unified Printing Renderer untuk format cetak browser modern dan thermal printer 58/80mm.' },
                    { name: 'StockHelper.php', desc: 'Helper kalkulasi mutasi stok gudang multi-satuan, konversi pcs/pack/bal, dan cek ketersediaan.' },
                    { name: 'Upload.php', desc: 'Secure upload handler gambar produk, logo toko, dan bukti transfer dengan validasi ekstensi.' }
                ]
            },
            {
                id: 'app_services',
                name: 'app/Services/',
                badge: '4 Enterprise Services',
                icon: '⚡',
                desc: 'Layanan terpusat anti-duplikasi logika bisnis pengujian, media Cloudflare R2, dan engine impor data.',
                files: [
                    { name: 'TestRunnerService.php', desc: 'Master registry 27 test suites, execution timer, global concurrency lock, dan unified runner CLI/Web.' },
                    { name: 'R2StorageService.php', desc: 'Layanan Cloudflare R2 Object Storage berbasis S3-compatible API untuk upload dan presigned URL media.' },
                    { name: 'MediaCacheService.php', desc: 'Layanan smart caching lokal media R2 di server untuk performa loading secepat kilat.' },
                    { name: 'Import/ImportProcessor.php', desc: 'Engine pemroses impor file Excel dengan validasi schema, smart reader, dan deteksi perbedaan (diffing).' },
                    { name: 'Import/SmartReader.php', desc: 'Reader cerdas parsing baris Excel dengan sanitasi nilai sel dan normalisasi tipe data.' },
                    { name: 'Import/TemplateGenerator.php', desc: 'Generator berkas spreadsheet template impor resmi untuk 10 modul master data.' },
                    { name: 'Import/Handlers/', desc: 'Kumpulan 10 handler entitas master: Brand, Category, Customer, Employee, Item, Material, Pricing, Supplier, Territory, User.' }
                ]
            },
            {
                id: 'tests',
                name: 'tests/',
                badge: '27 Test Suites',
                icon: '🧪',
                desc: 'Rangkaian pengujian integrasi otomatis menyeluruh yang memverifikasi 100% kesehatan kode ERP.',
                files: [
                    { name: 'run_all.php', desc: 'CLI runner wrapper tipis yang mengeksekusi seluruh 27 test suites terpadu.' },
                    { name: 'SalesDriverIntegrityTest.php', desc: 'Integritas pemisahan tugas ketat Sales (punya komisi) vs Driver (punya armada nopol).' },
                    { name: 'CustomerIntegrityTest.php', desc: 'Integritas master pelanggan, validasi NIK/WA, grup tier harga, dan assignment sales.' },
                    { name: 'MasterDataCoreTest.php', desc: 'Integritas master data inti (produk, pelanggan, supplier, kas, karyawan).' },
                    { name: 'MasterRelationIntegrityTest.php', desc: 'Integritas relasi foreign keys dan constraint integritas database public.' },
                    { name: 'SupplierMasterUpgradeTest.php', desc: 'Verifikasi master vendor supplier bahan baku dan syarat pembayaran tempo.' },
                    { name: 'ProductMasterModuleTest.php', desc: 'Integritas produk jadi, bahan baku, resep komposisi BOM, dan tarif upah borongan.' },
                    { name: 'PricingAndStockIntegrationTest.php', desc: 'Integrasi matriks 30 level harga terhadap kalkulasi mutasi stok POS dan B2B.' },
                    { name: 'PricingSystemReconciliationTest.php', desc: 'Rekonsiliasi akurasi harga level pelanggan versus level default produk.' },
                    { name: 'InventoryAndLedgerPrecisionTest.php', desc: 'Presisi kalkulasi kartu stok gudang dan mutasi penyesuaian fisik.' },
                    { name: 'PosCashierLifecycleTest.php', desc: 'Siklus lengkap kasir retail: scan barcode, checkout, potong stok, mutasi kas.' },
                    { name: 'CustomerOrderLifecycleTest.php', desc: 'Siklus order grosir B2B: pesanan, picking list, faktur, surat jalan, dan pelunasan.' },
                    { name: 'PurchaseProcurementLifecycleTest.php', desc: 'Siklus pengadaan vendor: PO, penerimaan fisik gudang, dan hutang dagang.' },
                    { name: 'DeliveryAndLogisticsLifecycleTest.php', desc: 'Siklus surat jalan: antrean kirim, keberangkatan driver, konfirmasi sampai (POD).' },
                    { name: 'CashLedgerFinancialTest.php', desc: 'Integritas buku kas: penerimaan, pengeluaran, transfer kas, dan saldo berjalan.' },
                    { name: 'ConsignmentFullCycleTest.php', desc: 'Siklus penuh titip jual konsinyasi: titip rak, opname sisa fisik, retur, faktur, pelunasan.' },
                    { name: 'ConsignmentConversionTest.php', desc: 'Pengujian formula konversi penjualan laku konsinyasi dan kalkulasi omzet toko.' },
                    { name: 'TieredCommissionTest.php', desc: 'Pengujian skema komisi berjenjang (tiered commission) sales otomatis.' },
                    { name: 'UnifiedPrintingEngineTest.php', desc: 'Integritas engine cetak dokumen: Surat Jalan, Faktur, Struk Thermal, Picking List.' },
                    { name: 'AuthAndRbacLifecycleTest.php', desc: 'Siklus autentikasi login bcrypt dan guard 5-tab permission RBAC.' },
                    { name: 'SecurityAndReconciliationTest.php', desc: 'Uji ketahanan keamanan: CSRF protection, SQL injection prevention, sanitasi input.' },
                    { name: 'ActivityLogComprehensiveAuditTest.php', desc: 'Uji pencatatan jejak audit forensik aktivitas pengguna secara real-time.' },
                    { name: 'DataHygieneAndSettingsTest.php', desc: 'Uji kebersihan konfigurasi sistem toko dan pengaturan perusahaan.' },
                    { name: 'OwnerDashboardExecutiveTest.php', desc: 'Verifikasi integritas angka analitik eksekutif pada Owner Command Center.' },
                    { name: 'ImportDataLifecycleTest.php', desc: 'Verifikasi pengujian siklus impor data master Excel dan full-sync reconciliation.' },
                    { name: 'EmployeeManualTypeAndWhatsAppTest.php', desc: 'Verifikasi tipe penggajian karyawan manual dan unifikasi kontak WhatsApp.' },
                    { name: 'AccessDenied403Test.php', desc: 'Verifikasi halaman kejutan 403, auto-logout, audio API, dan proteksi rute developer.' },
                    { name: 'BrandMasterModuleTest.php', desc: 'Verifikasi master data merek (Brand) dan relasinya dengan grup produk.' },
                    { name: 'test_r2_integration.php', desc: 'Pengujian konektivitas langsung Cloudflare R2 bucket dan verifikasi presigned URL.' }
                ]
            },
            {
                id: 'developer',
                name: 'developer/',
                badge: '2 Developer Tools',
                icon: '💻',
                desc: 'Alat bantu diagnostik database PostgreSQL Supabase dan runner pengujian cepat.',
                files: [
                    { name: 'test_db.php', desc: 'Skrip diagnostik kesehatan database Supabase, SSL pooler latency, integrity schema, dan RPC test.' },
                    { name: 'run_all.php', desc: 'Shortcut CLI runner pengujian 27 test suites di lingkungan developer.' }
                ]
            },
            {
                id: 'views',
                name: 'views/',
                badge: '19 Modul Views',
                icon: '🎨',
                desc: 'Antarmuka visual pengguna berbasis Server-Side Rendered PHP dengan Alpine.js reactivity.',
                files: [
                    { name: 'consignment/', desc: 'Portal konsinyasi: index.php, stok_rak.php, opname.php, opname_hasil.php, tagihan.php, laporan_penjualan.php, dll.' },
                    { name: 'deliveries/index.php', desc: 'Layar Driver Mobile & ekspedisi: manifest pengiriman surat jalan tanpa bocor harga.' },
                    { name: 'pos/index.php', desc: 'Layar Kasir POS Retail: keranjang transaksi, scan barcode, diskon, dan cetak struk thermal.' },
                    { name: 'customer_orders/index.php', desc: 'Layar Pesanan B2B / Grosir: manajemen faktur order grosir, PO list, dan pelunasan.' },
                    { name: 'owner/index.php', desc: 'Layar Owner Command Center: profitabilitas, net working capital, beban kas operasional, dan sales.' },
                    { name: 'developer/', desc: 'Layar Developer Hub: index.php (portal utama), architecture.php (blueprint), dan tests.php (runner console).' },
                    { name: 'cash/', desc: 'Layar Buku Kas: index.php (daftar akun kas), transactions.php (mutasi), dan reports.php (cash flow).' },
                    { name: 'inventory/', desc: 'Layar Stok Gudang: index.php (katalog stok), bulk_opname.php, dan opname_detail.php.' },
                    { name: 'pricing/index.php', desc: 'Layar Matriks Harga: konfigurasi 30 level harga dinamis dan grup produk.' },
                    { name: 'products/index.php', desc: 'Layar Master Produk: katalog snack, bahan baku, kemasan, resep BOM, dan upah borongan.' },
                    { name: 'customers/index.php', desc: 'Layar Master Pelanggan: toko mitra, rute wilayah, grup tier toko, dan katalog khusus.' },
                    { name: 'employees/index.php', desc: 'Layar Master Karyawan: data pegawai, input komisi sales vs nopol driver, absensi, kasbon.' },
                    { name: 'purchases/index.php', desc: 'Layar Pengadaan: PO pembelian bahan baku ke supplier dan penerimaan gudang.' },
                    { name: 'suppliers/index.php', desc: 'Layar Master Supplier: vendor bahan mentah, bumbu, dan kemasan.' },
                    { name: 'settings/', desc: 'Layar Pengaturan: index.php (portal pengaturan), company.php (identitas toko), logs.php (audit log), impor_data.php.' },
                    { name: 'auth/login.php', desc: 'Layar Autentikasi: form login multi-role dengan styling minimalis modern.' },
                    { name: 'profile/index.php', desc: 'Layar Profil: pembaruan data pengguna dan pergantian kata sandi.' },
                    { name: 'errors/403.php', desc: 'Layar Error 403: halaman proteksi akses ditolak interaktif.' },
                    { name: 'layouts/', desc: 'Template induk: master.php (kerangka umum), sidebar.php (navigasi), header.php (topbar).' }
                ]
            },
            {
                id: 'database',
                name: 'database/',
                badge: '45 Tables & 18 RPC',
                icon: '🗄️',
                desc: 'Definisi skema basis data PostgreSQL Supabase, triggers, dan stored procedures atomik.',
                files: [
                    { name: '01_schema.sql', desc: 'Single Source of Truth: Definisi lengkap 45 tabel master, relasi FK, indeks performa, dan constraint.' },
                    { name: '02_triggers_and_rpc.sql', desc: '18 Stored procedures & triggers: fn_proses_kunjungan_konsinyasi, fn_hitung_harga_jual_item, dll.' },
                    { name: 'migrations 03 - 61.sql', desc: '60+ Berkas migrasi database terstruktur berurutan yang mencatat seluruh evolusi skema sistem.' }
                ]
            },
            {
                id: 'config',
                name: 'config/',
                badge: 'System Config',
                icon: '🔌',
                desc: 'Konfigurasi koneksi database Singleton PDO PostgreSQL dengan proteksi SSL.',
                files: [
                    { name: 'database.php', desc: 'Kelas Singleton Database::getInstance() dengan penanganan error PDO exception dan SSL context.' },
                    { name: 'env.php', desc: 'Parser environment variables mandiri yang memuat file .env ke getenv().' }
                ]
            },
            {
                id: 'public',
                name: 'public/',
                badge: 'Web Front Controller',
                icon: '🌐',
                desc: 'Entry point publik web server dan aset statis sistem (CSS, JS, Fonts).',
                files: [
                    { name: 'index.php', desc: 'Front Controller utama: inisialisasi sesi, autoloader PSR-4, registrasi rute, dan Router::dispatch().' },
                    { name: '.htaccess', desc: 'Aturan rewrite Apache, proteksi URL, dan normalisasi trailing-slash.' },
                    { name: 'assets/', desc: 'Direktori aset statis: css/app.css, js/app.js, alpine.min.js, dan lucide.min.js.' }
                ]
            },
            {
                id: 'docs',
                name: 'docs/',
                badge: 'Documentation',
                icon: '📚',
                desc: 'Dokumentasi arsitektur sistem, PRD, dan panduan teknis operasional.',
                files: [
                    { name: 'ARCHITECTURE.md', desc: 'Spesifikasi teknis lengkap arsitektur sistem KEREN SNACK ERP.' },
                    { name: 'PRD_MASTER_KEREN_SNACK.md', desc: 'Product Requirement Document induk modul ERP.' }
                ]
            }
        ],

        routes: [
            { url: '/login', method: 'GET / POST', action: 'AuthController::showLogin() / login()', view: 'views/auth/login.php', role: 'Semua Pengguna' },
            { url: '/logout', method: 'GET / POST', action: 'AuthController::logout()', view: 'Redirect ke /login', role: 'Semua Pengguna' },
            { url: '/api/auth/heartbeat', method: 'POST', action: 'AuthController::heartbeat()', view: 'JSON Response', role: 'Semua Pengguna' },
            { url: '/media/view', method: 'GET', action: 'MediaController::serveFile()', view: 'Binary Stream Response', role: 'Semua Pengguna' },
            { url: '/api/media/presigned', method: 'GET', action: 'MediaController::getPresignedUrl()', view: 'JSON Response', role: 'Owner, Admin' },
            { url: '/pos', method: 'GET', action: 'PosController::index()', view: 'views/pos/index.php', role: 'Owner, Admin' },
            { url: '/api/pos/calculate-price', method: 'GET', action: 'PosController::calculatePrice()', view: 'JSON Response', role: 'Owner, Admin' },
            { url: '/api/pos/search-barcode', method: 'GET', action: 'PosController::searchBarcode()', view: 'JSON Response', role: 'Owner, Admin' },
            { url: '/api/pos/checkout', method: 'POST', action: 'PosController::checkout()', view: 'JSON Response', role: 'Owner, Admin' },
            { url: '/customer-orders', method: 'GET', action: 'CustomerOrderController::index()', view: 'views/customer_orders/index.php', role: 'Owner, Admin' },
            { url: '/customer-orders/create', method: 'GET', action: 'CustomerOrderController::create()', view: 'views/customer_orders/create.php', role: 'Owner, Admin' },
            { url: '/customer-orders/store', method: 'POST', action: 'CustomerOrderController::store()', view: 'Redirect ke detail', role: 'Owner, Admin' },
            { url: '/customer-orders/po-list', method: 'GET', action: 'CustomerOrderController::poList()', view: 'views/customer_orders/po_list.php', role: 'Owner, Admin' },
            { url: '/customer-orders/picking-list', method: 'GET', action: 'OrderDocumentController::printPickingList()', view: 'HTML Web Print', role: 'Owner, Admin' },
            { url: '/customer-orders/picking-list/pdf', method: 'GET', action: 'OrderDocumentController::pickingListPdf()', view: 'PDF Stream', role: 'Owner, Admin' },
            { url: '/customer-orders/invoice/pdf', method: 'GET', action: 'OrderDocumentController::invoicePdf()', view: 'PDF Stream', role: 'Owner, Admin' },
            { url: '/customer-orders/export/excel', method: 'GET', action: 'OrderDocumentController::exportExcel()', view: 'Excel Stream', role: 'Owner, Admin' },
            { url: '/customer-orders/pay', method: 'POST', action: 'CustomerOrderController::pay()', view: 'JSON / Redirect', role: 'Owner, Admin' },
            { url: '/consignment', method: 'GET', action: 'ConsignmentController::portal()', view: 'views/consignment/index.php', role: 'Owner, Admin, Sales' },
            { url: '/consignment/stok-rak', method: 'GET', action: 'ConsignmentController::stokRak()', view: 'views/consignment/stok_rak.php', role: 'Owner, Admin, Sales' },
            { url: '/consignment/opname', method: 'GET', action: 'ConsignmentController::opname()', view: 'views/consignment/opname.php', role: 'Sales, Admin, Owner' },
            { url: '/consignment/opname/proses', method: 'POST', action: 'ConsignmentController::opnameProses()', view: 'Redirect hasil', role: 'Sales, Admin, Owner' },
            { url: '/consignment/opname/hasil', method: 'GET', action: 'ConsignmentController::hasilKunjungan()', view: 'views/consignment/opname_hasil.php', role: 'Sales, Admin, Owner' },
            { url: '/consignment/tagihan', method: 'GET', action: 'ConsignmentController::tagihanIndex()', view: 'views/consignment/tagihan.php', role: 'Owner, Admin' },
            { url: '/consignment/tagihan/bayar', method: 'POST', action: 'ConsignmentController::tagihanBayar()', view: 'JSON / Redirect', role: 'Owner, Admin' },
            { url: '/consignment/laporan-penjualan', method: 'GET', action: 'ConsignmentController::laporanPenjualan()', view: 'views/consignment/laporan_penjualan.php', role: 'Owner, Admin' },
            { url: '/consignment/assignment-sales', method: 'GET', action: 'ConsignmentController::assignmentSales()', view: 'views/consignment/assignment.php', role: 'Owner, Admin' },
            { url: '/consignment/komisi-sales', method: 'GET', action: 'ConsignmentController::komisiSales()', view: 'views/consignment/komisi.php', role: 'Owner, Admin' },
            { url: '/consignment/kerugian-rusak', method: 'GET', action: 'ConsignmentController::kerugianRusak()', view: 'views/consignment/kerugian.php', role: 'Owner, Admin' },
            { url: '/deliveries', method: 'GET', action: 'DeliveryController::index()', view: 'views/deliveries/index.php', role: 'Driver, Admin, Owner' },
            { url: '/deliveries/store', method: 'POST', action: 'DeliveryController::store()', view: 'Redirect', role: 'Admin, Owner' },
            { url: '/deliveries/update-status', method: 'POST', action: 'DeliveryController::updateStatus()', view: 'Redirect', role: 'Driver, Admin, Owner' },
            { url: '/driver-deliveries', method: 'GET', action: 'DeliveryController::driverRoute()', view: 'views/deliveries/driver.php', role: 'Driver' },
            { url: '/driver-deliveries/complete', method: 'POST', action: 'DeliveryController::completeDelivery()', view: 'JSON Response', role: 'Driver' },
            { url: '/owner', method: 'GET', action: 'OwnerController::index()', view: 'views/owner/index.php', role: 'Owner' },
            { url: '/cash', method: 'GET', action: 'CashController::index()', view: 'views/cash/index.php', role: 'Owner, Admin' },
            { url: '/cash/transactions', method: 'GET', action: 'CashController::transactions()', view: 'views/cash/transactions.php', role: 'Owner, Admin' },
            { url: '/cash/reports', method: 'GET', action: 'CashController::reports()', view: 'views/cash/reports.php', role: 'Owner, Admin' },
            { url: '/cash/store-inflow', method: 'POST', action: 'CashController::storeInflow()', view: 'Redirect', role: 'Owner, Admin' },
            { url: '/cash/store-outflow', method: 'POST', action: 'CashController::storeOutflow()', view: 'Redirect', role: 'Owner, Admin' },
            { url: '/inventory', method: 'GET', action: 'InventoryController::index()', view: 'views/inventory/index.php', role: 'Owner, Admin, Mandor' },
            { url: '/inventory/bulk-opname', method: 'GET / POST', action: 'InventoryController::bulkOpname()', view: 'views/inventory/bulk_opname.php', role: 'Owner, Admin' },
            { url: '/inventory/adjust', method: 'POST', action: 'InventoryController::adjustStock()', view: 'Redirect', role: 'Owner, Admin' },
            { url: '/pricing', method: 'GET / POST', action: 'PricingController::index()', view: 'views/pricing/index.php', role: 'Owner, Admin' },
            { url: '/products', method: 'GET / POST', action: 'ProductController::index()', view: 'views/products/index.php', role: 'Owner, Admin' },
            { url: '/customers', method: 'GET / POST', action: 'CustomerController::index()', view: 'views/customers/index.php', role: 'Owner, Admin' },
            { url: '/employees', method: 'GET / POST', action: 'EmployeeController::index()', view: 'views/employees/index.php', role: 'Owner, Admin' },
            { url: '/purchases', method: 'GET / POST', action: 'PurchaseController::index()', view: 'views/purchases/index.php', role: 'Owner, Admin' },
            { url: '/suppliers', method: 'GET / POST', action: 'SupplierController::index()', view: 'views/suppliers/index.php', role: 'Owner, Admin' },
            { url: '/users', method: 'GET / POST', action: 'UserController::index()', view: 'views/users/index.php', role: 'Owner, Admin' },
            { url: '/permissions', method: 'GET / POST', action: 'PermissionController::index()', view: 'views/permissions/index.php', role: 'Owner, Admin' },
            { url: '/settings', method: 'GET', action: 'SettingsController::index()', view: 'views/settings/index.php', role: 'Owner, Admin, Developer' },
            { url: '/settings/company', method: 'GET / POST', action: 'SettingsController::company()', view: 'views/settings/company.php', role: 'Owner, Admin' },
            { url: '/settings/activity-logs', method: 'GET', action: 'ActivityLogController::index()', view: 'views/settings/logs.php', role: 'Owner, Admin' },
            { url: '/settings/impor-data', method: 'GET', action: 'ImportDataController::index()', view: 'views/settings/impor_data.php', role: 'Owner, Admin' },
            { url: '/profile', method: 'GET / POST', action: 'ProfileController::index()', view: 'views/profile/index.php', role: 'Semua Pengguna' },
            { url: '/developer', method: 'GET', action: 'DeveloperController::index()', view: 'views/developer/index.php', role: 'Khusus Developer' },
            { url: '/developer/architecture', method: 'GET', action: 'DeveloperController::architecture()', view: 'views/developer/architecture.php', role: 'Khusus Developer' },
            { url: '/developer/test-db', method: 'GET', action: 'DeveloperController::testDb()', view: 'developer/test_db.php', role: 'Khusus Developer' },
            { url: '/developer/tests', method: 'GET', action: 'DeveloperController::tests()', view: 'views/developer/tests.php', role: 'Khusus Developer' },
            { url: '/developer/tests/run-single', method: 'POST', action: 'DeveloperController::runSingleTest()', view: 'JSON AJAX Response', role: 'Khusus Developer' }
        ],

        get filteredFolders() {
            if (!this.searchFolder) return this.folders;
            const q = this.searchFolder.toLowerCase();
            return this.folders.filter(f => 
                f.name.toLowerCase().includes(q) || 
                f.desc.toLowerCase().includes(q) || 
                f.files.some(file => file.name.toLowerCase().includes(q) || file.desc.toLowerCase().includes(q))
            );
        },

        get filteredModalFiles() {
            if (!this.selectedFolder) return [];
            if (!this.searchFileInModal) return this.selectedFolder.files;
            const q = this.searchFileInModal.toLowerCase();
            return this.selectedFolder.files.filter(f => f.name.toLowerCase().includes(q) || f.desc.toLowerCase().includes(q));
        },

        get filteredRoutes() {
            if (!this.searchRoute) return this.routes;
            const q = this.searchRoute.toLowerCase();
            return this.routes.filter(r => r.url.toLowerCase().includes(q) || r.action.toLowerCase().includes(q) || r.view.toLowerCase().includes(q));
        },

        init() {
            this.generatePrompt();
        },

        notify(title, message) {
            if (this.toastTimeout) clearTimeout(this.toastTimeout);
            this.toastTitle = title;
            this.toastMessage = message;
            this.showToast = true;
            this.toastTimeout = setTimeout(() => {
                this.showToast = false;
            }, 2600);
        },

        openFolder(folder) {
            this.selectedFolder = folder;
            this.searchFileInModal = '';
            document.body.classList.add('modal-open');
            document.documentElement.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
        },

        closeFolder() {
            this.selectedFolder = null;
            document.body.classList.remove('modal-open');
            document.documentElement.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        },

        selectAndCopyModule(mod) {
            this.selectedModule = mod;
            this.activeTab = 'ai_copier';
            this.generatePrompt();
            this.notify('Modul Terpilih! 🎯', 'Konteks modul ' + mod + ' telah dimuat ke AI Prompt Studio.');
        },

        generatePrompt() {
            const taskHeaders = {
                add_feature: 'TASK: Tambahkan fitur/komponen baru pada modul berikut sesuai standar enterprise clean code.',
                fix_bug: 'TASK: Lakukan investigasi mendalam dan perbaiki bug/glitch pada modul berikut hingga tuntas 100% (zero-defect).',
                redesign_ui: 'TASK: Redesign dan rapikan tampilan UI/UX mobile/desktop pada modul berikut agar responsif, modern, dan minimalis.',
                optimize_logic: 'TASK: Optimasi logika bisnis, query SQL performa tinggi, atau stored procedure (RPC) pada modul berikut.',
                write_test: 'TASK: Buat test suite otomatisasi (integration test) PHP untuk memverifikasi fungsionalitas modul berikut.'
            };

            const modules = {
                consignment_portal: {
                    title: '📱 Modul Konsinyasi Terpadu & Sales Mobile',
                    controller: 'app/Controllers/ConsignmentController.php (portal, stokRak, opname, opnameProses, hasilKunjungan, tagihanIndex, tagihanBayar, assignmentSales, komisiSales, kerugianRusak)',
                    views: 'views/consignment/index.php, views/consignment/stok_rak.php, views/consignment/opname.php, views/consignment/opname_hasil.php, views/consignment/tagihan.php',
                    tables: 'pelanggan, stok_konsinyasi_toko, kunjungan_konsinyasi, rincian_kunjungan_konsinyasi, tagihan_kunjungan, item, karyawan',
                    rpc: 'fn_proses_kunjungan_konsinyasi, fn_buat_tagihan_konsinyasi, fn_catat_pembayaran_konsinyasi',
                    rules: [
                        'Sales hanya mengelola toko binaannya yang telah di-assign resmi di tabel pelanggan (sales_id).',
                        'Rumus laku: Titip Awal - (Sisa Fisik + Retur Bagus + Retur Rusak).',
                        'Form pengajuan titip baru tidak membocorkan harga HPP ke Sales.',
                        'Komisi Sales dihitung otomatis dari total omzet laku (skema_komisi_sales).',
                        'Setiap kunjungan otomatis menerbitkan faktur tagihan di tagihan_kunjungan.'
                    ]
                },
                delivery_driver: {
                    title: '🚚 Modul Driver Logistik & Pengiriman Surat Jalan',
                    controller: 'app/Controllers/DeliveryController.php (index, driverRoute, store, updateStatus, completeDelivery, failDelivery)',
                    views: 'views/deliveries/index.php, views/deliveries/driver.php',
                    tables: 'surat_jalan, pesanan, pelanggan, karyawan',
                    rpc: 'fn_trg_proses_pengiriman_konsinyasi (terpicu otomatis saat status surat jalan = "selesai_diterima")',
                    rules: [
                        'Driver TIDAK boleh melihat harga rupiah, HPP, atau komisi penjualan.',
                        'Driver hanya melihat daftar toko tujuan, alamat/lokasi, kontak WA, dan total pcs snack.',
                        'Alur status pengiriman: siap_kirim -> sedang_dikirim -> selesai_diterima.',
                        'Saat status surat jalan menjadi selesai_diterima, trigger otomatis memotong stok gudang dan menambah saldo rak toko mitra.'
                    ]
                },
                owner_dashboard: {
                    title: '👑 Owner Executive Command Center & Business Performance',
                    controller: 'app/Controllers/OwnerController.php (index)',
                    views: 'views/owner/index.php',
                    tables: 'pesanan, item_pesanan, akun_kas, arus_kas, pelanggan, item, pembelian, produksi_harian, stok_konsinyasi_toko, tagihan_kunjungan',
                    rpc: 'fn_hitung_tier_komisi_sales',
                    rules: [
                        'Matriks Profitabilitas: Omzet bersih riil, HPP terjual (COGS), laba kotor & margin %, beban operasional, dan laba bersih.',
                        'Neraca Modal Kerja Bersih (Net Working Capital): Kas & bank + piutang usaha + total valuasi persediaan (gudang bahan, kemas, barang jadi, rak konsinyasi) - hutang vendor.',
                        'Distribusi Multi-Channel: POS Kasir Retail vs Grosir B2B vs Titip Jual Rak Toko Konsinyasi.',
                        'Kinerja Pabrik & Produksi: Output pcs/bal, efisiensi upah borongan per pcs, dan monitoring bahan mentah.',
                        'Mitra Konsinyasi: Leaderboard sales, monitoring toko overdue >14 hari belum di-opname, evaluasi retur rusak.'
                    ]
                },
                pos_cashier: {
                    title: '🛒 Kasir POS Retail & Quick Checkout',
                    controller: 'app/Controllers/PosController.php (index, calculatePrice, searchBarcode, checkout)',
                    views: 'views/pos/index.php',
                    tables: 'pesanan, item_pesanan, item, akun_kas, arus_kas, riwayat_stok, master_level_harga',
                    rpc: 'fn_cari_item_by_barcode, fn_hitung_harga_jual_item',
                    rules: [
                        'Mendukung barcode scanner kemasan snack universal (grup_produk.barcode_universal).',
                        'Otomatis potong stok fisik gudang di riwayat_stok secara real-time.',
                        'Otomatis mencatat mutasi kas masuk ke buku kas aktif dan tabel arus_kas.',
                        'Mendukung cetak struk thermal 58mm / 80mm.'
                    ]
                },
                customer_orders_b2b: {
                    title: '📦 Pesanan Pelanggan & Grosir B2B',
                    controller: 'app/Controllers/CustomerOrderController.php, app/Controllers/OrderDocumentController.php',
                    views: 'views/customer_orders/index.php, views/customer_orders/create.php, views/customer_orders/edit.php, views/customer_orders/po_list.php',
                    tables: 'pesanan, item_pesanan, pelanggan, surat_jalan, akun_kas, arus_kas, riwayat_stok',
                    rpc: 'fn_rekonsiliasi_piutang_pelanggan, fn_hitung_harga_jual_item',
                    rules: [
                        'Pencatatan order grosir / B2B dengan nomor invoice otomatis (INV-YYYYMM-XXXX).',
                        'Daftar PO Pelanggan dapat diproses menjadi status siap kirim secara parsial/penuh.',
                        'Mendukung cetak Picking List batch PDF untuk tim gudang sebelum barang dikirim.',
                        'Pencatatan termin tempo pembayaran kredit dan pelunasan piutang bertahap.'
                    ]
                },
                finance_cash: {
                    title: '💰 Keuangan, Mutasi Kas & Valuasi Arus Kas',
                    controller: 'app/Controllers/CashController.php (index, transactions, reports, storeInflow, storeOutflow, storeTransfer)',
                    views: 'views/cash/index.php, views/cash/transactions.php, views/cash/reports.php',
                    tables: 'akun_kas, arus_kas, kategori_biaya',
                    rpc: 'N/A (Transaksi atomik database PDO)',
                    rules: [
                        'Pencatatan kas masuk & kas keluar per akun bank/kas operasional.',
                        'Rekonsiliasi saldo kas real-time terintegrasi POS, Grosir, dan Konsinyasi.',
                        'Laporan cash flow harian, bulanan, dan ekspor spreadsheet Excel.'
                    ]
                },
                inventory_bom: {
                    title: '🏭 Master Gudang, Stok Fisik & Resep Komposisi BOM',
                    controller: 'app/Controllers/InventoryController.php, app/Controllers/ProductController.php',
                    views: 'views/inventory/index.php, views/inventory/bulk_opname.php, views/products/index.php',
                    tables: 'item, komposisi_item, riwayat_stok, opname_gudang, opname_gudang_item, penyesuaian_stok, kelompok_upah_borongan',
                    rpc: 'fn_trg_produksi_harian_after_insert, fn_trg_produksi_harian_after_update, fn_trg_produksi_harian_after_delete',
                    rules: [
                        'Komposisi BOM (komposisi_item): bahan baku mentah + kemasan -> produk snack jadi.',
                        'Pencatatan produksi harian otomatis memotong stok bahan dan menambah stok barang jadi.',
                        'Kartu stok fisik multi-satuan (pcs, bal, pack, kg, gram, lembar).'
                    ]
                },
                pricing_engine: {
                    title: '🏷️ Matriks 30 Level Harga & Tier Toko Mitra',
                    controller: 'app/Controllers/PricingController.php (index, updateLevelPrice, deleteLevelPrice)',
                    views: 'views/pricing/index.php',
                    tables: 'master_level_harga, grup_produk, grup_produk_harga_level, grup_pelanggan, pelanggan_item, item',
                    rpc: 'fn_hitung_harga_jual_item',
                    rules: [
                        'Menyediakan hingga 30 level harga per grup produk.',
                        'Tier harga otomatis terhubung dengan kategori grup pelanggan.',
                        'Harga khusus toko konsinyasi terkunci pada level harga konsinyasi.'
                    ]
                },
                master_employees: {
                    title: '👥 Master Karyawan (Pemisahan Sales vs Driver) & Penggajian',
                    controller: 'app/Controllers/EmployeeController.php (index, store, update, delete, saveCommissionTiersBatch)',
                    views: 'views/employees/index.php',
                    tables: 'karyawan, skema_komisi_sales, penggajian, rincian_penggajian, tabungan, transaksi_tabungan, kasbon, potongan_kasbon, absensi',
                    rpc: 'fn_hitung_tier_komisi_sales, fn_guard_pelanggan_sales_driver, fn_trg_potongan_kasbon_update_saldo, fn_trg_transaksi_tabungan_update_saldo',
                    rules: [
                        'Sales: Memiliki input persentase komisi berjenjang (%) dan assignment toko binaan.',
                        'Driver: Memiliki input plat nopol armada kendaraan dan TIDAK memiliki kolom komisi.',
                        'Borongan: Pekerja borongan kemasan dengan skema upah kelompok_upah_borongan.'
                    ]
                },
                rbac_permissions: {
                    title: '🔐 Manajemen Pengguna, Peran & 5-Tab RBAC',
                    controller: 'app/Controllers/UserController.php, app/Controllers/PermissionController.php',
                    views: 'views/users/index.php, views/permissions/index.php',
                    tables: 'pengguna, peran, izin, izin_peran, izin_pengguna',
                    rpc: 'fn_guard_developer_account',
                    rules: [
                        'Peran inti: owner, admin, sales, driver, mandor, dan role terproteksi developer.',
                        'Matriks hak akses dinamis per fitur dengan dukungan user-level permission override.',
                        'Akun developer dilindungi di level basis data (tidak bisa dihapus/di-downgrade).'
                    ]
                },
                import_data_engine: {
                    title: '📥 Master Data Impor & Diffing Reconciliation Engine',
                    controller: 'app/Controllers/ImportDataController.php, app/Services/Import/ImportProcessor.php',
                    views: 'views/settings/impor_data.php',
                    tables: 'merek, kategori, item, pemasok, pelanggan, karyawan, pengguna, master_level_harga, wilayah',
                    rpc: 'N/A (Proses transaksi atomik di level PHP Service)',
                    rules: [
                        'Smart reader file Excel yang mendeteksi diff data secara otomatis (INSERT, UPDATE, DELETE, NO_CHANGE).',
                        'Verifikasi integritas referensi foreign key lintas tabel sebelum mutasi data dieksekusi.',
                        'Pencegahan penghapusan data berisiko tinggi (soft-deactivate vs hard delete).'
                    ]
                },
                media_cloud_storage: {
                    title: '☁️ Cloudflare R2 Media Proxy & Local Disk Cache',
                    controller: 'app/Controllers/MediaController.php, app/Services/R2StorageService.php, app/Services/MediaCacheService.php',
                    views: 'views/products/index.php, views/settings/company.php',
                    tables: 'pengaturan_sistem, item',
                    rpc: 'N/A',
                    rules: [
                        'Penyimpanan foto produk dan dokumen legal di Cloudflare R2 bucket berbasis S3 SDK.',
                        'Local caching transparan pada direktori /cache dengan invalidasi cerdas.',
                        'Presigned URL generation untuk transfer berkas terproteksi tanpa ekspos kredensial R2.'
                    ]
                },
                settings_audit: {
                    title: '⚙️ Pengaturan Perusahaan & Audit Log Forensik',
                    controller: 'app/Controllers/SettingsController.php, app/Controllers/ActivityLogController.php',
                    views: 'views/settings/index.php, views/settings/company.php, views/settings/logs.php',
                    tables: 'pengaturan_sistem, log_aktivitas',
                    rpc: 'fn_catat_log_aktivitas',
                    rules: [
                        'Profil toko: nama perusahaan, alamat, logo, nomor telepon, dan nomor rekening pembayaran.',
                        'Audit log mencatat timestamp, user_id, action, modul, deskripsi, IP address, dan user agent.',
                        'Dukungan ekspor spreadsheet log aktivitas dan pembersihan (prune) log lama.'
                    ]
                },
                developer_test_runner: {
                    title: '🧪 Developer Command Center & Automated Test Runner',
                    controller: 'app/Controllers/DeveloperController.php, app/Services/TestRunnerService.php',
                    views: 'views/developer/index.php, views/developer/architecture.php, views/developer/tests.php, developer/test_db.php',
                    tables: 'Seluruh 45 tabel skema public PostgreSQL Supabase',
                    rpc: 'Seluruh 18 Stored Procedures & Functions',
                    rules: [
                        'Akses eksklusif khusus user role developer (Auth::requireDeveloper()).',
                        'Dukungan eksekusi ganda: Web Browser interaktif & Terminal CLI tanpa duplikasi kode (27 Suites).',
                        'Seluruh pengujian berjalan dalam transaksi rollback sehingga database 100% steril.'
                    ]
                }
            };

            const m = modules[this.selectedModule];
            const taskHeader = taskHeaders[this.taskType] || 'Lakukan pengembangan modul berikut:';
            const noteText = this.customNote ? `\n\nDETAIL SPESIFIK DARI SAYA:\n${this.customNote}` : '';
            const rulesList = Array.isArray(m.rules)
                ? m.rules.map((r, i) => `${i + 1}. ${r}`).join('\n')
                : m.rules;

            this.generatedPrompt = `TARGET: ${taskHeader}

INFORMASI MODUL KEREN SNACK ERP:
- Nama Modul: ${m.title}
- Controller Terkait: ${m.controller}
- Views Terkait: ${m.views}
- Database Tables: ${m.tables}
- Stored Procedures / RPC: ${m.rpc}

ATURAN BISNIS & INTEGRITAS:
${rulesList}${noteText}

INSTRUKSI PENGERJAAN:
1. Pastikan kode mengikuti arsitektur PHP 8.1+ Native MVC yang sudah ada.
2. Gunakan Design Tokens (var(--color-canvas), var(--color-ink), var(--color-hairline)) agar adaptif di Mode Terang dan Gelap.
3. Jaga agar tidak ada breaking change pada rute dan tabel database lainnya.
4. Lakukan verifikasi pengujian otomatis setelah perubahan selesai.`;
        },

        copyPrompt() {
            const text = this.generatedPrompt;
            this.safeCopy(text, () => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2500);
                this.notify('Master Prompt Disalin! 📋', 'Prompt bersih siap ditempel langsung ke AI.');
            });
        },

        copyFullSystemMap() {
            const fullMap = `# KEREN SNACK ERP — SYSTEM ARCHITECTURE BLUEPRINT & CODEBASE GUIDE

## 1. 📌 GAMBARAN UMUM & PROFIL PROYEK
- **Nama Proyek**: KEREN SNACK ERP (Enterprise Resource Planning & POS System)
- **Domain Bisnis**: Manufaktur Snack/Makanan Ringan, Distribusi Multi-Channel, Konsinyasi Rak Toko Mitra, Driver Logistik, Kasir POS Ritel, Pesanan Grosir B2B, Manajemen Gudang & Resep BOM, Keuangan & Buku Kas, HR & Komisi Sales Berjenjang.
- **Tipe Aplikasi**: Web Application Server-Side Rendered (SSR) dengan Interaktivitas Reaktif Mobile-First (Alpine.js).

---

## 2. ⚡ TECH STACK & BAHASA PEMROGRAMAN
- **Backend Language**: PHP 8.1+ Native (Strict Types \`declare(strict_types=1)\`, PSR-4 Standard Autoloading, Zero Framework Overhead).
- **Architecture Pattern**: Modular MVC (Model-View-Controller) dengan Pemisahan Tanggung Jawab (Separation of Concerns).
- **Database Engine**: PostgreSQL 17 (Supabase Cloud Managed SSL Connection Pooler, 45+ Tabel Relasional, 18+ Stored Procedures & Triggers Atomik).
- **Cloud Object Storage**: Cloudflare R2 Object Storage (S3-Compatible API) + Local Disk Media Cache Layer.
- **Frontend Layer**: Native Server-Side Rendered PHP Views + Alpine.js (Reaktivitas Lapangan Ringan) + Native CSS Design System Tokens (Dark & Light Mode Adaptive) + Lucide Icons.
- **Third-Party Libraries**: Composer (\`phpoffice/phpspreadsheet\` untuk Impor/Ekspor Excel, \`dompdf/dompdf\` untuk Cetak Dokumen PDF, \`aws/aws-sdk-php\` untuk Cloudflare R2).
- **Testing & Verification Engine**: 27 Automated Test Suites Mandiri (Single Source of Truth CLI \`tests/run_all.php\` & Web Interactive \`/developer/tests\`).

---

## 3. 🛡️ PRINSIP REKAYASA & PROTOKOL KEAMANAN (ENGINEERING PROTOCOLS)
1. **Zero Persistent Mock Data**: Dilarang menyisipkan data tiruan/dummy permanen di database produksi. Seluruh pengujian wajib diisolasi menggunakan blok transaksi otomatis (\`BEGIN ... ROLLBACK\`).
2. **Single Source of Truth**:
   - Skema Database: \`database/01_schema.sql\`
   - Test Suites Registry: \`app/Services/TestRunnerService.php\`
   - Rute & Dispatcher: \`public/index.php\` & \`app/Core/Router.php\`
3. **Security Defense-in-Depth**:
   - CSRF Protection: Token Anti-CSRF wajib di setiap form mutasi data dan request AJAX POST.
   - Session Security: Sliding Inactivity Timeout 1 Jam (3.600 detik) dengan proteksi cookie HTTP-Only, SameSite=Lax.
   - RBAC Multi-Peran: 5 Peran Inti (\`owner\`, \`admin\`, \`sales\`, \`driver\`, \`mandor\`) + Peran Terproteksi (\`developer\`) dengan dukungan User-Level Permission Overrides.
   - Kredensial & Sandi: Bcrypt password hashing, parameter \`.env\` terisolasi tanpa hardcoded credentials.

---

## 4. 🌳 STRUKTUR DIREKTORI PROYEK (LENGKAP BESERTA TANGGUNG JAWAB)

\`\`\`text
kerensnack-erp/
├── app/                                 # 🧠 Logika Aplikasi & Backend Core
│   ├── Controllers/                     # 🕹️ 24 Application Controllers
│   │   ├── ActivityLogController.php    # Audit jejak forensik, tracking IP, filter modul & ekspor Excel
│   │   ├── AuthController.php           # Autentikasi multi-peran, verifikasi bcrypt, session heartbeat & logout
│   │   ├── CashController.php           # Buku kas multi-akun, mutasi, transfer bank, dan laporan cash flow
│   │   ├── ConsignmentController.php    # Konsinyasi terpadu: stok rak, opname, hitung laku, faktur tagihan & komisi
│   │   ├── CustomerController.php       # Master toko pelanggan, grup tier harga, wilayah & katalog item khusus
│   │   ├── CustomerOrderController.php  # Pesanan grosir B2B, invoice penjualan, alokasi stok & piutang tempo
│   │   ├── DeliveryController.php       # Surat jalan ekspedisi, Driver Mobile tanpa bocor harga & upload bukti terima
│   │   ├── DeveloperController.php      # Command center developer, blueprint visual, diagnostik DB & test runner
│   │   ├── EmployeeController.php       # Master karyawan (Sales komisi vs Driver armada nopol), kasbon & payroll
│   │   ├── ImportDataController.php     # Master Data Impor & Diffing Engine Excel 10 entitas & rekonsiliasi sync
│   │   ├── InventoryController.php      # Stok fisik gudang, kartu stok, penyesuaian susut/waste & bulk opname
│   │   ├── MediaController.php          # Media proxy Cloudflare R2, streaming file & manajemen cache lokal
│   │   ├── OrderDocumentController.php  # Unified Printing Engine: cetak PDF/Excel Surat Jalan, Faktur, Picking List
│   │   ├── OwnerController.php          # Owner Command Center: omzet, HPP, net working capital & analisis laba
│   │   ├── PermissionController.php     # 5-Tab Master RBAC: permission matrix dinamis & user overrides
│   │   ├── PosController.php            # Kasir POS retail, scan barcode universal, diskon & cetak struk thermal
│   │   ├── PricingController.php        # Matriks 30 level harga dinamis & penetapan harga tier pelanggan
│   │   ├── ProductController.php        # Master produk, brand/merek, bahan baku, kemasan, resep BOM & upah borongan
│   │   ├── ProfileController.php        # Manajemen profil akun pengguna login & ubah kata sandi
│   │   ├── PurchaseController.php       # Pengadaan vendor (PO), penerimaan fisik gudang & hutang dagang
│   │   ├── SalesOrderController.php     # Router compatibility wrapper untuk pesanan penjualan grosir B2B
│   │   ├── SettingsController.php       # Konfigurasi identitas toko, nama perusahaan, rekening bank & logo
│   │   ├── SupplierController.php       # Master vendor pemasok bahan mentah, bumbu racik & plastik kemasan
│   │   └── UserController.php           # Manajemen akun pengguna sistem: buat user, aktivasi status & ubah role
│   │
│   ├── Core/                            # ⚙️ Komponen Fondasi MVC Native
│   │   ├── Auth.php                     # Sesi keamanan, sliding timeout 1 jam, RBAC & requireDeveloper()
│   │   ├── Controller.php               # Base Controller: render view, json response, redirect & denyAccess
│   │   └── Router.php                   # Fast regex route dispatcher & automatic CSRF token verification
│   │
│   ├── Helpers/                         # 🛠️ 13 Helper Utilitas & Dokumen
│   │   ├── ActivityLog.php              # Logger audit forensik ke tabel log_aktivitas secara real-time
│   │   ├── CashVoucher.php              # Generator voucher kas masuk (BKM) dan kas keluar (BKK) bernomor resmi
│   │   ├── CompanySetting.php           # Helper konfigurasi parameter sistem dinamis dari tabel pengaturan_sistem
│   │   ├── CSRF.php                     # Generator & validator token Anti-CSRF berbasis sesi
│   │   ├── DocumentNumber.php           # Generator nomor transaksi resmi otomatis (SJ-..., INV-..., PO-...)
│   │   ├── ExcelExport.php              # Engine spreadsheet XML/Excel untuk omzet, stok, arus kas & piutang
│   │   ├── Flash.php                    # Manajer notifikasi kilat sesi pengguna (sukses, info, error)
│   │   ├── Format.php                   # Format standar Rupiah (Rp), tanggal lokal Indonesia & parsing numerik
│   │   ├── PaymentHelper.php            # Validasi & alokasi pembayaran piutang pelanggan dan faktur grosir
│   │   ├── PdfExport.php                # Engine generator PDF untuk surat jalan, invoice & picking list
│   │   ├── PrintDocumentHelper.php      # Unified print renderer untuk web browser & printer thermal 58/80mm
│   │   ├── StockHelper.php              # Kalkulasi mutasi stok multi-satuan (pcs, pack, bal) & cek ketersediaan
│   │   └── Upload.php                   # Secure upload handler gambar produk & bukti bayar dengan validasi MIME
│   │
│   └── Services/                        # ⚡ 4 Layanan Terpusat & Business Engine
│       ├── TestRunnerService.php        # Master registry 27 test suites, execution timer & global lock engine
│       ├── R2StorageService.php         # Cloudflare R2 Object Storage S3-compatible SDK integration
│       ├── MediaCacheService.php        # Local disk caching layer untuk media gambar R2
│       └── Import/                      # Engine Impor Data Master Excel
│           ├── ImportProcessor.php      # Orchestrator pemrosesan impor, validasi schema & diffing
│           ├── SmartReader.php          # Parser pintar berkas Excel & normalisasi tipe data sel
│           ├── TemplateGenerator.php    # Generator template Excel resmi untuk 10 modul master data
│           └── Handlers/                # 10 Entitas handler: Brand, Category, Customer, Employee, Item, Material, Pricing, Supplier, Territory, User
│
├── config/                              # 🔌 Konfigurasi Sistem & Basis Data
│   ├── database.php                     # Singleton PDO Database::getInstance() ke Supabase PostgreSQL 17
│   └── env.php                          # Parser mandiri berkas .env ke environment variables
│
├── database/                            # 🗄️ Skema SQL, RPC & Migrasi
│   ├── 01_schema.sql                    # Definisi 45 tabel relasional, foreign keys, indexes & enum
│   ├── 02_triggers_and_rpc.sql          # 18 Stored procedures & triggers (fn_proses_kunjungan_konsinyasi, dll)
│   ├── migrations 03 - 61.sql           # 60+ Berkas migrasi database berurutan resmi
│   └── seeds/                           # Seeder terisolasi untuk local sandbox testing
│
├── developer/                           # 💻 Alat Diagnostik Developer Lingkungan Lokal
│   ├── test_db.php                      # Diagnostik koneksi database Supabase, SSL latency & integritas schema
│   └── run_all.php                      # CLI shortcut runner pengujian 27 test suites
│
├── docs/                                # 📚 Dokumentasi Arsitektur & PRD
│   ├── ARCHITECTURE.md                  # Manual spesifikasi arsitektur sistem
│   └── PRD_MASTER_KEREN_SNACK.md        # Dokumen PRD master kebutuhan sistem
│
├── public/                              # 🌐 Web Server Entry Point (Public Web Root)
│   ├── index.php                        # Front Controller utama (Composer Autoload, Routing & Dispatch)
│   ├── .htaccess                        # Apache URL rewrite rules & trailing slash handling
│   └── assets/                          # Aset statis: css/app.css, js/app.js, alpine.min.js, lucide.min.js
│
├── storage/                             # 💾 Penyimpanan Berkas Lokal & Uploads
├── cache/                               # ⚡ Cache Lokal Media Cloudflare R2 & File Sementara
├── tests/                               # 🧪 27 Test Suites Otomatis & Integration Tests
│   ├── run_all.php                      # CLI test runner terpadu
│   └── test_r2_integration.php          # Verifikasi konektivitas bucket Cloudflare R2
│
└── views/                               # 🎨 19 Direktori Modul Antarmuka Tampilan (Views)
    ├── auth/                            # Layar login & sesi pengguna
    ├── cash/                            # Layar buku kas, transaksi & laporan arus kas
    ├── consignment/                     # Layar portal konsinyasi, stok rak, opname, tagihan & komisi
    ├── customer_orders/                 # Layar pesanan B2B / grosir, invoice & picking list
    ├── customers/                       # Layar master toko mitra pelanggan & rute wilayah
    ├── deliveries/                      # Layar manifest ekspedisi logistik & portal Driver Mobile
    ├── developer/                       # Layar developer command center, blueprint arsitektur & test runner
    ├── employees/                       # Layar master karyawan (Sales vs Driver), kasbon & penggajian
    ├── errors/                          # Layar error interaktif (403.php)
    ├── inventory/                       # Layar stok gudang, kartu stok & bulk opname
    ├── layouts/                         # Master template: master.php, sidebar.php, header.php
    ├── owner/                           # Layar Owner Executive Command Center & Business Analytics
    ├── pos/                             # Layar kasir POS retail, keranjang & scan barcode
    ├── pricing/                         # Layar matriks 30 level harga dinamis
    ├── products/                        # Layar master produk snack, bahan baku, kemasan & resep BOM
    ├── profile/                         # Layar profil akun pengguna & ubah kata sandi
    ├── purchases/                       # Layar pengadaan PO supplier & penerimaan fisik gudang
    ├── settings/                        # Layar pengaturan identitas perusahaan, log audit & impor data
    └── suppliers/                       # Layar master vendor pemasok bahan baku
\`\`\`

---

## 5. 🗺️ KATALOG 12 MODUL BISNIS UTAMA & ALUR KERJA
1. **Konsinyasi Terpadu & Sales Mobile (\`/consignment\`)**: Titip barang di etalase toko mitra, opname fisik rutin, formula laku instan (\`Titip Awal - (Sisa + Retur)\`), penerbitan faktur tagihan, dan komisi sales berjenjang.
2. **Driver Logistik & Surat Jalan (\`/deliveries\`, \`/driver-deliveries\`)**: Manifest pengiriman barang ke toko tanpa membocorkan harga HPP ke driver, update status antar real-time, dan trigger otomatis tambah saldo rak saat selesai diterima.
3. **Kasir POS Ritel Universal (\`/pos\`)**: Penjualan cepat ritel dengan scanner barcode universal kemasan snack, potong stok otomatis, dan mutasi kas masuk ke buku kas aktif.
4. **Pesanan Pelanggan Grosir B2B (\`/customer-orders\`)**: Pencatatan PO grosir, batch picking list gudang format PDF, invoice tempo kredit, dan pelunasan piutang bertahap.
5. **Owner Executive Command Center (\`/owner\`)**: Analitik profitabilitas (omzet bersih, HPP terjual, laba bersih), Net Working Capital (kas + piutang + valuasi stok gudang/rak - hutang vendor), dan leaderboard sales.
6. **Keuangan & Buku Kas (\`/cash\`)**: Mutasi kas masuk/keluar multi-akun bank, transfer berpasangan, rekonsiliasi kas terpadu (POS, Grosir, Konsinyasi), dan ekspor laporan arus kas.
7. **Gudang, Resep BOM & Opname (\`/inventory\`, \`/products\`)**: Bill of Materials (komposisi_item) dari bahan baku mentah + kemasan menjadi snack jadi, pencatatan produksi harian otomatis, dan kartu stok multi-satuan.
8. **Matriks 30 Level Harga (\`/pricing\`)**: Penentuan harga jual bertingkat hingga 30 level per grup produk yang terhubung dengan kategori tier toko pelanggan.
9. **Master Karyawan & Payroll (\`/employees\`)**: Pemisahan tegas posisi Sales (memiliki % komisi & toko binaan) vs Driver (memiliki nopol armada kendaraan tanpa komisi), kasbon, dan penggajian.
10. **Pengguna & 5-Tab RBAC (\`/users\`, \`/permissions\`)**: Matriks izin dinamis per fitur dengan user-level override dan proteksi akun developer di level database.
11. **Impor Data & Full-Sync Reconciliation (\`/settings/impor-data\`)**: Upload spreadsheet Excel untuk 10 modul master, smart reader, dan deteksi diff otomatis sebelum commit data.
12. **Developer Portal & Test Runner (\`/developer\`, \`/developer/tests\`)**: Visual blueprint arsitektur 100% Single Source of Truth, diagnostik database Supabase, dan eksekusi 27 test suites terisolasi dengan auto-rollback.

---

## 6. 🗄️ DATABASE SCHEMA & STORED PROCEDURES (RPC)
- **Tabel Relasional Utama**: \`pelanggan\`, \`stok_konsinyasi_toko\`, \`kunjungan_konsinyasi\`, \`rincian_kunjungan_konsinyasi\`, \`tagihan_kunjungan\`, \`pesanan\`, \`item_pesanan\`, \`surat_jalan\`, \`item\`, \`komposisi_item\`, \`pembelian\`, \`pemasok\`, \`karyawan\`, \`skema_komisi_sales\`, \`akun_kas\`, \`arus_kas\`, \`pengguna\`, \`peran\`, \`izin\`, \`pengaturan_sistem\`, \`log_aktivitas\`.
- **Fungsi Stored Procedure & Trigger Utama**:
  - \`fn_proses_kunjungan_konsinyasi\`: Kalkulasi barang laku, kerugian retur rusak, update rak toko, dan penerbitan faktur tagihan.
  - \`fn_catat_pembayaran_konsinyasi\`: Pelunasan tagihan bertahap/lunas dan pencatatan jurnal arus kas.
  - \`fn_trg_proses_pengiriman_konsinyasi\`: Trigger potong stok gudang dan tambah saldo rak toko saat surat jalan selesai diterima.
  - \`fn_hitung_harga_jual_item\`: Matriks penentuan harga jual 30 level dinamis.
  - \`fn_cari_item_by_barcode\`: Resolusi pencarian produk via barcode universal snack.
  - \`fn_guard_pelanggan_sales_driver\`: Guard integritas bahwa hanya karyawan posisi Sales yang bisa menjadi penanggung jawab toko konsinyasi.`;

            this.safeCopy(fullMap, () => {
                this.copiedFull = true;
                setTimeout(() => { this.copiedFull = false; }, 2500);
                this.notify('Full Arsitektur Disalin! 🗺️', 'Dokumentasi arsitektur & struktur direktori lengkap 100% siap ditempel ke AI.');
            });
        },

        copyFilePath(path) {
            this.safeCopy(path, () => {
                this.notify('Path File Disalin! 📄', path);
            });
        },

        copyTableName(tableName) {
            this.safeCopy(tableName, () => {
                this.notify('Nama Tabel Disalin! 🗄️', tableName);
            });
        },

        copyProcedureName(procedureName) {
            this.safeCopy(procedureName, () => {
                this.notify('Stored Procedure Disalin! ⚡', procedureName);
            });
        },

        copyFolderContext(folder) {
            let context = `KONTEKS DIREKTORI: ${folder.name}\n${folder.desc}\n\nDAFTAR FILE & FUNGSINYA:\n`;
            folder.files.forEach((f, i) => {
                context += `${i + 1}. ${f.name}: ${f.desc}\n`;
            });

            this.safeCopy(context, () => {
                this.notify('Konteks Folder Disalin! 📁', 'Seluruh rincian file di folder ' + folder.name + ' telah disalin.');
            });
        },

        safeCopy(text, onSuccess) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    if (onSuccess) onSuccess();
                }).catch(() => {
                    this.fallbackExecCopy(text, onSuccess);
                });
            } else {
                this.fallbackExecCopy(text, onSuccess);
            }
        },

        fallbackExecCopy(text, onSuccess) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                if (onSuccess) onSuccess();
            } catch (err) {
                console.error('Fallback copy failed: ', err);
            }
            document.body.removeChild(textArea);
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>
