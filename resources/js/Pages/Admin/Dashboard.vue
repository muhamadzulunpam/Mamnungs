<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { computed } from 'vue';

// ===== DUMMY DATA — ganti dengan props dari controller =====
const stats = {
    revenue: 12450000,
    orders: 342,
    customers: 128,
    products: 24,
};

const revenueChange = 12.5;   // % vs bulan lalu
const ordersChange = 8.2;
const customersChange = -3.1; // contoh negatif
const productsChange = 0;

const recentOrders = [
    { id: 'ORD-1204', customer: 'Siti Aminah',   item: 'Es Teler Special',      total: 45000,  status: 'completed', time: '5 mnt lalu' },
    { id: 'ORD-1203', customer: 'Budi Santoso',  item: 'Es Teler Durian x2',    total: 90000,  status: 'processing', time: '12 mnt lalu' },
    { id: 'ORD-1202', customer: 'Maya Lestari',  item: 'Es Teler Kelapa',       total: 35000,  status: 'pending',    time: '28 mnt lalu' },
    { id: 'ORD-1201', customer: 'Andi Pratama',  item: 'Es Teler Mangga x3',    total: 105000, status: 'completed', time: '1 jam lalu' },
    { id: 'ORD-1200', customer: 'Rina Wijaya',   item: 'Es Teler Alpukat',      total: 40000,  status: 'cancelled',  time: '2 jam lalu' },
];

const topProducts = [
    { name: 'Es Teler Special',  sold: 128, revenue: 5760000, color: '#84bd33', percent: 100 },
    { name: 'Es Teler Durian',   sold: 86,  revenue: 4300000, color: '#f4a82a', percent: 67 },
    { name: 'Es Teler Kelapa',   sold: 64,  revenue: 2240000, color: '#22c55e', percent: 50 },
    { name: 'Es Teler Mangga',   sold: 42,  revenue: 1680000, color: '#ec4899', percent: 33 },
];

const weeklySales = [
    { day: 'Sen', value: 55 },
    { day: 'Sel', value: 72 },
    { day: 'Rab', value: 48 },
    { day: 'Kam', value: 85 },
    { day: 'Jum', value: 95 },
    { day: 'Sab', value: 100 },
    { day: 'Min', value: 78 },
];

// ===== HELPERS =====
const formatRupiah = (n) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n);

const statusMap = {
    completed:  { label: 'Selesai',   class: 'status-completed' },
    processing: { label: 'Diproses',  class: 'status-processing' },
    pending:    { label: 'Menunggu',  class: 'status-pending' },
    cancelled:  { label: 'Dibatalkan',class: 'status-cancelled' },
};

const today = computed(() =>
    new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
);
</script>

