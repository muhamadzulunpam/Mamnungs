<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import DashboardLayout from '../../../Layouts/DashboardLayout.vue';
import EmptyState from '../../../Components/UI/EmptyState.vue';

const props = defineProps({
    logs: Object,
    users: Array,
    filters: Object,
});

// ===== FILTER STATE =====
const f = ref({
    search: props.filters?.search ?? '',
    user_id: props.filters?.user_id ?? '',
    action: props.filters?.action ?? '',
    from: props.filters?.from ?? '',
    to: props.filters?.to ?? '',
});

let timer;
watch(
    f,
    (v) => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const q = Object.fromEntries(
                Object.entries(v).filter(([, val]) => val)
            );
            router.get('/admin/activity-logs', q, {
                preserveState: true,
                replace: true,
            });
        }, 350);
    },
    { deep: true }
);

const hasFilter = computed(() =>
    Object.values(f.value).some((v) => v !== '')
);

const reset = () => {
    f.value = { search: '', user_id: '', action: '', from: '', to: '' };
};

// ===== ACTION CONFIG =====
const actions = {
    login:             { label: 'Login',            icon: 'bi-box-arrow-in-right', color: '#15803d', bg: '#dcfce7' },
    logout:            { label: 'Logout',           icon: 'bi-box-arrow-right',    color: '#64748b', bg: '#f1f5f9' },
    login_failed:      { label: 'Login Gagal',      icon: 'bi-x-octagon',          color: '#b91c1c', bg: '#fee2e2' },
    forbidden:         { label: 'Akses Ditolak',    icon: 'bi-shield-x',           color: '#b91c1c', bg: '#fee2e2' },
    created:           { label: 'Tambah',           icon: 'bi-plus-circle',        color: '#1d4ed8', bg: '#dbeafe' },
    updated:           { label: 'Ubah',             icon: 'bi-pencil',             color: '#b45309', bg: '#fef3c7' },
    deleted:           { label: 'Hapus',            icon: 'bi-trash',              color: '#b91c1c', bg: '#fee2e2' },
    checkout:          { label: 'Transaksi',        icon: 'bi-cart-check',         color: '#15803d', bg: '#dcfce7' },
    payment_paid:      { label: 'QRIS Lunas',       icon: 'bi-check-circle',       color: '#15803d', bg: '#dcfce7' },
    payment_expired:   { label: 'QRIS Kedaluwarsa', icon: 'bi-clock-history',      color: '#64748b', bg: '#f1f5f9' },
    payment_cancelled: { label: 'Bayar Batal',      icon: 'bi-x-circle',           color: '#b45309', bg: '#fef3c7' },
    export:            { label: 'Export',           icon: 'bi-download',           color: '#0e7490', bg: '#cffafe' },
};

const getAction = (action) =>
    actions[action] || {
        label: action,
        icon: 'bi-circle',
        color: '#64748b',
        bg: '#f1f5f9',
    };

// ===== TIME HELPERS =====
const waktu = (d) =>
    new Date(d).toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });

const waktuRingkas = (d) =>
    new Date(d).toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });

const timeAgo = (d) => {
    const diff = (Date.now() - new Date(d).getTime()) / 1000;
    if (diff < 60) return 'baru saja';
    if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
    if (diff < 604800) return `${Math.floor(diff / 86400)} hari lalu`;
    return null;
};

// ===== INITIALS =====
const initials = (name) =>
    (name || '?')
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

// ===== USER AVATAR COLORS =====
const userColors = [
    { bg: '#eef7e0', color: '#6ba324' },
    { bg: '#dbeafe', color: '#1d4ed8' },
    { bg: '#fce7f3', color: '#ec4899' },
    { bg: '#fef3c7', color: '#d97706' },
    { bg: '#e0f2fe', color: '#0ea5e9' },
    { bg: '#ede9fe', color: '#7c3aed' },
];

const userAvatarStyle = (userId) => userColors[(userId || 0) % userColors.length];

// ===== EXPANDABLE ROW =====
const open = ref(null);
const toggle = (id) => (open.value = open.value === id ? null : id);

const val = (v) =>
    v === null || v === undefined || v === '' ? '(kosong)' : String(v);

// ===== STATS =====
const stats = computed(() => {
    const list = props.logs?.data ?? [];
    const uniqueUsers = new Set(list.map((l) => l.user_name).filter(Boolean))
        .size;
    return {
        total: props.logs?.total ?? 0,
        uniqueUsers,
        onPage: list.length,
    };
});
</script>

