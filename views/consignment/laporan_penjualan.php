<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();

// Siapkan data untuk Chart.js (JSON encoded)
// Urutkan toko berdasarkan omzet tertinggi
$sortedStores = $storeStats;
usort($sortedStores, fn($a, $b) => (float)$b['total_omzet'] <=> (float)$a['total_omzet']);

// Ambil maksimal 16 toko teratas untuk bar chart
$topBarStores = array_slice($sortedStores, 0, 16);
$chartLabels  = array_column($topBarStores, 'nama_toko');
$chartOmzet   = array_map(fn($s) => (float)$s['total_omzet'], $topBarStores);

// Untuk donat kontribusi: Ambil top 6 toko teratas, sisanya kelompokkan ke "Lainnya"
$donutLabels = [];
$donutOmzet  = [];
$donutPct    = [];
$topDonutStores = array_slice($sortedStores, 0, 6);
$grandTotalOmzet = (float)($totalOmzet > 0 ? $totalOmzet : 1);
$topDonutSum = 0;

foreach ($topDonutStores as $ds) {
    $omzetVal = (float)$ds['total_omzet'];
    if ($omzetVal > 0) {
        $donutLabels[] = $ds['nama_toko'];
        $donutOmzet[]  = $omzetVal;
        $donutPct[]    = round(($omzetVal / $grandTotalOmzet) * 100, 1);
        $topDonutSum  += $omzetVal;
    }
}
$otherOmzet = max(0, $totalOmzet - $topDonutSum);
if ($otherOmzet > 0 && count($sortedStores) > 6) {
    $donutLabels[] = 'Toko Lainnya (' . (count($sortedStores) - 6) . ')';
    $donutOmzet[]  = $otherOmzet;
    $donutPct[]    = round(($otherOmzet / $grandTotalOmzet) * 100, 1);
}

// Data tren penjualan harian
$trendLabels = array_map(fn($t) => date('d/m', strtotime($t['tanggal_kunjungan'])), $trendData);
$trendOmzet  = array_map(fn($t) => (float)$t['total_omzet_hari'], $trendData);
?>

<style>
/* Skeleton Shimmer Keyframes */
@keyframes modalShimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

.skeleton-box {
    background: linear-gradient(90deg, rgba(255,255,255,0.06) 25%, rgba(255,255,255,0.15) 37%, rgba(255,255,255,0.06) 63%);
    background-size: 400% 100%;
    animation: modalShimmer 1.5s ease-in-out infinite;
    border-radius: 8px;
}

:root:not(.dark) .skeleton-box {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 37%, #f1f5f9 63%);
    background-size: 400% 100%;
    animation: modalShimmer 1.5s ease-in-out infinite;
}

/* Chart Horizontal Scrollable Container & Drag Cursor */
.chart-scroll-container {
    overflow-x: auto;
    overflow-y: hidden;
    overscroll-behavior-x: contain;
    -webkit-overflow-scrolling: touch;
    cursor: grab;
    width: 100%;
    position: relative;
    padding-bottom: 6px;
}

.chart-scroll-container.is-dragging {
    cursor: grabbing !important;
    user-select: none !important;
}

/* Scroll hint chip */
.chart-hint-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 99px;
    background: var(--color-canvas-soft);
    color: var(--color-ink-mute);
    border: 1px solid var(--color-hairline);
    white-space: nowrap;
}

/* Modal Structural Shell (Anti-Gepeng & Fixed 3-Layer) */
.detail-modal-shell {
    max-width: 960px;
    width: 95%;
    height: 88vh;
    max-height: 860px;
    padding: 0;
    overflow: hidden;
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-3);
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    margin: auto;
    animation: modalPopIn 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.detail-modal-header {
    flex-shrink: 0;
    padding: 16px 22px;
    border-bottom: 1px solid var(--color-hairline);
    background-color: var(--color-canvas);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.detail-modal-body {
    flex: 1 1 0%;
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    padding: 20px 22px;
}

.detail-modal-footer {
    flex-shrink: 0;
    padding: 14px 22px;
    border-top: 1px solid var(--color-hairline);
    background-color: var(--color-canvas-soft);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Stat card value clamp */
.stat-card-value-clamp {
    font-size: clamp(17px, 2.2vw, 22px);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

/* Modal Detail Interior Components (High Contrast & Airy Spacing) */
.modal-detail-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    display: flex;
    flex-direction: column;
}

.modal-detail-card-header {
    padding: 12px 16px;
    border-bottom: 1px solid var(--color-hairline);
    background-color: var(--color-canvas-soft);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

/* Modal Tagihan List Container & Distinct Invoice Cards */
.modal-invoice-list {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 235px;
    overflow-y: auto;
}

.modal-invoice-item {
    background-color: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    border-radius: 10px;
    padding: 11px 13px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    transition: all 0.15s ease;
}

.modal-invoice-item:hover {
    border-color: var(--color-hairline-strong);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.dark .modal-invoice-item {
    background-color: rgba(255, 255, 255, 0.03);
    border-color: var(--color-hairline);
}

.dark .modal-invoice-item:hover {
    background-color: rgba(255, 255, 255, 0.06);
    border-color: var(--color-hairline-strong);
}

/* Modal Product List & Items with Clear Separators */
.modal-product-list {
    display: flex;
    flex-direction: column;
}

.modal-product-item {
    padding: 11px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--color-hairline);
    transition: background-color 0.15s ease;
}

.modal-product-item:last-child {
    border-bottom: none;
}

.modal-product-item:hover {
    background-color: var(--color-canvas-soft);
}

.dark .modal-product-item:hover {
    background-color: rgba(255, 255, 255, 0.03);
}

/* Modal Table Container */
.modal-table-wrap {
    max-height: 220px;
    overflow-y: auto;
    overflow-x: auto;
}

.modal-table-wrap table th {
    padding: 9px 14px;
    font-size: 11.5px;
    background-color: var(--color-canvas-soft);
    position: sticky;
    top: 0;
    z-index: 2;
}

.modal-table-wrap table td {
    padding: 9px 14px;
    font-size: 12px;
}

/* Responsive Modal Rules for Mobile Devices (Anti-Cutoff & Maximum Usable Width) */
@media (max-width: 640px) {
    .detail-modal-shell {
        width: 100% !important;
        height: 94vh !important;
        max-height: none !important;
        border-radius: 18px 18px 0 0 !important;
        margin-top: auto !important;
        margin-bottom: 0 !important;
    }
    .detail-modal-header {
        padding: 12px 14px !important;
    }
    .detail-modal-body {
        padding: 12px 10px !important;
    }
    .detail-modal-footer {
        padding: 12px 14px !important;
    }
    .modal-invoice-list {
        padding: 8px !important;
        gap: 8px !important;
    }
    .modal-invoice-item {
        padding: 9px 11px !important;
    }
    .modal-product-item {
        padding: 9px 12px !important;
    }
    .modal-table-wrap table th,
    .modal-table-wrap table td {
        padding: 8px 10px !important;
        font-size: 11px !important;
    }
}

/* ========================================================================= */
/* MAIN PAGE LAYOUT ENGINE (High Contrast, Proper Spacing, Zero Cramping)     */
/* ========================================================================= */
.laporan-page-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding-bottom: 80px;
}

@media (min-width: 640px) {
    .laporan-page-container {
        gap: 28px;
    }
}

/* Standalone Airy Card */
.laporan-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 18px;
    padding: 20px 22px;
    box-shadow: var(--shadow-1);
    display: flex;
    flex-direction: column;
}

@media (max-width: 640px) {
    .laporan-card {
        padding: 16px;
        border-radius: 14px;
    }
}

.dark .laporan-card {
    background-color: var(--color-canvas);
    border-color: var(--color-hairline);
}

/* Card Section Header with Generous Breathing Room */
.laporan-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 14px;
    margin-bottom: 18px;
    border-bottom: 1px solid var(--color-hairline);
    flex-shrink: 0;
}

