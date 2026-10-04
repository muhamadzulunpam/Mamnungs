<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    order: Object,
    store: Object,
});

const page = usePage();

// ===== HELPERS =====
const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0);

const waktu = new Date(props.order.created_at).toLocaleString('id-ID', {
    timeZone: 'Asia/Jakarta',
    dateStyle: 'medium',
    timeStyle: 'short',
});

const tanggalCetak = new Date().toLocaleString('id-ID', {
    timeZone: 'Asia/Jakarta',
    dateStyle: 'long',
    timeStyle: 'short',
});

const isKasir = computed(() => page.props.auth.user.role === 'kasir');

// ===== METODE =====
const metodeLabel = computed(() => {
    const m = String(props.order.payment_method || '').toUpperCase();
    const map = {
        CASH: 'Tunai',
        QRIS: 'QRIS',
        DEBIT: 'Kartu Debit',
        TRANSFER: 'Transfer Bank',
    };
    return map[m] || props.order.payment_method || '-';
});

const isCash = computed(
    () => String(props.order.payment?.payment_method || '').toUpperCase() === 'CASH'
);

// ===== TOTAL ITEMS =====
const totalItems = computed(() =>
    (props.order.items || []).reduce((s, i) => s + (i.quantity || 0), 0)
);

// ===== CETAK =====
const cetak = () => window.print();
</script>

