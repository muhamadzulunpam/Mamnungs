<script setup>
import { onBeforeUnmount, onMounted, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';

const props = defineProps({
    order: Object,
    qrUrl: String,
    expiresAt: String,
});

const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0);

const status = ref(props.order.status);
const secondsLeft = ref(null);
const showCancelModal = ref(false);
let pollTimer;
let countdownTimer;

// ===== COUNTDOWN =====
const startCountdown = () => {
    if (!props.expiresAt) return;
    const target = new Date(props.expiresAt).getTime();
    const tick = () => {
        const diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
        secondsLeft.value = diff;
        if (diff === 0) clearInterval(countdownTimer);
    };
    tick();
    countdownTimer = setInterval(tick, 1000);
};

const countdownText = computed(() => {
    if (secondsLeft.value === null) return null;
    const m = Math.floor(secondsLeft.value / 60);
    const s = secondsLeft.value % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
});

const isUrgent = computed(
    () => secondsLeft.value !== null && secondsLeft.value < 60
);

// ===== CEK STATUS =====
const cek = async () => {
    try {
        const res = await fetch(`/kasir/pembayaran/${props.order.id}/status`, {
            headers: { Accept: 'application/json' },
        });
        const json = await res.json();
        status.value = json.status;

        if (json.status === 'PAID') {
            clearInterval(pollTimer);
            clearInterval(countdownTimer);
            setTimeout(() => {
                router.visit(`/transaksi/${props.order.id}/struk`);
            }, 1000);
        } else if (json.status !== 'PENDING') {
            clearInterval(pollTimer);
            clearInterval(countdownTimer);
        }
    } catch (e) {
        /* coba lagi di putaran berikutnya */
    }
};

// ===== BATAL =====
const askCancel = () => {
    showCancelModal.value = true;
};

const confirmCancel = () => {
    showCancelModal.value = false;
    router.post(`/kasir/pembayaran/${props.order.id}/batal`);
};

// ===== LIFECYCLE =====
onMounted(() => {
    pollTimer = setInterval(cek, 3000);
    startCountdown();
});

onBeforeUnmount(() => {
    clearInterval(pollTimer);
    clearInterval(countdownTimer);
});
</script>

