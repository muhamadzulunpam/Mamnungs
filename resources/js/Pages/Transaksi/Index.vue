<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import EmptyState from '../../Components/UI/EmptyState.vue';

const props = defineProps({
    orders: Object,
    filters: Object,
    summary: Object,
});

// ===== HELPERS =====
const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0);

const jam = (d) =>
    new Date(d).toLocaleTimeString('id-ID', {
        timeZone: 'Asia/Jakarta',
        hour: '2-digit',
        minute: '2-digit',
    });

const tanggalPanjang = (d) => {
    if (!d) return '';
    return new Date(d).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

// ===== DATE FILTER =====
const date = ref(props.filters?.date ?? new Date().toISOString().slice(0, 10));

const loadOrders = (v) => {
    router.get('/transaksi', { date: v }, { preserveState: true, replace: true });
};

watch(date, loadOrders);

// Quick date shortcuts
const setDate = (offsetDays) => {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    date.value = d.toISOString().slice(0, 10);
};

const isToday = computed(() => {
    const today = new Date().toISOString().slice(0, 10);
    return date.value === today;
});

const isYesterday = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() - 1);
    return date.value === d.toISOString().slice(0, 10);
});

// ===== STATUS CONFIG =====
const statusConfig = {
    PAID:      { label: 'Lunas',      class: 'status-paid',      icon: 'bi-check-circle-fill' },
    PENDING:   { label: 'Menunggu',   class: 'status-pending',   icon: 'bi-clock-fill' },
    CANCELLED: { label: 'Dibatalkan', class: 'status-cancelled', icon: 'bi-x-circle-fill' },
    EXPIRED:   { label: 'Kedaluwarsa',class: 'status-expired',   icon: 'bi-dash-circle-fill' },
};

const getStatus = (s) =>
    statusConfig[s] || { label: s, class: 'status-default', icon: 'bi-circle-fill' };

// ===== METODE PEMBAYARAN =====
const metodeConfig = {
    cash:  { label: 'Tunai',    icon: 'bi-cash-coin' },
    qris:  { label: 'QRIS',     icon: 'bi-qr-code' },
    debit: { label: 'Debit',    icon: 'bi-credit-card' },
    transfer: { label: 'Transfer', icon: 'bi-bank' },
};

const getMetode = (m) => {
    if (!m) return { label: '-', icon: 'bi-dash' };
    return metodeConfig[String(m).toLowerCase()] || { label: m, icon: 'bi-wallet2' };
};

// ===== MINI STATS =====
const stats = computed(() => ({
    count: props.summary?.count ?? 0,
    total: props.summary?.total ?? 0,
    average: props.summary?.count > 0
        ? Math.round(props.summary.total / props.summary.count)
        : 0,
}));
</script>

