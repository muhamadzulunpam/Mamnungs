<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DashboardLayout from '../../../Layouts/DashboardLayout.vue';
import ConfirmModal from '../../../Components/UI/ConfirmModal.vue';
import EmptyState from '../../../Components/UI/EmptyState.vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const me = usePage().props.auth.user;

// ===== SEARCH =====
const search = ref(props.filters?.search ?? '');

let timer;
watch(search, (v) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            '/admin/users',
            { search: v || undefined },
            { preserveState: true, replace: true }
        );
    }, 300);
});

// ===== ROLE FILTER =====
const roleFilter = ref(props.filters?.role ?? '');

watch(roleFilter, (v) => {
    router.get(
        '/admin/users',
        {
            search: search.value || undefined,
            role: v || undefined,
        },
        { preserveState: true, replace: true }
    );
});

// ===== STATUS FILTER =====
const statusFilter = ref(props.filters?.status ?? '');

watch(statusFilter, (v) => {
    router.get(
        '/admin/users',
        {
            search: search.value || undefined,
            role: roleFilter.value || undefined,
            status: v || undefined,
        },
        { preserveState: true, replace: true }
    );
});

// ===== HELPERS =====
const initials = (name) =>
    (name || 'U')
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

const hasFilter = computed(
    () =>
        search.value !== '' ||
        roleFilter.value !== '' ||
        statusFilter.value !== ''
);

const clearFilters = () => {
    search.value = '';
    roleFilter.value = '';
    statusFilter.value = '';
};

// ===== AVATAR COLOR (deterministic) =====
const avatarColors = [
    { bg: '#eef7e0', color: '#6ba324' },
    { bg: '#dbeafe', color: '#1d4ed8' },
    { bg: '#fce7f3', color: '#ec4899' },
    { bg: '#fef3c7', color: '#d97706' },
    { bg: '#e0f2fe', color: '#0ea5e9' },
    { bg: '#ede9fe', color: '#7c3aed' },
];

const avatarStyle = (id) => avatarColors[id % avatarColors.length];

// ===== TOGGLE ACTIVE =====
const togglingId = ref(null);

const toggle = (u) => {
    if (u.id === me.id) return;
    togglingId.value = u.id;
    router.patch(
        `/admin/users/${u.id}/toggle`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                togglingId.value = null;
            },
        }
    );
};

// ===== DELETE MODAL =====
const showDeleteModal = ref(false);
const targetUser = ref(null);
const processingDelete = ref(false);

const askDelete = (u) => {
    if (u.id === me.id) return;
    targetUser.value = u;
    showDeleteModal.value = true;
};

const cancelDelete = () => {
    showDeleteModal.value = false;
    targetUser.value = null;
};

