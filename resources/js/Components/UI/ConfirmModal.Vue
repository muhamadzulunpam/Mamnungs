<script setup>
import { watch, onUnmounted } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Konfirmasi' },
    message: { type: String, default: 'Apakah Anda yakin?' },
    confirmText: { type: String, default: 'Ya, Lanjutkan' },
    cancelText: { type: String, default: 'Batal' },
    variant: { type: String, default: 'danger' }, // danger | warning | success | primary
    processing: { type: Boolean, default: false },
    closeOnBackdrop: { type: Boolean, default: true },
});

const emit = defineEmits(['confirm', 'cancel']);

const icons = {
    danger:  { icon: 'bi-trash3',              bg: '#fee2e2', color: '#dc2626' },
    warning: { icon: 'bi-exclamation-triangle',bg: '#fef3c7', color: '#d97706' },
    success: { icon: 'bi-check-lg',            bg: '#dcfce7', color: '#15803d' },
    primary: { icon: 'bi-question-lg',         bg: '#dbeafe', color: '#1d4ed8' },
};

const current = () => icons[props.variant] || icons.danger;

// ===== ACTION =====
const onCancel = () => {
    if (props.processing) return;
    emit('cancel');
};

const onConfirm = () => {
    if (props.processing) return;
    emit('confirm');
};

const onBackdrop = () => {
    if (props.closeOnBackdrop) onCancel();
};

// ===== KEYBOARD: ESC =====
const onKey = (e) => {
    if (e.key === 'Escape' && props.show) onCancel();
};

// ===== BODY SCROLL LOCK + ESC LISTENER =====
watch(
    () => props.show,
    (val) => {
        if (val) {
            document.body.style.overflow = 'hidden';
            window.addEventListener('keydown', onKey);
        } else {
            document.body.style.overflow = '';
            window.removeEventListener('keydown', onKey);
        }
    }
);

onUnmounted(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKey);
});
</script>

<template>
    <transition name="modal">
        <div
            v-if="show"
            class="modal-overlay"
            @click.self="onBackdrop"
        >
            <div class="modal-card">
                <button
                    class="modal-close"
                    aria-label="Tutup"
                    :disabled="processing"
                    @click="onCancel"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

                <div
                    class="modal-icon-wrap"
                    :style="{ background: current().bg, color: current().color }"
                >
                    <i :class="['bi', current().icon]"></i>
                </div>

                <h3 class="modal-title">{{ title }}</h3>
                <p class="modal-message" v-html="message"></p>

                <div class="modal-actions">
                    <button
                        class="btn-cancel"
                        :disabled="processing"
                        @click="onCancel"
                    >
                        {{ cancelText }}
                    </button>
                    <button
                        :class="['btn-confirm', `btn-${variant}`]"
                        :disabled="processing"
                        @click="onConfirm"
                    >
                        <span v-if="!processing">{{ confirmText }}</span>
                        <span v-else class="spinner"></span>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
/* ============ OVERLAY ============ */
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

/* ============ CARD ============ */
.modal-card {
    position: relative;
    width: 100%;
    max-width: 440px;
    background: #ffffff;
    border-radius: 22px;
    padding: 32px 28px 26px;
    box-shadow:
        0 30px 70px -20px rgba(20, 33, 10, 0.35),
        0 0 0 1px rgba(20, 33, 10, 0.04);
    text-align: center;
}

.modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: none;
    background: #f4f7ee;
    color: #6b7a5e;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    transition: all 0.2s;
}

.modal-close:hover:not(:disabled) {
    background: #e8efdb;
    color: #14210a;
    transform: rotate(90deg);
}

.modal-close:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ============ ICON ============ */
.modal-icon-wrap {
    width: 68px;
    height: 68px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    margin: 4px auto 20px;
    position: relative;
    animation: pop 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-icon-wrap::before {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 26px;
    background: inherit;
    opacity: 0.35;
    z-index: -1;
    animation: pulse-ring 1.8s ease-out infinite;
}

@keyframes pop {
    0% { transform: scale(0.4); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes pulse-ring {
    0% { transform: scale(0.9); opacity: 0.4; }
    100% { transform: scale(1.35); opacity: 0; }
}

/* ============ TEXT ============ */
.modal-title {
    font-size: 20px;
    font-weight: 800;
    color: #14210a;
    margin: 0 0 8px;
    letter-spacing: -0.4px;
}

.modal-message {
    font-size: 13.5px;
    color: #6b7a5e;
    line-height: 1.6;
    margin: 0 0 26px;
    max-width: 340px;
    margin-left: auto;
    margin-right: auto;
}

.modal-message :deep(strong) {
    color: #14210a;
    font-weight: 700;
}

/* ============ ACTIONS ============ */
.modal-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.btn-cancel,
.btn-confirm {
    padding: 12px 18px;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
}

.btn-cancel {
    background: #f4f7ee;
    color: #4a5a3d;
    border: 1.5px solid #e2e8d5;
}

.btn-cancel:hover:not(:disabled) {
    background: #e8efdb;
    color: #14210a;
}

.btn-confirm {
    color: #fff;
    position: relative;
    overflow: hidden;
}

/* Variants */
.btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    box-shadow: 0 8px 20px -8px rgba(220, 38, 38, 0.6);
}
.btn-danger:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px -8px rgba(220, 38, 38, 0.7);
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    box-shadow: 0 8px 20px -8px rgba(217, 119, 6, 0.6);
}
.btn-warning:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px -8px rgba(217, 119, 6, 0.7);
}

.btn-success {
    background: linear-gradient(135deg, #84bd33, #6ba324);
    box-shadow: 0 8px 20px -8px rgba(107, 163, 36, 0.6);
}
.btn-success:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px -8px rgba(107, 163, 36, 0.7);
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    box-shadow: 0 8px 20px -8px rgba(29, 78, 216, 0.6);
}
.btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px -8px rgba(29, 78, 216, 0.7);
}

/* Disabled */
.btn-cancel:disabled,
.btn-confirm:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Spinner */
.spinner {
    width: 18px;
    height: 18px;
    border: 2.5px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* ============ TRANSITIONS ============ */
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
@media (max-width: 480px) {
    .modal-card { padding: 28px 22px 22px; }
    .modal-title { font-size: 18px; }
    .modal-message { font-size: 13px; }
}
</style>