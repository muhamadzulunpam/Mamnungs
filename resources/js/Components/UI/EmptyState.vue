<script setup>
defineProps({
    icon: { type: String, default: 'bi-inbox' },
    title: { type: String, default: 'Data Tidak Ditemukan' },
    message: { type: String, default: 'Belum ada data yang bisa ditampilkan di sini.' },
    actionText: { type: String, default: '' },
    actionHref: { type: String, default: '' },
    compact: { type: Boolean, default: false },
});

defineEmits(['action']);
</script>

<template>
    <div class="empty-state" :class="{ 'empty-compact': compact }">
        <div class="empty-icon">
            <i :class="['bi', icon]"></i>
        </div>
        <h4 class="empty-title">{{ title }}</h4>
        <p class="empty-message">{{ message }}</p>

        <!-- Action: pakai Link (Inertia) kalau ada actionHref -->
        <Link
            v-if="actionText && actionHref"
            :href="actionHref"
            class="empty-btn"
        >
            <i class="bi bi-plus-lg"></i>
            {{ actionText }}
        </Link>

        <!-- Action: pakai button kalau tidak ada href -->
        <button
            v-else-if="actionText"
            class="empty-btn"
            @click="$emit('action')"
        >
            <i class="bi bi-plus-lg"></i>
            {{ actionText }}
        </button>
    </div>
</template>

<style scoped>
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 24px;
    text-align: center;
}

.empty-compact {
    padding: 32px 20px;
}

/* ============ ICON ============ */
.empty-icon {
    width: 90px;
    height: 90px;
    border-radius: 24px;
    background: linear-gradient(135deg, #f0f8e5 0%, #e8efdb 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: #84bd33;
    margin-bottom: 20px;
    position: relative;
}

.empty-icon::after {
    content: '';
    position: absolute;
    inset: -6px;
    border-radius: 30px;
    background: #f0f8e5;
    opacity: 0.5;
    z-index: -1;
}

.empty-compact .empty-icon {
    width: 68px;
    height: 68px;
    font-size: 30px;
    border-radius: 18px;
    margin-bottom: 14px;
}

/* ============ TEXT ============ */
.empty-title {
    font-size: 17px;
    font-weight: 800;
    color: #14210a;
    margin: 0 0 6px;
    letter-spacing: -0.3px;
}

.empty-message {
    font-size: 13.5px;
    color: #6b7a5e;
    line-height: 1.6;
    margin: 0 0 20px;
    max-width: 340px;
}

.empty-compact .empty-title { font-size: 15px; }
.empty-compact .empty-message { font-size: 12.5px; margin-bottom: 14px; }

/* ============ BUTTON ============ */
.empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #84bd33, #6ba324);
    color: #fff;
    text-decoration: none;
    border: none;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 8px 20px -8px rgba(107, 163, 36, 0.6);
}

.empty-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px -8px rgba(107, 163, 36, 0.7);
    color: #fff;
}
</style>