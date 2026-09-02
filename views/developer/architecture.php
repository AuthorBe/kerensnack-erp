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
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="badge badge-primary" style="font-size:10px;font-weight:800;letter-spacing:0.04em;padding:2px 8px;">AI ARCHITECTURE STUDIO</span>
                        <span style="font-size:11px;font-weight:700;color:var(--color-success);display:inline-flex;align-items:center;gap:4px;">
                            <span style="width:6px;height:6px;border-radius:9999px;background:var(--color-success);display:inline-block;"></span>
                            Engine Online
                        </span>
                        <span class="badge badge-mono" style="font-size:10px;">PHP <?= htmlspecialchars($systemInfo['php_version'] ?? PHP_VERSION) ?></span>
                        <span class="badge badge-mono" style="font-size:10px;"><?= $systemInfo['total_tables'] ?? count($tables) ?> Tables</span>
                        <span class="badge badge-mono" style="font-size:10px;"><?= $systemInfo['total_procedures'] ?? count($procedures) ?> Procedures</span>
                    </div>
                    <h1 style="font-size:18px;font-weight:900;color:var(--color-ink);margin-top:3px;line-height:1.3;">
                        System Architecture Blueprint &amp; AI Studio
                    </h1>
                    <p style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
                        Pusat navigasi visual modular &amp; generator prompt konteks terpadu untuk AI Programming Logic.
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

                <a href="<?= Router::url('/owner') ?>" class="btn btn-primary flex-1 md:flex-initial" style="font-size:12px;font-weight:700;height:38px;background:var(--color-primary);border-color:var(--color-primary);border-radius:10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;">
                        <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                        <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                        <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                        <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                    </svg>
                    <span>Owner Hub</span>
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
            <span>Struktur Folder &amp; File</span>
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
            <span>Visual Topology &amp; Flow</span>
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
            <span>Peta Rute &amp; URL</span>
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
            <span>Database &amp; RPC (<?= count($tables) ?>)</span>
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
                    <h2 style="font-size:16px;font-weight:900;color:var(--color-ink);">Interactive AI Prompt Studio &amp; AI Context Prompt Generator</h2>
                </div>
                <p style="font-size:12px;color:var(--color-ink-mute);line-height:1.5;">
                    Pilih modul &amp; tipe tugas, masukkan instruksi singkat, dan AI Studio akan otomatis membuat prompt terstruktur kelas master siap tempel!
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
                        <option value="consignment_sales">📱 Sales Mobile &amp; Opname Rak</option>
                        <option value="delivery_driver">🚚 Driver Logistik &amp; Kiriman</option>
                        <option value="owner_dashboard">👑 Owner Command Center (C1-C6)</option>
                        <option value="consignment_admin">💻 Konsinyasi Admin (B1-B5)</option>
                        <option value="pos_cashier">🛒 POS Kasir &amp; Retail Penjualan</option>
                        <option value="finance_cash">💰 Keuangan, Kas &amp; Valuasi</option>
                        <option value="master_employees">👥 Karyawan (Sales vs Driver)</option>
                        <option value="pricing_engine">🏷️ Matriks 28 Level Harga &amp; Tier</option>
                        <option value="inventory_bom">📦 Gudang, Stok &amp; Resep BOM</option>
                    </select>
                </div>

                <!-- Step 2: Task Intent -->
                <div class="space-y-1.5">
                    <label style="display:block;font-size:12px;font-weight:800;color:var(--color-ink);">
                        2. Tipe Tugas AI
                    </label>
                    <select x-model="taskType" @change="generatePrompt()" class="form-select w-full text-xs font-bold" style="height:42px;border-radius:10px;">
                        <option value="add_feature">✨ Tambah Fitur / Tombol Baru</option>
                        <option value="fix_bug">🐛 Perbaiki Bug / Glitch / Gap</option>
                        <option value="redesign_ui">🎨 Polish &amp; Redesign UI/UX Mobile</option>
                        <option value="optimize_logic">⚡ Optimasi Logika Bisnis &amp; Stored Procedure</option>
                        <option value="write_test">🧪 Buat Test Otomatisasi (Integration Test)</option>
                    </select>
                </div>

                <!-- Step 3: Custom Instruction -->
                <div class="space-y-1.5">
                    <label style="display:block;font-size:12px;font-weight:800;color:var(--color-ink);">
                        3. Instruksi Khusus (Opsional)
                    </label>
                    <input type="text" x-model="customNote" @input="generatePrompt()" placeholder="Contoh: Tambahkan tombol export PDF..." class="form-input text-xs" style="height:42px;border-radius:10px;">
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
                        <h3 style="font-size:13.5px;font-weight:900;color:var(--color-ink);">Master AI Prompt (Siap Tempel ke AI)</h3>
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
                        Sentuh / klik folder mana saja untuk melihat seluruh file di dalamnya beserta penjelasan ringkas fungsinya!
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

    <!-- MODAL FOLDER INSPECTOR (RINCIAN FILE & PENJELASAN SINGKAT - FULL OVERLAY TELEPORTED) -->
    <template x-teleport="body">
        <div x-show="selectedFolder !== null" 
             x-cloak 
             class="modal-backdrop" 
             style="position:fixed !important;inset:0 !important;top:0 !important;left:0 !important;right:0 !important;bottom:0 !important;width:100vw !important;height:100vh !important;height:100dvh !important;margin:0 !important;padding:16px !important;z-index:999999 !important;background:rgba(0,0,0,0.85) !important;backdrop-filter:blur(10px) !important;-webkit-backdrop-filter:blur(10px) !important;display:flex !important;align-items:center !important;justify-content:center !important;box-sizing:border-box !important;" 
             @keydown.escape.window="closeFolder()">
            
            <div class="modal-box space-y-4" 
                 style="width:100%;max-width:640px;max-height:88vh;max-height:88dvh;border-radius:20px;background:var(--color-canvas);border:1.5px solid var(--color-hairline-strong);box-shadow:0 25px 50px -12px rgba(0, 0, 0, 0.7);padding:20px;margin:auto;display:flex;flex-direction:column;position:relative;z-index:1000000;" 
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
                        <div class="space-y-2 overflow-y-auto flex-1 min-h-0 pr-1 no-scrollbar" style="max-height:48vh;">
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
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Mobile &amp; Web Desktop Interfaces</div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div @click="selectAndCopyModule('consignment_sales')" class="p-3 rounded-xl cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-center">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">📱 Sales Mobile (`/consignment/sales`)</div>
                            <span class="badge badge-mono text-[9.5px]">Pilih</span>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Opname rak fisik, hitung laku instan, retur bagus/rusak, komisi toko.</div>
                    </div>

                    <div @click="selectAndCopyModule('delivery_driver')" class="p-3 rounded-xl cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-center">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">🚚 Driver Mobile (`/deliveries`)</div>
                            <span class="badge badge-mono text-[9.5px]">Pilih</span>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Manifest tugas antar, tanpa bocor harga, update status sampai toko.</div>
                    </div>

                    <div @click="selectAndCopyModule('pos_cashier')" class="p-3 rounded-xl cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-center">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">🛒 Kasir POS (`/pos`)</div>
                            <span class="badge badge-mono text-[9.5px]">Pilih</span>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Penjualan retail cepat, barcode universal, auto mutasi kas &amp; stok.</div>
                    </div>

                    <div @click="selectAndCopyModule('owner_dashboard')" class="p-3 rounded-xl cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-center">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">👑 Owner Hub (`/owner`)</div>
                            <span class="badge badge-mono text-[9.5px]">Pilih</span>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Gatekeeper approval pengiriman, omzet, komisi sales, aging piutang.</div>
                    </div>
                </div>
            </div>

            <!-- LAYER 2: CONTROLLERS & CORE -->
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
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">⚙️ `app/Core/Router.php`</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Fast URI regex dispatcher &amp; proteksi CSRF token otomatis.</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">🔐 `app/Core/Auth.php`</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">RBAC Multi-Role: Owner, Admin, Sales, Driver, Mandor.</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">🕹️ `app/Controllers/*`</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Consignment, Delivery, POS, Owner, Cash, Product, Employee, dll.</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">🛠️ `app/Helpers/*`</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Format rupiah/tanggal, ActivityLog, CSRF token, Flash session.</div>
                    </div>
                </div>
            </div>

            <!-- LAYER 3: POSTGRESQL SUPABASE ENGINE -->
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
                            <h3 style="font-size:13px;font-weight:800;color:var(--color-ink);">PostgreSQL Supabase</h3>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">42 Tabel Master &amp; Stored Procedures</div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">⚡ `fn_proses_kunjungan_konsinyasi`</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Hitung laku otomatis, kerugian HPP rusak, faktur tagihan riil.</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">⚡ `fn_catat_pembayaran_konsinyasi`</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Pelunasan cicil/lunas, mutasi saldo akun kas &amp; arus kas.</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">⚡ `trg_proses_pengiriman_konsinyasi`</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Auto potong stok gudang &amp; tambah saldo rak saat barang sampai.</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);">⚡ Matriks 28 Level Harga</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Katalog harga khusus otomatis per kategori toko mitra.</div>
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
                <h3 style="font-size:13px;font-weight:800;color:var(--color-ink);">Katalog Lengkap URL Endpoints &amp; Controllers</h3>
                <p style="font-size:11px;color:var(--color-ink-mute);">Daftar rute aktif aplikasi KEREN SNACK ERP.</p>
            </div>
            <input type="text" x-model="searchRoute" placeholder="Cari endpoint / controller..." class="form-input" style="height:34px;font-size:12px;max-width:240px;">
        </div>

        <div class="overflow-x-auto no-scrollbar">
            <table class="data-table" style="font-size:12px;">
                <thead>
                    <tr>
                        <th style="min-width:140px;">URL Route</th>
                        <th style="width:90px;">Method</th>
                        <th style="min-width:180px;">Controller Action</th>
                        <th style="min-width:180px;">File View</th>
                        <th style="min-width:130px;">Hak Akses</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in filteredRoutes" :key="r.url">
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
                        ⚡ Stored Procedures (<?= count($procedures) ?>)
                    </button>
                </div>
                <p style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;" x-text="dbViewMode === 'tables' ? 'Daftar tabel database aktif di skema public PostgreSQL Supabase.' : 'Daftar fungsi stored procedures & RPC aktif di PostgreSQL.'"></p>
            </div>
            <input type="text" x-model="searchTable" :placeholder="dbViewMode === 'tables' ? 'Cari nama tabel...' : 'Cari nama procedure...'" class="form-input" style="height:34px;font-size:12px;max-width:240px;">
        </div>

        <!-- Tables Grid View -->
        <div x-show="dbViewMode === 'tables'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 pt-1">
            <?php foreach ($tables as $t): ?>
            <div x-show="!searchTable || '<?= $t['table_name'] ?>'.toLowerCase().includes(searchTable.toLowerCase())"
                 @click="copyTableName('<?= $t['table_name'] ?>')"
                 class="p-2.5 rounded-xl flex items-center justify-between cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);font-size:11.5px;">
                <span class="font-mono font-bold" style="color:var(--color-ink);"><?= htmlspecialchars($t['table_name']) ?></span>
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-ink-mute);">
                    <path d="M12 3v18"></path>
                    <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                    <path d="M3 9h18"></path>
                    <path d="M3 15h18"></path>
                </svg>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Procedures Grid View -->
        <div x-show="dbViewMode === 'procedures'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 pt-1">
            <?php foreach ($procedures as $p): ?>
            <div x-show="!searchTable || '<?= $p['routine_name'] ?>'.toLowerCase().includes(searchTable.toLowerCase())"
                 @click="copyProcedureName('<?= $p['routine_name'] ?>')"
                 class="p-2.5 rounded-xl flex items-center justify-between cursor-pointer hover:border-primary transition-all" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);font-size:11.5px;">
                <div class="min-w-0 pr-2">
                    <span class="font-mono font-bold truncate block" style="color:var(--color-ink);"><?= htmlspecialchars($p['routine_name']) ?></span>
                    <span class="text-[10px] text-mute uppercase font-mono"><?= htmlspecialchars($p['routine_type']) ?></span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-primary);flex-shrink:0;">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- FLOATING TOAST NOTIFICATION (MODERN HUD BANNER)                           -->
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
        selectedModule: 'consignment_sales',
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
                badge: '17 Controllers',
                icon: '🕹️',
                desc: 'Otak pengendali request dan alur bisnis seluruh modul sistem.',
                files: [
                    { name: 'ConsignmentController.php', desc: 'Menangani opname rak fisik sales (A1-A4), pengajuan barang titip, rekonsiliasi, dan pencatatan pembayaran faktur (B1-B5).' },
                    { name: 'DeliveryController.php', desc: 'Menangani alur pengiriman logistik driver (antrian tugas, status berangkat, dan konfirmasi barang sampai di toko).' },
                    { name: 'OwnerController.php', desc: 'Menangani Owner Command Center (C1-C6): Gatekeeper persetujuan kiriman, komparasi omzet, leaderboard komisi sales, dan approval beban biaya.' },
                    { name: 'PosController.php', desc: 'Menangani transaksi kasir retail cepat, scan barcode snack, hitung diskon otomatis, dan potong stok langsung.' },
                    { name: 'CustomerOrderController.php', desc: 'Menangani pesanan penjualan grosir/B2B, surat jalan langsung, dan penerbitan faktur tagihan pelanggan.' },
                    { name: 'SalesOrderController.php', desc: 'Alias controller kompatibilitas pesanan penjualan B2B dan grosir.' },
                    { name: 'CashController.php', desc: 'Menangani pencatatan mutasi buku kas, transaksi masuk/keluar, rekonsiliasi kas toko, dan laporan cash flow.' },
                    { name: 'ProductController.php', desc: 'Menangani master item produk jadi, bahan baku mentah, kategori, dan resep Bill of Materials (BOM).' },
                    { name: 'PricingController.php', desc: 'Menangani matriks 28 level harga khusus per kategori toko dan tier pelanggan.' },
                    { name: 'CustomerController.php', desc: 'Menangani master data toko mitra pelanggan, grup tier harga, dan wilayah/rute pengiriman.' },
                    { name: 'EmployeeController.php', desc: 'Menangani master karyawan dengan pemisahan peran tegas antara Sales (punya % komisi) vs Driver (punya plat nopol armada).' },
                    { name: 'InventoryController.php', desc: 'Menangani manajemen stok fisik gudang, mutasi antar lokasi, opname stok internal, dan log kartu stok.' },
                    { name: 'PurchaseController.php', desc: 'Menangani order pembelian bahan baku ke supplier (PO Vendor) dan penerimaan barang gudang.' },
                    { name: 'SupplierController.php', desc: 'Menangani master data vendor pemasok bahan baku snack dan kemasan.' },
                    { name: 'AuthController.php', desc: 'Menangani login multi-role, verifikasi kredensial bcrypt, dan penghancuran sesi saat logout.' },
                    { name: 'DeveloperController.php', desc: 'Menangani portal developer arsitektur, inspeksi metadata database, dan generator prompt AI.' },
                    { name: 'ProfileController.php', desc: 'Menangani pembaruan data profil dan ganti kata sandi pengguna yang sedang login.' }
                ]
            },
            {
                id: 'app_core',
                name: 'app/Core/',
                badge: 'MVC Framework Core',
                icon: '⚙️',
                desc: 'Komponen inti pengendali arsitektur MVC native, keamanan, dan routing sistem.',
                files: [
                    { name: 'Router.php', desc: 'Engine routing regex cepat yang memetakan URL ke Controller dan memvalidasi CSRF token otomatis.' },
                    { name: 'Auth.php', desc: 'Guard autentikasi dan RBAC (Role-Based Access Control) multi-peran (Owner, Admin, Sales, Driver, Mandor).' },
                    { name: 'Controller.php', desc: 'Base class controller induk yang menyediakan helper render view, redirect, dan JSON response.' }
                ]
            },
            {
                id: 'app_helpers',
                name: 'app/Helpers/',
                badge: 'Security & Utilities',
                icon: '🛠️',
                desc: 'Kumpulan fungsi pembantu untuk format data, keamanan form, dan audit forensik.',
                files: [
                    { name: 'Format.php', desc: 'Formatting standar rupiah (Rp 1.000.000), format tanggal Indonesia, dan satuan angka.' },
                    { name: 'CSRF.php', desc: 'Generator dan validator token Anti-CSRF untuk melindungi seluruh form dari serangan eksternal.' },
                    { name: 'ActivityLog.php', desc: 'Pencatat riwayat aktivitas forensik sistem (siapa, kapan, aksi apa) secara real-time.' },
                    { name: 'Flash.php', desc: 'Manajer pesan flash notifikasi sesi (sukses, peringatan, error) antar request.' }
                ]
            },
            {
                id: 'views',
                name: 'views/',
                badge: 'UI Views & Layouts',
                icon: '🎨',
                desc: 'Antarmuka visual pengguna berbasis Server-Side Rendered PHP dengan reaktivitas Alpine.js.',
                files: [
                    { name: 'consignment/sales/index.php', desc: 'Layar utama Sales Mobile: Daftar toko binaan, status kunjungan, dan pencarian cepat.' },
                    { name: 'consignment/sales/opname.php', desc: 'Layar opname rak fisik toko: Hitung barang laku instan, retur bagus/rusak, dan modal konfirmasi.' },
                    { name: 'consignment/sales/delivery.php', desc: 'Layar form pengajuan barang titipan baru oleh Sales (tanpa membocorkan harga HPP).' },
                    { name: 'consignment/sales/summary.php', desc: 'Layar nota faktur tagihan dan rekap hasil kunjungan opname toko.' },
                    { name: 'consignment/index.php', desc: 'Layar Admin Konsinyasi 5-Tab (B1 Saldo Rak, B2 Assignment, B3 Pengiriman, B4 Piutang, B5 Riwayat).' },
                    { name: 'owner/index.php', desc: 'Layar Owner Command Center (C1 Omzet, C2 Gatekeeper Kiriman, C3 Komisi Sales, C4 Aging Piutang, C5 Kerugian, C6 Overdue).' },
                    { name: 'deliveries/index.php', desc: 'Layar Driver Mobile: Manifest tugas kirim surat jalan tanpa bocor harga, update status berangkat & sampai.' },
                    { name: 'pos/index.php', desc: 'Layar Kasir POS Retail: Keranjang kasir, scan barcode, kalkulasi diskon, cetak struk thermal.' },
                    { name: 'customer_orders/index.php', desc: 'Layar Penjualan B2B/Grosir: Manajemen faktur order grosir dan pencatatan pembayaran piutang.' },
                    { name: 'cash/index.php', desc: 'Layar Buku Kas: Rekonsiliasi saldo akun kas toko/bank dan mutasi kas.' },
                    { name: 'employees/index.php', desc: 'Layar Master Karyawan: Tabel pegawai, modal tambah/edit karyawan dengan input dinamis Sales vs Driver.' },
                    { name: 'products/index.php', desc: 'Layar Master Produk: Katalog snack, bahan baku mentah, resep BOM, dan level harga.' },
                    { name: 'layouts/master.php', desc: 'Layout template induk: Container responsif, dark/light theme engine, dan sidebar navigation.' }
                ]
            },
            {
                id: 'database',
                name: 'database/',
                badge: 'SQL & Stored Procedures',
                icon: '🗄️',
                desc: 'Skema basis data, stored procedures atomik, trigger mutasi stok, dan migrasi konsinyasi.',
                files: [
                    { name: '01_schema.sql', desc: 'Definisi lengkap 42 tabel master, transaksi, foreign keys, dan indeks performa.' },
                    { name: '02_triggers_and_rpc.sql', desc: 'Stored procedure fn_proses_kunjungan_konsinyasi, fn_catat_pembayaran_konsinyasi, dan trigger stok.' },
                    { name: '03_migration_konsinyasi_fase1.sql', desc: 'Skrip migrasi khusus pemisahan status pengiriman dan assignment toko binaan sales.' }
                ]
            },
            {
                id: 'config',
                name: 'config/',
                badge: 'System Config',
                icon: '🔌',
                desc: 'Konfigurasi koneksi database Singleton PDO PostgreSQL dengan proteksi SSL.',
                files: [
                    { name: 'database.php', desc: 'Kelas koneksi Singleton Database::getInstance() dengan penanganan error PDO exception.' }
                ]
            },
            {
                id: 'public',
                name: 'public/',
                badge: 'Web Entry Point',
                icon: '🌐',
                desc: 'Entry point publik web server dan aset statis (CSS, JS, Fonts).',
                files: [
                    { name: 'index.php', desc: 'Front Controller yang menangani seluruh request masuk dan memanggil Router.' },
                    { name: 'assets/css/app.css', desc: 'Stylesheet mandiri dengan token tema adaptif Light/Dark, zero-scrollbar, dan modal engine.' },
                    { name: 'assets/js/app.js', desc: 'Skrip global: ThemeEngine (Light/Dark mode) dan Sidebar toggle.' }
                ]
            },
            {
                id: 'docs',
                name: 'docs/',
                badge: 'Documentation',
                icon: '📚',
                desc: 'Buku panduan arsitektur sistem, spesifikasi PRD, dan petunjuk AI Programming Logic.',
                files: [
                    { name: 'ARCHITECTURE.md', desc: 'Dokumen arsitektur resmi single source of truth berisi 17 controller, 42 tabel, dan matriks RBAC.' },
                    { name: 'PRD-Konsinyasi-PENYEMPURNAAN.md', desc: 'Spesifikasi detail siklus konsinyasi 3 Persona (Sales A1-A4, Admin B1-B5, Owner C1-C6).' },
                    { name: 'PRD_MASTER_KEREN_SNACK.md', desc: 'PRD master seluruh modul ERP Keren Snack.' }
                ]
            }
        ],

        routes: [
            { url: '/login', method: 'GET / POST', action: 'AuthController::showLogin()', view: 'views/auth/login.php', role: 'Semua Pengguna' },
            { url: '/pos', method: 'GET / POST', action: 'PosController::index()', view: 'views/pos/index.php', role: 'Owner, Admin' },
            { url: '/customer-orders', method: 'GET / POST', action: 'CustomerOrderController::index()', view: 'views/customer_orders/index.php', role: 'Owner, Admin' },
            { url: '/customer-orders/create', method: 'GET / POST', action: 'CustomerOrderController::create()', view: 'views/customer_orders/create.php', role: 'Owner, Admin' },
            { url: '/consignment', method: 'GET / POST', action: 'ConsignmentController::index()', view: 'views/consignment/index.php', role: 'Owner, Admin' },
            { url: '/consignment/sales', method: 'GET', action: 'ConsignmentController::salesIndex()', view: 'views/consignment/sales/index.php', role: 'Sales, Admin, Owner' },
            { url: '/consignment/sales/opname', method: 'GET / POST', action: 'ConsignmentController::salesOpname()', view: 'views/consignment/sales/opname.php', role: 'Sales, Admin, Owner' },
            { url: '/consignment/sales/summary', method: 'GET', action: 'ConsignmentController::salesSummary()', view: 'views/consignment/sales/summary.php', role: 'Sales, Admin, Owner' },
            { url: '/consignment/sales/request-delivery', method: 'GET / POST', action: 'ConsignmentController::salesRequestDelivery()', view: 'views/consignment/sales/delivery.php', role: 'Sales, Admin, Owner' },
            { url: '/deliveries', method: 'GET / POST', action: 'DeliveryController::index()', view: 'views/deliveries/index.php', role: 'Driver, Sales, Admin, Owner' },
            { url: '/owner', method: 'GET / POST', action: 'OwnerController::index()', view: 'views/owner/index.php', role: 'Owner' },
            { url: '/cash', method: 'GET', action: 'CashController::index()', view: 'views/cash/index.php', role: 'Owner, Admin' },
            { url: '/cash/transactions', method: 'GET / POST', action: 'CashController::transactions()', view: 'views/cash/transactions.php', role: 'Owner, Admin' },
            { url: '/cash/reports', method: 'GET', action: 'CashController::reports()', view: 'views/cash/reports.php', role: 'Owner, Admin' },
            { url: '/inventory', method: 'GET / POST', action: 'InventoryController::index()', view: 'views/inventory/index.php', role: 'Owner, Admin, Mandor' },
            { url: '/products', method: 'GET / POST', action: 'ProductController::index()', view: 'views/products/index.php', role: 'Owner, Admin' },
            { url: '/pricing', method: 'GET / POST', action: 'PricingController::index()', view: 'views/pricing/index.php', role: 'Owner, Admin' },
            { url: '/customers', method: 'GET / POST', action: 'CustomerController::index()', view: 'views/customers/index.php', role: 'Owner, Admin' },
            { url: '/employees', method: 'GET / POST', action: 'EmployeeController::index()', view: 'views/employees/index.php', role: 'Owner, Admin' },
            { url: '/suppliers', method: 'GET / POST', action: 'SupplierController::index()', view: 'views/suppliers/index.php', role: 'Owner, Admin' },
            { url: '/purchases', method: 'GET / POST', action: 'PurchaseController::index()', view: 'views/purchases/index.php', role: 'Owner, Admin' },
            { url: '/profile', method: 'GET / POST', action: 'ProfileController::index()', view: 'views/profile/index.php', role: 'Semua Pengguna' },
            { url: '/developer/architecture', method: 'GET', action: 'DeveloperController::architecture()', view: 'views/developer/architecture.php', role: 'Developer, Owner' }
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
            this.notify('Modul Terpilih! 🎯', 'Konteks modul telah dimuat ke AI Prompt Studio.');
        },

        generatePrompt() {
            const taskHeaders = {
                add_feature: 'TASK: Tambahkan fitur/komponen baru pada modul berikut sesuai standar enterprise.',
                fix_bug: 'TASK: Lakukan investigasi dan perbaiki bug/glitch pada modul berikut hingga tuntas 100% (zero-defect).',
                redesign_ui: 'TASK: Redesign dan rapikan tampilan UI/UX mobile pada modul berikut agar responsif, modern, dan minimalis.',
                optimize_logic: 'TASK: Optimasi logika bisnis, query SQL, atau stored procedure (RPC) pada modul berikut.',
                write_test: 'TASK: Buat test suite otomatisasi (integration test) PHP untuk memverifikasi fungsionalitas modul berikut.'
            };

            const modules = {
                consignment_sales: {
                    title: '📱 Modul Konsinyasi & Sales Mobile Field App',
                    controller: 'app/Controllers/ConsignmentController.php (salesIndex, salesOpname, processSalesOpname, salesSummary, salesRequestDelivery, submitSalesDelivery)',
                    views: 'views/consignment/sales/index.php, views/consignment/sales/opname.php, views/consignment/sales/summary.php, views/consignment/sales/delivery.php',
                    tables: 'pelanggan, stok_konsinyasi_toko, kunjungan_konsinyasi, rincian_kunjungan_konsinyasi, pesanan, surat_jalan, karyawan',
                    rpc: 'fn_proses_kunjungan_konsinyasi',
                    rules: [
                        'Sales hanya mengelola toko binaannya yang telah di-assign resmi.',
                        'Rumus laku: Titip Awal - (Sisa Fisik + Retur Bagus + Retur Rusak).',
                        'Form pengajuan titip baru tidak membocorkan harga rupiah / HPP ke sales.',
                        'Komisi Sales dihitung otomatis dari total omzet laku.'
                    ]
                },
                delivery_driver: {
                    title: '🚚 Modul Driver Logistik & Pengiriman Surat Jalan',
                    controller: 'app/Controllers/DeliveryController.php (index, updateStatus)',
                    views: 'views/deliveries/index.php',
                    tables: 'surat_jalan, pesanan, pelanggan, karyawan',
                    rpc: 'trg_proses_pengiriman_konsinyasi (terpicu saat status surat jalan = "selesai_diterima")',
                    rules: [
                        'Driver TIDAK boleh melihat harga rupiah, HPP, atau komisi.',
                        'Driver hanya melihat daftar toko tujuan, kontak WA, dan total pcs snack.',
                        'Alur status pengiriman: disetujui_owner -> sedang_dikirim -> selesai_diterima.'
                    ]
                },
                owner_dashboard: {
                    title: '👑 Owner Command Center & Gatekeeper Approval',
                    controller: 'app/Controllers/OwnerController.php (index, approveConsignmentDelivery, rejectConsignmentDelivery, approveDraft, rejectDraft)',
                    views: 'views/owner/index.php',
                    tables: 'surat_jalan, pesanan, kunjungan_konsinyasi, rincian_kunjungan_konsinyasi, karyawan, akun_kas',
                    rpc: 'fn_proses_kunjungan_konsinyasi, fn_catat_pembayaran_konsinyasi',
                    rules: [
                        'C2 Gatekeeper: Barang titip konsinyasi baru WAJIB disetujui Owner sebelum keluar gudang.',
                        'C1 Komparasi omzet konsinyasi vs direct order retail.',
                        'C3 Leaderboard & estimasi komisi sales berkala.',
                        'C4 Aging piutang (<14 hari aman, 14-30 hari perhatian, >30 hari kritis/macet).',
                        'C5 Kerugian beban barang rusak HPP.',
                        'C6 Warning toko overdue belum dikunjungi >14 hari.'
                    ]
                },
                consignment_admin: {
                    title: '💻 Konsinyasi Admin Portal (B1-B5)',
                    controller: 'app/Controllers/ConsignmentController.php (index, assignDriver, cancelDelivery, payInvoice, printBilling)',
                    views: 'views/consignment/index.php',
                    tables: 'stok_konsinyasi_toko, pelanggan, pesanan, kunjungan_konsinyasi, surat_jalan, akun_kas, arus_kas',
                    rpc: 'fn_catat_pembayaran_konsinyasi',
                    rules: [
                        'B1 Saldo rak real-time seluruh toko mitra konsinyasi.',
                        'B2 Assignment Sales penanggung jawab toko binaan.',
                        'B3 Monitoring pengiriman berjalan dan surat jalan.',
                        'B4 Pencatatan cicil & lunas piutang konsinyasi.',
                        'B5 Riwayat log kunjungan forensik sistem.'
                    ]
                },
                pos_cashier: {
                    title: '🛒 Kasir POS & Direct Retail Order',
                    controller: 'app/Controllers/PosController.php (index, calculatePrice, searchBarcode, checkout)',
                    views: 'views/pos/index.php',
                    tables: 'pesanan, item_pesanan, item, akun_kas, arus_kas, riwayat_stok',
                    rpc: 'fn_hitung_harga_dinamis',
                    rules: [
                        'Mendukung barcode scanner kemasan snack universal.',
                        'Otomatis potong stok fisik gudang secara real-time.',
                        'Otomatis mencatat mutasi uang masuk ke buku kas aktif.'
                    ]
                },
                finance_cash: {
                    title: '💰 Keuangan, Buku Kas & Valuasi Arus Kas',
                    controller: 'app/Controllers/CashController.php (index, transactions, reports)',
                    views: 'views/cash/index.php, views/cash/transactions.php, views/cash/reports.php',
                    tables: 'akun_kas, arus_kas, kategori_arus_kas',
                    rpc: 'N/A (Transaksi atomik)',
                    rules: [
                        'Pencatatan kas masuk & kas keluar per akun bank/kas.',
                        'Rekonsiliasi saldo kas real-time terintegrasi POS & Konsinyasi.',
                        'Laporan cash flow harian dan bulanan.'
                    ]
                },
                master_employees: {
                    title: '👥 Master Karyawan (Pemisahan Jabatan Sales vs Driver)',
                    controller: 'app/Controllers/EmployeeController.php (index, store, update, toggleStatus)',
                    views: 'views/employees/index.php',
                    tables: 'karyawan, tabungan, kasbon',
                    rpc: 'N/A',
                    rules: [
                        'Sales: Memiliki input Komisi Penjualan (%).',
                        'Driver: Memiliki input Plat Nopol armada dan TANPA kolom komisi.',
                        'Borongan: Buruh packing pengemasan.'
                    ]
                },
                pricing_engine: {
                    title: '🏷️ Matriks 28 Level Harga & Tier Pelanggan',
                    controller: 'app/Controllers/PricingController.php (index, update)',
                    views: 'views/pricing/index.php',
                    tables: 'harga_khusus_pelanggan, level_harga, item, pelanggan',
                    rpc: 'fn_hitung_harga_dinamis',
                    rules: [
                        'Tier harga otomatis berdasarkan kategori grup toko.',
                        'Khusus konsinyasi menggunakan harga tier konsinyasi tetap.'
                    ]
                },
                inventory_bom: {
                    title: '📦 Master Gudang, Stok Fisik & Resep BOM',
                    controller: 'app/Controllers/ProductController.php, app/Controllers/InventoryController.php',
                    views: 'views/products/index.php, views/inventory/index.php',
                    tables: 'item, resep_bom, riwayat_stok, kategori_item, satuan_barang',
                    rpc: 'trg_update_stok_otomatis',
                    rules: [
                        'BOM (Bill of Materials) bahan mentah -> produk jadi.',
                        'Kartu stok fisik multi-satuan (pcs, bal, pack).'
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
            const fullMap = `KEREN SNACK ERP — COMPLETE SYSTEM ARCHITECTURE MAP
Tech Stack: PHP 8.1+ Native MVC, PostgreSQL Supabase (42 Tables), Alpine.js, Supabase Design System.

MODUL UTAMA:
1. Sales Mobile (/consignment/sales): views/consignment/sales/* -> ConsignmentController.php
2. Driver Mobile (/deliveries): views/deliveries/* -> DeliveryController.php
3. Owner Hub (/owner): views/owner/* -> OwnerController.php
4. Konsinyasi Admin (/consignment): views/consignment/* -> ConsignmentController.php
5. Kasir POS (/pos): views/pos/* -> PosController.php
6. Keuangan & Kas (/cash): views/cash/* -> CashController.php
7. Master Karyawan (/employees): views/employees/* -> EmployeeController.php
8. Matriks Harga (/pricing): views/pricing/* -> PricingController.php
9. Master Produk & BOM (/products): views/products/* -> ProductController.php

Seluruh tabel database berada di skema public PostgreSQL Supabase.`;

            this.safeCopy(fullMap, () => {
                this.copiedFull = true;
                setTimeout(() => { this.copiedFull = false; }, 2500);
                this.notify('Full Arsitektur Disalin! 🗺️', 'Dokumentasi arsitektur bersih siap ditempel ke AI.');
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