<template>
    <DashboardLayout>
        <!-- ============ HEADER ============ -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Log Aktivitas</h1>
                <p class="page-subtitle">
                    Catatan <strong>siapa melakukan apa</strong> di sistem Mamnungs.
                    Data ini hanya bisa dibaca.
                </p>
            </div>
            <div class="readonly-badge">
                <i class="bi bi-eye-fill"></i>
                Read-only
            </div>
        </div>

        <!-- ============ TOOLBAR FILTER ============ -->
        <div class="toolbar">
            <!-- Search -->
            <div class="toolbar-search">
                <i class="bi bi-search"></i>
                <input
                    v-model="f.search"
                    type="text"
                    placeholder="Cari deskripsi aktivitas..."
                    autocomplete="off"
                />
                <button
                    v-if="f.search"
                    class="toolbar-clear"
                    @click="f.search = ''"
                    title="Hapus pencarian"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- User filter -->
            <div class="toolbar-select">
                <i class="bi bi-person"></i>
                <select v-model="f.user_id">
                    <option value="">Semua Pengguna</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">
                        {{ u.name }}
                    </option>
                </select>
                <i class="bi bi-chevron-down select-caret"></i>
            </div>

            <!-- Action filter -->
            <div class="toolbar-select">
                <i class="bi bi-lightning"></i>
                <select v-model="f.action">
                    <option value="">Semua Aksi</option>
                    <option v-for="(a, key) in actions" :key="key" :value="key">
                        {{ a.label }}
                    </option>
                </select>
                <i class="bi bi-chevron-down select-caret"></i>
            </div>

            <!-- Date range -->
            <div class="toolbar-daterange">
                <div class="date-field">
                    <label>Dari</label>
                    <input v-model="f.from" type="date" />
                </div>
                <i class="bi bi-arrow-right date-arrow"></i>
                <div class="date-field">
                    <label>Sampai</label>
                    <input v-model="f.to" type="date" />
                </div>
            </div>

            <!-- Reset -->
            <button v-if="hasFilter" class="toolbar-reset" @click="reset">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Reset</span>
            </button>
        </div>

        <!-- ============ RESULT INFO ============ -->
        <div v-if="logs.data.length > 0" class="result-info">
            <i class="bi bi-list-check"></i>
            Menampilkan <strong>{{ logs.from }}–{{ logs.to }}</strong>
            dari <strong>{{ logs.total }}</strong> aktivitas
            <template v-if="hasFilter">
                <span class="filter-active">
                    <i class="bi bi-funnel-fill"></i>
                    Filter aktif
                </span>
            </template>
        </div>

        <!-- ============ PANEL ============ -->
        <div class="panel">
            <!-- Empty state -->
            <EmptyState
                v-if="logs.data.length === 0"
                :icon="hasFilter ? 'bi-search' : 'bi-journal-text'"
                :title="hasFilter ? 'Tidak Ada Hasil' : 'Belum Ada Aktivitas'"
                :message="hasFilter
                    ? 'Tidak ada aktivitas yang cocok dengan filter Anda. Coba ubah kata kunci atau reset filter.'
                    : 'Log aktivitas akan muncul setelah ada pengguna yang melakukan aksi di sistem.'
                "
                :action-text="hasFilter ? 'Reset Filter' : ''"
                @action="reset"
            />

            <!-- Log list -->
            <div v-else class="log-list">
                <div
                    v-for="l in logs.data"
                    :key="l.id"
                    :class="['log-item', { 'is-open': open === l.id }]"
                >
                    <!-- Main row -->
                    <div class="log-row" @click="toggle(l.id)">
                        <!-- Time -->
                        <div class="log-time">
                            <div class="log-time-main">{{ waktuRingkas(l.created_at) }}</div>
                            <div v-if="timeAgo(l.created_at)" class="log-time-ago">
                                {{ timeAgo(l.created_at) }}
                            </div>
                        </div>

                        <!-- User -->
                        <div class="log-user">
                            <div
                                class="log-avatar"
                                :style="{
                                    background: userAvatarStyle(l.user_id).bg,
                                    color: userAvatarStyle(l.user_id).color,
                                }"
                            >
                                {{ initials(l.user_name) }}
                            </div>
                            <div class="log-user-name">
                                {{ l.user_name ?? 'Sistem' }}
                            </div>
                        </div>

                        <!-- Action badge -->
                        <div class="log-action">
                            <span
                                class="action-badge"
                                :style="{
                                    background: getAction(l.action).bg,
                                    color: getAction(l.action).color,
                                }"
                            >
                                <i :class="['bi', getAction(l.action).icon]"></i>
                                {{ getAction(l.action).label }}
                            </span>
                        </div>

                        <!-- Description -->
                        <div class="log-description">
                            {{ l.description }}
                        </div>

                        <!-- IP -->
                        <div class="log-ip">
                            <i class="bi bi-globe2"></i>
                            {{ l.ip_address }}
                        </div>

                        <!-- Expand toggle -->
                        <button class="log-toggle" :class="{ 'is-open': open === l.id }">
                            <i :class="open === l.id ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                        </button>
                    </div>

                    <!-- Detail (expandable) -->
                    <transition name="expand">
                        <div v-if="open === l.id" class="log-detail">
                            <!-- Diff table -->
                            <div v-if="l.properties?.new" class="detail-block">
                                <div class="detail-title">
                                    <i class="bi bi-file-diff"></i>
                                    Perubahan Data
                                </div>
                                <div class="diff-table-wrap">
                                    <table class="diff-table">
                                        <thead>
                                            <tr>
                                                <th>Kolom</th>
                                                <th>Sebelum</th>
                                                <th>Sesudah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(v, k) in l.properties.new"
                                                :key="k"
                                            >
                                                <td>
                                                    <span class="diff-key">{{ k }}</span>
                                                </td>
                                                <td>
                                                    <span class="diff-old">
                                                        {{ k in (l.properties.old ?? {})
                                                            ? val(l.properties.old[k])
                                                            : '−'
                                                        }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="diff-new">
                                                        {{ val(v) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Generic properties -->
                            <div
                                v-else-if="l.properties && Object.keys(l.properties).length"
                                class="detail-block"
                            >
                                <div class="detail-title">
                                    <i class="bi bi-info-circle"></i>
                                    Detail
                                </div>
                                <div class="detail-list">
                                    <div
                                        v-for="(v, k) in l.properties"
                                        :key="k"
                                        class="detail-item"
                                    >
                                        <span class="detail-key">{{ k }}</span>
                                        <span class="detail-value">{{ v }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Metadata -->
                            <div class="detail-meta">
                                <div class="meta-item">
                                    <i class="bi bi-clock"></i>
                                    <span>{{ waktu(l.created_at) }}</span>
                                </div>
                                <div v-if="l.user_agent" class="meta-item">
                                    <i class="bi bi-laptop"></i>
                                    <span class="meta-ua">{{ l.user_agent }}</span>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="pagination-wrap">
                <div class="pagination-info">
                    Menampilkan
                    <strong>{{ logs.from }}</strong>–<strong>{{ logs.to }}</strong>
                    dari <strong>{{ logs.total }}</strong> aktivitas
                </div>

                <nav class="pagination-nav">
                    <ul class="pagination">
                        <li
                            v-for="(l, idx) in logs.links"
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

.page-subtitle strong { color: #14210a; font-weight: 700; }

.readonly-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #f4f7ee;
    border: 1.5px solid #e2e8d5;
    border-radius: 100px;
    font-size: 11.5px;
    font-weight: 700;
    color: #6b7a5e;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.readonly-badge i { font-size: 11px; color: #94a3b8; }

/* ============ TOOLBAR ============ */
.toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 14px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.toolbar-search {
    position: relative;
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 200px;
}

.toolbar-search > i {
    position: absolute;
    left: 14px;
    color: #a3b190;
    font-size: 14px;
    pointer-events: none;
}

.toolbar-search input {
    width: 100%;
    padding: 10px 36px 10px 38px;
    border: 1.5px solid #e2e8d5;
    border-radius: 10px;
    background: #f8fbf3;
    font-size: 13.5px;
    font-family: inherit;
    color: #14210a;
    transition: all 0.2s;
    outline: none;
}

.toolbar-search input::placeholder { color: #a3b190; }

.toolbar-search input:focus {
    border-color: #84bd33;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(132, 189, 51, 0.12);
}

.toolbar-search:focus-within > i { color: #6ba324; }

.toolbar-clear {
    position: absolute;
    right: 10px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: none;
    background: #e8efdb;
    color: #6b7a5e;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    transition: all 0.15s;
}
.toolbar-clear:hover { background: #d8e8bf; color: #14210a; }

/* Select */
.toolbar-select {
    position: relative;
    display: flex;
    align-items: center;
    min-width: 150px;
}

.toolbar-select > i:first-child {
    position: absolute;
    left: 12px;
    color: #a3b190;
    font-size: 13px;
    pointer-events: none;
}

.toolbar-select .select-caret {
    position: absolute;
    right: 12px;
    color: #a3b190;
    font-size: 11px;
    pointer-events: none;
}

.toolbar-select select {
    width: 100%;
    padding: 10px 32px 10px 34px;
    border: 1.5px solid #e2e8d5;
    border-radius: 10px;
    background: #f8fbf3;
    font-size: 13px;
    font-family: inherit;
    color: #14210a;
    cursor: pointer;
    transition: all 0.2s;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
}

.toolbar-select select:focus {
    border-color: #84bd33;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(132, 189, 51, 0.12);
}

.toolbar-select:focus-within > i { color: #6ba324; }

/* Date range */
.toolbar-daterange {
    display: flex;
    align-items: flex-end;
    gap: 8px;
}

.date-field {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.date-field label {
    font-size: 10px;
    font-weight: 700;
    color: #6b7a5e;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding-left: 2px;
}

.date-field input {
    padding: 8px 10px;
    border: 1.5px solid #e2e8d5;
    border-radius: 10px;
    background: #f8fbf3;
    font-size: 12.5px;
    font-family: inherit;
    font-weight: 600;
    color: #14210a;
    outline: none;
    transition: all 0.2s;
    cursor: pointer;
}

.date-field input:focus {
    border-color: #84bd33;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(132, 189, 51, 0.12);
}

.date-arrow {
    padding-bottom: 10px;
    color: #cbd5b5;
    font-size: 12px;
}

/* Reset */
.toolbar-reset {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: 10px;
    background: #fef2f2;
    border: 1.5px solid #fecaca;
    color: #dc2626;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s;
}
.toolbar-reset:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #ffffff;
}

/* ============ RESULT INFO ============ */
.result-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: #f0f8e5;
    border: 1px solid #d8e8bf;
    border-radius: 10px;
    font-size: 12.5px;
    color: #4a5a3d;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.result-info i:first-child { color: #6ba324; }
.result-info strong { color: #14210a; font-weight: 700; }

.filter-active {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    background: #ffffff;
    border: 1px solid #d8e8bf;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 700;
    color: #6ba324;
    margin-left: auto;
}

.filter-active i { font-size: 9px; }

/* ============ PANEL ============ */
.panel {
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 18px;
    padding: 8px;
    box-shadow: 0 4px 20px -12px rgba(20, 33, 10, 0.08);
}

/* ============ LOG LIST ============ */
.log-list {
    display: flex;
    flex-direction: column;
}

.log-item {
    border-bottom: 1px solid #f4f7ee;
    transition: background 0.15s;
}

.log-item:last-child { border-bottom: none; }

.log-item.is-open {
    background: #fafcf6;
}

.log-item:hover:not(.is-open) {
    background: #fafcf6;
}

/* Main row */
.log-row {
    display: grid;
    grid-template-columns: 130px 180px 140px 1fr 130px 40px;
    gap: 12px;
    align-items: center;
    padding: 14px 16px;
    cursor: pointer;
    transition: background 0.15s;
}

/* Time */
.log-time-main {
    font-size: 12.5px;
    font-weight: 700;
    color: #14210a;
    font-family: ui-monospace, monospace;
    white-space: nowrap;
}

.log-time-ago {
    font-size: 10.5px;
    color: #94a3b8;
    margin-top: 2px;
}

/* User */
.log-user {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.log-avatar {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    flex-shrink: 0;
    letter-spacing: 0.3px;
}

.log-user-name {
    font-size: 13px;
    font-weight: 700;
    color: #14210a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Action */
.action-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.action-badge i { font-size: 10px; }

/* Description */
.log-description {
    font-size: 13px;
    color: #4a5a3d;
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* IP */
.log-ip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    color: #94a3b8;
    font-family: ui-monospace, monospace;
    white-space: nowrap;
}

.log-ip i { font-size: 10px; }

/* Toggle */
.log-toggle {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    border: 1.5px solid #e2e8d5;
    background: #ffffff;
    color: #6b7a5e;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    transition: all 0.2s;
    flex-shrink: 0;
}

.log-toggle:hover {
    border-color: #84bd33;
    color: #6ba324;
    background: #f0f8e5;
}

.log-toggle.is-open {
    background: linear-gradient(135deg, #84bd33, #6ba324);
    border-color: #6ba324;
    color: #ffffff;
}

/* ============ LOG DETAIL ============ */
.log-detail {
    padding: 0 16px 18px;
    border-top: 1px dashed #e2e8d5;
    margin-top: -1px;
}

.detail-block {
    padding: 16px 0 8px;
}

.detail-title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 800;
    color: #6ba324;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.detail-title i { font-size: 12px; }

/* Diff table */
.diff-table-wrap {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid #eef2e6;
}

.diff-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    background: #ffffff;
}

.diff-table thead th {
    text-align: left;
    padding: 10px 12px;
    font-size: 10.5px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    background: #fafcf6;
    border-bottom: 1px solid #eef2e6;
    white-space: nowrap;
}

.diff-table tbody td {
    padding: 10px 12px;
    border-bottom: 1px solid #f4f7ee;
    vertical-align: top;
}

.diff-table tbody tr:last-child td { border-bottom: none; }

.diff-key {
    display: inline-block;
    padding: 2px 8px;
    background: #f4f7ee;
    border-radius: 6px;
    font-family: ui-monospace, monospace;
    font-size: 11.5px;
    font-weight: 700;
    color: #4a5a3d;
}

.diff-old {
    color: #94a3b8;
    text-decoration: line-through;
    text-decoration-color: #cbd5b5;
    font-family: ui-monospace, monospace;
    font-size: 12px;
    word-break: break-word;
}

.diff-new {
    color: #15803d;
    font-weight: 700;
    font-family: ui-monospace, monospace;
    font-size: 12px;
    word-break: break-word;
}

/* Generic detail list */
.detail-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 12px;
    background: #fafcf6;
    border: 1px solid #eef2e6;
    border-radius: 12px;
}

.detail-item {
    display: flex;
    gap: 12px;
    font-size: 12.5px;
    padding: 4px 0;
    border-bottom: 1px dashed #f0f4e8;
}

.detail-item:last-child { border-bottom: none; }

.detail-key {
    min-width: 120px;
    font-weight: 700;
    color: #6b7a5e;
    font-family: ui-monospace, monospace;
    font-size: 11.5px;
}

.detail-value {
    color: #14210a;
    font-family: ui-monospace, monospace;
    word-break: break-word;
    flex: 1;
}

/* Metadata */
.detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    padding-top: 12px;
    margin-top: 4px;
    border-top: 1px dashed #f0f4e8;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    color: #94a3b8;
}

.meta-item i { font-size: 12px; color: #a3b190; }

.meta-ua {
    max-width: 500px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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
    flex-wrap: wrap;
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

/* ============ EXPAND TRANSITION ============ */
.expand-enter-active,
.expand-leave-active {
    transition: all 0.25s ease;
    overflow: hidden;
}
.expand-enter-from,
.expand-leave-to {
    opacity: 0;
    max-height: 0;
}
.expand-enter-to,
.expand-leave-from {
    opacity: 1;
    max-height: 500px;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 1024px) {
    .log-row {
        grid-template-columns: 110px 140px 120px 1fr 40px;
    }
    .log-ip { display: none; }
}

@media (max-width: 768px) {
    .toolbar { padding: 10px; }
    .toolbar-search { min-width: 100%; }
    .toolbar-select { flex: 1; min-width: 0; }
    .toolbar-daterange { width: 100%; }
    .date-field { flex: 1; }
    .date-field input { width: 100%; }
    .date-arrow { display: none; }

    .log-row {
        grid-template-columns: 1fr 40px;
        grid-template-areas:
            "time toggle"
            "user user"
            "action action"
            "description description";
        gap: 8px;
        padding: 12px;
    }

    .log-time { grid-area: time; display: flex; align-items: center; gap: 8px; }
    .log-time-ago { margin-top: 0; }
    .log-user { grid-area: user; }
    .log-action { grid-area: action; }
    .log-description { grid-area: description; white-space: normal; }
    .log-toggle { grid-area: toggle; }

    .pagination-wrap { flex-direction: column; align-items: stretch; }
    .pagination-info { text-align: center; }
    .pagination { justify-content: center; }
}

@media (max-width: 640px) {
    .page-title { font-size: 22px; }
    .readonly-badge { display: none; }
}
</style>