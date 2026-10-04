<script setup>
import { computed, ref, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';

const props = defineProps({
    products: Array,
    categories: Array,
});

const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0);

// ===== FILTER MENU =====
const search = ref('');
const activeCategory = ref(null);
const searchInputRef = ref(null);

const filtered = computed(() =>
    props.products.filter((p) => {
        const matchCat = !activeCategory.value || p.category_id === activeCategory.value;
        const matchSearch = p.name.toLowerCase().includes(search.value.toLowerCase());
        return matchCat && matchSearch;
    })
);

// ===== KERANJANG =====
const cart = ref([]);

const tambah = (p) => {
    const found = cart.value.find((i) => i.id === p.id);
    if (found) {
        if (found.quantity < 99) found.quantity++;
    } else {
        cart.value.unshift({
            id: p.id,
            name: p.name,
            price: p.price,
            quantity: 1,
            image: p.image,
        });
    }
};

const ubahQty = (item, delta) => {
    const newQty = item.quantity + delta;
    if (newQty <= 0) {
        cart.value = cart.value.filter((i) => i.id !== item.id);
    } else if (newQty <= 99) {
        item.quantity = newQty;
    }
};

const setQty = (item, qty) => {
    const n = parseInt(qty, 10);
    if (isNaN(n) || n <= 0) {
        cart.value = cart.value.filter((i) => i.id !== item.id);
    } else {
        item.quantity = Math.min(n, 99);
    }
};

const hapusItem = (id) => {
    cart.value = cart.value.filter((i) => i.id !== id);
};

const kosongkan = () => {
    if (cart.value.length === 0) return;
    if (!confirm('Kosongkan keranjang?')) return;
    cart.value = [];
    form.reset();
    form.clearErrors();
};

// ===== TOTAL =====
const totalItems = computed(() =>
    cart.value.reduce((s, i) => s + i.quantity, 0)
);

const total = computed(() =>
    cart.value.reduce((s, i) => s + i.price * i.quantity, 0)
);

// ===== PEMBAYARAN =====
const form = useForm({
    items: [],
    notes: '',
    amount_received: '',
});

const received = computed(() => Number(form.amount_received) || 0);
const kembalian = computed(() => received.value - total.value);
const bisaBayar = computed(
    () => cart.value.length > 0 && received.value >= total.value
);

const uangCepat = computed(() => {
    const t = total.value;
    if (t === 0) return [];
    const pembulatan = [20000, 50000, 100000, 200000].filter((n) => n >= t);
    return [...new Set([t, ...pembulatan])].filter((n) => n > 0);
});

const bayar = () => {
    form.items = cart.value.map((i) => ({
        product_id: i.id,
        quantity: i.quantity,
    }));
    form.post('/kasir/checkout', {
        preserveScroll: true,
        onSuccess: () => {
            cart.value = [];
            form.reset();
        },
    });
};

// ===== KEYBOARD SHORTCUT: F2 = Fokus search =====
const handleKeydown = (e) => {
    if (e.key === 'F2') {
        e.preventDefault();
        searchInputRef.value?.focus();
    }
};

import { onMounted, onUnmounted } from 'vue';
onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
</script>