<template>
    <DashboardLayout>
        <div class="qris-page">
            <!-- ============ STATUS: PENDING (QR BESAR) ============ -->
            <template v-if="status === 'PENDING'">
                <div class="qris-layout">
                    <!-- KIRI: INFO -->
                    <div class="info-side">
                        <div class="info-badge">
                            <span class="dot-pulse"></span>
                            Menunggu Pembayaran
                        </div>

                        <div class="info-invoice">
                            <i class="bi bi-receipt"></i>
                            {{ order.invoice_number }}
                        </div>

                        <div class="info-label">Total Tagihan</div>
                        <div class="info-total">{{ rupiah(order.total) }}</div>

                        <div v-if="countdownText" :class="['info-timer', { 'is-urgent': isUrgent }]">
                            <i class="bi bi-clock-history"></i>
                            <span>Berlaku {{ countdownText }}</span>
                        </div>

                        <div class="info-instruction">
                            <div class="instruction-step">
                                <span class="step-number">1</span>
                                <span class="step-text">Buka aplikasi <strong>e-wallet</strong> atau <strong>m-banking</strong></span>
                            </div>
                            <div class="instruction-step">
                                <span class="step-number">2</span>
                                <span class="step-text">Pilih menu <strong>Scan QRIS</strong></span>
                            </div>
                            <div class="instruction-step">
                                <span class="step-number">3</span>
                                <span class="step-text">Arahkan kamera ke QR code di samping</span>
                            </div>
                        </div>

                        <button class="btn-cancel-payment" @click="askCancel">
                            <i class="bi bi-x-circle"></i>
                            Batalkan Pembayaran
                        </button>
                    </div>

                    <!-- KANAN: QR BESAR -->
                    <div class="qr-side">
                        <div class="qr-frame">
                            <!-- Header QR -->
                            <div class="qr-brand">
                                <div class="qr-brand-logo">
                                    <i class="bi bi-cup-straw"></i>
                                </div>
                                <div class="qr-brand-text">
                                    <div class="qr-brand-name">Mamnungs</div>
                                    <div class="qr-brand-sub">Es Teler Segar</div>
                                </div>
                            </div>

                            <!-- QR Image BESAR -->
                            <div class="qr-big-wrap">
                                <img
                                    v-if="qrUrl"
                                    :src="qrUrl"
                                    alt="QRIS Payment"
                                    class="qr-big-image"
                                />
                                <div v-else class="qr-big-empty">
                                    <i class="bi bi-qr-code"></i>
                                    <div class="qr-empty-text">QR belum tersedia</div>
                                </div>

                                <!-- Animasi scan line -->
                                <div v-if="qrUrl" class="qr-scan-line"></div>
                            </div>

                            <!-- Footer QR -->
                            <div class="qr-footer">
                                <div class="qr-footer-logo">
                                    <span class="qris-tag">QRIS</span>
                                    <span class="qris-by">didukung oleh</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tip di bawah QR -->
                        <div class="qr-tip">
                            <i class="bi bi-lightbulb"></i>
                            Pastikan nominal di HP pelanggan <strong>sesuai</strong> dengan total tagihan
                        </div>
                    </div>
                </div>
            </template>

            <!-- ============ STATUS: PAID ============ -->
            <template v-else-if="status === 'PAID'">
                <div class="result-card">
                    <div class="result-icon result-icon-success">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <div class="result-title">Pembayaran Berhasil!</div>
                    <div class="result-text">
                        Transaksi <strong>{{ order.invoice_number }}</strong> telah lunas.<br />
                        Mengalihkan ke struk...
                    </div>
                    <div class="result-loader">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </template>

            <!-- ============ STATUS: EXPIRED / CANCELLED ============ -->
            <template v-else>
                <div class="result-card">
                    <div
                        :class="[
                            'result-icon',
                            status === 'EXPIRED' ? 'result-icon-warning' : 'result-icon-danger',
                        ]"
                    >
                        <i :class="status === 'EXPIRED' ? 'bi bi-clock-history' : 'bi bi-x-lg'"></i>
                    </div>
                    <div class="result-title">
                        {{ status === 'EXPIRED' ? 'Pembayaran Kedaluwarsa' : 'Pembayaran Dibatalkan' }}
                    </div>
                    <div class="result-text">
                        {{ status === 'EXPIRED'
                            ? 'Waktu pembayaran sudah habis. Buat transaksi baru untuk melanjutkan.'
                            : 'Transaksi ini telah dibatalkan. Buat transaksi baru untuk memulai lagi.'
                        }}
                    </div>
                    <a href="/kasir" class="result-btn">
                        <i class="bi bi-arrow-left"></i>
                        Kembali ke Kasir
                    </a>
                </div>
            </template>
        </div>

        <!-- ============ CANCEL CONFIRM MODAL ============ -->
        <transition name="modal">
            <div
                v-if="showCancelModal"
                class="modal-overlay"
                @click.self="showCancelModal = false"
            >
                <div class="modal-card">
                    <div class="modal-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h3 class="modal-title">Batalkan Pembayaran?</h3>
                    <p class="modal-message">
                        Transaksi <strong>"{{ order.invoice_number }}"</strong> akan dibatalkan.
                        Pelanggan harus scan ulang QR baru.
                    </p>
                    <div class="modal-actions">
                        <button
                            class="modal-btn modal-btn-cancel"
                            @click="showCancelModal = false"
                        >
                            Batal
                        </button>
                        <button
                            class="modal-btn modal-btn-confirm"
                            @click="confirmCancel"
                        >
                            Ya, Batalkan
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </DashboardLayout>
</template>

<style scoped>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');

/* ============ PAGE ============ */
.qris-page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px 0 40px;
}

/* ============ QRIS LAYOUT (2 KOLOM) ============ */
.qris-layout {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 28px;
    align-items: center;
}

@media (max-width: 900px) {
    .qris-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

/* ============ SISI KIRI: INFO ============ */
.info-side {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    align-self: flex-start;
    padding: 7px 14px;
    background: #fef3c7;
    border: 1.5px solid #fde68a;
    border-radius: 100px;
    font-size: 12.5px;
    font-weight: 700;
    color: #b45309;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.dot-pulse {
    width: 8px;
    height: 8px;
    background: #f59e0b;
    border-radius: 50%;
    animation: pulse-amber 1.8s ease-in-out infinite;
}

@keyframes pulse-amber {
    0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.5); }
    50% { box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
}

.info-invoice {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    align-self: flex-start;
    padding: 6px 12px;
    background: #f0f8e5;
    color: #558a1a;
    border-radius: 100px;
    font-family: ui-monospace, monospace;
    font-size: 12.5px;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.info-invoice i { font-size: 12px; }

.info-label {
    font-size: 12px;
    font-weight: 700;
    color: #6b7a5e;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-top: 4px;
}

.info-total {
    font-size: 42px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -1.5px;
    font-family: ui-monospace, monospace;
    line-height: 1;
    margin-bottom: 4px;
}

.info-timer {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    align-self: flex-start;
    padding: 6px 12px;
    background: #f8fbf3;
    border: 1px solid #e2e8d5;
    border-radius: 100px;
    font-family: ui-monospace, monospace;
    font-size: 13px;
    font-weight: 700;
    color: #4a5a3d;
    transition: all 0.3s;
}

.info-timer i { font-size: 13px; }

.info-timer.is-urgent {
    background: #fee2e2;
    border-color: #fecaca;
    color: #b91c1c;
    animation: pulse-urgent 1.5s ease-in-out infinite;
}

@keyframes pulse-urgent {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.3); }
    50% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
}