<template>
    <DashboardLayout>
        <!-- ============ HEADER ============ -->
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Dashboard</h1>
                <p class="dash-subtitle">
                    Selamat datang kembali! Berikut ringkasan bisnis Anda hari ini.
                    <span class="date-chip"><i class="bi bi-calendar3"></i> {{ today }}</span>
                </p>
            </div>
            <div class="dash-actions">
                <button class="btn-ghost"><i class="bi bi-download"></i> Export</button>
                <button class="btn-primary-custom"><i class="bi bi-plus-lg"></i> Pesanan Baru</button>
            </div>
        </div>

        <!-- ============ STAT CARDS ============ -->
        <div class="stats-grid">
            <!-- Revenue -->
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon" style="background:#eef7e0; color:#6ba324">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div :class="['stat-badge', revenueChange >= 0 ? 'up' : 'down']">
                        <i :class="revenueChange >= 0 ? 'bi bi-arrow-up-short' : 'bi bi-arrow-down-short'"></i>
                        {{ Math.abs(revenueChange) }}%
                    </div>
                </div>
                <div class="stat-label">Pendapatan Bulan Ini</div>
                <div class="stat-value">{{ formatRupiah(stats.revenue) }}</div>
                <div class="stat-footer">vs bulan lalu</div>
            </div>

            <!-- Orders -->
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon" style="background:#fff4e0; color:#f4a82a">
                        <i class="bi bi-bag-check"></i>
                    </div>
                    <div :class="['stat-badge', ordersChange >= 0 ? 'up' : 'down']">
                        <i :class="ordersChange >= 0 ? 'bi bi-arrow-up-short' : 'bi bi-arrow-down-short'"></i>
                        {{ Math.abs(ordersChange) }}%
                    </div>
                </div>
                <div class="stat-label">Total Pesanan</div>
                <div class="stat-value">{{ stats.orders }}</div>
                <div class="stat-footer">vs bulan lalu</div>
            </div>

            <!-- Customers -->
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon" style="background:#e0f2fe; color:#0ea5e9">
                        <i class="bi bi-people"></i>
                    </div>
                    <div :class="['stat-badge', customersChange >= 0 ? 'up' : 'down']">
                        <i :class="customersChange >= 0 ? 'bi bi-arrow-up-short' : 'bi bi-arrow-down-short'"></i>
                        {{ Math.abs(customersChange) }}%
                    </div>
                </div>
                <div class="stat-label">Pelanggan Aktif</div>
                <div class="stat-value">{{ stats.customers }}</div>
                <div class="stat-footer">vs bulan lalu</div>
            </div>

            <!-- Products -->
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon" style="background:#fce7f3; color:#ec4899">
                        <i class="bi bi-cup-straw"></i>
                    </div>
                    <div class="stat-badge neutral">
                        <i class="bi bi-dash"></i> 0%
                    </div>
                </div>
                <div class="stat-label">Menu Es Teler</div>
                <div class="stat-value">{{ stats.products }}</div>
                <div class="stat-footer">tidak ada perubahan</div>
            </div>
        </div>

        <!-- ============ MAIN GRID ============ -->
        <div class="main-grid">
            <!-- Weekly Sales Chart -->
            <div class="panel chart-panel">
                <div class="panel-header">
                    <div>
                        <h3 class="panel-title">Penjualan Mingguan</h3>
                        <p class="panel-sub">7 hari terakhir</p>
                    </div>
                    <div class="panel-tabs">
                        <button class="tab active">Minggu</button>
                        <button class="tab">Bulan</button>
                        <button class="tab">Tahun</button>
                    </div>
                </div>

                <div class="chart-area">
                    <div class="chart-y">
                        <span>100</span><span>75</span><span>50</span><span>25</span><span>0</span>
                    </div>
                    <div class="chart-bars">
                        <div
                            v-for="(d, i) in weeklySales"
                            :key="i"
                            class="bar-wrap"
                        >
                            <div class="bar-tooltip">{{ d.value }}%</div>
                            <div class="bar" :style="{ height: d.value + '%' }"></div>
                            <span class="bar-label">{{ d.day }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h3 class="panel-title">Menu Terlaris</h3>
                        <p class="panel-sub">Bulan ini</p>
                    </div>
                    <button class="link-btn">Lihat Semua <i class="bi bi-arrow-right"></i></button>
                </div>

                <div class="top-products">
                    <div v-for="(p, i) in topProducts" :key="i" class="product-row">
                        <div class="product-rank" :style="{ background: p.color + '20', color: p.color }">
                            {{ i + 1 }}
                        </div>
                        <div class="product-info">
                            <div class="product-name">{{ p.name }}</div>
                            <div class="product-sold">{{ p.sold }} terjual · {{ formatRupiah(p.revenue) }}</div>
                            <div class="progress-track">
                                <div class="progress-fill" :style="{ width: p.percent + '%', background: p.color }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ RECENT ORDERS TABLE ============ -->
        <div class="panel table-panel">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Pesanan Terbaru</h3>
                    <p class="panel-sub">Update real-time</p>
                </div>
                <button class="link-btn">Lihat Semua <i class="bi bi-arrow-right"></i></button>
            </div>

            <div class="table-wrap">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Pelanggan</th>
                            <th>Menu</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="o in recentOrders" :key="o.id">
                            <td><span class="order-id">{{ o.id }}</span></td>
                            <td>
                                <div class="customer-cell">
                                    <div class="avatar">{{ o.customer.charAt(0) }}</div>
                                    <span>{{ o.customer }}</span>
                                </div>
                            </td>
                            <td class="text-muted-cell">{{ o.item }}</td>
                            <td><strong>{{ formatRupiah(o.total) }}</strong></td>
                            <td>
                                <span :class="['status-pill', statusMap[o.status].class]">
                                    <span class="status-dot"></span>
                                    {{ statusMap[o.status].label }}
                                </span>
                            </td>
                            <td class="text-muted-cell">{{ o.time }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
    margin-bottom: 28px;
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

.dash-actions { display: flex; gap: 10px; }

.btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: #ffffff;
    border: 1.5px solid #e2e8d5;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 600;
    color: #4a5a3d;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-ghost:hover { border-color: #84bd33; color: #6ba324; background: #f8fbf3; }

.btn-primary-custom {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #84bd33 0%, #6ba324 100%);
    border: none;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 700;
    color: #ffffff;
    cursor: pointer;
    transition: all 0.25s;
    box-shadow: 0 8px 20px -8px rgba(107, 163, 36, 0.6);
}
.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -8px rgba(107, 163, 36, 0.7);
}

/* ============ STAT CARDS ============ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 18px;
    padding: 20px;
    transition: all 0.25s;
    position: relative;
    overflow: hidden;
}

.stat-card::after {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 80px; height: 80px;
    background: radial-gradient(circle, rgba(132, 189, 51, 0.06), transparent 70%);
    border-radius: 50%;
    transform: translate(30%, -30%);
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 32px -16px rgba(60, 100, 20, 0.18);
    border-color: #d8e8bf;
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.stat-badge {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 4px 8px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
}

.stat-badge.up { background: #dcfce7; color: #15803d; }
.stat-badge.down { background: #fee2e2; color: #b91c1c; }
.stat-badge.neutral { background: #f1f5f9; color: #64748b; }

.stat-label {
    font-size: 12.5px;
    font-weight: 600;
    color: #6b7a5e;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 6px;
}

.stat-value {
    font-size: 24px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.5px;
    margin-bottom: 4px;
}

.stat-footer { font-size: 12px; color: #94a3b8; }

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

.panel-tabs { display: flex; gap: 4px; background: #f8fbf3; padding: 4px; border-radius: 10px; }

.tab {
    padding: 6px 14px;
    border: none;
    background: transparent;
    font-size: 12.5px;
    font-weight: 600;
    color: #6b7a5e;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}
.tab.active { background: #ffffff; color: #6ba324; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }

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
    transition: gap 0.2s;
}
.link-btn:hover { gap: 10px; }

/* ============ CHART ============ */
.chart-panel { flex: 1.4; }

.chart-area {
    display: flex;
    gap: 12px;
    height: 260px;
    padding-top: 12px;
}

.chart-y {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    font-size: 11px;
    color: #94a3b8;
    font-weight: 500;
    padding-bottom: 24px;
    width: 28px;
    text-align: right;
}

.chart-bars {
    flex: 1;
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    gap: 8px;
    border-bottom: 1.5px dashed #e2e8d5;
    padding-bottom: 0;
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
    min-height: 6px;
}

.bar:hover { filter: brightness(1.08); transform: scaleY(1.02); transform-origin: bottom; }

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
}

.bar-wrap:hover .bar-tooltip { opacity: 1; transform: translateY(-8px); }

.bar-label {
    font-size: 11.5px;
    color: #6b7a5e;
    font-weight: 600;
    margin-top: 10px;
    position: absolute;
    bottom: -24px;
}

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

/* ============ TOP PRODUCTS ============ */
.top-products { display: flex; flex-direction: column; gap: 16px; }

.product-row { display: flex; align-items: center; gap: 12px; }

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

.table-wrap { overflow-x: auto; margin: 0 -22px; padding: 0 22px; }

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
    font-family: 'JetBrains Mono', monospace, ui-monospace;
    font-size: 12.5px;
    font-weight: 700;
    color: #6ba324;
    background: #f0f8e5;
    padding: 4px 8px;
    border-radius: 6px;
}

.customer-cell { display: flex; align-items: center; gap: 10px; }

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
.status-processing { background: #dbeafe; color: #1d4ed8; }
.status-pending { background: #fef3c7; color: #b45309; }
.status-cancelled { background: #fee2e2; color: #b91c1c; }
</style>