/* KPI Card Custom Spacing */
.laporan-kpi-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 16px;
    padding: 18px 20px;
    box-shadow: var(--shadow-1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 135px;
    transition: all 0.15s ease-in-out;
}

.laporan-kpi-card:hover {
    box-shadow: var(--shadow-2);
    border-color: var(--color-hairline-strong);
}

.dark .laporan-kpi-card {
    background-color: var(--color-canvas);
    border-color: var(--color-hairline);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('laporanDashboard', () => ({
        // Chart datasets
        chartLabels: <?= json_encode($chartLabels) ?>,
        chartOmzet: <?= json_encode($chartOmzet) ?>,
        donutLabels: <?= json_encode($donutLabels) ?>,
        donutOmzet: <?= json_encode($donutOmzet) ?>,
        donutPct: <?= json_encode($donutPct) ?>,
        trendLabels: <?= json_encode($trendLabels) ?>,
        trendOmzet: <?= json_encode($trendOmzet) ?>,
        
        // UI & Filter states
        searchQuery: '',
        isModalOpen: false,
        isLoading: false,
        modalData: null,
        
        // Chart instances
        barChartInstance: null,
        donutChartInstance: null,
        lineChartInstance: null,
        modalChartInstance: null,

        init() {
            this.initChartsWithPolling();
            this.watchThemeChanges();
            this.setupDragToScroll();
        },

        setupDragToScroll() {
            // Drag-to-scroll horizontal gesture for mouse users
            this.$nextTick(() => {
                document.querySelectorAll('.chart-scroll-container').forEach(slider => {
                    let isDown = false;
                    let startX;
                    let scrollLeft;

                    slider.addEventListener('mousedown', (e) => {
                        isDown = true;
                        slider.classList.add('is-dragging');
                        startX = e.pageX - slider.offsetLeft;
                        scrollLeft = slider.scrollLeft;
                    });
                    slider.addEventListener('mouseleave', () => {
                        isDown = false;
                        slider.classList.remove('is-dragging');
                    });
                    slider.addEventListener('mouseup', () => {
                        isDown = false;
                        slider.classList.remove('is-dragging');
                    });
                    slider.addEventListener('mousemove', (e) => {
                        if (!isDown) return;
                        e.preventDefault();
                        const x = e.pageX - slider.offsetLeft;
                        const walk = (x - startX) * 1.5;
                        slider.scrollLeft = scrollLeft - walk;
                    });
                });
            });
        },

        isDarkMode() {
            return document.documentElement.classList.contains('dark');
        },

        getThemeConfig() {
            const dark = this.isDarkMode();
            return {
                isDark: dark,
                textColor: dark ? '#9aa0a6' : '#64748b',
                titleColor: dark ? '#e8eaed' : '#0f172a',
                gridColor: dark ? 'rgba(255, 255, 255, 0.07)' : 'rgba(0, 0, 0, 0.05)',
                tooltipBg: dark ? '#28292c' : '#0f172a',
                tooltipBorder: dark ? '#3c4043' : '#1e293b',
                tooltipText: '#ffffff'
            };
        },

        initChartsWithPolling() {
            if (this.chartLabels.length === 0 && this.trendLabels.length === 0) return;
            
            const check = setInterval(() => {
                if (typeof Chart !== 'undefined') {
                    clearInterval(check);
                    this.renderAllCharts();
                }
            }, 80);
        },

        watchThemeChanges() {
            const observer = new MutationObserver(() => {
                this.updateChartsTheme();
            });
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
        },

        updateChartsTheme() {
            if (this.barChartInstance) {
                this.barChartInstance.destroy();
                this.renderBarChart();
            }
            if (this.donutChartInstance) {
                this.donutChartInstance.destroy();
                this.renderDonutChart();
            }
            if (this.lineChartInstance) {
                this.lineChartInstance.destroy();
                this.renderLineChart();
            }
            if (this.modalChartInstance && this.modalData?.trend_omzet) {
                this.modalChartInstance.destroy();
                this.renderModalChart(this.modalData.trend_omzet);
            }
        },

        renderAllCharts() {
            this.renderBarChart();
            this.renderDonutChart();
            this.renderLineChart();
        },

        renderBarChart() {
            const canvas = document.getElementById('barChart');
            if (!canvas || this.chartLabels.length === 0) return;

            const theme = this.getThemeConfig();

            this.barChartInstance = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: this.chartLabels,
                    datasets: [{
                        label: 'Total Omzet (Rp)',
                        data: this.chartOmzet,
                        backgroundColor: '#10b981',
                        hoverBackgroundColor: '#059669',
                        borderRadius: 6,
                        borderSkipped: false,
                        maxBarThickness: 42
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: theme.tooltipBg,
                            borderColor: theme.tooltipBorder,
                            borderWidth: 1,
                            titleColor: theme.tooltipText,
                            bodyColor: theme.tooltipText,
                            padding: 12,
                            boxPadding: 6,
                            cornerRadius: 8,
                            callbacks: {
                                label: (ctx) => ' Omzet: ' + this.formatRupiah(ctx.parsed.y)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: theme.textColor,
                                font: { family: 'Inter', size: 11, weight: '600' },
                                maxRotation: 35,
                                minRotation: 0,
                                callback: function(val, idx) {
                                    const lbl = this.getLabelForValue(val);
                                    return lbl.length > 18 ? lbl.substr(0, 16) + '...' : lbl;
                                }
                            }
                        },
                        y: {
                            grid: { color: theme.gridColor },
                            ticks: {
                                color: theme.textColor,
                                font: { family: 'JetBrains Mono', size: 10.5 },
                                callback: (val) => {
                                    if (val >= 1000000) return (val / 1000000).toFixed(1) + ' jt';
                                    if (val >= 1000) return (val / 1000).toFixed(0) + ' rb';
                                    return val;
                                }
                            }
                        }
                    }
                }
            });
        },

        renderDonutChart() {
            const canvas = document.getElementById('donutChart');
            if (!canvas || this.donutLabels.length === 0) return;

            const theme = this.getThemeConfig();
            const colors = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#06b6d4', '#ec4899', '#64748b'];

            this.donutChartInstance = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: this.donutLabels,
                    datasets: [{
                        data: this.donutOmzet,
                        backgroundColor: colors.slice(0, this.donutLabels.length),
                        borderWidth: 2,
                        borderColor: theme.isDark ? '#303134' : '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 12,
                                color: theme.textColor,
                                font: { family: 'Inter', size: 11, weight: '600' }
                            }
                        },
                        tooltip: {
                            backgroundColor: theme.tooltipBg,
                            borderColor: theme.tooltipBorder,
                            borderWidth: 1,
                            titleColor: theme.tooltipText,
                            bodyColor: theme.tooltipText,
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: (ctx) => {
                                    const val = ctx.parsed;
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                    return ` ${ctx.label}: ${this.formatRupiah(val)} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });
        },

        renderLineChart() {
            const canvas = document.getElementById('lineChart');
            if (!canvas || this.trendLabels.length === 0) return;

            const theme = this.getThemeConfig();
            const ctx = canvas.getContext('2d');

            const gradient = ctx.createLinearGradient(0, 0, 0, 240);
            gradient.addColorStop(0, theme.isDark ? 'rgba(59, 130, 246, 0.35)' : 'rgba(37, 99, 235, 0.2)');
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

            this.lineChartInstance = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: this.trendLabels,
                    datasets: [{
                        label: 'Omzet Harian (Rp)',
                        data: this.trendOmzet,
                        borderColor: theme.isDark ? '#60a5fa' : '#2563eb',
                        borderWidth: 2.5,
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: theme.isDark ? '#60a5fa' : '#2563eb',
                        pointBorderColor: theme.isDark ? '#1f2937' : '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: theme.tooltipBg,
                            borderColor: theme.tooltipBorder,
                            borderWidth: 1,
                            titleColor: theme.tooltipText,
                            bodyColor: theme.tooltipText,
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: (ctx) => ' Omzet: ' + this.formatRupiah(ctx.parsed.y)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: theme.textColor,
                                font: { family: 'JetBrains Mono', size: 10.5 }
                            }
                        },
                        y: {
                            grid: { color: theme.gridColor },
                            ticks: {
                                color: theme.textColor,
                                font: { family: 'JetBrains Mono', size: 10.5 },
                                callback: (val) => {
                                    if (val >= 1000000) return (val / 1000000).toFixed(1) + ' jt';
                                    if (val >= 1000) return (val / 1000).toFixed(0) + ' rb';
                                    return val;
                                }
                            }
                        }
                    }
                }
            });
        },

        openModalDetail(pelangganId) {
            this.isModalOpen = true;
            this.isLoading = true;
            this.modalData = null;
            document.body.style.overflow = 'hidden';

            if (this.modalChartInstance) {
                this.modalChartInstance.destroy();
                this.modalChartInstance = null;
            }

            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;

            fetch(`<?= Router::url('/consignment/laporan-penjualan/detail-toko') ?>?pelanggan_id=${pelangganId}&start_date=${startDate}&end_date=${endDate}`)
                .then(res => res.json())
                .then(data => {
                    this.modalData = data;
                    this.isLoading = false;
                    
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                        this.renderModalChart(data.trend_omzet);
                    });
                })
                .catch(err => {
                    console.error(err);
                    this.isLoading = false;
                    alert('Gagal mengambil data detail toko.');
                });
        },

        closeModal() {
            this.isModalOpen = false;
            document.body.style.overflow = '';
        },

        renderModalChart(trendData) {
            if (!trendData || trendData.length === 0) return;
            const canvas = document.getElementById('modalLineChart');
            if (!canvas) return;

            const theme = this.getThemeConfig();
            const labels = trendData.map(t => {
                const parts = t.tanggal_kunjungan.split('-');
                return `${parts[2]}/${parts[1]}`;
            });
            const data = trendData.map(t => parseFloat(t.omzet));

            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 160);
            gradient.addColorStop(0, 'rgba(139, 92, 246, 0.25)');
            gradient.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

            if (this.modalChartInstance) {
                this.modalChartInstance.destroy();
            }

            this.modalChartInstance = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Omzet (Rp)',
                        data: data,
                        borderColor: '#8b5cf6',
                        borderWidth: 2,
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: '#8b5cf6',
                        pointBorderColor: theme.isDark ? '#1f2937' : '#ffffff',
                        pointRadius: 3.5
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    layout: {
                        padding: { top: 14, bottom: 4, left: 4, right: 8 }
                    },
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: theme.tooltipBg,
                            borderColor: theme.tooltipBorder,
                            borderWidth: 1,
                            titleColor: theme.tooltipText,
                            bodyColor: theme.tooltipText,
                            padding: 8,
                            cornerRadius: 6,
                            callbacks: {
                                label: (ctx) => ' ' + this.formatRupiah(ctx.parsed.y)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: theme.textColor,
                                font: { family: 'JetBrains Mono', size: 10 }
                            }
                        },
                        y: {
                            grid: { color: theme.gridColor },
                            ticks: {
                                color: theme.textColor,
                                font: { family: 'JetBrains Mono', size: 9.5 },
                                callback: (val) => {
                                    if (val >= 1000000) return (val / 1000000).toFixed(1) + ' jt';
                                    if (val >= 1000) return (val / 1000).toFixed(0) + ' rb';
                                    return val;
                                }
                            }
                        }
                    }
                }
            });
        },

        formatRupiah(num) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num || 0);
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const parts = dateStr.split(' ')[0].split('-');
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        },

        setQuickFilter(days) {
            const end = new Date();
            let start = new Date();
            
            if (days === 'today') {
                // start = end
            } else if (days === 'week') {
                const day = end.getDay();
                const diff = end.getDate() - day + (day === 0 ? -6 : 1);
                start = new Date(new Date().setDate(diff));
            } else if (days === 'month') {
                start = new Date(end.getFullYear(), end.getMonth(), 1);
            } else if (days === 'last_month') {
                start = new Date(end.getFullYear(), end.getMonth() - 1, 1);
                end.setDate(0); // Hari terakhir bulan lalu
            }

            const formatYMD = (d) => {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const dt = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${dt}`;
            };
            
            document.querySelector('input[name="start_date"]').value = formatYMD(start);
            document.querySelector('input[name="end_date"]').value = formatYMD(end);
            document.getElementById('filterForm').submit();
        }
    }));
});
</script>