/* Instruction steps */
.info-instruction {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 18px;
    background: #fafcf6;
    border: 1px solid #eef2e6;
    border-radius: 16px;
    margin-top: 8px;
}

.instruction-step {
    display: flex;
    align-items: center;
    gap: 12px;
}

.step-number {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: linear-gradient(135deg, #84bd33, #6ba324);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12.5px;
    font-weight: 800;
    flex-shrink: 0;
}

.step-text {
    font-size: 13.5px;
    color: #4a5a3d;
    line-height: 1.4;
}

.step-text strong { color: #14210a; font-weight: 700; }

/* Cancel button */
.btn-cancel-payment {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    align-self: flex-start;
    padding: 12px 20px;
    background: #ffffff;
    border: 1.5px solid #fecaca;
    border-radius: 12px;
    color: #dc2626;
    font-family: inherit;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 8px;
}

.btn-cancel-payment:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px -8px rgba(239, 68, 68, 0.6);
}

.btn-cancel-payment i { font-size: 15px; }

/* ============ SISI KANAN: QR BESAR ============ */
.qr-side {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

.qr-frame {
    width: 100%;
    max-width: 460px;
    background: #ffffff;
    border: 3px solid #14210a;
    border-radius: 28px;
    padding: 24px;
    box-shadow:
        0 24px 60px -20px rgba(20, 33, 10, 0.3),
        0 0 0 8px #f4f9f0;
    position: relative;
}

/* QR Header / Brand */
.qr-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding-bottom: 18px;
    border-bottom: 2px dashed #e2e8d5;
    margin-bottom: 20px;
}

.qr-brand-logo {
    width: 44px;
    height: 44px;
    border-radius: 13px;
    background: linear-gradient(135deg, #84bd33, #6ba324);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
    box-shadow: 0 6px 16px -6px rgba(107, 163, 36, 0.6);
}

.qr-brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}

.qr-brand-name {
    font-size: 18px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.5px;
}

.qr-brand-sub {
    font-size: 11.5px;
    color: #6ba324;
    font-weight: 600;
    margin-top: 2px;
    letter-spacing: 0.3px;
}

/* QR Image BESAR */
.qr-big-wrap {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    max-width: 380px;
    margin: 0 auto;
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qr-big-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.qr-big-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #cbd5b5;
    text-align: center;
}

.qr-big-empty i { font-size: 64px; }

.qr-empty-text {
    font-size: 13px;
    color: #94a3b8;
    font-weight: 600;
}

/* Animated scan line */
.qr-scan-line {
    position: absolute;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg,
        transparent 0%,
        rgba(132, 189, 51, 0.8) 20%,
        #84bd33 50%,
        rgba(132, 189, 51, 0.8) 80%,
        transparent 100%
    );
    box-shadow: 0 0 12px rgba(132, 189, 51, 0.6);
    animation: scan-move 2.5s ease-in-out infinite;
    pointer-events: none;
}