<template>
    <DashboardLayout>
        <!-- ============ HEADER ============ -->
        <div class="pos-header">
            <div>
                <h1 class="pos-title">
                    <i class="bi bi-shop"></i>
                    Kasir
                </h1>
                <p class="pos-subtitle">
                    Pilih menu, atur jumlah, dan proses pembayaran.
                    <kbd class="kbd-shortcut">F2</kbd> untuk fokus pencarian.
                </p>
            </div>
        </div>

        <div class="pos-grid">
            <!-- ============ KOLOM KIRI: MENU ============ -->
            <div class="pos-menu">
                <!-- Search & filter -->
                <div class="pos-toolbar">
                    <div class="pos-search">
                        <i class="bi bi-search"></i>
                        <input
                            ref="searchInputRef"
                            v-model="search"
                            type="text"
                            placeholder="Cari menu... (F2)"
                            autocomplete="off"
                        />
                        <button
                            v-if="search"
                            class="pos-search-clear"
                            @click="search = ''"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Category pills -->
                <div class="category-scroller">
                    <button
                        :class="['cat-pill', { active: activeCategory === null }]"
                        @click="activeCategory = null"
                    >
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                        Semua
                        <span class="cat-count">{{ products.length }}</span>
                    </button>
                    <button
                        v-for="c in categories"
                        :key="c.id"
                        :class="['cat-pill', { active: activeCategory === c.id }]"
                        @click="activeCategory = c.id"
                    >
                        {{ c.name }}
                        <span class="cat-count">
                            {{ products.filter((p) => p.category_id === c.id).length }}
                        </span>
                    </button>
                </div>

                <!-- Result info -->
                <div v-if="search || activeCategory" class="result-line">
                    <i class="bi bi-funnel-fill"></i>
                    Menampilkan <strong>{{ filtered.length }}</strong> dari <strong>{{ products.length }}</strong> menu
                    <button
                        class="result-clear"
                        @click="search = ''; activeCategory = null"
                    >
                        Reset
                    </button>
                </div>

                <!-- Menu grid -->
                <div v-if="filtered.length > 0" class="menu-grid">
                    <button
                        v-for="p in filtered"
                        :key="p.id"
                        class="menu-card"
                        :class="{ 'in-cart': cart.find((i) => i.id === p.id) }"
                        @click="tambah(p)"
                    >
                        <div class="menu-card-img">
                            <img
                                v-if="p.image"
                                :src="`/storage/${p.image}`"
                                :alt="p.name"
                            />
                            <div v-else class="menu-card-placeholder">
                                <i class="bi bi-cup-straw"></i>
                            </div>

                            <!-- Badge qty kalau sudah di cart -->
                            <div
                                v-if="cart.find((i) => i.id === p.id)"
                                class="menu-card-qty-badge"
                            >
                                {{ cart.find((i) => i.id === p.id).quantity }}
                            </div>
                        </div>
                        <div class="menu-card-body">
                            <div class="menu-card-name">{{ p.name }}</div>
                            <div class="menu-card-price">{{ rupiah(p.price) }}</div>
                        </div>
                    </button>
                </div>

                <!-- Empty state -->
                <div v-else class="menu-empty">
                    <div class="menu-empty-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <div class="menu-empty-title">Menu Tidak Ditemukan</div>
                    <div class="menu-empty-text">
                        Coba kata kunci lain atau reset filter.
                    </div>
                    <button
                        class="menu-empty-btn"
                        @click="search = ''; activeCategory = null"
                    >
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- ============ KOLOM KANAN: CART ============ -->
            <aside class="pos-cart">
                <div class="cart-card">
                    <!-- Header -->
                    <div class="cart-header">
                        <div class="cart-header-left">
                            <div class="cart-icon">
                                <i class="bi bi-bag-check-fill"></i>
                            </div>
                            <div>
                                <div class="cart-title">Keranjang</div>
                                <div class="cart-subtitle">
                                    {{ totalItems }} item · {{ cart.length }} jenis
                                </div>
                            </div>
                        </div>
                        <button
                            v-if="cart.length"
                            class="cart-clear"
                            @click="kosongkan"
                            title="Kosongkan keranjang"
                        >
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="cart-body">
                        <!-- Empty -->
                        <div v-if="!cart.length" class="cart-empty">
                            <div class="cart-empty-icon">
                                <i class="bi bi-bag"></i>
                            </div>
                            <div class="cart-empty-title">Keranjang Kosong</div>
                            <div class="cart-empty-text">
                                Klik menu di sebelah kiri untuk menambahkan.
                            </div>
                        </div>

                        <!-- Items -->
                        <div v-else class="cart-items">
                            <transition-group name="cart-item">
                                <div
                                    v-for="i in cart"
                                    :key="i.id"
                                    class="cart-item"
                                >
                                    <div class="cart-item-img">
                                        <img
                                            v-if="i.image"
                                            :src="`/storage/${i.image}`"
                                            :alt="i.name"
                                        />
                                        <div v-else class="cart-item-placeholder">
                                            {{ i.name.charAt(0).toUpperCase() }}
                                        </div>
                                    </div>

                                    <div class="cart-item-info">
                                        <div class="cart-item-name">{{ i.name }}</div>
                                        <div class="cart-item-price">
                                            {{ rupiah(i.price) }}
                                        </div>
                                    </div>

                                    <div class="cart-item-controls">
                                        <button
                                            class="qty-btn"
                                            @click="ubahQty(i, -1)"
                                            :title="i.quantity === 1 ? 'Hapus' : 'Kurangi'"
                                        >
                                            <i :class="i.quantity === 1 ? 'bi bi-trash3' : 'bi bi-dash-lg'"></i>
                                        </button>
                                        <input
                                            class="qty-input"
                                            type="number"
                                            min="1"
                                            max="99"
                                            :value="i.quantity"
                                            @input="setQty(i, $event.target.value)"
                                        />
                                        <button
                                            class="qty-btn qty-btn-plus"
                                            @click="ubahQty(i, 1)"
                                        >
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>

                                    <div class="cart-item-subtotal">
                                        {{ rupiah(i.price * i.quantity) }}
                                    </div>
                                </div>
                            </transition-group>
                        </div>
                    </div>

                    <!-- Payment Section -->
                    <div v-if="cart.length" class="cart-payment">
                        <!-- Total -->
                        <div class="total-row">
                            <span class="total-label">Total</span>
                            <span class="total-value">{{ rupiah(total) }}</span>
                        </div>

                        <!-- Error items -->
                        <div v-if="form.errors.items" class="cart-error">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            {{ form.errors.items }}
                        </div>

                        <!-- Notes -->
                        <div class="cart-field">
                            <label class="cart-field-label">
                                <i class="bi bi-chat-left-text"></i>
                                Catatan
                                <span class="label-optional">opsional</span>
                            </label>
                            <input
                                v-model="form.notes"
                                type="text"
                                class="cart-input"
                                placeholder="Contoh: es dipisah, tanpa susu"
                            />
                        </div>

                        <!-- Cash input -->
                        <div class="cart-field">
                            <label class="cart-field-label">
                                <i class="bi bi-cash-stack"></i>
                                Uang Diterima
                            </label>
                            <div class="cash-input-wrap">
                                <span class="cash-prefix">Rp</span>
                                <input
                                    v-model.number="form.amount_received"
                                    type="number"
                                    min="0"
                                    class="cash-input"
                                    placeholder="0"
                                />
                            </div>
                            <div v-if="form.errors.amount_received" class="cart-error">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                {{ form.errors.amount_received }}
                            </div>
                        </div>

                        <!-- Quick cash -->
                        <div class="quick-cash">
                            <button
                                v-for="n in uangCepat"
                                :key="n"
                                :class="[
                                    'quick-cash-btn',
                                    { active: received === n },
                                    { 'is-exact': n === total },
                                ]"
                                @click="form.amount_received = n"
                            >
                                {{ n === total ? 'Uang Pas' : rupiah(n) }}
                            </button>
                        </div>

                        <!-- Kembalian -->
                        <div :class="['change-row', { 'is-negative': kembalian < 0 }]">
                            <span class="change-label">
                                <i class="bi bi-arrow-return-left"></i>
                                Kembalian
                            </span>
                            <span class="change-value">
                                {{ kembalian < 0
                                    ? '−' + rupiah(-kembalian)
                                    : rupiah(kembalian)
                                }}
                            </span>
                        </div>

                        <!-- Pay button -->
                        <button
                            class="pay-btn"
                            :disabled="!bisaBayar || form.processing"
                            @click="bayar"
                        >
                            <span v-if="!form.processing">
                                <i class="bi bi-check-circle-fill"></i>
                                Bayar Tunai
                                <span v-if="bisaBayar" class="pay-amount">
                                    {{ rupiah(total) }}
                                </span>
                            </span>
                            <span v-else class="pay-spinner">
                                <span class="mini-spinner"></span>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </DashboardLayout>
