<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import DashboardLayout from '../../../Layouts/DashboardLayout.vue';
import ConfirmModal from '../../../Components/UI/ConfirmModal.vue';
import EmptyState from '../../../Components/UI/EmptyState.vue';

const props = defineProps({ categories: Array });

// ===== SEARCH & FILTER STATE =====
const search = ref('');
const sortBy = ref('name');       // name | products | newest
const sortDir = ref('asc');       // asc | desc
const filterMode = ref('all');    // all | empty | used

// ===== COMPUTED: FILTERED & SORTED =====
const filteredCategories = computed(() => {
    let list = [...(props.categories || [])];

    // 1. Search
    const q = search.value.trim().toLowerCase();
    if (q) {
        list = list.filter((c) =>
            c.name.toLowerCase().includes(q) ||
            String(c.id).includes(q)
        );
    }

    // 2. Filter by product count
    if (filterMode.value === 'empty') {
        list = list.filter((c) => (c.products_count || 0) === 0);
    } else if (filterMode.value === 'used') {
        list = list.filter((c) => (c.products_count || 0) > 0);
    }

    // 3. Sort
    list.sort((a, b) => {
        let valA, valB;
        if (sortBy.value === 'name') {
            valA = a.name.toLowerCase();
            valB = b.name.toLowerCase();
        } else if (sortBy.value === 'products') {
            valA = a.products_count || 0;
            valB = b.products_count || 0;
        } else {
            valA = a.id;
            valB = b.id;
        }
        if (valA < valB) return sortDir.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortDir.value === 'asc' ? 1 : -1;
        return 0;
    });

    return list;
});

const hasSearchOrFilter = computed(() =>
    search.value.trim() !== '' || filterMode.value !== 'all'
);

const clearFilters = () => {
    search.value = '';
    filterMode.value = 'all';
    sortBy.value = 'name';
    sortDir.value = 'asc';
};

const toggleSort = (field) => {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
};

// ===== DELETE MODAL =====
const showDeleteModal = ref(false);
const targetCategory = ref(null);
const processing = ref(false);

const askDelete = (c) => {
    targetCategory.value = c;
    showDeleteModal.value = true;
};

const cancelDelete = () => {
    showDeleteModal.value = false;
    targetCategory.value = null;
};

const confirmDelete = () => {
    if (!targetCategory.value) return;
    processing.value = true;
    router.delete(`/admin/categories/${targetCategory.value.id}`, {
        onFinish: () => {
            processing.value = false;
            showDeleteModal.value = false;
            targetCategory.value = null;
        },
    });
};
</script>

