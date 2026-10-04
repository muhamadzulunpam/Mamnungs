<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import EmptyState from '../../Components/UI/EmptyState.vue';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    weekly: { type: Array, default: () => [] },
    topProducts: { type: Array, default: () => [] },
    recentOrders: { type: Array, default: () => [] },
});

// ===== HELPERS =====
const formatRupiah = (n) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(n || 0);

const ringkas = (n) => {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1).replace('.0', '') + ' jt';
    if (n >= 1_000) return Math.round(n / 1_000) + ' rb';
    return String(n);
};

const hari = (d) =>
    new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'short' });

const tanggalLengkap = (d) =>
    new Date(d + 'T00:00:00').toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
    });

// ===== STATUS MAP =====
const statusMap = {
    PAID:      { label: 'Lunas',       class: 'status-completed' },
    PENDING:   { label: 'Menunggu',    class: 'status-pending' },
    CANCELLED: { label: 'Dibatalkan',  class: 'status-cancelled' },
    EXPIRED:   { label: 'Kedaluwarsa', class: 'status-expired' },
};

const getStatus = (s) =>
    statusMap[s] || { label: s || '-', class: 'status-default' };

// ===== TODAY =====
const today = computed(() =>
    new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    })
);

// ===== CHART =====
const maxWeekly = computed(() =>
    Math.max(...(props.weekly || []).map((d) => d.value || 0), 1)
);

const barHeight = (v) => {
    if (!v) return 2; // minimum bar height
    return Math.max((v / maxWeekly.value) * 100, 2);
};

const yLabels = computed(() => {
    const max = maxWeekly.value;
    return [1, 0.75, 0.5, 0.25, 0].map((f) => ringkas(Math.round(max * f)));
});

const hasWeeklyData = computed(() =>
    (props.weekly || []).some((d) => (d.value || 0) > 0)
);

// ===== TOP PRODUCTS =====
const colors = ['#84bd33', '#f4a82a', '#22c55e', '#ec4899', '#0ea5e9'];
const maxSold = computed(() =>
    Math.max(...(props.topProducts || []).map((p) => p.sold || 0), 1)
);

// ===== STATS SAFE ACCESS =====
const s = computed(() => props.stats || {});
</script>

