<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import DashboardLayout from '../../../Layouts/DashboardLayout.vue';
import EmptyState from '../../../Components/UI/EmptyState.vue';

const props = defineProps({
    filters: Object,
    summary: Object,
    byMethod: Array,
    daily: Array,
    topProducts: Array,
});

// ===== HELPERS =====
const rupiah = (n) =>
    'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0);

const tanggal = (d) =>
    new Date(d + 'T00:00:00').toLocaleDateString('id-ID', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });

const ringkasTanggal = (d) =>
    new Date(d + 'T00:00:00').toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
    });

// ===== FILTER =====
const from = ref(props.filters?.from ?? '');
const to = ref(props.filters?.to ?? '');

watch([from, to], () => {
    if (!from.value || !to.value || from.value > to.value) return;
    router.get(
        '/admin/reports',
        { from: from.value, to: to.value },
        { preserveState: true, replace: true }
    );
});

const fmt = (d) => d.toLocaleDateString('en-CA');

const preset = (jenis) => {
    const now = new Date();
    if (jenis === 'today') {
        from.value = to.value = fmt(now);
    }
    if (jenis === 'week') {
        const s = new Date();
        s.setDate(now.getDate() - 6);
        from.value = fmt(s);
        to.value = fmt(now);
    }
    if (jenis === 'month') {
        from.value = fmt(new Date(now.getFullYear(), now.getMonth(), 1));
        to.value = fmt(now);
    }
};

// Cek active preset
const isToday = computed(() => {
    const t = fmt(new Date());
    return from.value === t && to.value === t;
});

const isWeek = computed(() => {
    const now = new Date();
    const s = new Date();
    s.setDate(now.getDate() - 6);
    return from.value === fmt(s) && to.value === fmt(now);
});

const isMonth = computed(() => {
    const now = new Date();
    const start = fmt(new Date(now.getFullYear(), now.getMonth(), 1));
    return from.value === start && to.value === fmt(now);
});

const exportUrl = computed(
    () => `/admin/reports/export?from=${from.value}&to=${to.value}`
);

// ===== CHART / STATS =====
const maxDaily = computed(() =>
    Math.max(...(props.daily || []).map((d) => d.total || 0), 1)
);

const maxSold = computed(() =>
    Math.max(...(props.topProducts || []).map((p) => p.sold || 0), 1)
);

const methodShare = (m) =>
    props.summary.total > 0
        ? Math.round((m.total / props.summary.total) * 100)
        : 0;

// ===== METHOD COLORS =====
const methodColors = {
    CASH:     { bg: '#eef7e0', color: '#6ba324', icon: 'bi-cash-coin' },
    QRIS:     { bg: '#dbeafe', color: '#1d4ed8', icon: 'bi-qr-code' },
    DEBIT:    { bg: '#fef3c7', color: '#d97706', icon: 'bi-credit-card' },
    TRANSFER: { bg: '#fce7f3', color: '#ec4899', icon: 'bi-bank' },
};

const getMethodStyle = (m) => {
    const key = String(m).toUpperCase();
    return methodColors[key] || { bg: '#f4f7ee', color: '#6b7a5e', icon: 'bi-wallet2' };
};

const methodLabel = (m) => {
    const map = {
        CASH: 'Tunai',
        QRIS: 'QRIS',
        DEBIT: 'Debit',
        TRANSFER: 'Transfer',
    };
    return map[String(m).toUpperCase()] || m;
};

// ===== TOP PRODUCT COLORS =====
const productColors = ['#84bd33', '#f4a82a', '#22c55e', '#ec4899', '#0ea5e9'];
</script>