</template>

<style scoped>
/* ============ HEADER ============ */
.pos-header { margin-bottom: 20px; }

.pos-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 26px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.8px;
    margin: 0 0 4px;
}

.pos-title i { color: #6ba324; font-size: 22px; }

.pos-subtitle {
    font-size: 13.5px;
    color: #6b7a5e;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.kbd-shortcut {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    background: #f0f8e5;
    border: 1px solid #d8e8bf;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    color: #558a1a;
    font-family: ui-monospace, monospace;
}

/* ============ GRID ============ */
.pos-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 20px;
    align-items: start;
}

@media (max-width: 1100px) {
    .pos-grid { grid-template-columns: 1fr 360px; }
}

@media (max-width: 900px) {
    .pos-grid { grid-template-columns: 1fr; }
}

/* ============ KOLOM KIRI: MENU ============ */
.pos-menu { min-width: 0; }

/* Toolbar */
.pos-toolbar {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}

.pos-search {
    position: relative;
    display: flex;
    align-items: center;
    flex: 1;
}

.pos-search > i {
    position: absolute;
    left: 16px;
    color: #a3b190;
    font-size: 15px;
    pointer-events: none;
}

.pos-search input {
    width: 100%;
    padding: 13px 40px 13px 44px;
    border: 1.5px solid #e2e8d5;
    border-radius: 14px;
    background: #ffffff;
    font-size: 14px;
    font-family: inherit;
    color: #14210a;
    transition: all 0.2s;
    outline: none;
    box-shadow: 0 2px 8px -4px rgba(20, 33, 10, 0.06);
}