<template>
    <DashboardLayout>
        <!-- ============ HEADER ============ -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Riwayat Transaksi</h1>
                <p class="page-subtitle">
                    Lihat dan kelola riwayat penjualan berdasarkan tanggal.
                </p>
            </div>

            <!-- Date filter group -->
            <div class="date-filter-group">
                <div class="date-quick">
                    <button
                        :class="['date-quick-btn', { active: isToday }]"
                        @click="setDate(0)"
                    >
                        Hari Ini
                    </button>
                    <button
                        :class="['date-quick-btn', { active: isYesterday }]"
                        @click="setDate(-1)"
                    >
                        Kemarin
                    </button>
                </div>

                <div class="date-picker">
                    <i class="bi bi-calendar3"></i>
                    <input v-model="date" type="date" class="date-input" />
                </div>
            </div>
        </div>

        <!-- ============ DATE BANNER ============ -->
        <div class="date-banner">
            <div class="date-banner-icon">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="date-banner-text">
                <div class="date-banner-label">Menampilkan data untuk</div>
                <div class="date-banner-value">{{ tanggalPanjang(date) }}</div>
            </div>
        </div>

        <!-- ============ STAT CARDS ============ -->
        <div class="stats-grid">
            <!-- Transaksi Lunas -->
            <div class="stat-card">
                <div class="stat-icon" style="background:#eef7e0; color:#6ba324">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Transaksi Lunas</div>
                    <div class="stat-value">{{ stats.count }}</div>
                    <div class="stat-sub">transaksi berhasil</div>
                </div>
            </div>

            <!-- Total Penjualan -->
            <div class="stat-card">
                <div class="stat-icon" style="background:#dcfce7; color:#15803d">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Total Penjualan</div>
                    <div class="stat-value">{{ rupiah(stats.total) }}</div>
                    <div class="stat-sub">dari {{ stats.count }} transaksi</div>
                </div>
            </div>

            <!-- Rata-rata -->
            <div class="stat-card">
                <div class="stat-icon" style="background:#dbeafe; color:#1d4ed8">
                    <i class="bi bi-calculator"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Rata-rata</div>
                    <div class="stat-value">{{ rupiah(stats.average) }}</div>
                    <div class="stat-sub">per transaksi</div>
                </div>
            </div>
        </div>

        <!-- ============ PANEL ============ -->
        <div class="panel">
            <!-- Empty state -->
            <EmptyState
                v-if="orders.data.length === 0"
                icon="bi-receipt-cutoff"
                title="Belum Ada Transaksi"
                message="Belum ada transaksi tercatat di tanggal ini. Pilih tanggal lain atau mulai transaksi baru di kasir."
                action-text="Buka Kasir"
                action-href="/kasir"
            />

            <!-- Table -->
            <div v-else class="table-wrap">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Jam</th>
                            <th>Kasir</th>
                            <th>Metode</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="o in orders.data" :key="o.id">
                            <!-- Invoice -->
                            <td>
                                <Link
                                    :href="`/transaksi/${o.id}/struk`"
                                    class="invoice-link"
                                >
                                    <i class="bi bi-receipt"></i>
                                    {{ o.invoice_number }}
                                </Link>
                            </td>

                            <!-- Jam -->
                            <td>
                                <div class="time-cell">
                                    <i class="bi bi-clock"></i>
                                    <span>{{ jam(o.created_at) }}</span>
                                </div>
                            </td>

                            <!-- Kasir -->
                            <td>
                                <div class="cashier-cell">
                                    <div class="cashier-avatar">
                                        {{ (o.user?.name || 'U').charAt(0).toUpperCase() }}
                                    </div>
                                    <span>{{ o.user?.name || '-' }}</span>
                                </div>
                            </td>

                            <!-- Metode -->
                            <td>
                                <span class="method-badge">
                                    <i :class="['bi', getMetode(o.payment_method).icon]"></i>
                                    {{ getMetode(o.payment_method).label }}
                                </span>
                            </td>

                            <!-- Total -->
                            <td>
                                <span class="total-cell">{{ rupiah(o.total) }}</span>
                            </td>

                            <!-- Status -->
                            <td>
                                <span :class="['status-pill', getStatus(o.status).class]">
                                    <i :class="['bi', getStatus(o.status).icon]"></i>
                                    {{ getStatus(o.status).label }}
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="text-end">
                                <Link
                                    :href="`/transaksi/${o.id}/struk`"
                                    class="btn-action btn-receipt"
                                >
                                    <i class="bi bi-printer"></i>
                                    <span>Struk</span>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="orders.last_page > 1" class="pagination-wrap">
                <div class="pagination-info">
                    Menampilkan
                    <strong>{{ orders.from }}</strong>–<strong>{{ orders.to }}</strong>
                    dari <strong>{{ orders.total }}</strong> transaksi
                </div>

                <nav class="pagination-nav">
                    <ul class="pagination">
                        <li
                            v-for="(l, idx) in orders.links"
                            :key="idx"
                            :class="['page-item', { active: l.active, disabled: !l.url }]"
                        >
                            <Link
                                v-if="l.url"
                                :href="l.url"
                                class="page-link"
                                preserve-scroll
                                v-html="l.label"
                            />
                            <span v-else class="page-link" v-html="l.label"></span>
                        </li>
                    </ul>
                </nav>
            </div>
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

/* Date filter group */
.date-filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.date-quick {
    display: inline-flex;
    gap: 4px;
    padding: 4px;
    background: #ffffff;
    border: 1.5px solid #e2e8d5;
    border-radius: 12px;
}

.date-quick-btn {
    padding: 8px 14px;
    border: none;
    background: transparent;
    color: #4a5a3d;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 700;
    border-radius: 9px;
    cursor: pointer;
    transition: all 0.18s;
}

.date-quick-btn:hover {
    color: #6ba324;
    background: #f8fbf3;
}

.date-quick-btn.active {
    background: linear-gradient(135deg, #84bd33, #6ba324);
    color: #ffffff;
    box-shadow: 0 4px 12px -4px rgba(107, 163, 36, 0.5);
}

.date-picker {
    position: relative;
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 1.5px solid #e2e8d5;
    border-radius: 12px;
    padding: 0 14px;
    transition: all 0.2s;
}

.date-picker:focus-within {
    border-color: #84bd33;
    box-shadow: 0 0 0 4px rgba(132, 189, 51, 0.12);
}

.date-picker > i {
    color: #a3b190;
    font-size: 14px;
    pointer-events: none;
    margin-right: 8px;
}

.date-picker:focus-within > i { color: #6ba324; }

.date-input {
    border: none;
    background: transparent;
    padding: 10px 0;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    color: #14210a;
    outline: none;
    cursor: pointer;
}

.date-input::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.15s;
}
.date-input::-webkit-calendar-picker-indicator:hover { opacity: 1; }

/* ============ DATE BANNER ============ */
.date-banner {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    background: linear-gradient(135deg, #f0f8e5 0%, #e8efdb 100%);
    border: 1px solid #d8e8bf;
    border-radius: 14px;
    margin-bottom: 20px;
}

.date-banner-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #ffffff;
    color: #6ba324;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px -4px rgba(107, 163, 36, 0.25);
}

.date-banner-label {
    font-size: 11px;
    font-weight: 700;
    color: #6b7a5e;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.date-banner-value {
    font-size: 15px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.3px;
    text-transform: capitalize;
}

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
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.stat-body {
    flex: 1;
    min-width: 0;
}

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

/* ============ RESPONSIVE ============ */
@media (max-width: 768px) {
    .stats-grid { grid-template-columns: 1fr; }
}

/* ============ PANEL ============ */
.panel {
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 18px;
    padding: 8px;
    box-shadow: 0 4px 20px -12px rgba(20, 33, 10, 0.08);
}

/* ============ TABLE ============ */
.table-wrap { overflow-x: auto; border-radius: 12px; }

.custom-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13.5px;
}