@keyframes scan-move {
    0% { top: 0%; opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { top: 100%; opacity: 0; }
}

/* QR Footer */
.qr-footer {
    padding-top: 18px;
    margin-top: 20px;
    border-top: 2px dashed #e2e8d5;
    display: flex;
    justify-content: center;
}

.qr-footer-logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.qris-tag {
    display: inline-block;
    padding: 5px 18px;
    background: #14210a;
    color: #ffffff;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 900;
    letter-spacing: 3px;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.qris-by {
    font-size: 10px;
    color: #94a3b8;
    font-weight: 600;
    letter-spacing: 0.4px;
    text-transform: uppercase;
}

/* Tip di bawah QR */
.qr-tip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;
    background: #fef3c7;
    border: 1px solid #fde68a;
    border-radius: 12px;
    font-size: 12.5px;
    color: #78350f;
    max-width: 460px;
    line-height: 1.4;
}

.qr-tip i {
    color: #f59e0b;
    font-size: 16px;
    flex-shrink: 0;
}

.qr-tip strong { color: #451a03; font-weight: 800; }

/* ============ RESULT CARD (PAID / EXPIRED / CANCEL) ============ */
.result-card {
    max-width: 480px;
    margin: 60px auto;
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 24px;
    padding: 40px 32px 36px;
    box-shadow: 0 20px 50px -20px rgba(20, 33, 10, 0.2);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.result-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #84bd33, #6ba324, #558a1a);
}

.result-icon {
    width: 96px;
    height: 96px;
    border-radius: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 44px;
    margin: 0 auto 24px;
    position: relative;
    animation: pop 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.result-icon::before {
    content: '';
    position: absolute;
    inset: -10px;
    border-radius: 36px;
    background: inherit;
    opacity: 0.3;
    z-index: -1;
    animation: pulse-ring 2s ease-out infinite;
}

@keyframes pop {
    0% { transform: scale(0.4); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes pulse-ring {
    0% { transform: scale(0.9); opacity: 0.4; }
    100% { transform: scale(1.4); opacity: 0; }
}

.result-icon-success {
    background: #dcfce7;
    color: #15803d;
}

.result-icon-warning {
    background: #fef3c7;
    color: #d97706;
}

.result-icon-danger {
    background: #fee2e2;
    color: #dc2626;
}

.result-title {
    font-size: 24px;
    font-weight: 800;
    color: #14210a;
    margin-bottom: 10px;
    letter-spacing: -0.5px;
}

.result-text {
    font-size: 14px;
    color: #6b7a5e;
    line-height: 1.6;
    margin-bottom: 28px;
}

.result-text strong { color: #14210a; font-weight: 700; }

.result-loader {
    display: inline-flex;
    gap: 8px;
    margin-top: 4px;
}

.result-loader span {
    width: 10px;
    height: 10px;
    background: #84bd33;
    border-radius: 50%;
    animation: dot-bounce 1.4s ease-in-out infinite;
}

.result-loader span:nth-child(2) { animation-delay: 0.15s; }
.result-loader span:nth-child(3) { animation-delay: 0.3s; }

@keyframes dot-bounce {
    0%, 80%, 100% { transform: translateY(0); opacity: 0.4; }
    40% { transform: translateY(-10px); opacity: 1; }
}

.result-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 28px;
    background: linear-gradient(135deg, #84bd33, #6ba324);
    color: #ffffff;
    text-decoration: none;
    border: none;
    border-radius: 12px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 8px 20px -8px rgba(107, 163, 36, 0.6);
}

.result-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -8px rgba(107, 163, 36, 0.7);
    color: #ffffff;
}

/* ============ MODAL ============ */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(20, 33, 10, 0.55);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 1000;
}

.modal-card {
    position: relative;
    width: 100%;
    max-width: 420px;
    background: #ffffff;
    border-radius: 22px;
    padding: 28px 24px 22px;
    box-shadow: 0 30px 70px -20px rgba(20, 33, 10, 0.35);
    text-align: center;
}

.modal-icon {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: #fef3c7;
    color: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin: 0 auto 18px;
    animation: pop 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-title {
    font-size: 18px;
    font-weight: 800;
    color: #14210a;
    margin: 0 0 8px;
    letter-spacing: -0.3px;
}

.modal-message {
    font-size: 13px;
    color: #6b7a5e;
    line-height: 1.6;
    margin: 0 0 22px;
}

.modal-message strong { color: #14210a; font-weight: 700; }

.modal-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.modal-btn {
    padding: 12px 16px;
    border-radius: 12px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: 1.5px solid transparent;
    transition: all 0.2s;
    min-height: 44px;
}

.modal-btn-cancel {
    background: #f4f7ee;
    border-color: #e2e8d5;
    color: #4a5a3d;
}
.modal-btn-cancel:hover {
    background: #e8efdb;
    color: #14210a;
}

.modal-btn-confirm {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #ffffff;
    box-shadow: 0 8px 20px -8px rgba(220, 38, 38, 0.6);
}
.modal-btn-confirm:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px -8px rgba(220, 38, 38, 0.7);
}

/* Modal animation */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.22s ease;
}
.modal-enter-active .modal-card,
.modal-leave-active .modal-card {
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.22s;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from .modal-card,
.modal-leave-to .modal-card {
    transform: scale(0.9) translateY(20px);
    opacity: 0;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 900px) {
    .info-total { font-size: 32px; }
    .qr-frame { max-width: 100%; padding: 20px; }
    .qr-big-wrap { max-width: 100%; }
}

@media (max-width: 480px) {
    .qris-page { padding: 12px 0 30px; }
    .info-total { font-size: 28px; }
    .info-instruction { padding: 14px; }
    .instruction-step { gap: 10px; }
    .step-text { font-size: 12.5px; }
    .btn-cancel-payment { width: 100%; }
    .qr-frame { padding: 16px; border-radius: 22px; }
    .qr-brand-logo { width: 38px; height: 38px; font-size: 18px; }
    .qr-brand-name { font-size: 15px; }
    .qr-brand-sub { font-size: 10.5px; }
    .qr-tip { font-size: 11.5px; padding: 10px 14px; }
    .result-card { padding: 32px 22px 28px; margin: 30px auto; }
    .result-title { font-size: 20px; }
    .result-icon { width: 80px; height: 80px; font-size: 38px; }
}
</style>