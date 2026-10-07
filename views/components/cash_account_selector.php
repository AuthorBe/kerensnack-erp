<?php
/**
 * views/components/cash_account_selector.php
 * Reusable Interactive Cash Account Selector Component
 * 100% Mengikuti Standar ERP UI Design (Inter & Tabular Numbers, Supabase Clean UI, Lucide Icons)
 * 
 * Props expected in view scope or passed array:
 * - $akunKasOptions: array of active cash accounts [id, nama_akun, tipe_akun, saldo_saat_ini, is_escrow, is_default_pos]
 * - $fieldName: string name of input (default: 'akun_kas_id')
 * - $modelName: string Alpine.js property name (default: 'selectedKasId')
 * - $amountModel: string Alpine.js property for transaction nominal (default: 'transaksiNominal')
 * - $allowEscrow: bool whether to show/allow escrow accounts (default: false)
 * - $isRequired: bool (default: true)
 * - $labelTitle: string (default: 'Pilih Akun Kas / Bank')
 */

$fieldName = $fieldName ?? 'akun_kas_id';
$modelName = $modelName ?? 'selectedKasId';
$amountModel = $amountModel ?? 'transaksiNominal';
$allowEscrow = $allowEscrow ?? false;
$isRequired = $isRequired ?? true;
$labelTitle = $labelTitle ?? 'Pilih Sumber Akun Kas';
?>

<div class="space-y-2">
    <div class="flex items-center justify-between">
        <label class="form-label mb-0 text-xs font-bold text-slate-700 dark:text-slate-200">
            <?= htmlspecialchars($labelTitle) ?>
            <?php if ($isRequired): ?><span class="text-rose-500">*</span><?php endif; ?>
        </label>
        <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Klik salah satu akun</span>
    </div>

    <!-- Hidden Input for Standard Form POST -->
    <input type="hidden" name="<?= $fieldName ?>" :value="<?= $modelName ?>" <?= $isRequired ? 'required' : '' ?>>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto custom-scrollbar p-0.5">
        <template x-for="acc in (availableAccounts || [])" :key="acc.id">
            <div @click="<?= $modelName ?> = acc.id"
                 :class="{
                     'border-rose-600 dark:border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/40 dark:bg-rose-950/20': <?= $modelName ?> === acc.id,
                     'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 hover:border-slate-300 dark:hover:border-slate-600': <?= $modelName ?> !== acc.id,
                     'opacity-60 cursor-not-allowed border-dashed': !acc.is_escrow && (acc.saldo < (<?= $amountModel ?> || 0)) && isExpenseTransaction
                 }"
                 class="relative flex items-center justify-between p-2.5 rounded-lg border transition-all cursor-pointer select-none">
                
                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    <!-- Icon based on type -->
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                         :class="{
                             'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400': acc.tipe_akun === 'kas_tunai',
                             'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400': acc.tipe_akun === 'rekening_bank',
                             'bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400': acc.tipe_akun === 'kas_tabungan',
                             'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400': !['kas_tunai', 'rekening_bank', 'kas_tabungan'].includes(acc.tipe_akun)
                         }">
                        <template x-if="acc.tipe_akun === 'kas_tunai'">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                        </template>
                        <template x-if="acc.tipe_akun === 'rekening_bank'">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                        </template>
                        <template x-if="acc.tipe_akun === 'kas_tabungan'">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </template>
                        <template x-if="!['kas_tunai', 'rekening_bank', 'kas_tabungan'].includes(acc.tipe_akun)">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        </template>
                    </div>

                    <!-- Label & Type Badge -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate" x-text="acc.nama_akun"></span>
                            <template x-if="acc.is_escrow">
                                <span class="px-1.5 py-0.2 text-[9.5px] font-bold rounded bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-300">Terkunci</span>
                            </template>
                            <template x-if="acc.is_default_pos">
                                <span class="px-1.5 py-0.2 text-[9.5px] font-bold rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">Kasir Toko</span>
                            </template>
                        </div>
                        <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400 font-semibold mt-0.5">
                            Saldo: <span :class="acc.saldo < (<?= $amountModel ?> || 0) && isExpenseTransaction ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-700 dark:text-slate-200'" x-text="formatRupiah(acc.saldo)"></span>
                        </div>
                    </div>
                </div>

                <!-- Check Radio Indicator -->
                <div class="ml-2 flex-shrink-0">
                    <div class="w-4 h-4 rounded-full border flex items-center justify-center transition-colors"
                         :class="<?= $modelName ?> === acc.id ? 'border-rose-600 bg-rose-600 text-white' : 'border-slate-300 dark:border-slate-600 bg-transparent'">
                        <template x-if="<?= $modelName ?> === acc.id">
                            <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Alert Warning if Insufficient Balance for Expense -->
    <template x-if="isExpenseTransaction && selectedAccount && selectedAccount.saldo < (<?= $amountModel ?> || 0)">
        <div class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 flex items-center gap-2 text-rose-700 dark:text-rose-300 text-xs">
            <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 text-rose-600"></i>
            <span>Saldo akun kas tidak mencukupi untuk transaksi ini (Saldo: <b class="font-mono" x-text="formatRupiah(selectedAccount.saldo)"></b>).</span>
        </div>
    </template>
</div>