<template>
    <DashboardLayout>
        <!-- ============ HEADER ============ -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Laporan Penjualan</h1>
                <p class="page-subtitle">
                    Hanya transaksi <strong>lunas</strong> yang dihitung dalam laporan ini.
                </p>
            </div>
            <a :href="exportUrl" class="btn-export">
                <i class="bi bi-download"></i>
                <span>Export CSV</span>
            </a>
        </div>

        <!-- ============ TOOLBAR FILTER ============ -->
        <div class="toolbar">
            <div class="date-group">
                <div class="date-field">
                    <label>Dari</label>
                    <div class="date-input-wrap">
                        <i class="bi bi-calendar3"></i>
                        <input v-model="from" type="date" />
                    </div>
                </div>
                <div class="date-arrow">
                    <i class="bi bi-arrow-right"></i>
                </div>
                <div class="date-field">
                    <label>Sampai</label>
                    <div class="date-input-wrap">
                        <i class="bi bi-calendar3"></i>
                        <input v-model="to" type="date" />
                    </div>
                </div>
            </div>

            <div class="preset-group">
                <button
                    :class="['preset-btn', { active: isToday }]"
                    @click="preset('today')"
                >
                    <i class="bi bi-calendar-day"></i>
                    Hari Ini
                </button>
                <button
                    :class="['preset-btn', { active: isWeek }]"
                    @click="preset('week')"
                >
                    <i class="bi bi-calendar-week"></i>
                    7 Hari
                </button>
                <button
                    :class="['preset-btn', { active: isMonth }]"
                    @click="preset('month')"
                >
                    <i class="bi bi-calendar-month"></i>
                    Bulan Ini
                </button>
            </div>
        </div>

        <!-- ============ STAT CARDS ============ -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eef7e0; color:#6ba324">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Total Penjualan</div>
                    <div class="stat-value">{{ rupiah(summary.total) }}</div>
                    <div class="stat-sub">periode terpilih</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:#e0f2fe; color:#0ea5e9">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Transaksi</div>
                    <div class="stat-value">{{ summary.count }}</div>
                    <div class="stat-sub">transaksi lunas</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:#fce7f3; color:#ec4899">
                    <i class="bi bi-calculator"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Rata-rata</div>
                    <div class="stat-value">{{ rupiah(summary.average) }}</div>
                    <div class="stat-sub">per transaksi</div>
                </div>
            </div>
        </div>

        <!-- ============ MAIN GRID ============ -->
        <div class="main-grid">
            <!-- Metode Pembayaran -->
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h3 class="panel-title">
                            <i class="bi bi-credit-card-2-front"></i>
                            Metode Pembayaran
                        </h3>
                        <p class="panel-sub">Distribusi per metode</p>
                    </div>
                </div>

                <div v-if="byMethod.length > 0" class="method-list">
                    <div
                        v-for="m in byMethod"
                        :key="m.method"
                        class="method-row"
                    >
                        <div
                            class="method-icon"
                            :style="{
                                background: getMethodStyle(m.method).bg,
                                color: getMethodStyle(m.method).color,
                            }"
                        >
                            <i :class="['bi', getMethodStyle(m.method).icon]"></i>
                        </div>

                        <div class="method-info">
                            <div class="method-top">
                                <span class="method-name">
                                    {{ methodLabel(m.method) }}
                                </span>
                                <span class="method-share">
                                    {{ methodShare(m) }}%
                                </span>
                            </div>
                            <div class="method-bottom">
                                <span class="method-total">
                                    {{ rupiah(m.total) }}
                                </span>
                                <span class="method-count">
                                    {{ m.count }} trx
                                </span>
                            </div>
                            <div class="progress-track">
                                <div
                                    class="progress-fill"
                                    :style="{
                                        width: methodShare(m) + '%',
                                        background: getMethodStyle(m.method).color,
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState
                    v-else
                    icon="bi-credit-card"
                    title="Belum Ada Data"
                    message="Belum ada transaksi lunas dalam periode ini."
                    :compact="true"
                />
            </div>

            <!-- Produk Terlaris -->
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h3 class="panel-title">
                            <i class="bi bi-trophy"></i>
                            Produk Terlaris
                        </h3>
                        <p class="panel-sub">Top 5 menu di periode ini</p>
                    </div>
                </div>

                <div v-if="topProducts.length > 0" class="product-list">
                    <div
                        v-for="(p, i) in topProducts"
                        :key="p.name"
                        class="product-row"
                    >
                        <div
                            class="product-rank"
                            :style="{
                                background: productColors[i % 5] + '20',
                                color: productColors[i % 5],
                            }"
                        >
                            {{ i + 1 }}
                        </div>
                        <div class="product-info">
                            <div class="product-name">{{ p.name }}</div>
                            <div class="product-detail">
                                {{ p.sold }} terjual · {{ rupiah(p.revenue) }}
                            </div>
                            <div class="progress-track">
                                <div
                                    class="progress-fill"
                                    :style="{
                                        width: (p.sold / maxSold) * 100 + '%',
                                        background: productColors[i % 5],
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
                    message="Produk terlaris akan muncul setelah ada transaksi lunas."
                    :compact="true"
                />
            </div>
        </div>

        <!-- ============ DAILY TABLE ============ -->
        <div class="panel table-panel">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">
                        <i class="bi bi-calendar3"></i>
                        Penjualan Per Hari
                    </h3>
                    <p class="panel-sub">
                        {{ daily.length }} hari tercatat dalam periode ini
                    </p>
                </div>
            </div>

            <div v-if="daily.length > 0" class="table-wrap">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Transaksi</th>
                            <th>Total</th>
                            <th class="text-end">Proporsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in daily" :key="d.date">
                            <td>
                                <div class="date-cell">
                                    <i class="bi bi-calendar2-check"></i>
                                    <span>{{ tanggal(d.date) }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="trx-badge">
                                    {{ d.count }} trx
                                </span>
                            </td>
                            <td>
                                <span class="total-cell">{{ rupiah(d.total) }}</span>
                            </td>
                            <td>
                                <div class="bar-cell">
                                    <div class="bar-track">
                                        <div
                                            class="bar-fill"
                                            :style="{
                                                width: (d.total / maxDaily) * 100 + '%',
                                            }"
                                        ></div>
                                    </div>
                                    <span class="bar-percent">
                                        {{ Math.round((d.total / summary.total) * 100) }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="row-total">
                            <td><strong>TOTAL</strong></td>
                            <td><strong>{{ summary.count }} trx</strong></td>
                            <td><strong>{{ rupiah(summary.total) }}</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <EmptyState
                v-else
                icon="bi-calendar-x"
                title="Tidak Ada Transaksi"
                message="Belum ada transaksi lunas di rentang tanggal yang dipilih. Coba ubah filter tanggal."
                :compact="true"
            />
        </div>
    </DashboardLayout>
</template>

<style scoped>
/* ============ HEADER ============ */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.page-title {
    font-size: 26px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.8px;
    margin: 0 0 4px;
}

.page-subtitle {
    font-size: 13.5px;
    color: #6b7a5e;
    margin: 0;
}

.page-subtitle strong { color: #14210a; font-weight: 700; }

.btn-export {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 20px;
    background: linear-gradient(135deg, #84bd33 0%, #6ba324 100%);
    color: #ffffff;
    text-decoration: none;
    border: none;
    border-radius: 12px;
    font-family: inherit;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s;
    box-shadow: 0 8px 20px -8px rgba(107, 163, 36, 0.6);
}

.btn-export:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -8px rgba(107, 163, 36, 0.7);
    color: #ffffff;
}

.btn-export i { font-size: 14px; }

/* ============ TOOLBAR ============ */
.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 14px 18px;
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    box-shadow: 0 4px 20px -12px rgba(20, 33, 10, 0.08);
}

.date-group {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.date-field label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #6b7a5e;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 5px;
}

.date-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    background: #f8fbf3;
    border: 1.5px solid #e2e8d5;
    border-radius: 10px;
    padding: 0 12px;
    transition: all 0.2s;
}

