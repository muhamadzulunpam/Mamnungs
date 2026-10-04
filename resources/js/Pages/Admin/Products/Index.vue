<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import DashboardLayout from '../../../Layouts/DashboardLayout.vue';
import ConfirmModal from '../../../Components/UI/ConfirmModal.vue';
import EmptyState from '../../../Components/UI/EmptyState.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
});

// ===== SEARCH & FILTER =====
const search = ref(props.filters?.search ?? '');
const category = ref(props.filters?.category ?? '');
const statusFilter = ref(props.filters?.status ?? ''); // '' | '1' | '0'

// ===== DEBOUNCED SEARCH =====
let timer;
const cari = () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            '/admin/products',
            {
                search: search.value || undefined,
                category: category.value || undefined,
                status: statusFilter.value || undefined,
            },
            { preserveState: true, replace: true }
        );
    }, 300);
};

watch([search, category, statusFilter], cari);

const clearFilters = () => {
    search.value = '';
    category.value = '';
    statusFilter.value = '';
};

const hasFilter = computed(() =>
    search.value !== '' || category.value !== '' || statusFilter.value !== ''
);

// ===== HELPERS =====
const rupiah = (n) =>
    'Rp ' + new Intl.NumberFormat('id-ID').format(n);

const initials = (name) =>
    (name || 'P').split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase();

// ===== DELETE MODAL =====
const showDeleteModal = ref(false);
const targetProduct = ref(null);
const processingDelete = ref(false);

const askDelete = (p) => {
    targetProduct.value = p;
    showDeleteModal.value = true;
};

const cancelDelete = () => {
    showDeleteModal.value = false;
    targetProduct.value = null;
};