<template>
    <DashboardLayout>
        <!-- ============ HEADER ============ -->
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Dashboard</h1>
                <p class="dash-subtitle">
                    Selamat datang kembali! Berikut ringkasan bisnis Anda.
                    <span class="date-chip">
                        <i class="bi bi-calendar3"></i>
                        {{ today }}
                    </span>
                </p>
            </div>
            <div class="dash-actions">
                <Link href="/kasir" class="btn-primary-custom">
                    <i class="bi bi-plus-lg"></i>
                    Pesanan Baru
                </Link>
            </div>
        </div>

        <!-- ============ STAT CARDS ============ -->
        <div class="stats-grid">
            <!-- Pendapatan -->
            <div class="stat-card">
                <div class="stat-icon" style="background:#eef7e0; color:#6ba324">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Pendapatan</div>
                    <div class="stat-value">{{ formatRupiah(s.revenue) }}</div>
                </div>
                <div
                    v-if="s.revenueChange !== null && s.revenueChange !== undefined"
                    :class="['stat-badge', s.revenueChange >= 0 ? 'up' : 'down']"
                >
                    <i :class="s.revenueChange >= 0 ? 'bi bi-arrow-up-short' : 'bi bi-arrow-down-short'"></i>
                    {{ Math.abs(s.revenueChange) }}%
                </div>
            </div>

            <!-- Transaksi -->
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff4e0; color:#f4a82a">
                    <i class="bi bi-bag-check"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Transaksi</div>
                    <div class="stat-value">{{ s.orders || 0 }}</div>
                </div>
                <div
                    v-if="s.ordersChange !== null && s.ordersChange !== undefined"
                    :class="['stat-badge', s.ordersChange >= 0 ? 'up' : 'down']"
                >
                    <i :class="s.ordersChange >= 0 ? 'bi bi-arrow-up-short' : 'bi bi-arrow-down-short'"></i>
                    {{ Math.abs(s.ordersChange) }}%
                </div>
            </div>

            <!-- Penjualan Hari Ini -->
            <div class="stat-card">
                <div class="stat-icon" style="background:#e0f2fe; color:#0ea5e9">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Hari Ini</div>
                    <div class="stat-value">{{ formatRupiah(s.todayRevenue) }}</div>
                </div>
            </div>

            <!-- Menu Tersedia -->
            <div class="stat-card">
                <div class="stat-icon" style="background:#fce7f3; color:#ec4899">
                    <i class="bi bi-cup-straw"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Menu</div>
                    <div class="stat-value">{{ s.products || 0 }}</div>
                </div>
            </div>
        </div>

        <!-- ============ MAIN GRID ============ -->
        <div class="main-grid">
            <!-- Chart mingguan -->
            <div class="panel chart-panel">
                <div class="panel-header">
                    <div>
                        <h3 class="panel-title">Penjualan Mingguan</h3>
                        <p class="panel-sub">7 hari terakhir (transaksi lunas)</p>
                    </div>
                </div>

                <div v-if="hasWeeklyData" class="chart-area">
                    <div class="chart-y">
                        <span v-for="(l, i) in yLabels" :key="i">{{ l }}</span>
                    </div>
                    <div class="chart-bars">
                        <div
                            v-for="d in weekly"
                            :key="d.date"
                            class="bar-wrap"
                        >
                            <div class="bar-tooltip">{{ formatRupiah(d.value) }}</div>
                            <div
                                class="bar"
                                :style="{ height: barHeight(d.value) + '%' }"
                                :class="{ 'bar-empty': !d.value }"
                            ></div>
                            <span class="bar-label">
                                {{ hari(d.date) }}
                                <small>{{ tanggalLengkap(d.date) }}</small>
                            </span>
                        </div>
                    </div>
                </div>

                <div v-else class="chart-empty">
                    <div class="chart-empty-icon">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                    <div class="chart-empty-title">Belum Ada Penjualan</div>
                    <div class="chart-empty-text">
                        Data 7 hari terakhir akan muncul setelah ada transaksi lunas.
                    </div>
                </div>
            </div>

            <!-- Menu terlaris -->
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h3 class="panel-title">Menu Terlaris</h3>
                        <p class="panel-sub">Bulan ini</p>
                    </div>
                </div>

                <div v-if="topProducts.length > 0" class="top-products">
                    <div
                        v-for="(p, i) in topProducts"
                        :key="p.name"
                        class="product-row"
                    >
                        <div
                            class="product-rank"
                            :style="{
                                background: colors[i % 5] + '20',
                                color: colors[i % 5],
                            }"
                        >
                            {{ i + 1 }}
                        </div>
                        <div class="product-info">
                            <div class="product-name">{{ p.name }}</div>
                            <div class="product-sold">
                                {{ p.sold }} terjual · {{ formatRupiah(p.revenue) }}
                            </div>
                            <div class="progress-track">
                                <div
                                    class="progress-fill"
                                    :style="{
                                        width: (p.sold / maxSold) * 100 + '%',
                                        background: colors[i % 5],
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState
                    v-else
                    icon="bi-cup-straw"
                    title="Belum Ada Penjualan"
                    message="Menu terlaris akan muncul setelah ada transaksi bulan ini."
                    :compact="true"
                />
            </div>
        </div>

        <!-- ============ TRANSAKSI TERBARU ============ -->
        <div class="panel table-panel">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Transaksi Terbaru</h3>
                    <p class="panel-sub">5 transaksi terakhir</p>
                </div>
                <Link href="/transaksi" class="link-btn">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </Link>
            </div>

            <div v-if="recentOrders.length > 0" class="table-wrap">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Kasir</th>
                            <th>Menu</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="o in recentOrders" :key="o.id">
                            <td>
                                <span class="order-id">{{ o.invoice }}</span>
                            </td>
                            <td>
                                <div class="customer-cell">
                                    <div class="avatar">
                                        {{ (o.kasir || '?').charAt(0).toUpperCase() }}
                                    </div>
                                    <span>{{ o.kasir || '-' }}</span>
                                </div>
                            </td>
                            <td class="text-muted-cell">{{ o.item || '-' }}</td>
                            <td>
                                <strong>{{ formatRupiah(o.total) }}</strong>
                            </td>
                            <td>
                                <span :class="['status-pill', getStatus(o.status).class]">
                                    <span class="status-dot"></span>
                                    {{ getStatus(o.status).label }}
                                </span>
                            </td>
                            <td class="text-muted-cell">{{ o.time }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                icon="bi-receipt-cutoff"
                title="Belum Ada Transaksi"
                message="Mulai transaksi pertama Anda dengan membuka halaman Kasir."
                action-text="Buka Kasir"
                action-href="/kasir"
                :compact="true"
            />
        </div>
    </DashboardLayout>
</template>

<style scoped>
/* ============ HEADER ============ */
.dash-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.dash-title {
    font-size: 28px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.8px;
    margin: 0 0 6px;
}

.dash-subtitle {
    font-size: 14px;
    color: #6b7a5e;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.date-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: #f0f8e5;
    color: #558a1a;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 600;
}

.date-chip i { font-size: 11px; }

.dash-actions { display: flex; gap: 10px; }

.btn-primary-custom {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 20px;
    background: linear-gradient(135deg, #84bd33 0%, #6ba324 100%);
    border: none;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 700;
    font-family: inherit;
    color: #ffffff;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.25s;
    box-shadow: 0 8px 20px -8px rgba(107, 163, 36, 0.6);
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -8px rgba(107, 163, 36, 0.7);
    color: #ffffff;
}

.btn-primary-custom i { font-size: 14px; }

/* ============ STAT CARDS (COMPACT) ============ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 14px;
    transition: all 0.2s;
    position: relative;
    overflow: hidden;
    min-width: 0;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -12px rgba(60, 100, 20, 0.15);
    border-color: #d8e8bf;
}

.stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}

.stat-body {
    flex: 1;
    min-width: 0;
}

.stat-label {
    font-size: 11px;
    font-weight: 700;
    color: #6b7a5e;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stat-value {
    font-size: 15px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.3px;
    font-family: ui-monospace, monospace;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stat-badge {
    display: inline-flex;
    align-items: center;
    gap: 1px;
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 700;
    flex-shrink: 0;
}

.stat-badge.up { background: #dcfce7; color: #15803d; }
.stat-badge.down { background: #fee2e2; color: #b91c1c; }

@media (max-width: 900px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 480px) {
    .stats-grid { grid-template-columns: 1fr; }
    .stat-value { font-size: 14px; }
}
/* ============ PANEL ============ */
.panel {
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 18px;
    padding: 22px;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    gap: 12px;
    flex-wrap: wrap;
}

.panel-title {
    font-size: 16px;
    font-weight: 700;
    color: #14210a;
    margin: 0 0 2px;
}

.panel-sub { font-size: 12.5px; color: #94a3b8; margin: 0; }

.link-btn {
    background: none;
    border: none;
    color: #6ba324;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0;
    font-family: inherit;
    text-decoration: none;
    transition: gap 0.2s;
}

.link-btn:hover { gap: 10px; }

/* ============ MAIN GRID ============ */
.main-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 16px;
    margin-bottom: 24px;
}

@media (max-width: 900px) {
    .main-grid { grid-template-columns: 1fr; }
}

/* ============ CHART ============ */
.chart-panel { display: flex; flex-direction: column; }

.chart-area {
    display: flex;
    gap: 12px;
    height: 260px;
    padding-top: 12px;
    flex: 1;
}

.chart-y {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    font-size: 11px;
    color: #94a3b8;
    font-weight: 500;
    padding-bottom: 32px;
    width: 36px;
    text-align: right;
    flex-shrink: 0;
}

.chart-bars {
    flex: 1;
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    gap: 8px;
    border-bottom: 1.5px dashed #e2e8d5;
    position: relative;
}

.bar-wrap {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    height: 100%;
    position: relative;
}

.bar {
    width: 100%;
    max-width: 42px;
    background: linear-gradient(180deg, #a3d65c 0%, #84bd33 60%, #6ba324 100%);
    border-radius: 10px 10px 0 0;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    min-height: 4px;
}

.bar-empty {
    background: #e8efdb;
    opacity: 0.5;
}

.bar:hover {
    filter: brightness(1.08);
    transform: scaleY(1.02);
    transform-origin: bottom;
}

.bar-tooltip {
    position: absolute;
    top: -4px;
    background: #14210a;
    color: #fff;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    opacity: 0;
    transform: translateY(4px);
    transition: all 0.2s;
    pointer-events: none;
    white-space: nowrap;
    z-index: 5;
}

.bar-wrap:hover .bar-tooltip {
    opacity: 1;
    transform: translateY(-8px);
}

.bar-label {
    font-size: 11.5px;
    color: #6b7a5e;
    font-weight: 700;
    margin-top: 10px;
    position: absolute;
    bottom: -30px;
    display: flex;
    flex-direction: column;
    align-items: center;
    line-height: 1.1;
}

.bar-label small {
    font-size: 9.5px;
    color: #a3b190;
    font-weight: 500;
    margin-top: 1px;
}

/* Chart empty */
.chart-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    min-height: 260px;
}

.chart-empty-icon {
    width: 68px;
    height: 68px;
    border-radius: 20px;
    background: linear-gradient(135deg, #f0f8e5 0%, #e8efdb 100%);
    color: #84bd33;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    margin-bottom: 14px;
}

.chart-empty-title {
    font-size: 15px;
    font-weight: 800;
    color: #14210a;
    margin-bottom: 4px;
}

.chart-empty-text {
    font-size: 12.5px;
    color: #6b7a5e;
    line-height: 1.5;
    max-width: 280px;
}

/* ============ TOP PRODUCTS ============ */
.top-products {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.product-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.product-rank {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 14px;
    flex-shrink: 0;
}

.product-info { flex: 1; min-width: 0; }

.product-name {
    font-size: 13.5px;
    font-weight: 700;
    color: #14210a;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-sold {
    font-size: 11.5px;
    color: #94a3b8;
    margin-bottom: 6px;
}

.progress-track {
    height: 6px;
    background: #f0f4e8;
    border-radius: 100px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 100px;
    transition: width 0.6s ease;
}

/* ============ TABLE ============ */
.table-panel { padding-bottom: 8px; }

.table-wrap {
    overflow-x: auto;
    margin: 0 -22px;
    padding: 0 22px;
}

.custom-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}

.custom-table th {
    text-align: left;
    padding: 10px 12px;
    font-size: 11.5px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    border-bottom: 1.5px solid #eef2e6;
    white-space: nowrap;
}

.custom-table td {
    padding: 14px 12px;
    border-bottom: 1px solid #f4f7ee;
    color: #14210a;
    vertical-align: middle;
}

.custom-table tbody tr { transition: background 0.15s; }
.custom-table tbody tr:hover { background: #fafcf6; }
.custom-table tbody tr:last-child td { border-bottom: none; }

.order-id {
    font-family: ui-monospace, monospace;
    font-size: 12.5px;
    font-weight: 700;
    color: #6ba324;
    background: #f0f8e5;
    padding: 4px 8px;
    border-radius: 6px;
    white-space: nowrap;
}

.customer-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #84bd33, #558a1a);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    flex-shrink: 0;
}

.text-muted-cell { color: #6b7a5e; font-size: 13px; }

/* ============ STATUS PILL ============ */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 11px;
    border-radius: 100px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.status-completed { background: #dcfce7; color: #15803d; }
.status-pending   { background: #fef3c7; color: #b45309; }
.status-cancelled { background: #fee2e2; color: #b91c1c; }
.status-expired   { background: #f1f5f9; color: #64748b; }
.status-default   { background: #f4f7ee; color: #6b7a5e; }

/* ============ RESPONSIVE ============ */
@media (max-width: 640px) {
    .dash-title { font-size: 22px; }
    .dash-actions { width: 100%; }
    .btn-primary-custom { width: 100%; justify-content: center; }
    .stats-grid { grid-template-columns: 1fr; }
    .panel { padding: 18px; }
    .table-wrap { margin: 0 -18px; padding: 0 18px; }
    .chart-area { height: 220px; }
    .chart-y { width: 30px; font-size: 10px; }
    .bar-label small { display: none; }
}
</style>