.date-input-wrap:focus-within {
    border-color: #84bd33;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(132, 189, 51, 0.12);
}

.date-input-wrap > i {
    color: #a3b190;
    font-size: 13px;
    pointer-events: none;
    margin-right: 8px;
}

.date-input-wrap:focus-within > i { color: #6ba324; }

.date-input-wrap input {
    border: none;
    background: transparent;
    padding: 9px 0;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 600;
    color: #14210a;
    outline: none;
    cursor: pointer;
    width: 120px;
}

.date-input-wrap input::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 0.6;
}

.date-arrow {
    padding-bottom: 10px;
    color: #cbd5b5;
    font-size: 14px;
}

/* Preset group */
.preset-group {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.preset-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: 10px;
    background: #f8fbf3;
    border: 1.5px solid #e2e8d5;
    color: #4a5a3d;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s;
    white-space: nowrap;
}

.preset-btn:hover {
    border-color: #84bd33;
    color: #6ba324;
    background: #f0f8e5;
}

.preset-btn.active {
    background: linear-gradient(135deg, #84bd33, #6ba324);
    border-color: #6ba324;
    color: #ffffff;
    box-shadow: 0 4px 12px -4px rgba(107, 163, 36, 0.5);
}

.preset-btn i { font-size: 13px; }

/* ============ STAT CARDS (COMPACT) ============ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
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
    min-width: 0;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -12px rgba(60, 100, 20, 0.15);
    border-color: #d8e8bf;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.stat-body { flex: 1; min-width: 0; }

.stat-label {
    font-size: 10.5px;
    font-weight: 700;
    color: #6b7a5e;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stat-value {
    font-size: 16px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.3px;
    font-family: ui-monospace, monospace;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stat-sub {
    font-size: 10.5px;
    color: #94a3b8;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ============ MAIN GRID ============ */
.main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

@media (max-width: 900px) {
    .main-grid { grid-template-columns: 1fr; }
}

/* ============ PANEL ============ */
.panel {
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 18px;
    padding: 20px;
    box-shadow: 0 4px 20px -12px rgba(20, 33, 10, 0.08);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.panel-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 800;
    color: #14210a;
    margin: 0 0 2px;
    letter-spacing: -0.2px;
}

.panel-title i { color: #6ba324; font-size: 15px; }

.panel-sub { font-size: 12px; color: #94a3b8; margin: 0; }

/* ============ METHOD LIST ============ */
.method-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.method-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.method-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.method-info { flex: 1; min-width: 0; }

.method-top {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 3px;
}

.method-name {
    font-size: 13.5px;
    font-weight: 700;
    color: #14210a;
}

.method-share {
    font-size: 13px;
    font-weight: 800;
    color: #6ba324;
    font-family: ui-monospace, monospace;
}

.method-bottom {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 7px;
}

.method-total {
    font-size: 12.5px;
    color: #4a5a3d;
    font-weight: 600;
    font-family: ui-monospace, monospace;
}

.method-count {
    font-size: 11px;
    color: #94a3b8;
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
    transition: width 0.5s ease;
}

/* ============ PRODUCT LIST ============ */
.product-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
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

.product-detail {
    font-size: 11.5px;
    color: #94a3b8;
    margin-bottom: 6px;
}

/* ============ TABLE ============ */
.table-panel { padding-bottom: 8px; }

.table-wrap {
    overflow-x: auto;
    margin: 0 -20px;
    padding: 0 20px;
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

.custom-table th.text-end { text-align: right; }

.custom-table td {
    padding: 12px;
    border-bottom: 1px solid #f4f7ee;
    color: #14210a;
    vertical-align: middle;
}

.custom-table tbody tr { transition: background 0.15s; }
.custom-table tbody tr:hover { background: #fafcf6; }

.date-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.date-cell i { color: #a3b190; font-size: 13px; }

.trx-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    background: #f0f8e5;
    color: #558a1a;
    border-radius: 100px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}

.total-cell {
    font-family: ui-monospace, monospace;
    font-weight: 800;
    font-size: 13.5px;
    color: #14210a;
    white-space: nowrap;
}

/* Bar in table */
.bar-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: flex-end;
}

.bar-track {
    flex: 1;
    max-width: 180px;
    height: 8px;
    background: #f0f4e8;
    border-radius: 100px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #a3d65c 0%, #84bd33 100%);
    border-radius: 100px;
    transition: width 0.5s ease;
}

.bar-percent {
    font-size: 11.5px;
    font-weight: 700;
    color: #6b7a5e;
    font-family: ui-monospace, monospace;
    min-width: 34px;
    text-align: right;
}

/* Table footer */
.row-total {
    background: #fafcf6;
    border-top: 2px solid #eef2e6;
}

.row-total td {
    padding: 14px 12px;
    border-bottom: none;
    font-size: 13.5px;
}

.row-total td strong {
    color: #14210a;
    font-weight: 800;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 768px) {
    .toolbar { padding: 12px 14px; }
    .date-group { flex: 1; width: 100%; }
    .date-field { flex: 1; }
    .date-input-wrap { width: 100%; }
    .date-input-wrap input { width: 100%; }
    .date-arrow { display: none; }
    .preset-group { width: 100%; }
    .preset-btn { flex: 1; justify-content: center; }
    .stats-grid { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
    .page-title { font-size: 22px; }
    .btn-export { width: 100%; justify-content: center; }
    .panel { padding: 16px; }
    .table-wrap { margin: 0 -16px; padding: 0 16px; }
    .custom-table th,
    .custom-table td { padding: 10px 8px; font-size: 12.5px; }
    .bar-track { max-width: 80px; }
    .bar-percent { min-width: 30px; font-size: 10.5px; }
}
</style>