const confirmDelete = () => {
    if (!targetUser.value) return;
    processingDelete.value = true;
    router.delete(`/admin/users/${targetUser.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            processingDelete.value = false;
            showDeleteModal.value = false;
            targetUser.value = null;
        },
    });
};

// ===== MINI STATS =====
const stats = computed(() => {
    const list = props.users?.data ?? [];
    const admin = list.filter((u) => u.role === 'admin').length;
    const active = list.filter((u) => u.is_active).length;
    return {
        total: props.users?.total ?? 0,
        admin,
        active,
    };
});
</script>

<template>
    <DashboardLayout>
        <!-- ============ HEADER ============ -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Pengguna</h1>
                <p class="page-subtitle">
                    Kelola akun admin dan kasir yang memiliki akses ke sistem.
                </p>
            </div>
            <Link href="/admin/users/create" class="btn-primary-custom">
                <i class="bi bi-plus-lg"></i>
                Tambah Pengguna
            </Link>
        </div>

        <!-- ============ MINI STATS ============ -->
        <div class="stats-row">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background:#eef7e0; color:#6ba324">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="stat-mini-value">{{ stats.total }}</div>
                    <div class="stat-mini-label">Total Pengguna</div>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background:#14210a; color:#ffffff">
                    <i class="bi bi-shield-fill-check"></i>
                </div>
                <div>
                    <div class="stat-mini-value">{{ stats.admin }}</div>
                    <div class="stat-mini-label">Admin</div>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background:#dcfce7; color:#15803d">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="stat-mini-value">{{ stats.active }}</div>
                    <div class="stat-mini-label">Aktif</div>
                </div>
            </div>
        </div>

        <!-- ============ TOOLBAR ============ -->
        <div class="toolbar">
            <!-- Search -->
            <div class="toolbar-search">
                <i class="bi bi-search"></i>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari nama atau email..."
                    autocomplete="off"
                />
                <button
                    v-if="search"
                    class="toolbar-clear"
                    @click="search = ''"
                    title="Hapus pencarian"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Role pills -->
            <div class="toolbar-filters">
                <button
                    :class="['filter-pill', { active: roleFilter === '' }]"
                    @click="roleFilter = ''"
                >
                    Semua Role
                </button>
                <button
                    :class="['filter-pill', { active: roleFilter === 'admin' }]"
                    @click="roleFilter = 'admin'"
                >
                    <i class="bi bi-shield-fill-check"></i>
                    Admin
                </button>
                <button
                    :class="['filter-pill', { active: roleFilter === 'kasir' }]"
                    @click="roleFilter = 'kasir'"
                >
                    <i class="bi bi-cash-coin"></i>
                    Kasir
                </button>
            </div>

            <!-- Status pills -->
            <div class="toolbar-filters">
                <button
                    :class="['filter-pill', { active: statusFilter === '' }]"
                    @click="statusFilter = ''"
                >
                    Semua
                </button>
                <button
                    :class="['filter-pill', { active: statusFilter === '1' }]"
                    @click="statusFilter = '1'"
                >
                    <span class="dot dot-green"></span> Aktif
                </button>
                <button
                    :class="['filter-pill', { active: statusFilter === '0' }]"
                    @click="statusFilter = '0'"
                >
                    <span class="dot dot-red"></span> Nonaktif
                </button>
            </div>

            <!-- Reset -->
            <button
                v-if="hasFilter"
                class="toolbar-reset"
                @click="clearFilters"
            >
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Reset</span>
            </button>
        </div>

        <!-- ============ PANEL ============ -->
        <div class="panel">
            <!-- Empty: no data -->
            <EmptyState
                v-if="users.data.length === 0 && !hasFilter"
                icon="bi-people"
                title="Belum Ada Pengguna"
                message="Mulai dengan menambahkan admin atau kasir pertama untuk mengelola Mamnungs."
                action-text="Tambah Pengguna Pertama"
                action-href="/admin/users/create"
            />

            <!-- Empty: no results -->
            <EmptyState
                v-else-if="users.data.length === 0"
                icon="bi-search"
                title="Pengguna Tidak Ditemukan"
                message="Tidak ada pengguna yang cocok dengan filter Anda. Coba ubah kata kunci atau reset filter."
                action-text="Reset Filter"
                @action="clearFilters"
            />

            <!-- Table -->
            <div v-else class="table-wrap">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Role</th>
                            <th>Transaksi</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in users.data" :key="u.id">
                            <!-- Pengguna (Avatar + Nama + Email) -->
                            <td>
                                <div class="user-cell">
                                    <div
                                        class="user-avatar"
                                        :style="{
                                            background: avatarStyle(u.id).bg,
                                            color: avatarStyle(u.id).color,
                                        }"
                                    >
                                        {{ initials(u.name) }}
                                    </div>
                                    <div class="user-info">
                                        <div class="user-name">
                                            {{ u.name }}
                                            <span v-if="u.id === me.id" class="you-badge">
                                                Anda
                                            </span>
                                        </div>
                                        <div class="user-email">{{ u.email }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Role -->
                            <td>
                                <span
                                    :class="[
                                        'role-badge',
                                        u.role === 'admin' ? 'role-admin' : 'role-kasir',
                                    ]"
                                >
                                    <i
                                        :class="[
                                            'bi',
                                            u.role === 'admin'
                                                ? 'bi-shield-fill-check'
                                                : 'bi-cash-coin',
                                        ]"
                                    ></i>
                                    {{ u.role === 'admin' ? 'Admin' : 'Kasir' }}
                                </span>
                            </td>

                            <!-- Transaksi -->
                            <td>
                                <span class="trx-badge">
                                    <i class="bi bi-receipt"></i>
                                    {{ u.orders_count }} trx
                                </span>
                            </td>

                            <!-- Status toggle -->
                            <td>
                                <button
                                    :class="[
                                        'status-pill',
                                        u.is_active ? 'status-available' : 'status-empty',
                                        { 'is-loading': togglingId === u.id },
                                        { 'is-disabled': u.id === me.id },
                                    ]"
                                    :disabled="togglingId === u.id || u.id === me.id"
                                    :title="u.id === me.id ? 'Tidak bisa ubah status diri sendiri' : 'Klik untuk ubah status'"
                                    @click="toggle(u)"
                                >
                                    <span class="status-dot"></span>
                                    <span v-if="togglingId !== u.id">
                                        {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    <span v-else class="mini-spinner"></span>
                                </button>
                            </td>

                            <!-- Aksi -->
                            <td class="text-end">
                                <div class="action-group">
                                    <Link
                                        :href="`/admin/users/${u.id}/edit`"
                                        class="btn-action btn-edit"
                                        title="Ubah"
                                    >
                                        <i class="bi bi-pencil"></i>
                                        <span>Ubah</span>
                                    </Link>
                                    <button
                                        class="btn-action btn-delete"
                                        :disabled="u.id === me.id"
                                        :title="u.id === me.id ? 'Tidak bisa hapus diri sendiri' : 'Hapus'"
                                        @click="askDelete(u)"
                                    >
                                        <i class="bi bi-trash3"></i>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="pagination-wrap">
                <div class="pagination-info">
                    Menampilkan
                    <strong>{{ users.from }}</strong>–<strong>{{ users.to }}</strong>
                    dari <strong>{{ users.total }}</strong> pengguna
                </div>

                <nav class="pagination-nav">
                    <ul class="pagination">
                        <li
                            v-for="(l, idx) in users.links"
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

        <!-- ============ CONFIRM DELETE MODAL ============ -->
        <ConfirmModal
            :show="showDeleteModal"
            variant="danger"
            title="Hapus Pengguna?"
            :message="`Akun <strong>'${targetUser?.name}'</strong> akan dihapus permanen. Pengguna tidak akan bisa login lagi setelah ini.`"
            confirm-text="Ya, Hapus"
            cancel-text="Batal"
            :processing="processingDelete"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
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

/* ============ MINI STATS ============ */
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

@media (max-width: 640px) {
    .stats-row { grid-template-columns: 1fr; }
}

.stat-mini {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 14px;
    transition: all 0.2s;
}

.stat-mini:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -12px rgba(20, 33, 10, 0.15);
}

.stat-mini-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.stat-mini-value {
    font-size: 20px;
    font-weight: 800;
    color: #14210a;
    line-height: 1;
    letter-spacing: -0.5px;
    font-family: ui-monospace, monospace;
}

.stat-mini-label {
    font-size: 11.5px;
    color: #6b7a5e;
    font-weight: 600;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

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
    min-width: 220px;
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

/* Filter pills */
.toolbar-filters {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.filter-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
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

.filter-pill i { font-size: 12px; }

.filter-pill:hover {
    border-color: #84bd33;
    color: #6ba324;
    background: #f0f8e5;
}

.filter-pill.active {
    background: linear-gradient(135deg, #84bd33, #6ba324);
    border-color: #6ba324;
    color: #ffffff;
    box-shadow: 0 4px 12px -4px rgba(107, 163, 36, 0.5);
}

.dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}
.dot-green { background: #84bd33; }
.dot-red   { background: #ef4444; }
.filter-pill.active .dot-green,
.filter-pill.active .dot-red { background: #ffffff; }

/* Reset button */
.toolbar-reset {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 10px;
    background: #fef2f2;
    border: 1.5px solid #fecaca;
    color: #dc2626;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s;
}
.toolbar-reset:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #ffffff;
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

/* ============ USER CELL ============ */
.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13.5px;
    font-weight: 800;
    letter-spacing: 0.3px;
    flex-shrink: 0;
}

.user-info { min-width: 0; }

.user-name {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13.5px;
    font-weight: 700;
    color: #14210a;
    margin-bottom: 2px;
}

.you-badge {
    display: inline-flex;
    align-items: center;
    padding: 1px 7px;
    background: #f0f8e5;
    color: #558a1a;
    border: 1px solid #d8e8bf;
    border-radius: 100px;
    font-size: 9.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.user-email {
    font-size: 12px;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ============ ROLE BADGE ============ */
.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    border-radius: 100px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.role-badge i { font-size: 10.5px; }

.role-admin {
    background: #14210a;
    color: #ffffff;
}

.role-kasir {
    background: #dbeafe;
    color: #1d4ed8;
}

/* ============ TRX BADGE ============ */
.trx-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    background: #f4f7ee;
    color: #4a5a3d;
    border-radius: 100px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
    font-family: ui-monospace, monospace;
}

.trx-badge i { font-size: 10.5px; color: #6b7a5e; }

/* ============ STATUS PILL ============ */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 12px;
    border-radius: 100px;
    border: 1.5px solid transparent;
    font-family: inherit;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s;
    white-space: nowrap;
    min-width: 90px;
    justify-content: center;
    min-height: 30px;
}

.status-pill:disabled { cursor: not-allowed; opacity: 0.6; }
.status-pill.is-disabled { cursor: not-allowed; opacity: 0.5; }
.status-pill.is-loading { cursor: wait; }

.status-available {
    background: #dcfce7;
    color: #15803d;
    border-color: #bbf7d0;
}
.status-available:hover:not(:disabled) {
    background: #bbf7d0;
    transform: translateY(-1px);
}

.status-empty {
    background: #f4f7ee;
    color: #94a3b8;
    border-color: #e2e8d5;
}
.status-empty:hover:not(:disabled) {
    background: #e8efdb;
    transform: translateY(-1px);
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    flex-shrink: 0;
}

.mini-spinner {
    width: 12px;
    height: 12px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: mini-spin 0.6s linear infinite;
}

@keyframes mini-spin {
    to { transform: rotate(360deg); }
}

/* ============ ACTION BUTTONS ============ */
.action-group {
    display: inline-flex;
    gap: 6px;
    justify-content: flex-end;
}

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

.btn-edit {
    background: #f8fbf3;
    border-color: #e2e8d5;
    color: #4a5a3d;
}
.btn-edit:hover {
    background: #f0f8e5;
    border-color: #84bd33;
    color: #6ba324;
    transform: translateY(-1px);
}

.btn-delete {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}
.btn-delete:hover:not(:disabled) {
    background: #ef4444;
    border-color: #ef4444;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px -6px rgba(239, 68, 68, 0.6);
}

.btn-delete:disabled {
    opacity: 0.4;
    cursor: not-allowed;
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
@media (max-width: 768px) {
    .toolbar { padding: 10px; }
    .toolbar-search { min-width: 100%; }
    .toolbar-filters { width: 100%; overflow-x: auto; flex-wrap: nowrap; padding-bottom: 2px; }
    .filter-pill { flex-shrink: 0; }

    .pagination-wrap { flex-direction: column; align-items: stretch; }
    .pagination-info { text-align: center; }
    .pagination { justify-content: center; }
}

@media (max-width: 640px) {
    .btn-action span { display: none; }
    .btn-action { padding: 8px 10px; }
    .page-title { font-size: 22px; }
    .custom-table thead th,
    .custom-table tbody td { padding: 12px 10px; }
    .user-avatar { width: 36px; height: 36px; font-size: 12px; }
    .user-email { display: none; }
    .status-pill { padding: 5px 9px; font-size: 11px; min-width: 76px; }
}
</style>