.pos-search input::placeholder { color: #a3b190; }

.pos-search input:focus {
    border-color: #84bd33;
    box-shadow: 0 0 0 4px rgba(132, 189, 51, 0.12);
}

.pos-search:focus-within > i { color: #6ba324; }

.pos-search-clear {
    position: absolute;
    right: 12px;
    width: 24px;
    height: 24px;
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
.pos-search-clear:hover { background: #d8e8bf; color: #14210a; }

/* Category scroller */
.category-scroller {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 10px;
    margin-bottom: 12px;
    scrollbar-width: thin;
}

.category-scroller::-webkit-scrollbar { height: 5px; }
.category-scroller::-webkit-scrollbar-thumb {
    background: #d8e8bf;
    border-radius: 10px;
}

.cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: 11px;
    border: 1.5px solid #e2e8d5;
    background: #ffffff;
    color: #4a5a3d;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.18s;
    flex-shrink: 0;
}

.cat-pill:hover {
    border-color: #84bd33;
    color: #6ba324;
    background: #f8fbf3;
}

.cat-pill.active {
    background: linear-gradient(135deg, #84bd33 0%, #6ba324 100%);
    border-color: #6ba324;
    color: #ffffff;
    box-shadow: 0 6px 16px -6px rgba(107, 163, 36, 0.5);
}

.cat-count {
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

.cat-pill.active .cat-count {
    background: rgba(255, 255, 255, 0.25);
}

/* Result line */
.result-line {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    background: #f0f8e5;
    border: 1px solid #d8e8bf;
    border-radius: 10px;
    font-size: 12px;
    color: #4a5a3d;
    margin-bottom: 14px;
}

.result-line strong { color: #14210a; font-weight: 700; }
.result-line i { color: #6ba324; font-size: 11px; }

.result-clear {
    background: none;
    border: none;
    color: #dc2626;
    font-family: inherit;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    padding: 0;
    margin-left: 4px;
    text-decoration: underline;
}

/* Menu grid */
.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}

.menu-card {
    position: relative;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1.5px solid #eef2e6;
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    text-align: left;
    font-family: inherit;
    padding: 0;
}

.menu-card:hover {
    transform: translateY(-3px);
    border-color: #84bd33;
    box-shadow: 0 16px 32px -16px rgba(107, 163, 36, 0.4);
}

.menu-card:active { transform: translateY(-1px) scale(0.98); }

.menu-card.in-cart {
    border-color: #84bd33;
    background: #fafcf6;
}

.menu-card-img {
    position: relative;
    aspect-ratio: 4 / 3;
    background: #f4f9f0;
    overflow: hidden;
}

.menu-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.3s;
}

.menu-card:hover .menu-card-img img { transform: scale(1.05); }

.menu-card-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    color: #cbd5b5;
    background: linear-gradient(135deg, #f0f8e5 0%, #e8efdb 100%);
}

.menu-card-qty-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    min-width: 26px;
    height: 26px;
    padding: 0 8px;
    background: linear-gradient(135deg, #84bd33, #6ba324);
    color: #ffffff;
    border-radius: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(107, 163, 36, 0.5);
    border: 2px solid #ffffff;
    animation: pop-badge 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes pop-badge {
    0% { transform: scale(0); }
    100% { transform: scale(1); }
}

.menu-card-body {
    padding: 10px 12px 12px;
}

.menu-card-name {
    font-size: 13px;
    font-weight: 700;
    color: #14210a;
    line-height: 1.3;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.menu-card-price {
    font-size: 13px;
    font-weight: 800;
    color: #6ba324;
    font-family: ui-monospace, monospace;
    letter-spacing: -0.3px;
}

/* Menu empty */
.menu-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 18px;
    text-align: center;
}

.menu-empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 22px;
    background: linear-gradient(135deg, #f0f8e5 0%, #e8efdb 100%);
    color: #84bd33;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
    margin-bottom: 16px;
}

.menu-empty-title {
    font-size: 16px;
    font-weight: 800;
    color: #14210a;
    margin-bottom: 4px;
}

.menu-empty-text {
    font-size: 13px;
    color: #6b7a5e;
    margin-bottom: 16px;
}

.menu-empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 16px;
    background: #f4f7ee;
    border: 1.5px solid #e2e8d5;
    border-radius: 10px;
    color: #4a5a3d;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s;
}

.menu-empty-btn:hover {
    background: #e8efdb;
    color: #14210a;
}

/* ============ KOLOM KANAN: CART ============ */
.pos-cart { min-width: 0; }

.cart-card {
    position: sticky;
    top: 90px;
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 20px;
    box-shadow: 0 8px 32px -16px rgba(20, 33, 10, 0.15);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 110px);
}

/* Header */
.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 18px;
    background: linear-gradient(135deg, #f8fbf3 0%, #f0f8e5 100%);
    border-bottom: 1px solid #eef2e6;
}

.cart-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.cart-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, #84bd33, #6ba324);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    box-shadow: 0 6px 16px -6px rgba(107, 163, 36, 0.6);
}

.cart-title {
    font-size: 15px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.3px;
    line-height: 1.2;
}

.cart-subtitle {
    font-size: 11.5px;
    color: #6b7a5e;
    font-weight: 600;
    margin-top: 2px;
}

.cart-clear {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1.5px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    transition: all 0.18s;
}

.cart-clear:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #ffffff;
    transform: rotate(-8deg) scale(1.05);
}

/* Body */
.cart-body {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    min-height: 120px;
}

.cart-body::-webkit-scrollbar { width: 6px; }
.cart-body::-webkit-scrollbar-thumb {
    background: #d8e8bf;
    border-radius: 10px;
}
.cart-body::-webkit-scrollbar-thumb:hover { background: #84bd33; }

/* Empty */
.cart-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
}

.cart-empty-icon {
    width: 68px;
    height: 68px;
    border-radius: 20px;
    background: #f4f9f0;
    color: #cbd5b5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    margin-bottom: 14px;
}

.cart-empty-title {
    font-size: 14.5px;
    font-weight: 800;
    color: #14210a;
    margin-bottom: 4px;
}

.cart-empty-text {
    font-size: 12.5px;
    color: #94a3b8;
    line-height: 1.5;
}

/* Items */
.cart-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
    position: relative;
}

.cart-item {
    display: grid;
    grid-template-columns: 44px 1fr auto;
    grid-template-areas:
        "img info subtotal"
        "img controls subtotal";
    gap: 6px 10px;
    padding: 10px;
    background: #fafcf6;
    border: 1.5px solid #f0f4e8;
    border-radius: 12px;
    transition: all 0.15s;
}

.cart-item:hover {
    background: #f4f9f0;
    border-color: #d8e8bf;
}

.cart-item-img {
    grid-area: img;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    overflow: hidden;
    background: #f0f4e8;
    flex-shrink: 0;
    align-self: center;
}

.cart-item-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-item-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 800;
    color: #6ba324;
    background: linear-gradient(135deg, #eef7e0 0%, #e0f0c8 100%);
}

.cart-item-info {
    grid-area: info;
    min-width: 0;
    align-self: end;
}

.cart-item-name {
    font-size: 12.5px;
    font-weight: 700;
    color: #14210a;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cart-item-price {
    font-size: 11px;
    color: #94a3b8;
    font-family: ui-monospace, monospace;
    margin-top: 1px;
}

.cart-item-controls {
    grid-area: controls;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    align-self: start;
}

.qty-btn {
    width: 24px;
    height: 24px;
    border-radius: 7px;
    border: 1.5px solid #e2e8d5;
    background: #ffffff;
    color: #4a5a3d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    transition: all 0.15s;
    padding: 0;
}

.qty-btn:hover {
    border-color: #84bd33;
    color: #6ba324;
    background: #f0f8e5;
}

.qty-btn-plus {
    background: linear-gradient(135deg, #84bd33, #6ba324);
    border-color: #6ba324;
    color: #ffffff;
}

.qty-btn-plus:hover {
    background: linear-gradient(135deg, #6ba324, #558a1a);
    border-color: #558a1a;
    color: #ffffff;
    transform: scale(1.05);
}

.qty-input {
    width: 34px;
    height: 24px;
    border: 1.5px solid #e2e8d5;
    border-radius: 7px;
    background: #ffffff;
    text-align: center;
    font-family: ui-monospace, monospace;
    font-size: 12px;
    font-weight: 700;
    color: #14210a;
    outline: none;
    -moz-appearance: textfield;
    padding: 0;
}

.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.qty-input:focus {
    border-color: #84bd33;
    box-shadow: 0 0 0 3px rgba(132, 189, 51, 0.15);
}

.cart-item-subtotal {
    grid-area: subtotal;
    align-self: center;
    text-align: right;
    font-family: ui-monospace, monospace;
    font-size: 13px;
    font-weight: 800;
    color: #14210a;
    white-space: nowrap;
}

/* Item transition */
.cart-item-enter-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.cart-item-leave-active {
    transition: all 0.2s ease;
    position: absolute;
    width: 100%;
}
.cart-item-enter-from {
    opacity: 0;
    transform: translateX(-20px);
}
.cart-item-leave-to {
    opacity: 0;
    transform: translateX(20px);
}
.cart-item-move {
    transition: transform 0.3s ease;
}

/* ============ PAYMENT SECTION ============ */
.cart-payment {
    border-top: 2px dashed #eef2e6;
    padding: 16px 18px 18px;
    background: linear-gradient(180deg, #fafcf6 0%, #ffffff 100%);
    max-height: 55vh;
    overflow-y: auto;
}

.cart-payment::-webkit-scrollbar { width: 5px; }
.cart-payment::-webkit-scrollbar-thumb {
    background: #d8e8bf;
    border-radius: 10px;
}

/* Total */
.total-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding-bottom: 14px;
    margin-bottom: 14px;
    border-bottom: 1px solid #f0f4e8;
}

.total-label {
    font-size: 13px;
    font-weight: 700;
    color: #6b7a5e;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.total-value {
    font-size: 22px;
    font-weight: 800;
    color: #14210a;
    font-family: ui-monospace, monospace;
    letter-spacing: -0.8px;
}

/* Cart error */
.cart-error {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 9px 12px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    color: #dc2626;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 12px;
}

/* Cart field */
.cart-field { margin-bottom: 12px; }

.cart-field-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 6px;
}

.cart-field-label i { color: #a3b190; font-size: 13px; }

.label-optional {
    font-size: 10.5px;
    font-weight: 500;
    color: #94a3b8;
    background: #f4f7ee;
    padding: 2px 7px;
    border-radius: 100px;
    text-transform: none;
}

.cart-input {
    width: 100%;
    padding: 10px 12px;
    border: 1.5px solid #e2e8d5;
    border-radius: 10px;
    background: #ffffff;
    font-size: 13px;
    font-family: inherit;
    color: #14210a;
    transition: all 0.2s;
    outline: none;
}

.cart-input::placeholder { color: #a3b190; }

.cart-input:focus {
    border-color: #84bd33;
    box-shadow: 0 0 0 4px rgba(132, 189, 51, 0.12);
}

/* Cash input */
.cash-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.cash-prefix {
    position: absolute;
    left: 14px;
    font-size: 13px;
    font-weight: 800;
    color: #6ba324;
    pointer-events: none;
}

.cash-input {
    width: 100%;
    padding: 11px 12px 11px 40px;
    border: 1.5px solid #e2e8d5;
    border-radius: 10px;
    background: #ffffff;
    font-family: ui-monospace, monospace;
    font-size: 15px;
    font-weight: 800;
    color: #14210a;
    transition: all 0.2s;
    outline: none;
    -moz-appearance: textfield;
}

.cash-input::-webkit-outer-spin-button,
.cash-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.cash-input:focus {
    border-color: #84bd33;
    box-shadow: 0 0 0 4px rgba(132, 189, 51, 0.12);
}

/* Quick cash */
.quick-cash {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 12px;
}

.quick-cash-btn {
    flex: 1;
    min-width: fit-content;
    padding: 8px 10px;
    border-radius: 9px;
    border: 1.5px solid #e2e8d5;
    background: #ffffff;
    color: #4a5a3d;
    font-family: inherit;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}

.quick-cash-btn:hover {
    border-color: #84bd33;
    color: #6ba324;
    background: #f8fbf3;
    transform: translateY(-1px);
}

.quick-cash-btn.active {
    background: #f0f8e5;
    border-color: #84bd33;
    color: #558a1a;
}

.quick-cash-btn.is-exact {
    background: linear-gradient(135deg, #84bd33, #6ba324);
    border-color: #6ba324;
    color: #ffffff;
}

.quick-cash-btn.is-exact:hover {
    background: linear-gradient(135deg, #6ba324, #558a1a);
    color: #ffffff;
}

/* Change row */
.change-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #f0f8e5;
    border: 1px solid #d8e8bf;
    border-radius: 10px;
    margin-bottom: 14px;
    transition: all 0.2s;
}

.change-row.is-negative {
    background: #fef2f2;
    border-color: #fecaca;
}

.change-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #4a5a3d;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.change-row.is-negative .change-label { color: #dc2626; }

.change-label i { font-size: 12px; }

.change-value {
    font-family: ui-monospace, monospace;
    font-size: 15px;
    font-weight: 800;
    color: #6ba324;
    letter-spacing: -0.3px;
}

.change-row.is-negative .change-value { color: #dc2626; }

/* Pay button */
.pay-btn {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 13px;
    background: linear-gradient(135deg, #84bd33 0%, #6ba324 100%);
    color: #ffffff;
    font-family: inherit;
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.25s;
    box-shadow: 0 10px 24px -8px rgba(107, 163, 36, 0.6);
    letter-spacing: 0.2px;
    min-height: 52px;
    position: relative;
    overflow: hidden;
}

.pay-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #6ba324, #558a1a);
    opacity: 0;
    transition: opacity 0.25s;
}

.pay-btn > span {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.pay-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px -8px rgba(107, 163, 36, 0.7);
}

.pay-btn:hover:not(:disabled)::before { opacity: 1; }

.pay-btn:active:not(:disabled) { transform: translateY(0); }

.pay-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.pay-amount {
    font-family: ui-monospace, monospace;
    font-size: 13px;
    padding: 3px 9px;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 7px;
    font-weight: 800;
}

.pay-spinner {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mini-spinner {
    width: 18px;
    height: 18px;
    border: 2.5px solid rgba(255, 255, 255, 0.3);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ============ RESPONSIVE ============ */
@media (max-width: 900px) {
    .cart-card {
        position: static;
        max-height: none;
    }
    .cart-payment { max-height: none; }
}

@media (max-width: 640px) {
    .pos-title { font-size: 22px; }
    .menu-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 10px;
    }
    .menu-card-body { padding: 8px 10px 10px; }
    .menu-card-name { font-size: 12px; }
    .menu-card-price { font-size: 12px; }
}
</style>