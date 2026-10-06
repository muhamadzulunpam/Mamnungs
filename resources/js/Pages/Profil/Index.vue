<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import EmptyState from '../../Components/UI/EmptyState.vue';

const props = defineProps({
    profile: Object,
    stats: Object,
    logs: Object,
    filters: Object,
});

// ===== HELPERS =====
const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0);

const waktu = (d) =>
    d
        ? new Date(d).toLocaleString('id-ID', {
              timeZone: 'Asia/Jakarta',
              day: '2-digit',
              month: 'short',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '-';

const waktuRingkas = (d) =>
    new Date(d).toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });

const initials = computed(() => {
    const name = props.profile?.name || 'U';
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

// ===== UBAH NAMA =====
const nameForm = useForm({ name: props.profile.name });
const simpanNama = () => nameForm.put('/profil', { preserveScroll: true });

// ===== GANTI PASSWORD =====
const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const pwForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const gantiPassword = () =>
    pwForm.put('/profil/password', {
        preserveScroll: true,
        onSuccess: () => pwForm.reset(),
    });

// ===== PASSWORD STRENGTH =====
const passwordStrength = computed(() => {
    const p = pwForm.password;
    if (!p) return { score: 0, label: '', color: '', width: '0%' };

    let score = 0;
    if (p.length >= 8) score++;
    if (p.length >= 12) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[a-z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;

    if (score <= 2) return { score: 1, label: 'Lemah', color: '#ef4444', width: '33%' };
    if (score <= 4) return { score: 2, label: 'Sedang', color: '#f59e0b', width: '66%' };
    return { score: 3, label: 'Kuat', color: '#84bd33', width: '100%' };
});

const passwordMatch = computed(() => {
    if (!pwForm.password_confirmation) return null;
    return pwForm.password === pwForm.password_confirmation;
});

// ===== LOG FILTER =====
const f = ref({
    action: props.filters?.action ?? '',
    from: props.filters?.from ?? '',
    to: props.filters?.to ?? '',
});

watch(
    f,
    (v) => {
        const q = Object.fromEntries(Object.entries(v).filter(([, val]) => val));
        router.get('/profil', q, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    },
    { deep: true }
);

const hasFilter = computed(() => Object.values(f.value).some((v) => v !== ''));

const reset = () => {
    f.value = { action: '', from: '', to: '' };
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
</script>

<template>
    <DashboardLayout>
        <!-- ============ HEADER ============ -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Profil Saya</h1>
                <p class="page-subtitle">
                    Kelola informasi akun dan lihat aktivitas Anda di Mamnungs.
                </p>
            </div>
        </div>

        <!-- ============ MAIN GRID ============ -->
        <div class="profile-grid">
            <!-- ============ KARTU IDENTITAS ============ -->
            <aside class="identity-card">
                <div class="identity-cover"></div>
                <div class="identity-body">
                    <div class="identity-avatar">
                        {{ initials }}
                    </div>

                    <div class="identity-name">{{ profile.name }}</div>
                    <div class="identity-email">
                        <i class="bi bi-envelope-fill"></i>
                        {{ profile.email }}
                    </div>

                    <div class="identity-badges">
                        <span
                            :class="[
                                'identity-role',
                                profile.role === 'admin'
                                    ? 'role-admin'
                                    : 'role-kasir',
                            ]"
                        >
                            <i
                                :class="[
                                    'bi',
                                    profile.role === 'admin'
                                        ? 'bi-shield-fill-check'
                                        : 'bi-cash-coin',
                                ]"
                            ></i>
                            {{ profile.role === 'admin' ? 'Admin' : 'Kasir' }}
                        </span>
                        <span
                            :class="[
                                'identity-status',
                                profile.is_active
                                    ? 'status-active'
                                    : 'status-inactive',
                            ]"
                        >
                            <span class="status-dot"></span>
                            {{ profile.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                </div>

                <!-- Stats ringkas -->
                <div class="identity-stats">
                    <div class="stat-item">
                        <div class="stat-icon" style="background:#eef7e0; color:#6ba324">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-value">{{ stats.orders }}</div>
                            <div class="stat-label">Transaksi</div>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon" style="background:#dcfce7; color:#15803d">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-value">{{ rupiah(stats.sales) }}</div>
                            <div class="stat-label">Total Penjualan</div>
                        </div>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="identity-meta">
                    <div class="meta-row">
                        <span class="meta-label">
                            <i class="bi bi-calendar-check"></i>
                            Bergabung
                        </span>
                        <span class="meta-value">{{ waktu(profile.created_at) }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">
                            <i class="bi bi-clock-history"></i>
                            Login Sebelumnya
                        </span>
                        <span class="meta-value">{{ waktu(stats.previousLogin) }}</span>
                    </div>
                </div>

                <div class="identity-note">
                    <i class="bi bi-info-circle-fill"></i>
                    Email dan role hanya bisa diubah oleh admin.
                </div>
            </aside>

            <!-- ============ FORM AREA ============ -->
            <div class="form-area">
                <!-- UBAH NAMA -->
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="form-card-icon" style="background:#eef7e0; color:#6ba324">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <div class="form-card-title">Ubah Nama</div>
                            <div class="form-card-sub">
                                Nama tampilan yang muncul di sistem
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="simpanNama">
                        <div class="input-group-custom">
                            <label class="input-label">Nama Lengkap</label>
                            <div class="input-wrapper" :class="{ 'has-error': nameForm.errors.name }">
                                <i class="bi bi-person input-icon"></i>
                                <input
                                    v-model="nameForm.name"
                                    type="text"
                                    placeholder="Masukkan nama lengkap"
                                    autocomplete="name"
                                />
                            </div>
                            <div v-if="nameForm.errors.name" class="error-text">
                                <i class="bi bi-exclamation-circle"></i>
                                {{ nameForm.errors.name }}
                            </div>
                        </div>

                        <button
                            class="btn-submit"
                            :disabled="nameForm.processing || !nameForm.isDirty"
                        >
                            <span v-if="!nameForm.processing">
                                <i class="bi bi-check-lg"></i>
                                Simpan Nama
                            </span>
                            <span v-else class="spinner"></span>
                        </button>
                    </form>
                </div>

                <!-- GANTI PASSWORD -->
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="form-card-icon" style="background:#fef3c7; color:#d97706">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <div>
                            <div class="form-card-title">Ganti Password</div>
                            <div class="form-card-sub">
                                Gunakan password yang kuat dan unik
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="gantiPassword">
                        <!-- Password Lama -->
                        <div class="input-group-custom">
                            <label class="input-label">Password Lama</label>
                            <div class="input-wrapper" :class="{ 'has-error': pwForm.errors.current_password }">
                                <i class="bi bi-lock input-icon"></i>
                                <input
                                    v-model="pwForm.current_password"
                                    :type="showCurrent ? 'text' : 'password'"
                                    placeholder="Masukkan password saat ini"
                                    autocomplete="current-password"
                                />
                                <button
                                    type="button"
                                    class="input-toggle-pass"
                                    @click="showCurrent = !showCurrent"
                                    tabindex="-1"
                                >
                                    <i :class="showCurrent ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                                </button>
                            </div>
                            <div v-if="pwForm.errors.current_password" class="error-text">
                                <i class="bi bi-exclamation-circle"></i>
                                {{ pwForm.errors.current_password }}
                            </div>
                        </div>

                        <!-- Password Baru -->
                        <div class="input-group-custom">
                            <label class="input-label">Password Baru</label>
                            <div class="input-wrapper" :class="{ 'has-error': pwForm.errors.password }">
                                <i class="bi bi-key input-icon"></i>
                                <input
                                    v-model="pwForm.password"
                                    :type="showNew ? 'text' : 'password'"
                                    placeholder="Minimal 8 karakter"
                                    autocomplete="new-password"
                                />
                                <button
                                    type="button"
                                    class="input-toggle-pass"
                                    @click="showNew = !showNew"
                                    tabindex="-1"
                                >
                                    <i :class="showNew ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                                </button>
                            </div>

                            <!-- Strength -->
                            <div v-if="pwForm.password" class="strength-wrap">
                                <div class="strength-track">
                                    <div
                                        class="strength-fill"
                                        :style="{
                                            width: passwordStrength.width,
                                            background: passwordStrength.color,
                                        }"
                                    ></div>
                                </div>
                                <div
                                    class="strength-label"
                                    :style="{ color: passwordStrength.color }"
                                >
                                    Kekuatan: {{ passwordStrength.label }}
                                </div>
                            </div>

                            <div v-if="pwForm.errors.password" class="error-text">
                                <i class="bi bi-exclamation-circle"></i>
                                {{ pwForm.errors.password }}
                            </div>
                        </div>

                        <!-- Konfirmasi -->
                        <div class="input-group-custom">
                            <label class="input-label">Ulangi Password Baru</label>
                            <div
                                class="input-wrapper"
                                :class="{
                                    'has-error': passwordMatch === false,
                                    'has-success': passwordMatch === true,
                                }"
                            >
                                <i class="bi bi-lock-fill input-icon"></i>
                                <input
                                    v-model="pwForm.password_confirmation"
                                    :type="showConfirm ? 'text' : 'password'"
                                    placeholder="Ketik ulang password baru"
                                    autocomplete="new-password"
                                />
                                <button
                                    type="button"
                                    class="input-toggle-pass"
                                    @click="showConfirm = !showConfirm"
                                    tabindex="-1"
                                >
                                    <i :class="showConfirm ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                                </button>
                            </div>

                            <div v-if="passwordMatch === false" class="error-text">
                                <i class="bi bi-exclamation-circle"></i>
                                Password tidak cocok
                            </div>
                            <div v-else-if="passwordMatch === true" class="success-text">
                                <i class="bi bi-check-circle-fill"></i>
                                Password cocok
                            </div>
                        </div>

                        <button class="btn-submit" :disabled="pwForm.processing">
                            <span v-if="!pwForm.processing">
                                <i class="bi bi-shield-check"></i>
                                Ganti Password
                            </span>
                            <span v-else class="spinner"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============ ACTIVITY LOG ============ -->
        <div class="log-section">
            <div class="log-header">
                <div class="log-header-left">
                    <div class="log-header-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="log-header-title">Aktivitas Saya</div>
                        <div class="log-header-sub">
                            Hanya menampilkan aktivitas akun Anda sendiri
                        </div>
                    </div>
                </div>
            </div>

            <!-- Log filter toolbar -->
            <div class="log-toolbar">
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

                <button v-if="hasFilter" class="toolbar-reset" @click="reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span>Reset</span>
                </button>
            </div>

            <!-- Log list -->
            <div class="log-panel">
                <EmptyState
                    v-if="logs.data.length === 0"
                    :icon="hasFilter ? 'bi-search' : 'bi-journal-text'"
                    :title="hasFilter ? 'Tidak Ada Hasil' : 'Belum Ada Aktivitas'"
                    :message="hasFilter
                        ? 'Tidak ada aktivitas yang cocok dengan filter Anda.'
                        : 'Aktivitas Anda akan muncul di sini setelah Anda melakukan aksi di sistem.'
                    "
                    :action-text="hasFilter ? 'Reset Filter' : ''"
                    @action="reset"
                    :compact="true"
                />

                <div v-else class="log-list">
                    <div v-for="l in logs.data" :key="l.id" class="log-item">
                        <div
                            class="log-item-icon"
                            :style="{
                                background: getAction(l.action).bg,
                                color: getAction(l.action).color,
                            }"
                        >
                            <i :class="['bi', getAction(l.action).icon]"></i>
                        </div>

                        <div class="log-item-body">
                            <div class="log-item-top">
                                <span
                                    class="log-action-badge"
                                    :style="{
                                        background: getAction(l.action).bg,
                                        color: getAction(l.action).color,
                                    }"
                                >
                                    {{ getAction(l.action).label }}
                                </span>
                                <span class="log-time">
                                    <i class="bi bi-clock"></i>
                                    {{ waktu(l.created_at) }}
                                </span>
                            </div>
                            <div class="log-description">{{ l.description }}</div>
                            <div class="log-meta">
                                <span>
                                    <i class="bi bi-globe2"></i>
                                    {{ l.ip_address }}
                                </span>
                            </div>
                        </div>
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
        </div>
    </DashboardLayout>
</template>

<style scoped>
/* ============ HEADER ============ */
.page-header { margin-bottom: 24px; }

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

/* ============ PROFILE GRID ============ */
.profile-grid {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 20px;
    margin-bottom: 24px;
    align-items: start;
}

@media (max-width: 900px) {
    .profile-grid { grid-template-columns: 1fr; }
}

/* ============ IDENTITY CARD ============ */
.identity-card {
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px -12px rgba(20, 33, 10, 0.1);
    position: sticky;
    top: 90px;
}

@media (max-width: 900px) {
    .identity-card { position: static; }
}

.identity-cover {
    height: 80px;
    background: linear-gradient(135deg, #84bd33 0%, #6ba324 50%, #558a1a 100%);
    position: relative;
}

.identity-cover::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0, transparent 3px),
        radial-gradient(circle at 70% 60%, rgba(255,255,255,0.12) 0, transparent 3px);
    background-size: 60px 60px, 80px 80px;
}