<template>
    <div class="struk-page">
        <!-- ============ ACTION BAR (no print) ============ -->
        <div class="action-bar no-print">
            <div class="action-bar-inner">
                <div class="action-info">
                    <div class="action-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="action-title">Struk Transaksi</div>
                        <div class="action-sub">{{ order.invoice_number }}</div>
                    </div>
                </div>

                <div class="action-buttons">
                    <Link
                        v-if="isKasir"
                        href="/kasir"
                        class="btn-action btn-primary-action"
                    >
                        <i class="bi bi-plus-lg"></i>
                        <span>Transaksi Baru</span>
                    </Link>
                    <Link href="/transaksi" class="btn-action btn-ghost-action">
                        <i class="bi bi-clock-history"></i>
                        <span>Riwayat</span>
                    </Link>
                    <button class="btn-action btn-print" @click="cetak">
                        <i class="bi bi-printer"></i>
                        <span>Cetak Struk</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ============ STRUK ============ -->
        <div class="struk-wrapper">
            <div class="struk">
                <!-- ===== HEADER / STORE ===== -->
                <div class="struk-header">
                    <div class="store-logo">
                        <i class="bi bi-cup-straw"></i>
                    </div>
                    <div class="store-name">{{ store.name }}</div>
                    <div v-if="store.address" class="store-address">
                        {{ store.address }}
                    </div>
                    <div v-if="store.phone" class="store-phone">
                        <i class="bi bi-telephone-fill"></i> {{ store.phone }}
                    </div>
                </div>

                <div class="divider-solid"></div>

                <!-- ===== TRANSACTION INFO ===== -->
                <div class="info-block">
                    <div class="info-row">
                        <span class="info-label">No. Invoice</span>
                        <span class="info-value info-value-strong">
                            {{ order.invoice_number }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Waktu</span>
                        <span class="info-value">{{ waktu }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kasir</span>
                        <span class="info-value">{{ order.user?.name || '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Metode</span>
                        <span class="info-value">{{ metodeLabel }}</span>
                    </div>
                </div>

                <div class="divider-dash"></div>

                <!-- ===== ITEMS ===== -->
                <div class="items-block">
                    <div class="items-header">
                        <span class="items-header-title">Item</span>
                        <span class="items-header-qty">Qty</span>
                        <span class="items-header-total">Total</span>
                    </div>

                    <div
                        v-for="i in order.items"
                        :key="i.id"
                        class="item-row"
                    >
                        <div class="item-name">{{ i.product_name }}</div>
                        <div class="item-detail">
                            <span class="item-qty">
                                {{ i.quantity }} × {{ rupiah(i.price) }}
                            </span>
                            <span class="item-subtotal">
                                {{ rupiah(i.subtotal) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="divider-dash"></div>

                <!-- ===== SUMMARY ===== -->
                <div class="summary-block">
                    <div v-if="order.discount > 0" class="summary-row">
                        <span class="summary-label">Diskon</span>
                        <span class="summary-value summary-discount">
                            −{{ rupiah(order.discount) }}
                        </span>
                    </div>

                    <div class="summary-row summary-row-total">
                        <span class="summary-label-total">TOTAL</span>
                        <span class="summary-value-total">
                            {{ rupiah(order.total) }}
                        </span>
                    </div>
                </div>

                <!-- ===== PAYMENT DETAIL (CASH) ===== -->
                <template v-if="isCash">
                    <div class="divider-dash"></div>

                    <div class="payment-block">
                        <div class="payment-row">
                            <span class="payment-label">Tunai</span>
                            <span class="payment-value">
                                {{ rupiah(order.payment.amount_received) }}
                            </span>
                        </div>
                        <div class="payment-row">
                            <span class="payment-label">Kembali</span>
                            <span class="payment-value payment-change">
                                {{ rupiah(order.payment.change_amount) }}
                            </span>
                        </div>
                    </div>
                </template>

                <!-- ===== NOTES ===== -->
                <template v-if="order.notes">
                    <div class="divider-dash"></div>

                    <div class="notes-block">
                        <div class="notes-label">
                            <i class="bi bi-chat-left-text"></i> Catatan
                        </div>
                        <div class="notes-text">{{ order.notes }}</div>
                    </div>
                </template>

                <div class="divider-dash"></div>

                <!-- ===== FOOTER ===== -->
                <div class="struk-footer">
                    <div class="thanks-title">Terima Kasih 🙏</div>
                    <div class="thanks-text">
                        Selamat menikmati es teler kami!
                    </div>
                    <div class="thanks-sub">Semoga harimu menyenangkan ✨</div>
                </div>

                <div class="divider-solid"></div>

                <!-- ===== META FOOTER ===== -->
                <div class="meta-footer">
                    <div class="meta-row">
                        <span>Dicetak</span>
                        <span>{{ tanggalCetak }}</span>
                    </div>
                    <div class="meta-row">
                        <span>Total item</span>
                        <span>{{ totalItems }} item</span>
                    </div>
                    <div class="meta-row">
                        <span>Struk ID</span>
                        <span>#{{ order.id }}</span>
                    </div>
                </div>

                <!-- ===== BARCODE (opsional) ===== -->
                <div class="struk-barcode">
                    <div class="barcode-bars">
                        <span v-for="n in 40" :key="n" :style="{ height: (Math.random() > 0.5 ? '20px' : '18px') }"></span>
                    </div>
                    <div class="barcode-text">{{ order.invoice_number }}</div>
                </div>
            </div>

            <!-- Tombol cetak bawah (mobile) -->
            <div class="mobile-print no-print">
                <button class="btn-print-mobile" @click="cetak">
                    <i class="bi bi-printer"></i>
                    Cetak Struk
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');

/* ============ PAGE ============ */
.struk-page {
    min-height: 100vh;
    background: #f4f9f0;
    background-image:
        radial-gradient(at 15% 15%, rgba(132, 189, 51, 0.12) 0, transparent 45%),
        radial-gradient(at 85% 85%, rgba(244, 168, 42, 0.1) 0, transparent 45%);
    padding: 24px 12px 60px;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
}

/* ============ ACTION BAR ============ */
.action-bar {
    max-width: 500px;
    margin: 0 auto 20px;
}

.action-bar-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 16px;
    box-shadow: 0 4px 20px -12px rgba(20, 33, 10, 0.15);
    flex-wrap: wrap;
}

.action-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.action-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, #eef7e0, #e0f0c8);
    color: #6ba324;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.action-title {
    font-size: 14px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.2px;
}

.action-sub {
    font-size: 11.5px;
    color: #94a3b8;
    font-family: ui-monospace, monospace;
    margin-top: 1px;
}

.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    border: 1.5px solid transparent;
    transition: all 0.18s;
    white-space: nowrap;
}

.btn-action i { font-size: 13px; }

.btn-primary-action {
    background: linear-gradient(135deg, #84bd33, #6ba324);
    color: #ffffff;
    box-shadow: 0 6px 16px -6px rgba(107, 163, 36, 0.6);
}
.btn-primary-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 20px -6px rgba(107, 163, 36, 0.7);
    color: #ffffff;
}

.btn-ghost-action {
    background: #ffffff;
    border-color: #e2e8d5;
    color: #4a5a3d;
}
.btn-ghost-action:hover {
    border-color: #84bd33;
    color: #6ba324;
    background: #f8fbf3;
}

.btn-print {
    background: #14210a;
    color: #ffffff;
    border-color: #14210a;
}
.btn-print:hover {
    background: #2a3d1a;
    border-color: #2a3d1a;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px -8px rgba(20, 33, 10, 0.5);
}

/* ============ STRUK WRAPPER ============ */
.struk-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

/* ============ STRUK ============ */
.struk {
    width: 340px;
    max-width: 100%;
    background: #ffffff;
    padding: 24px 20px 20px;
    font-family: 'Courier New', ui-monospace, monospace;
    font-size: 12.5px;
    color: #14210a;
    line-height: 1.5;
    border-radius: 4px;
    box-shadow:
        0 20px 50px -20px rgba(20, 33, 10, 0.25),
        0 0 0 1px rgba(20, 33, 10, 0.04);
    position: relative;
}

/* Efek gerigi di atas dan bawah seperti struk thermal */
.struk::before,
.struk::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    height: 8px;
    background-image:
        radial-gradient(circle, transparent 50%, #ffffff 50%);
    background-size: 12px 12px;
    background-repeat: repeat-x;
}

.struk::before {
    top: -8px;
    background-position: 0 4px;
}

.struk::after {
    bottom: -8px;
    background-position: 0 -4px;
    background-image:
        radial-gradient(circle, transparent 50%, #ffffff 50%);
    background-size: 12px 12px;
    transform: rotate(180deg);
}

/* ============ HEADER ============ */
.struk-header {
    text-align: center;
    margin-bottom: 14px;
}

.store-logo {
    width: 52px;
    height: 52px;
    margin: 0 auto 10px;
    border-radius: 14px;
    background: linear-gradient(135deg, #84bd33, #6ba324);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    box-shadow: 0 6px 16px -6px rgba(107, 163, 36, 0.5);
}

.store-name {
    font-size: 17px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.5px;
    margin-bottom: 4px;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.store-address {
    font-size: 11px;
    color: #4a5a3d;
    line-height: 1.4;
    max-width: 240px;
    margin: 0 auto;
}

.store-phone {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10.5px;
    color: #6b7a5e;
    margin-top: 4px;
}

.store-phone i { font-size: 9px; color: #84bd33; }

/* ============ DIVIDERS ============ */
.divider-solid {
    height: 2px;
    background: #14210a;
    margin: 12px 0;
    border-radius: 2px;
}

.divider-dash {
    height: 1px;
    margin: 10px 0;
    background-image: linear-gradient(90deg, #94a3b8 50%, transparent 50%);
    background-size: 8px 1px;
    background-repeat: repeat-x;
}

/* ============ INFO BLOCK ============ */
.info-block { margin-bottom: 4px; }

.info-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 4px;
    font-size: 12px;
}

.info-label {
    color: #6b7a5e;
    flex-shrink: 0;
}

.info-value {
    color: #14210a;
    text-align: right;
    word-break: break-word;
}

.info-value-strong {
    font-weight: 800;
    letter-spacing: 0.5px;
}

/* ============ ITEMS ============ */
.items-block { margin-bottom: 4px; }

.items-header {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 8px;
    font-size: 10.5px;
    font-weight: 800;
    color: #6b7a5e;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding-bottom: 6px;
    border-bottom: 1px dashed #cbd5b5;
    margin-bottom: 8px;
}

.items-header-title { text-align: left; }
.items-header-qty { display: none; }
.items-header-total { text-align: right; }

.item-row {
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px dotted #e2e8d5;
}

.item-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
    margin-bottom: 0;
}

.item-name {
    font-size: 12.5px;
    font-weight: 700;
    color: #14210a;
    margin-bottom: 2px;
    line-height: 1.35;
}

.item-detail {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 11.5px;
    color: #4a5a3d;
}

.item-qty { white-space: nowrap; }

.item-subtotal {
    font-weight: 800;
    color: #14210a;
    white-space: nowrap;
    font-family: ui-monospace, monospace;
}

/* ============ SUMMARY ============ */
.summary-block { margin-bottom: 4px; }

.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 12px;
    margin-bottom: 4px;
}

.summary-label {
    color: #6b7a5e;
}

.summary-value {
    color: #14210a;
    font-weight: 600;
}

.summary-discount {
    color: #dc2626;
    font-weight: 700;
}

.summary-row-total {
    margin-top: 8px;
    padding-top: 10px;
    border-top: 2px solid #14210a;
    font-size: 15px;
}

.summary-label-total {
    font-weight: 800;
    letter-spacing: 1px;
    color: #14210a;
}

.summary-value-total {
    font-weight: 800;
    color: #14210a;
    font-size: 17px;
    letter-spacing: -0.3px;
}

/* ============ PAYMENT ============ */
.payment-block { margin-bottom: 4px; }

.payment-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 12px;
    margin-bottom: 4px;
}

.payment-label { color: #6b7a5e; }

.payment-value {
    font-weight: 700;
    color: #14210a;
    font-family: ui-monospace, monospace;
}

.payment-change {
    color: #15803d;
}

/* ============ NOTES ============ */
.notes-block {
    background: #fafcf6;
    border-left: 3px solid #84bd33;
    padding: 8px 10px;
    border-radius: 4px;
    margin-bottom: 4px;
}

.notes-label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 10.5px;
    font-weight: 800;
    color: #6ba324;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 3px;
}

.notes-label i { font-size: 10px; }

.notes-text {
    font-size: 11.5px;
    color: #14210a;
    font-style: italic;
    line-height: 1.4;
}

/* ============ FOOTER ============ */
.struk-footer {
    text-align: center;
    padding: 12px 0 4px;
}

.thanks-title {
    font-size: 14px;
    font-weight: 800;
    color: #14210a;
    margin-bottom: 4px;
    letter-spacing: -0.3px;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.thanks-text {
    font-size: 11.5px;
    color: #4a5a3d;
    margin-bottom: 2px;
    line-height: 1.4;
}

.thanks-sub {
    font-size: 10.5px;
    color: #94a3b8;
    font-style: italic;
}

/* ============ META FOOTER ============ */
.meta-footer {
    margin-top: 10px;
    font-size: 10px;
    color: #94a3b8;
}

.meta-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 2px;
}

/* ============ BARCODE ============ */
.struk-barcode {
    margin-top: 16px;
    padding-top: 12px;
    text-align: center;
}

.barcode-bars {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    gap: 2px;
    height: 24px;
    margin-bottom: 4px;
}

.barcode-bars span {
    display: inline-block;
    width: 1.5px;
    background: #14210a;
    border-radius: 1px;
}

.barcode-bars span:nth-child(3n) { width: 3px; }
.barcode-bars span:nth-child(5n) { height: 24px !important; }

.barcode-text {
    font-family: ui-monospace, monospace;
    font-size: 10px;
    color: #4a5a3d;
    letter-spacing: 2px;
    font-weight: 700;
}

/* ============ MOBILE PRINT ============ */
.mobile-print {
    display: none;
    width: 100%;
    max-width: 340px;
}

.btn-print-mobile {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background: linear-gradient(135deg, #84bd33, #6ba324);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 20px -8px rgba(107, 163, 36, 0.6);
    transition: all 0.2s;
}

.btn-print-mobile:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -8px rgba(107, 163, 36, 0.7);
}

/* ============ RESPONSIVE ============ */
@media (max-width: 640px) {
    .struk-page { padding: 16px 10px 80px; }
    .action-bar-inner { padding: 12px; }
    .action-buttons { width: 100%; }
    .btn-action { flex: 1; justify-content: center; }
    .btn-action span { font-size: 11.5px; }
    .mobile-print { display: block; }
}

/* ============ PRINT ============ */
@media print {
    .no-print { display: none !important; }

    .struk-page {
        background: #ffffff;
        padding: 0;
        min-height: 0;
    }

    .struk-wrapper { gap: 0; }

    .struk {
        width: 100%;
        max-width: 58mm;
        margin: 0 auto;
        padding: 3mm 2mm;
        box-shadow: none;
        border-radius: 0;
        font-size: 10.5px;
        line-height: 1.35;
    }

    .struk::before,
    .struk::after { display: none; }

    .store-logo {
        width: 36px;
        height: 36px;
        font-size: 18px;
        margin-bottom: 6px;
    }

    .store-name { font-size: 13px; }
    .store-address,
    .store-phone { font-size: 9px; }

    .divider-solid { height: 1.5px; margin: 6px 0; }
    .divider-dash { margin: 5px 0; }

    .info-row,
    .summary-row,
    .payment-row { font-size: 10px; margin-bottom: 2px; }

    .item-row { margin-bottom: 5px; padding-bottom: 4px; }
    .item-name { font-size: 10.5px; }
    .item-detail { font-size: 10px; }

    .summary-row-total { font-size: 12px; padding-top: 6px; margin-top: 5px; }
    .summary-value-total { font-size: 13px; }

    .struk-footer { padding: 6px 0 2px; }
    .thanks-title { font-size: 11px; }
    .thanks-text { font-size: 9.5px; }
    .thanks-sub { font-size: 9px; }

    .meta-footer { font-size: 8.5px; }
    .struk-barcode { margin-top: 8px; padding-top: 6px; }
    .barcode-bars { height: 18px; }
    .barcode-text { font-size: 8.5px; }

    @page {
        margin: 0;
        size: 58mm auto;
    }
}
</style>