<div class="laporan-page-container" x-data="laporanDashboard()">
     
    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER                                                            -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm p-2 rounded-xl" title="Kembali ke Portal">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Modul Konsinyasi &bull; <?= $isSales ? 'Sales Lapangan' : 'Performa &amp; Analitik' ?></span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl"><?= htmlspecialchars($pageTitle ?? 'Laporan Penjualan Konsinyasi') ?></h1>
                <p class="page-subtitle text-xs sm:text-sm"><?= htmlspecialchars($pageSubtitle ?? 'Dashboard Performa Penjualan Semua Toko Konsinyasi') ?></p>
            </div>
        </div>
        
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center;">
            <a href="<?= Router::url('/consignment/laporan-penjualan/export-excel') ?>?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&pelanggan_id=<?= urlencode($selectedStoreId) ?>&sales_id=<?= urlencode($selectedSalesId) ?>" 
               class="btn btn-secondary"
               style="height:38px; background:#10b981; color:#fff; border-color:#059669; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                <span>Export Excel</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. FILTER CARD                                                            -->
    <!-- ========================================================================= -->
    <div class="laporan-card">
        <form id="filterForm" action="<?= Router::url('/consignment/laporan-penjualan') ?>" method="GET">
            
            <!-- Quick Filter Bar -->
            <div class="laporan-card-header flex-wrap gap-2.5">
                <div class="flex items-center gap-2 text-xs font-bold" style="color:var(--color-ink-mute);">
                    <i data-lucide="calendar" class="w-4 h-4 text-emerald-500"></i>
                    <span>PILIHAN PERIODE CEPAT:</span>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" @click="setQuickFilter('today')" class="btn btn-secondary btn-sm" style="font-size:11.5px;padding:5px 11px;border-radius:8px;">
                        Hari Ini
                    </button>
                    <button type="button" @click="setQuickFilter('week')" class="btn btn-secondary btn-sm" style="font-size:11.5px;padding:5px 11px;border-radius:8px;">
                        Minggu Ini
                    </button>
                    <button type="button" @click="setQuickFilter('month')" class="btn btn-secondary btn-sm" style="font-size:11.5px;padding:5px 11px;border-radius:8px;">
                        Bulan Ini
                    </button>
                    <button type="button" @click="setQuickFilter('last_month')" class="btn btn-secondary btn-sm" style="font-size:11.5px;padding:5px 11px;border-radius:8px;">
                        Bulan Lalu
                    </button>
                </div>
            </div>

            <!-- Fluid Responsive Filter Grid -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end;">
                <div>
                    <label class="form-label" style="font-size:11.5px;font-weight:700;color:var(--color-ink-secondary);margin-bottom:7px;display:block;">Dari Tanggal</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-input" style="height:38px; font-size:12.5px;">
                </div>

                <div>
                    <label class="form-label" style="font-size:11.5px;font-weight:700;color:var(--color-ink-secondary);margin-bottom:7px;display:block;">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-input" style="height:38px; font-size:12.5px;">
                </div>
                
                <div>
                    <label class="form-label" style="font-size:11.5px;font-weight:700;color:var(--color-ink-secondary);margin-bottom:7px;display:block;">Filter Toko Mitra</label>
                    <select name="pelanggan_id" class="form-select" style="height:38px; font-size:12.5px;">
                        <option value="">Semua Toko Konsinyasi</option>
                        <?php foreach ($stores as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $selectedStoreId === (string)$s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nama_toko']) ?> <?= !empty($s['kode_pelanggan']) ? '(' . htmlspecialchars($s['kode_pelanggan']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!$isSales): ?>
                <div>
                    <label class="form-label" style="font-size:11.5px;font-weight:700;color:var(--color-ink-secondary);margin-bottom:7px;display:block;">Filter Sales PIC</label>
                    <select name="sales_id" class="form-select" style="height:38px; font-size:12.5px;">
                        <option value="">Semua Sales</option>
                        <?php foreach ($salesList as $sl): ?>
                            <option value="<?= $sl['id'] ?>" <?= $selectedSalesId === (string)$sl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sl['nama_karyawan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="submit" class="btn btn-primary flex-1" style="height:38px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                        <i data-lucide="filter" class="w-4 h-4"></i>
                        <span>Tampilkan</span>
                    </button>
                    <?php if (!empty($selectedStoreId) || !empty($selectedSalesId) || $startDate !== date('Y-m-01') || $endDate !== date('Y-m-d')): ?>
                    <a href="<?= Router::url('/consignment/laporan-penjualan') ?>" class="btn btn-secondary" title="Reset Filter" style="height:38px; width:38px; padding:0; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. KPI METRIC STAT CARDS                                                  -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        
        <!-- CARD 1: Total Omzet -->
        <div class="laporan-kpi-card" style="border-left:4px solid #10b981;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-card-label">Total Omzet Terjual</div>
                <div class="stat-card-icon" style="background:rgba(16,185,129,0.12);color:#10b981;">
                    <i data-lucide="trending-up"></i>
                </div>
            </div>
            <div class="stat-card-value-clamp font-mono font-black" style="color:#10b981;margin:6px 0 10px 0;">
                <?= Format::rupiah($totalOmzet) ?>
            </div>
            <div class="stat-card-footer" style="display:flex;align-items:center;justify-content:space-between;padding-top:10px;margin-top:0;border-top:1px solid var(--color-hairline-cool);">
                <div style="display:flex;align-items:center;gap:4px;font-size:11px;font-weight:700;" class="<?= $momGrowth >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500 dark:text-rose-400' ?>">
                    <i data-lucide="<?= $momGrowth >= 0 ? 'trending-up' : 'trending-down' ?>" style="width:13px;height:13px;"></i>
                    <span><?= ($momGrowth >= 0 ? '+' : '') . $momGrowth ?>% MoM</span>
                </div>
                <span style="font-size:10.5px;color:var(--color-ink-mute);">vs bulan lalu</span>
            </div>
        </div>

        <!-- CARD 2: Total Kunjungan -->
        <div class="laporan-kpi-card" style="border-left:4px solid #3b82f6;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-card-label">Total Kunjungan Toko</div>
                <div class="stat-card-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6;">
                    <i data-lucide="map-pin"></i>
                </div>
            </div>
            <div class="stat-card-value-clamp font-mono font-black" style="color:#3b82f6;margin:6px 0 10px 0;">
                <?= number_format($totalKunjungan) ?> <span style="font-size:13px;font-weight:600;color:var(--color-ink-mute);">visit</span>
            </div>
            <div class="stat-card-footer" style="display:flex;align-items:center;gap:6px;padding-top:10px;margin-top:0;border-top:1px solid var(--color-hairline-cool);">
                <i data-lucide="calendar-check" style="width:13px;height:13px;"></i>
                <span>Log opname &amp; restock rak</span>
            </div>
        </div>

        <!-- CARD 3: Avg per Kunjungan -->
        <div class="laporan-kpi-card" style="border-left:4px solid #8b5cf6;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-card-label">Rata-Rata / Kunjungan</div>
                <div class="stat-card-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6;">
                    <i data-lucide="receipt"></i>
                </div>
            </div>
            <div class="stat-card-value-clamp font-mono font-black" style="color:#8b5cf6;margin:6px 0 10px 0;">
                <?= Format::rupiah($avgPerKunjungan) ?>
            </div>
            <div class="stat-card-footer" style="display:flex;align-items:center;gap:6px;padding-top:10px;margin-top:0;border-top:1px solid var(--color-hairline-cool);">
                <i data-lucide="activity" style="width:13px;height:13px;"></i>
                <span>Yield omzet rata-rata visit</span>
            </div>
        </div>

        <!-- CARD 4: Toko Aktif -->
        <div class="laporan-kpi-card" style="border-left:4px solid #f59e0b;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-card-label">Toko Aktif Transaksi</div>
                <div class="stat-card-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;">
                    <i data-lucide="store"></i>
                </div>
            </div>
            <div class="stat-card-value-clamp font-mono font-black" style="color:#f59e0b;margin:6px 0 10px 0;">
                <?= $tokoAktif ?> <span style="font-size:13px;font-weight:600;color:var(--color-ink-mute);">/ <?= count($storeStats) ?> toko</span>
            </div>
            <div class="stat-card-footer" style="display:flex;align-items:center;gap:6px;padding-top:10px;margin-top:0;border-top:1px solid var(--color-hairline-cool);">
                <i data-lucide="pie-chart" style="width:13px;height:13px;"></i>
                <span><?= count($storeStats) > 0 ? round(($tokoAktif / count($storeStats)) * 100) : 0 ?>% penetrasi aktif mitra</span>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. EMPTY DATA NOTICE OR CHARTS SECTION                                    -->
    <!-- ========================================================================= -->
    <?php if (empty($storeStats)): ?>
        <div class="card p-10 sm:p-14 text-center rounded-3xl" style="border:1px solid var(--color-hairline);">
            <div style="width:52px;height:52px;border-radius:50%;background:rgba(16,185,129,0.1);color:#10b981;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i data-lucide="bar-chart-2" style="width:26px;height:26px;"></i>
            </div>
            <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Tidak Ada Data Penjualan Konsinyasi</h3>
            <p class="text-xs sm:text-sm mt-1 max-w-md mx-auto" style="color:var(--color-ink-mute);">
                Belum ada data transaksi kunjungan dengan penjualan laku pada rentang filter tanggal yang dipilih.
            </p>
        </div>
    <?php else: ?>

        <!-- CHARTS ROW 1: BAR CHART RANKING & DONUT KONTRIBUSI -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            
            <!-- Ranking Omzet Toko (Horizontal Scrollable Canvas) -->
            <div class="laporan-card lg:col-span-2 justify-between">
                <div class="laporan-card-header flex-wrap gap-2">
                    <div class="flex items-center gap-2.5">
                        <div style="width:34px;height:34px;border-radius:10px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="bar-chart-3" style="width:17px;height:17px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0;">Ranking Omzet Mitra Toko</h3>
                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Top <?= count($topBarStores) ?> Toko dengan nominal penjualan konsinyasi tertinggi</div>
                        </div>
                    </div>
                    <div class="chart-hint-chip">
                        <i data-lucide="move-horizontal" style="width:13px;height:13px;"></i>
                        <span>Geser horizontal</span>
                    </div>
                </div>
                
                <div class="chart-scroll-container custom-scrollbar">
                    <div :style="`min-width: max(100%, ${Math.max(620, chartLabels.length * 56)}px); height: 320px; position: relative;`">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Donut Kontribusi Omzet -->
            <div class="laporan-card justify-between">
                <div class="laporan-card-header">
                    <div class="flex items-center gap-2.5">
                        <div style="width:34px;height:34px;border-radius:10px;background:rgba(59,130,246,0.12);color:#3b82f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="pie-chart" style="width:17px;height:17px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0;">Pangsa Kontribusi Omzet</h3>
                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Distribusi porsi penjualan antar toko</div>
                        </div>
                    </div>
                    <span class="badge badge-primary font-mono" style="font-size:10.5px;">Proporsi %</span>
                </div>

                <div class="relative w-full" style="height:320px;">
                    <canvas id="donutChart"></canvas>
                </div>
            </div>
        </div>

        <!-- CHARTS ROW 2: TREN PENJUALAN HARIAN (Horizontal Scrollable Canvas) -->
        <div class="laporan-card">
            <div class="laporan-card-header flex-col sm:flex-row gap-2.5">
                <div class="flex items-center gap-2.5">
                    <div style="width:34px;height:34px;border-radius:10px;background:rgba(37,99,235,0.12);color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="activity" style="width:17px;height:17px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0;">Tren Omzet Penjualan Harian</h3>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Fluktuasi omzet laku dari kunjungan sales selama rentang tanggal terpilih</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="chart-hint-chip">
                        <i data-lucide="move-horizontal" style="width:13px;height:13px;"></i>
                        <span>Geser horizontal</span>
                    </div>
                    <div class="hidden sm:flex items-center gap-2 text-xs font-mono" style="color:var(--color-ink-mute);">
                        <span>Total Hari: <strong><?= count($trendData) ?></strong></span>
                        <span>&bull;</span>
                        <span>Puncak: <strong class="text-emerald-600 dark:text-emerald-400"><?= Format::rupiah(!empty($trendOmzet) ? max($trendOmzet) : 0) ?></strong></span>
                    </div>
                </div>
            </div>

            <div class="chart-scroll-container custom-scrollbar">
                <div :style="`min-width: max(100%, ${Math.max(680, trendLabels.length * 48)}px); height: 260px; position: relative;`">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 5. TABEL KPI PER TOKO MITRA                                               -->
        <!-- ========================================================================= -->
        <div class="table-wrapper" style="border-radius:18px;">
            <!-- Table Action Header -->
            <div style="padding:16px 20px;border-bottom:1px solid var(--color-hairline);background-color:var(--color-canvas);display:flex;flex-direction:column;gap:12px;" class="sm:flex-row sm:items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div style="width:32px;height:32px;border-radius:9px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="table" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0;">Rincian KPI &amp; Performa Per Toko</h3>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Menampilkan <?= count($storeStats) ?> toko konsinyasi terdaftar</div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Search Input Filter -->
                    <div class="form-input-icon w-full sm:w-auto" style="min-width:220px;">
                        <i data-lucide="search" class="icon-left"></i>
                        <input type="text" 
                               x-model="searchQuery" 
                               placeholder="Cari toko / kode..." 
                               class="form-input" 
                               style="height:36px;font-size:12px;border-radius:8px;">
                    </div>
                    
                    <div class="hidden md:flex items-center gap-1.5 text-xs font-semibold" style="color:var(--color-ink-mute);">
                        <span style="width:8px;height:8px;border-radius:50%;background:#ef4444;display:inline-block;"></span>
                        <span>Idle (&gt;14 Hari)</span>
                    </div>
                </div>
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="cell-center" style="width:65px;">Rank</th>
                            <th style="min-width:220px;">Toko Mitra &amp; Kode</th>
                            <th style="min-width:130px;">Sales PIC</th>
                            <th class="cell-right" style="width:145px;">Total Omzet</th>
                            <th style="width:130px;">Kontribusi %</th>
                            <th class="cell-center" style="width:90px;">Kunjungan</th>
                            <th class="cell-right" style="width:130px;">Avg / Visit</th>
                            <th class="cell-center" style="width:115px;">Terakhir</th>
                            <th class="cell-center" style="width:95px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1; 
                        foreach ($storeStats as $s): 
                            $searchKey = strtolower($s['nama_toko'] . ' ' . ($s['kode_pelanggan'] ?? '') . ' ' . ($s['nama_sales'] ?? ''));
                        ?>
                        <tr x-show="!searchQuery || '<?= addslashes($searchKey) ?>'.includes(searchQuery.toLowerCase())"
                            class="<?= $s['is_idle'] ? 'bg-rose-500/5' : '' ?>">
                            
                            <!-- Rank -->
                            <td class="cell-center">
                                <?php if ($rank === 1): ?>
                                    <span class="badge" style="background:rgba(245,158,11,0.15);color:#d97706;border:1px solid rgba(245,158,11,0.3);font-weight:800;font-size:11px;">
                                        🥇 #1
                                    </span>
                                <?php elseif ($rank === 2): ?>
                                    <span class="badge" style="background:rgba(148,163,184,0.15);color:#64748b;border:1px solid rgba(148,163,184,0.3);font-weight:800;font-size:11px;">
                                        🥈 #2
                                    </span>
                                <?php elseif ($rank === 3): ?>
                                    <span class="badge" style="background:rgba(217,119,6,0.12);color:#b45309;border:1px solid rgba(217,119,6,0.25);font-weight:800;font-size:11px;">
                                        🥉 #3
                                    </span>
                                <?php else: ?>
                                    <span class="font-mono text-xs font-bold" style="color:var(--color-ink-mute);">
                                        #<?= $rank ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Toko & Kode (Structured 2-Line) -->
                            <td>
                                <div style="display:flex;flex-direction:column;gap:3px;">
                                    <div class="flex items-center gap-1.5">
                                        <strong style="color:var(--color-ink);font-size:13px;"><?= htmlspecialchars($s['nama_toko']) ?></strong>
                                        <?php if ($s['is_idle']): ?>
                                        <span class="badge badge-danger" style="font-size:9.5px;padding:1px 6px;">
                                            Idle <?= $s['idle_days'] ?>h
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span style="font-size:10px;font-weight:800;color:#0284c7;background:rgba(2,132,199,0.08);padding:1px 6px;border-radius:4px;border:1px solid rgba(2,132,199,0.18);font-family:var(--font-mono);">
                                            <?= htmlspecialchars($s['kode_pelanggan'] ?: 'TOKO') ?>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Sales PIC -->
                            <td style="color:var(--color-ink-secondary);font-size:12px;font-weight:600;">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="user" class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--color-ink-mute);"></i>
                                    <span class="truncate"><?= htmlspecialchars($s['nama_sales'] ?? 'Belum Diassign') ?></span>
                                </div>
                            </td>

                            <!-- Total Omzet -->
                            <td class="cell-right cell-currency" style="font-weight:800;color:#10b981;font-size:13px;">
                                <?= Format::rupiah($s['total_omzet']) ?>
                            </td>

                            <!-- Kontribusi % with progress bar -->
                            <td>
                                <div style="display:flex;flex-direction:column;gap:5px;">
                                    <div class="flex items-center justify-between text-[11px] font-mono">
                                        <span style="color:var(--color-ink-secondary);font-weight:700;"><?= $s['persen_kontribusi'] ?>%</span>
                                    </div>
                                    <div style="width:100%;height:5px;background:var(--color-hairline);border-radius:99px;overflow:hidden;">
                                        <div style="width:<?= min(100, $s['persen_kontribusi']) ?>%;height:100%;background:#10b981;border-radius:99px;"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kunjungan -->
                            <td class="cell-center">
                                <span class="badge badge-mono">
                                    <?= $s['total_kunjungan'] ?>x
                                </span>
                            </td>

                            <!-- Avg per visit -->
                            <td class="cell-right cell-currency" style="font-size:12px;color:var(--color-ink-secondary);">
                                <?= Format::rupiah($s['avg_per_kunjungan']) ?>
                            </td>

                            <!-- Terakhir Dikunjungi -->
                            <td class="cell-center" style="font-size:11.5px;">
                                <?php if ($s['last_visit']): ?>
                                    <span class="font-mono <?= $s['is_idle'] ? 'text-rose-600 dark:text-rose-400 font-bold' : '' ?>" style="<?= !$s['is_idle'] ? 'color:var(--color-ink-mute);' : '' ?>">
                                        <?= date('d/m/Y', strtotime($s['last_visit'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-muted" style="font-size:10px;">Belum Pernah</span>
                                <?php endif; ?>
                            </td>

                            <!-- Aksi -->
                            <td class="cell-center">
                                <button type="button" 
                                        @click="openModalDetail('<?= $s['pelanggan_id'] ?>')" 
                                        class="btn btn-secondary btn-sm"
                                        style="padding:5px 11px;font-size:11.5px;font-weight:700;border-radius:8px;gap:5px;display:inline-flex;align-items:center;">
                                    <i data-lucide="eye" style="width:13px;height:13px;"></i>
                                    <span>Detail</span>
                                </button>
                            </td>
                        </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- 6. MODAL POP-UP DETAIL TOKO (3-LAYER SHELL + SKELETON SHIMMER)            -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="isModalOpen" 
         x-cloak 
         class="modal-backdrop" 
         @click.self="closeModal()" 
         @keydown.escape.window="closeModal()">
        
        <div class="detail-modal-shell" @click.stop>
            
            <!-- LAYER 1: MODAL HEADER (STICKY) -->
            <div class="detail-modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="store" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <!-- Loaded state header -->
                        <template x-if="!isLoading && modalData">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span style="font-size:10px;font-weight:800;color:#0284c7;background:rgba(2,132,199,0.08);padding:1px 6px;border-radius:4px;border:1px solid rgba(2,132,199,0.18);font-family:var(--font-mono);flex-shrink:0;" x-text="modalData?.toko_info?.kode_pelanggan || 'TOKO'"></span>
                                    <h3 class="truncate" style="font-size:15px;font-weight:900;color:var(--color-ink);margin:0;" x-text="modalData?.toko_info?.nama_toko || 'Detail Toko'"></h3>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 text-xs truncate" style="color:var(--color-ink-mute);">
                                    <span>Sales PIC: <strong style="color:var(--color-ink);" x-text="modalData?.toko_info?.nama_sales || '-'"></strong></span>
                                    <template x-if="modalData?.toko_info?.alamat_lengkap">
                                        <span class="hidden sm:inline">&bull; <span x-text="modalData?.toko_info?.alamat_lengkap"></span></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <!-- Loading state header -->
                        <template x-if="isLoading">
                            <div class="space-y-1.5">
                                <div class="skeleton-box" style="width:170px;height:16px;"></div>
                                <div class="skeleton-box" style="width:110px;height:12px;"></div>
                            </div>
                        </template>
                    </div>
                </div>
                
                <button type="button" @click="closeModal()" class="btn btn-ghost btn-sm flex-shrink-0" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;" aria-label="Tutup">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <!-- LAYER 2: MODAL BODY (SCROLLABLE & TOUCH FRIENDLY) -->
            <div class="detail-modal-body custom-scrollbar">
                
                <!-- SKELETON SHIMMER LOADING STATE (ANTI-GEPENG) -->
                <div x-show="isLoading" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Left skeleton -->
                        <div class="space-y-5">
                            <div class="modal-detail-card">
                                <div class="modal-detail-card-header">
                                    <div class="skeleton-box" style="width:140px;height:14px;"></div>
                                    <div class="skeleton-box" style="width:65px;height:14px;"></div>
                                </div>
                                <div style="padding:14px;">
                                    <div class="skeleton-box" style="width:100%;height:150px;border-radius:10px;"></div>
                                </div>
                            </div>
                            <div class="modal-detail-card">
                                <div class="modal-detail-card-header">
                                    <div class="skeleton-box" style="width:140px;height:14px;"></div>
                                    <div class="skeleton-box" style="width:70px;height:14px;"></div>
                                </div>
                                <div style="padding:14px;display:flex;flex-direction:column;gap:10px;">
                                    <div class="skeleton-box" style="width:100%;height:34px;"></div>
                                    <div class="skeleton-box" style="width:100%;height:34px;"></div>
                                    <div class="skeleton-box" style="width:100%;height:34px;"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Right skeleton -->
                        <div class="space-y-5">
                            <div class="modal-detail-card">
                                <div class="modal-detail-card-header">
                                    <div class="skeleton-box" style="width:130px;height:14px;"></div>
                                    <div class="skeleton-box" style="width:50px;height:14px;"></div>
                                </div>
                                <div style="padding:14px;display:flex;flex-direction:column;gap:10px;">
                                    <div class="skeleton-box" style="width:100%;height:56px;border-radius:10px;"></div>
                                    <div class="skeleton-box" style="width:100%;height:56px;border-radius:10px;"></div>
                                </div>
                            </div>
                            <div class="modal-detail-card">
                                <div class="modal-detail-card-header">
                                    <div class="skeleton-box" style="width:150px;height:14px;"></div>
                                    <div class="skeleton-box" style="width:70px;height:14px;"></div>
                                </div>
                                <div style="padding:14px;display:flex;flex-direction:column;gap:10px;">
                                    <div class="skeleton-box" style="width:100%;height:34px;"></div>
                                    <div class="skeleton-box" style="width:100%;height:34px;"></div>
                                    <div class="skeleton-box" style="width:100%;height:34px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LOADED DATA STATE -->
                <div x-show="!isLoading && modalData" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        
                        <!-- KOLOM KIRI: TREN OMZET & TOP 5 ITEM -->
                        <div class="space-y-5">
                            <!-- Card Tren Omzet Toko -->
                            <div class="modal-detail-card">
                                <div class="modal-detail-card-header">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="trending-up" class="w-4 h-4 text-violet-500"></i>
                                        <h4 style="font-size:12.5px;font-weight:800;color:var(--color-ink);margin:0;">Tren Omzet Kunjungan</h4>
                                    </div>
                                    <span class="badge badge-mono" style="font-size:10px;">Periode Ini</span>
                                </div>
                                <div style="padding:14px 16px;">
                                    <div class="relative w-full" style="height:165px;">
                                        <canvas id="modalLineChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Top 5 Produk Terlaris -->
                            <div class="modal-detail-card">
                                <div class="modal-detail-card-header">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="award" class="w-4 h-4 text-amber-500"></i>
                                        <h4 style="font-size:12.5px;font-weight:800;color:var(--color-ink);margin:0;">Top 5 Produk Terlaris</h4>
                                    </div>
                                    <span class="badge badge-warning" style="font-size:10px;">Best Seller</span>
                                </div>
                                <div class="modal-product-list">
                                    <template x-for="(item, idx) in modalData?.top_items || []" :key="idx">
                                        <div class="modal-product-item">
                                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                <span class="badge badge-mono flex-shrink-0" style="font-size:10.5px;padding:2px 7px;font-weight:700;" x-text="`#${idx+1}`"></span>
                                                <span class="truncate" style="font-size:12.5px;font-weight:700;color:var(--color-ink);" x-text="item.nama_item"></span>
                                            </div>
                                            <div class="text-right flex-shrink-0 pl-3">
                                                <div class="font-mono" style="font-size:12.5px;font-weight:800;color:#10b981;" x-text="formatRupiah(item.total_omzet)"></div>
                                                <div style="font-size:10.5px;color:var(--color-ink-mute);" x-text="`${item.total_qty} pcs terjual`"></div>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!modalData?.top_items?.length">
                                        <div class="p-6 text-center text-xs" style="color:var(--color-ink-mute);">
                                            Belum ada data barang laku terjual pada periode ini.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- KOLOM KANAN: TAGIHAN TERTUNGGAK & STOK RAK -->
                        <div class="space-y-5">
                            
                            <!-- Card Tagihan Belum Lunas -->
                            <div class="modal-detail-card">
                                <div class="modal-detail-card-header">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="alert-circle" class="w-4 h-4 text-rose-500"></i>
                                        <h4 style="font-size:12.5px;font-weight:800;color:var(--color-ink);margin:0;">Tagihan Belum Lunas</h4>
                                    </div>
                                    <span class="badge badge-danger font-mono" style="font-size:10px;" x-text="`${modalData?.tagihan?.length || 0} Faktur`"></span>
                                </div>
                                <div class="modal-invoice-list custom-scrollbar">
                                    <template x-for="(t, idx) in modalData?.tagihan || []" :key="idx">
                                        <div class="modal-invoice-item">
                                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                                                <span class="badge badge-mono font-mono" style="font-size:11px;font-weight:800;letter-spacing:0.01em;word-break:break-all;" x-text="t.nomor_nota"></span>
                                                <span class="badge badge-danger" style="font-size:10px;padding:2px 8px;flex-shrink:0;white-space:nowrap;">Belum Lunas</span>
                                            </div>
                                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:6px;border-top:1px solid var(--color-hairline-cool);">
                                                <span style="font-size:11px;color:var(--color-ink-mute);display:inline-flex;align-items:center;gap:4px;flex-shrink:0;">
                                                    <i data-lucide="calendar" style="width:12px;height:12px;flex-shrink:0;"></i>
                                                    <span x-text="formatDate(t.tanggal_pesanan)"></span>
                                                </span>
                                                <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                                    <span style="font-size:10.5px;color:var(--color-ink-mute);">Sisa:</span>
                                                    <span class="font-mono font-bold text-rose-600 dark:text-rose-400" style="font-size:12.5px;" x-text="formatRupiah(t.sisa_tagihan)"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!modalData?.tagihan?.length">
                                        <div class="p-6 text-center text-xs space-y-1.5" style="color:var(--color-ink-mute);">
                                            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-500 mx-auto"></i>
                                            <div class="font-bold text-emerald-600 dark:text-emerald-400" style="font-size:12.5px;">Semua Tagihan Lunas!</div>
                                            <div>Tidak ada piutang konsinyasi tertunggak untuk toko ini.</div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Card Kondisi Fisik Rak Terakhir -->
                            <div class="modal-detail-card">
                                <div class="modal-detail-card-header">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="boxes" class="w-4 h-4 text-sky-500"></i>
                                        <h4 style="font-size:12.5px;font-weight:800;color:var(--color-ink);margin:0;">Kondisi Fisik Rak Terakhir</h4>
                                    </div>
                                    <span class="badge badge-mono" style="font-size:10px;" x-text="modalData?.last_visit ? formatDate(modalData.last_visit.tanggal_kunjungan) : 'Belum Ada'"></span>
                                </div>

                                <div class="modal-table-wrap custom-scrollbar">
                                    <table class="data-table" style="font-size:12px;width:100%;min-width:340px;margin:0;">
                                        <thead>
                                            <tr>
                                                <th style="padding:9px 14px;">Produk Snack</th>
                                                <th class="cell-center" style="padding:9px 14px;width:75px;">Sisa Rak</th>
                                                <th class="cell-center" style="padding:9px 14px;width:75px;">Rtr Bagus</th>
                                                <th class="cell-center" style="padding:9px 14px;width:75px;">Rtr Rusak</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(r, idx) in modalData?.stok_rak || []" :key="idx">
                                                <tr>
                                                    <td style="padding:9px 14px;font-weight:600;color:var(--color-ink);" x-text="r.nama_item"></td>
                                                    <td class="cell-center font-mono font-bold" style="padding:9px 14px;color:var(--color-ink);" x-text="`${r.sisa_fisik_di_rak} pcs`"></td>
                                                    <td class="cell-center font-mono font-bold text-amber-600 dark:text-amber-400" style="padding:9px 14px;" x-text="r.retur_bagus"></td>
                                                    <td class="cell-center font-mono font-bold text-rose-600 dark:text-rose-400" style="padding:9px 14px;" x-text="r.retur_rusak"></td>
                                                </tr>
                                            </template>
                                            <template x-if="!modalData?.stok_rak?.length">
                                                <tr>
                                                    <td colspan="4" class="p-6 text-center text-xs" style="color:var(--color-ink-mute);">
                                                        Belum ada catatan rincian opname fisik di rak.
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <!-- LAYER 3: MODAL FOOTER (STICKY) -->
            <div class="detail-modal-footer">
                <div class="text-xs hidden sm:block" style="color:var(--color-ink-mute);">
                    Data tersinkronisasi otomatis dengan modul Opname &amp; Faktur Konsinyasi.
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <a href="<?= Router::url('/consignment/tagihan') ?>" class="btn btn-secondary btn-sm flex-1 sm:flex-initial justify-center" style="font-weight:700;font-size:11.5px;">
                        <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                        <span>Kelola Tagihan</span>
                    </a>
                    <button type="button" @click="closeModal()" class="btn btn-primary btn-sm flex-1 sm:flex-initial justify-center" style="font-weight:700;font-size:11.5px;">
                        <span>Tutup</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
    </template>

</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>