.identity-body {
    padding: 0 24px 20px;
    text-align: center;
    margin-top: -44px;
    position: relative;
}

.identity-avatar {
    width: 88px;
    height: 88px;
    border-radius: 24px;
    background: linear-gradient(135deg, #84bd33 0%, #6ba324 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    font-weight: 800;
    letter-spacing: 0.5px;
    margin: 0 auto 14px;
    border: 4px solid #ffffff;
    box-shadow: 0 8px 24px -8px rgba(107, 163, 36, 0.6);
}

.identity-name {
    font-size: 19px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.5px;
    margin-bottom: 4px;
}

.identity-email {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    color: #6b7a5e;
    margin-bottom: 14px;
    word-break: break-all;
}

.identity-email i { font-size: 11px; color: #94a3b8; }

.identity-badges {
    display: flex;
    gap: 6px;
    justify-content: center;
    flex-wrap: wrap;
}

.identity-role,
.identity-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.identity-role i { font-size: 10px; }

.role-admin {
    background: #14210a;
    color: #ffffff;
}

.role-kasir {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-active {
    background: #dcfce7;
    color: #15803d;
}

.status-inactive {
    background: #f4f7ee;
    color: #94a3b8;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

/* Identity stats */
.identity-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    padding: 16px 20px;
    border-top: 1px solid #f4f7ee;
    border-bottom: 1px solid #f4f7ee;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #fafcf6;
    border-radius: 12px;
}

.stat-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.stat-body { min-width: 0; }

.stat-value {
    font-size: 14px;
    font-weight: 800;
    color: #14210a;
    line-height: 1.1;
    font-family: ui-monospace, monospace;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stat-label {
    font-size: 10px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    font-weight: 700;
    margin-top: 2px;
}

/* Identity meta */
.identity-meta {
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

.meta-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    color: #6b7a5e;
    font-weight: 600;
}

.meta-label i { font-size: 12px; color: #a3b190; }

.meta-value {
    font-size: 11.5px;
    color: #14210a;
    font-weight: 700;
    font-family: ui-monospace, monospace;
    text-align: right;
}

.identity-note {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 12px 16px;
    background: #fafcf6;
    border-top: 1px solid #f4f7ee;
    font-size: 11.5px;
    color: #6b7a5e;
    line-height: 1.5;
}

.identity-note i {
    color: #a3b190;
    font-size: 12px;
    margin-top: 2px;
    flex-shrink: 0;
}

/* ============ FORM AREA ============ */
.form-area {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-card {
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 20px;
    padding: 22px 22px 20px;
    box-shadow: 0 4px 20px -12px rgba(20, 33, 10, 0.08);
}

.form-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 16px;
    margin-bottom: 16px;
    border-bottom: 1px dashed #e2e8d5;
}

.form-card-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    flex-shrink: 0;
}

.form-card-title {
    font-size: 15px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.2px;
}

.form-card-sub {
    font-size: 11.5px;
    color: #94a3b8;
    margin-top: 2px;
}

/* ============ INPUT ============ */
.input-group-custom { margin-bottom: 16px; }

.input-label {
    display: block;
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 7px;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 16px;
    color: #a3b190;
    font-size: 15px;
    pointer-events: none;
    transition: color 0.2s;
    z-index: 2;
}

.input-wrapper input {
    width: 100%;
    padding: 13px 42px 13px 44px;
    border: 1.5px solid #e2e8d5;
    border-radius: 12px;
    background: #f8fbf3;
    font-size: 13.5px;
    font-family: inherit;
    color: #14210a;
    transition: all 0.2s ease;
    outline: none;
}

.input-wrapper input::placeholder { color: #a3b190; }

.input-wrapper input:focus {
    border-color: #84bd33;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(132, 189, 51, 0.15);
}

.input-wrapper:focus-within .input-icon { color: #6ba324; }

.input-wrapper.has-error input {
    border-color: #ef4444;
    background: #fef7f7;
}

.input-wrapper.has-error input:focus {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
}

.input-wrapper.has-error .input-icon { color: #ef4444; }

.input-wrapper.has-success input {
    border-color: #84bd33;
    background: #f8fbf3;
}

.input-wrapper.has-success .input-icon { color: #6ba324; }

/* Toggle password */
.input-toggle-pass {
    position: absolute;
    right: 12px;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: #a3b190;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    transition: all 0.15s;
    z-index: 3;
}

.input-toggle-pass:hover {
    background: #f0f8e5;
    color: #6ba324;
}

/* ============ PASSWORD STRENGTH ============ */
.strength-wrap { margin-top: 10px; }

.strength-track {
    height: 4px;
    background: #f0f4e8;
    border-radius: 100px;
    overflow: hidden;
    margin-bottom: 6px;
}

.strength-fill {
    height: 100%;
    border-radius: 100px;
    transition: all 0.3s ease;
}

.strength-label {
    font-size: 11.5px;
    font-weight: 700;
}

/* ============ HINT / ERROR / SUCCESS ============ */
.hint-text,
.error-text,
.success-text {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 500;
    margin-top: 6px;
}

.error-text { color: #dc2626; font-weight: 600; }
.success-text { color: #15803d; font-weight: 700; }

/* ============ BUTTONS ============ */
.btn-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 22px;
    border-radius: 12px;
    background: linear-gradient(135deg, #84bd33 0%, #6ba324 100%);
    color: #ffffff;
    border: none;
    font-family: inherit;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 44px;
    box-shadow: 0 8px 20px -8px rgba(107, 163, 36, 0.6);
}

.btn-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -8px rgba(107, 163, 36, 0.7);
}

.btn-submit:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-submit i { font-size: 14px; }

.spinner {
    width: 18px;
    height: 18px;
    border: 2.5px solid rgba(255, 255, 255, 0.3);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ============ LOG SECTION ============ */
.log-section {
    margin-top: 8px;
}

.log-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.log-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.log-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #eef7e0, #e0f0c8);
    color: #6ba324;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.log-header-title {
    font-size: 17px;
    font-weight: 800;
    color: #14210a;
    letter-spacing: -0.3px;
}

.log-header-sub {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 2px;
}

/* Log toolbar */
.log-toolbar {
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

/* Log panel */
.log-panel {
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 18px;
    padding: 8px;
    box-shadow: 0 4px 20px -12px rgba(20, 33, 10, 0.08);
}

.log-list {
    display: flex;
    flex-direction: column;
}

.log-item {
    display: flex;
    gap: 14px;
    padding: 14px 16px;
    border-bottom: 1px solid #f4f7ee;
    transition: background 0.15s;
}

.log-item:last-child { border-bottom: none; }

.log-item:hover { background: #fafcf6; }

.log-item-icon {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}

.log-item-body {
    flex: 1;
    min-width: 0;
}

.log-item-top {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}

.log-action-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 9px;
    border-radius: 100px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.log-time {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #94a3b8;
    font-family: ui-monospace, monospace;
}

.log-time i { font-size: 10px; }

.log-description {
    font-size: 13px;
    color: #4a5a3d;
    line-height: 1.5;
    margin-bottom: 6px;
}

.log-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 11px;
    color: #94a3b8;
    font-family: ui-monospace, monospace;
}

.log-meta span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.log-meta i { font-size: 10px; }

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

/* ============ RESPONSIVE ============ */
@media (max-width: 768px) {
    .log-toolbar { padding: 10px; }
    .toolbar-select { flex: 1; min-width: 0; }
    .toolbar-daterange { width: 100%; }
    .date-field { flex: 1; }
    .date-field input { width: 100%; }
    .date-arrow { display: none; }
    .pagination-wrap { flex-direction: column; align-items: stretch; }
    .pagination-info { text-align: center; }
    .pagination { justify-content: center; }
}

@media (max-width: 640px) {
    .page-title { font-size: 22px; }
    .identity-avatar { width: 76px; height: 76px; font-size: 26px; }
    .form-card { padding: 18px 16px 16px; }
}
</style>