<template>
    <DashboardLayout>
        <!-- ============ HEADER ============ -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Kategori Menu</h1>
                <p class="page-subtitle">
                    Kelola kategori untuk mengelompokkan menu es teler Anda.
                </p>
            </div>
            <Link href="/admin/categories/create" class="btn-primary-custom">
                <i class="bi bi-plus-lg"></i> Tambah Kategori
            </Link>
        </div>

        <!-- ============ TOOLBAR ============ -->
        <div v-if="categories.length > 0" class="toolbar">
            <!-- Search -->
            <div class="toolbar-search">
                <i class="bi bi-search"></i>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari kategori..."
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

            <!-- Filter pills -->
            <div class="toolbar-filters">
                <button
                    :class="['filter-pill', { active: filterMode === 'all' }]"
                    @click="filterMode = 'all'"
                >
                    Semua
                    <span class="pill-count">{{ categories.length }}</span>
                </button>
                <button
                    :class="['filter-pill', { active: filterMode === 'used' }]"
                    @click="filterMode = 'used'"
                >
                    Berisi Produk
                    <span class="pill-count">
                        {{ categories.filter((c) => (c.products_count || 0) > 0).length }}
                    </span>
                </button>
                <button
                    :class="['filter-pill', { active: filterMode === 'empty' }]"
                    @click="filterMode = 'empty'"
                >
                    Kosong
                    <span class="pill-count">
                        {{ categories.filter((c) => (c.products_count || 0) === 0).length }}
                    </span>
                </button>
            </div>

            <!-- Reset -->
            <button
                v-if="hasSearchOrFilter"
                class="toolbar-reset"
                @click="clearFilters"
                title="Reset filter"
            >
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Reset</span>
            </button>
        </div>

        <!-- ============ RESULT INFO ============ -->
        <div v-if="hasSearchOrFilter && filteredCategories.length > 0" class="result-info">
            <i class="bi bi-info-circle"></i>
            Menampilkan <strong>{{ filteredCategories.length }}</strong>
            dari <strong>{{ categories.length }}</strong> kategori
            <template v-if="search">untuk "<strong>{{ search }}</strong>"</template>
        </div>

        <!-- ============ PANEL ============ -->
        <div class="panel">
            <!-- Empty State: no data at all -->
            <EmptyState
                v-if="categories.length === 0"
                icon="bi-tags"
                title="Belum Ada Kategori"
                message="Mulai dengan membuat kategori pertama Anda, seperti 'Es Teler', 'Minuman', atau 'Topping'."
                action-text="Tambah Kategori Pertama"
                action-href="/admin/categories/create"
            />

            <!-- Empty State: no search results -->
            <EmptyState
                v-else-if="filteredCategories.length === 0"
                icon="bi-search"
                title="Tidak Ada Hasil"
                :message="`Tidak ada kategori yang cocok dengan pencarian '${search}'. Coba kata kunci lain atau reset filter.`"
                action-text="Reset Filter"
                @action="clearFilters"
            />

            <!-- Table -->
            <div v-else class="table-wrap">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 60px">#</th>
                            <th>
                                <button class="th-sort" @click="toggleSort('name')">
                                    Nama Kategori
                                    <i
                                        :class="[
                                            'bi',
                                            sortBy === 'name'
                                                ? (sortDir === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down')
                                                : 'bi-arrow-down-up',
                                            'sort-icon',
                                            { active: sortBy === 'name' },
                                        ]"
                                    ></i>
                                </button>
                            </th>
                            <th>
                                <button class="th-sort" @click="toggleSort('products')">
                                    Jumlah Produk
                                    <i
                                        :class="[
                                            'bi',
                                            sortBy === 'products'
                                                ? (sortDir === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down')
                                                : 'bi-arrow-down-up',
                                            'sort-icon',
                                            { active: sortBy === 'products' },
                                        ]"
                                    ></i>
                                </button>
                            </th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(c, i) in filteredCategories" :key="c.id">
                            <td>
                                <span class="row-number">
                                    {{ String(i + 1).padStart(2, '0') }}
                                </span>
                            </td>
                            <td>
                                <div class="category-cell">
                                    <div class="category-icon">
                                        <i class="bi bi-tag-fill"></i>
                                    </div>
                                    <div>
                                        <div class="category-name" v-html="highlight(c.name)"></div>
                                        <div class="category-meta">ID #{{ c.id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span
                                    :class="[
                                        'badge-count',
                                        { 'badge-empty': (c.products_count || 0) === 0 },
                                    ]"
                                >
                                    <i class="bi bi-cup-straw"></i>
                                    {{ c.products_count }} produk
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="action-group">
                                    <Link
                                        :href="`/admin/categories/${c.id}/edit`"
                                        class="btn-action btn-edit"
                                    >
                                        <i class="bi bi-pencil"></i>
                                        <span>Ubah</span>
                                    </Link>
                                    <button
                                        class="btn-action btn-delete"
                                        @click="askDelete(c)"
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
        </div>

        <!-- ============ CONFIRM MODAL ============ -->
        <ConfirmModal
            :show="showDeleteModal"
            variant="danger"
            title="Hapus Kategori?"
            :message="`Kategori <strong>'${targetCategory?.name}'</strong> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`"
            confirm-text="Ya, Hapus"
            cancel-text="Batal"
            :processing="processing"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </DashboardLayout>
</template>

<script>
// Helper untuk highlight keyword di nama
export default {
    methods: {
        highlight(text) {
            if (!this.search || !text) return text;
            const q = this.search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const re = new RegExp(`(${q})`, 'gi');
            return text.replace(re, '<mark>$1</mark>');
        },
    },
};
</script>

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