.custom-table thead th {
    text-align: left;
    padding: 14px 16px;
    font-size: 11.5px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #fafcf6;
    border-bottom: 1.5px solid #eef2e6;
    white-space: nowrap;
}

.custom-table thead th:first-child { border-radius: 12px 0 0 0; }
.custom-table thead th:last-child  { border-radius: 0 12px 0 0; }

.custom-table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid #f4f7ee;
    color: #14210a;
    vertical-align: middle;
}

.custom-table tbody tr { transition: background 0.15s; }
.custom-table tbody tr:hover { background: #fafcf6; }
.custom-table tbody tr:last-child td { border-bottom: none; }

/* Invoice link */
.invoice-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    background: #f0f8e5;
    color: #558a1a;
    border-radius: 8px;
    font-family: ui-monospace, monospace;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
    transition: all 0.15s;
    white-space: nowrap;
}

.invoice-link:hover {
    background: #84bd33;
    color: #ffffff;
    transform: translateY(-1px);
}

.invoice-link i { font-size: 11px; }

/* Time cell */
.time-cell {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #4a5a3d;
    font-weight: 600;
}

.time-cell i { color: #a3b190; font-size: 12px; }

/* Cashier cell */
.cashier-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
}

.cashier-avatar {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: linear-gradient(135deg, #84bd33, #558a1a);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    flex-shrink: 0;
}

/* Method badge */
.method-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    background: #f4f7ee;
    color: #4a5a3d;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}

.method-badge i { font-size: 11px; color: #6b7a5e; }

/* Total cell */
.total-cell {
    font-family: ui-monospace, monospace;
    font-size: 13.5px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.3px;
    white-space: nowrap;
}

/* Status pill */
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

.status-pill i { font-size: 10px; }

.status-paid {
    background: #dcfce7;
    color: #15803d;
}

.status-pending {
    background: #fef3c7;
    color: #b45309;
}

.status-cancelled {
    background: #fee2e2;
    color: #b91c1c;
}

.status-expired {
    background: #f1f5f9;
    color: #64748b;
}

.status-default {
    background: #f4f7ee;
    color: #6b7a5e;
}

/* Action buttons */
.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 9px;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    border: 1.5px solid transparent;
    transition: all 0.18s;
    white-space: nowrap;
}

.btn-action i { font-size: 13px; }

.btn-receipt {
    background: #f8fbf3;
    border-color: #e2e8d5;
    color: #4a5a3d;
}

.btn-receipt:hover {
    background: #f0f8e5;
    border-color: #84bd33;
    color: #6ba324;
    transform: translateY(-1px);
}

/* ============ PAGINATION ============ */
.pagination-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border-top: 1px solid #f4f7ee;
    flex-wrap: wrap;
}

.pagination-info {
    font-size: 12.5px;
    color: #6b7a5e;
}

.pagination-info strong { color: #14210a; font-weight: 700; }

.pagination-nav { display: flex; }

.pagination {
    display: flex;
    gap: 4px;
    list-style: none;
    padding: 0;
    margin: 0;
}

.page-item .page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 9px;
    border: 1.5px solid #e2e8d5;
    background: #ffffff;
    color: #4a5a3d;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.15s;
    cursor: pointer;
}

.page-item .page-link:hover {
    border-color: #84bd33;
    color: #6ba324;
    background: #f0f8e5;
}

.page-item.active .page-link {
    background: linear-gradient(135deg, #84bd33, #6ba324);
    border-color: #6ba324;
    color: #ffffff;
    box-shadow: 0 4px 12px -4px rgba(107, 163, 36, 0.5);
}

.page-item.disabled .page-link {
    background: #fafcf6;
    color: #cbd5b5;
    border-color: #eef2e6;
    cursor: not-allowed;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 640px) {
    .page-title { font-size: 22px; }
    .date-filter-group { width: 100%; }
    .date-quick { flex: 1; }
    .date-quick-btn { flex: 1; }
    .date-picker { flex: 1; }
    .date-input { width: 100%; }
    .btn-action span { display: none; }
    .btn-action { padding: 8px 10px; }
    .custom-table thead th,
    .custom-table tbody td { padding: 12px 10px; }
    .pagination-wrap { flex-direction: column; align-items: stretch; }
    .pagination-info { text-align: center; }
    .pagination { justify-content: center; }
}
</style>