const confirmDelete = () => {
    if (!targetProduct.value) return;
    processingDelete.value = true;
    router.delete(`/admin/products/${targetProduct.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            processingDelete.value = false;
            showDeleteModal.value = false;
            targetProduct.value = null;
        },
    });
};

// ===== TOGGLE STATUS =====
const togglingId = ref(null);

const toggle = (p) => {
    togglingId.value = p.id;
    router.patch(
        `/admin/products/${p.id}/toggle`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                togglingId.value = null;
            },
        }
    );
};

// ===== STATS MINI =====
const stats = computed(() => {
    const list = props.products?.data ?? [];
    const available = list.filter((p) => p.is_available).length;
    const outOfStock = list.length - available;
    return {
        total: props.products?.total ?? 0,
        available,
        outOfStock,
    };
});
</script>

<template>
    <DashboardLayout>
        <!-- ============ HEADER ============ -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Menu & Produk</h1>
                <p class="page-subtitle">
                    Kelola menu es teler, harga, dan ketersediaan stok.
                </p>
            </div>
            <Link href="/admin/products/create" class="btn-primary-custom">
                <i class="bi bi-plus-lg"></i> Tambah Produk
            </Link>
        </div>

        <!-- ============ MINI STATS ============ -->
        <div class="stats-row">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background:#eef7e0; color:#6ba324">
                    <i class="bi bi-cup-straw"></i>
                </div>
                <div>
                    <div class="stat-mini-value">{{ stats.total }}</div>
                    <div class="stat-mini-label">Total Menu</div>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background:#dcfce7; color:#15803d">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="stat-mini-value">{{ stats.available }}</div>
                    <div class="stat-mini-label">Tersedia</div>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background:#fee2e2; color:#dc2626">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <div class="stat-mini-value">{{ stats.outOfStock }}</div>
                    <div class="stat-mini-label">Habis</div>
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
                    placeholder="Cari nama produk..."
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

            <!-- Category -->
            <div class="toolbar-select">
                <i class="bi bi-tags"></i>
                <select v-model="category">
                    <option value="">Semua Kategori</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
                <i class="bi bi-chevron-down select-caret"></i>
            </div>

            <!-- Status filter pills -->
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
                    <span class="dot dot-green"></span> Tersedia
                </button>
                <button
                    :class="['filter-pill', { active: statusFilter === '0' }]"
                    @click="statusFilter = '0'"
                >
                    <span class="dot dot-red"></span> Habis
                </button>
            </div>

            <!-- Reset -->
            <button
                v-if="hasFilter"
                class="toolbar-reset"
                @click="clearFilters"
                title="Reset filter"
            >
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Reset</span>
            </button>
        </div>

        <!-- ============ PANEL ============ -->
        <div class="panel">
            <!-- Empty: belum ada data sama sekali -->
            <EmptyState
                v-if="products.data.length === 0 && !hasFilter"
                icon="bi-cup-straw"
                title="Belum Ada Produk"
                message="Mulai dengan menambahkan menu es teler pertama Anda, seperti 'Es Teler Special' atau 'Es Kelapa Muda'."
                action-text="Tambah Produk Pertama"
                action-href="/admin/products/create"
            />

            <!-- Empty: tidak ada hasil pencarian -->
            <EmptyState
                v-else-if="products.data.length === 0"
                icon="bi-search"
                title="Produk Tidak Ditemukan"
                message="Tidak ada produk yang cocok dengan filter Anda. Coba kata kunci lain atau reset filter."
                action-text="Reset Filter"
                @action="clearFilters"
            />

            <!-- Table -->
            <div v-else class="table-wrap">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 70px">Foto</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in products.data" :key="p.id">
                            <!-- Foto -->
                            <td>
                                <div class="product-image-wrap">
                                    <img
                                        v-if="p.image"
                                        :src="`/storage/${p.image}`"
                                        :alt="p.name"
                                        class="product-image"
                                    />
                                    <div v-else class="product-image product-image-placeholder">
                                        {{ initials(p.name) }}
                                    </div>
                                </div>
                            </td>

                            <!-- Nama -->
                            <td>
                                <div class="product-name">{{ p.name }}</div>
                                <div class="product-meta">ID #{{ p.id }}</div>
                            </td>

                            <!-- Kategori -->
                            <td>
                                <span v-if="p.category" class="badge-category">
                                    <i class="bi bi-tag-fill"></i>
                                    {{ p.category.name }}
                                </span>
                                <span v-else class="badge-category badge-category-empty">
                                    Tanpa Kategori
                                </span>
                            </td>

                            <!-- Harga -->
                            <td>
                                <span class="price-tag">{{ rupiah(p.price) }}</span>
                            </td>

                            <!-- Status toggle -->
                            <td>
                                <button
                                    :class="[
                                        'status-pill',
                                        p.is_available ? 'status-available' : 'status-empty',
                                        { 'is-loading': togglingId === p.id },
                                    ]"
                                    :disabled="togglingId === p.id"
                                    @click="toggle(p)"
                                    title="Klik untuk ubah status"
                                >
                                    <span class="status-dot"></span>
                                    <span v-if="togglingId !== p.id">
                                        {{ p.is_available ? 'Tersedia' : 'Habis' }}
                                    </span>
                                    <span v-else class="mini-spinner"></span>
                                </button>
                            </td>

                            <!-- Aksi -->
                            <td class="text-end">
                                <div class="action-group">
                                    <Link
                                        :href="`/admin/products/${p.id}/edit`"
                                        class="btn-action btn-edit"
                                        title="Ubah"
                                    >
                                        <i class="bi bi-pencil"></i>
                                        <span>Ubah</span>
                                    </Link>
                                    <button
                                        class="btn-action btn-delete"
                                        title="Hapus"
                                        @click="askDelete(p)"
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
            <div v-if="products.last_page > 1" class="pagination-wrap">
                <div class="pagination-info">
                    Menampilkan
                    <strong>{{ products.from }}</strong>–<strong>{{ products.to }}</strong>
                    dari <strong>{{ products.total }}</strong> produk
                </div>

                <nav class="pagination-nav">
                    <ul class="pagination">
                        <li
                            v-for="(l, idx) in products.links"
                            :key="idx"
                            :class="[
                                'page-item',
                                { active: l.active, disabled: !l.url },
                            ]"
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

        <!-- ============ CONFIRM DELETE ============ -->
        <ConfirmModal
            :show="showDeleteModal"
            variant="danger"
            title="Hapus Produk?"
            :message="`Produk <strong>'${targetProduct?.name}'</strong> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`"
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
    gap: 12px;
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

/* Category select */
.toolbar-select {
    position: relative;
    display: flex;
    align-items: center;
    min-width: 180px;
}

.toolbar-select > i:first-child {
    position: absolute;
    left: 12px;
    color: #a3b190;
    font-size: 14px;
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
    padding: 10px 32px 10px 36px;
    border: 1.5px solid #e2e8d5;
    border-radius: 10px;
    background: #f8fbf3;
    font-size: 13.5px;
    font-family: inherit;
    color: #14210a;
    cursor: pointer;
    transition: all 0.2s;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

.toolbar-select select:focus {
    border-color: #84bd33;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(132, 189, 51, 0.12);
}

.toolbar-select:focus-within > i { color: #6ba324; }

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
    padding: 8px 14px;
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

.filter-pill.active .dot-green { background: #ffffff; }
.filter-pill.active .dot-red   { background: #ffffff; }

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

/* Product image */
.product-image-wrap {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    overflow: hidden;
    background: #f4f9f0;
    flex-shrink: 0;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.product-image-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 800;
    color: #6ba324;
    background: linear-gradient(135deg, #eef7e0 0%, #e0f0c8 100%);
    letter-spacing: 0.5px;
}

/* Product name */
.product-name {
    font-size: 14px;
    font-weight: 700;
    color: #14210a;
    margin-bottom: 2px;
    line-height: 1.3;
}

.product-meta {
    font-size: 11px;
    color: #94a3b8;
    font-family: ui-monospace, monospace;
}

/* Category badge */
.badge-category {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    background: #f0f8e5;
    color: #558a1a;
    border-radius: 100px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}

.badge-category i { font-size: 10.5px; }

.badge-category-empty {
    background: #f4f7ee;
    color: #94a3b8;
    font-style: italic;
}

/* Price */
.price-tag {
    font-family: ui-monospace, monospace;
    font-size: 13.5px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.3px;
}

/* ============ STATUS TOGGLE PILL ============ */
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

.status-pill:disabled { cursor: wait; opacity: 0.7; }

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
    background: #fee2e2;
    color: #b91c1c;
    border-color: #fecaca;
}
.status-empty:hover:not(:disabled) {
    background: #fecaca;
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
.action-group { display: inline-flex; gap: 6px; justify-content: flex-end; }

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
.btn-delete:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px -6px rgba(239, 68, 68, 0.6);
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
    .toolbar-select { width: 100%; }
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
    .custom-table tbody td { padding: 10px 12px; }
    .product-image-wrap { width: 44px; height: 44px; }
    .status-pill { padding: 5px 9px; font-size: 11px; min-width: 76px; }
}
</style>