.pill-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 100px;
    background: rgba(0, 0, 0, 0.06);
    font-size: 10.5px;
    font-weight: 700;
}

.filter-pill.active .pill-count {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
}

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

/* ============ RESULT INFO ============ */
.result-info {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: #f0f8e5;
    border: 1px solid #d8e8bf;
    border-radius: 10px;
    font-size: 12.5px;
    color: #4a5a3d;
    margin-bottom: 14px;
}

.result-info i { color: #6ba324; }
.result-info strong { color: #14210a; font-weight: 700; }

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
.custom-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13.5px; }
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

/* Sort button in header */
.th-sort {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: none;
    border: none;
    padding: 0;
    font-family: inherit;
    font-size: 11.5px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: color 0.15s;
}

.th-sort:hover { color: #6ba324; }

.sort-icon {
    font-size: 11px;
    opacity: 0.5;
    transition: all 0.15s;
}

.sort-icon.active { opacity: 1; color: #6ba324; }

.custom-table thead th:first-child { border-radius: 12px 0 0 0; }
.custom-table thead th:last-child  { border-radius: 0 12px 0 0; }
.custom-table tbody td {
    padding: 16px;
    border-bottom: 1px solid #f4f7ee;
    color: #14210a;
    vertical-align: middle;
}
.custom-table tbody tr { transition: background 0.15s; }
.custom-table tbody tr:hover { background: #fafcf6; }
.custom-table tbody tr:last-child td { border-bottom: none; }

/* Highlight hasil search */
.custom-table :deep(mark) {
    background: #fef9c3;
    color: #14210a;
    padding: 0 2px;
    border-radius: 3px;
    font-weight: 800;
}

/* Row number */
.row-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    background: #f4f9f0;
    color: #6ba324;
    font-family: ui-monospace, monospace;
    font-size: 12px;
    font-weight: 700;
    border-radius: 8px;
}

/* Category cell */
.category-cell { display: flex; align-items: center; gap: 12px; }
.category-icon {
    width: 40px; height: 40px;
    border-radius: 11px;
    background: linear-gradient(135deg, #eef7e0 0%, #e0f0c8 100%);
    color: #6ba324;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
}
.category-name { font-size: 14px; font-weight: 700; color: #14210a; margin-bottom: 2px; }
.category-meta { font-size: 11.5px; color: #94a3b8; font-family: ui-monospace, monospace; }

/* Badge count */
.badge-count {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 11px;
    background: #f0f8e5; color: #558a1a;
    border-radius: 100px;
    font-size: 12px; font-weight: 700; white-space: nowrap;
}
.badge-count i { font-size: 12px; }
.badge-empty {
    background: #f4f7ee;
    color: #94a3b8;
}

/* Action buttons */
.action-group { display: inline-flex; gap: 6px; justify-content: flex-end; }
.btn-action {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 12px;
    border-radius: 9px;
    font-family: inherit; font-size: 12.5px; font-weight: 600;
    cursor: pointer; text-decoration: none;
    border: 1.5px solid transparent;
    transition: all 0.18s;
    white-space: nowrap;
}
.btn-action i { font-size: 13px; }
.btn-edit { background: #f8fbf3; border-color: #e2e8d5; color: #4a5a3d; }
.btn-edit:hover { background: #f0f8e5; border-color: #84bd33; color: #6ba324; transform: translateY(-1px); }
.btn-delete { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
.btn-delete:hover {
    background: #ef4444; border-color: #ef4444; color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px -6px rgba(239, 68, 68, 0.6);
}

/* ============ RESPONSIVE ============ */
@media (max-width: 768px) {
    .toolbar { padding: 10px; }
    .toolbar-search { min-width: 100%; }
    .toolbar-filters { width: 100%; overflow-x: auto; flex-wrap: nowrap; padding-bottom: 2px; }
    .filter-pill { flex-shrink: 0; }
}

@media (max-width: 640px) {
    .btn-action span { display: none; }
    .btn-action { padding: 8px 10px; }
    .page-title { font-size: 22px; }
    .custom-table thead th, .custom-table tbody td { padding: 12px; }
}
</style>