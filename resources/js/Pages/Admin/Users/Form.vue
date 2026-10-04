<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '../../../Layouts/DashboardLayout.vue';

const props = defineProps({
    user: Object,
    isSelf: Boolean,
});

const isEdit = computed(() => !!props.user);

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    role: props.user?.role ?? 'kasir',
    is_active: props.user?.is_active ?? true,
    password: '',
    password_confirmation: '',
});

// ===== SHOW / HIDE PASSWORD =====
const showPassword = ref(false);
const showConfirm = ref(false);

// ===== INITIALS (untuk preview avatar) =====
const initials = computed(() => {
    const name = form.name?.trim() || 'U';
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

// ===== PASSWORD STRENGTH =====
const passwordStrength = computed(() => {
    const p = form.password;
    if (!p) return { score: 0, label: '', color: '', width: '0%' };

    let score = 0;
    if (p.length >= 8) score++;
    if (p.length >= 12) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[a-z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;

    if (score <= 2) {
        return { score: 1, label: 'Lemah', color: '#ef4444', width: '33%' };
    } else if (score <= 4) {
        return { score: 2, label: 'Sedang', color: '#f59e0b', width: '66%' };
    } else {
        return { score: 3, label: 'Kuat', color: '#84bd33', width: '100%' };
    }
});

// ===== PASSWORD MATCH =====
const passwordMatch = computed(() => {
    if (!form.password_confirmation) return null;
    return form.password === form.password_confirmation;
});

// ===== ROLE OPTIONS =====
const roleOptions = [
    {
        value: 'kasir',
        label: 'Kasir',
        desc: 'Hanya bisa akses halaman POS',
        icon: 'bi-cash-coin',
    },
    {
        value: 'admin',
        label: 'Admin',
        desc: 'Akses penuh ke semua fitur',
        icon: 'bi-shield-fill-check',
    },
];

// ===== SUBMIT =====
const submit = () => {
    if (isEdit.value) {
        form.put(`/admin/users/${props.user.id}`);
    } else {
        form.post('/admin/users');
    }
};

// ===== AVATAR COLOR (untuk preview) =====
const avatarColor = computed(() => {
    const colors = [
        { bg: '#eef7e0', color: '#6ba324' },
        { bg: '#dbeafe', color: '#1d4ed8' },
        { bg: '#fce7f3', color: '#ec4899' },
        { bg: '#fef3c7', color: '#d97706' },
        { bg: '#e0f2fe', color: '#0ea5e9' },
    ];
    const id = props.user?.id ?? 0;
    return colors[id % colors.length];
});
</script>

<template>
    <DashboardLayout>
        <!-- ============ BREADCRUMB ============ -->
        <nav class="breadcrumb-nav">
            <Link href="/admin/dashboard" class="breadcrumb-link">Dashboard</Link>
            <i class="bi bi-chevron-right breadcrumb-sep"></i>
            <Link href="/admin/users" class="breadcrumb-link">Pengguna</Link>
            <i class="bi bi-chevron-right breadcrumb-sep"></i>
            <span class="breadcrumb-current">{{ isEdit ? 'Ubah' : 'Tambah' }}</span>
        </nav>

        <!-- ============ HEADER ============ -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    {{ isEdit ? 'Ubah Pengguna' : 'Tambah Pengguna Baru' }}
                </h1>
                <p class="page-subtitle">
                    {{ isEdit
                        ? 'Perbarui informasi akun, role, atau password pengguna.'
                        : 'Buat akun baru untuk admin atau kasir Mamnungs.'
                    }}
                </p>
            </div>
        </div>

        <!-- ============ SELF EDIT WARNING ============ -->
        <div v-if="isSelf" class="info-banner">
            <div class="info-banner-icon">
                <i class="bi bi-info-circle-fill"></i>
            </div>
            <div class="info-banner-text">
                <strong>Anda sedang mengedit akun sendiri.</strong>
                Role dan status aktif tidak bisa diubah untuk mencegah Anda terkunci dari sistem.
            </div>
        </div>

        <!-- ============ FORM WRAP ============ -->
        <div class="form-wrap">
            <!-- ============ FORM CARD ============ -->
            <div class="form-card">
                <form @submit.prevent="submit">
                    <!-- SECTION: Info Akun -->
                    <div class="section-title">
                        <i class="bi bi-person-badge"></i>
                        Informasi Akun
                    </div>

                    <!-- Nama -->
                    <div class="input-group-custom">
                        <label class="input-label" for="user-name">
                            Nama Lengkap <span class="required">*</span>
                        </label>
                        <div class="input-wrapper" :class="{ 'has-error': form.errors.name }">
                            <i class="bi bi-person input-icon"></i>
                            <input
                                id="user-name"
                                v-model="form.name"
                                type="text"
                                placeholder="Contoh: Budi Santoso"
                                autofocus
                                autocomplete="name"
                            />
                        </div>
                        <div v-if="form.errors.name" class="error-text">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="input-group-custom">
                        <label class="input-label" for="user-email">
                            Email <span class="required">*</span>
                        </label>
                        <div class="input-wrapper" :class="{ 'has-error': form.errors.email }">
                            <i class="bi bi-envelope input-icon"></i>
                            <input
                                id="user-email"
                                v-model="form.email"
                                type="email"
                                placeholder="nama@mamnungs.com"
                                autocomplete="email"
                            />
                        </div>
                        <div v-if="form.errors.email" class="error-text">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <!-- SECTION: Role -->
                    <div class="section-title section-title-spaced">
                        <i class="bi bi-shield-lock"></i>
                        Role & Akses
                    </div>

                    <!-- Role Picker -->
                    <div class="input-group-custom">
                        <label class="input-label">
                            Role <span class="required">*</span>
                            <span v-if="isSelf" class="label-locked">
                                <i class="bi bi-lock-fill"></i>
                                Terkunci
                            </span>
                        </label>

                        <div class="role-grid">
                            <button
                                v-for="opt in roleOptions"
                                :key="opt.value"
                                type="button"
                                :class="[
                                    'role-card',
                                    {
                                        'is-selected': form.role === opt.value,
                                        'is-disabled': isSelf,
                                    },
                                ]"
                                :disabled="isSelf"
                                @click="form.role = opt.value"
                            >
                                <div class="role-card-icon">
                                    <i :class="['bi', opt.icon]"></i>
                                </div>
                                <div class="role-card-body">
                                    <div class="role-card-label">{{ opt.label }}</div>
                                    <div class="role-card-desc">{{ opt.desc }}</div>
                                </div>
                                <div class="role-card-check">
                                    <i class="bi bi-check-lg"></i>
                                </div>
                            </button>
                        </div>

                        <div v-if="form.errors.role" class="error-text">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ form.errors.role }}
                        </div>
                    </div>

                    <!-- SECTION: Password -->
                    <div class="section-title section-title-spaced">
                        <i class="bi bi-key"></i>
                        {{ isEdit ? 'Ganti Password' : 'Password' }}
                    </div>

                    <div v-if="isEdit" class="info-hint">
                        <i class="bi bi-info-circle"></i>
                        Kosongkan kolom password jika tidak ingin mengganti password pengguna.
                    </div>

                    <!-- Password -->
                    <div class="input-group-custom">
                        <label class="input-label" for="user-password">
                            Password
                            <span v-if="!isEdit" class="required">*</span>
                            <span v-else class="label-optional">opsional</span>
                        </label>
                        <div class="input-wrapper" :class="{ 'has-error': form.errors.password }">
                            <i class="bi bi-lock input-icon"></i>
                            <input
                                id="user-password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="Minimal 8 karakter"
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                class="input-toggle-pass"
                                @click="showPassword = !showPassword"
                                tabindex="-1"
                            >
                                <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                            </button>
                        </div>

                        <!-- Password Strength -->
                        <div v-if="form.password" class="strength-wrap">
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

                        <div v-if="form.errors.password" class="error-text">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ form.errors.password }}
                        </div>
                        <div v-else-if="!form.password && !isEdit" class="hint-text">
                            <i class="bi bi-info-circle"></i>
                            Gunakan kombinasi huruf besar, kecil, angka, dan simbol
                        </div>
                    </div>

                    <!-- Konfirmasi Password -->
                    <div class="input-group-custom">
                        <label class="input-label" for="user-password-confirm">
                            Ulangi Password
                        </label>
                        <div
                            class="input-wrapper"
                            :class="{
                                'has-error': passwordMatch === false,
                                'has-success': passwordMatch === true,
                            }"
                        >
                            <i class="bi bi-lock-fill input-icon"></i>
                            <input
                                id="user-password-confirm"
                                v-model="form.password_confirmation"
                                :type="showConfirm ? 'text' : 'password'"
                                placeholder="Ketik ulang password"
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

                        <!-- Match indicator -->
                        <div v-if="passwordMatch === false" class="error-text">
                            <i class="bi bi-exclamation-circle"></i>
                            Password tidak cocok
                        </div>
                        <div v-else-if="passwordMatch === true" class="success-text">
                            <i class="bi bi-check-circle-fill"></i>
                            Password cocok
                        </div>
                    </div>

                    <!-- SECTION: Status -->
                    <div class="section-title section-title-spaced">
                        <i class="bi bi-toggle-on"></i>
                        Status Akun
                    </div>

                    <!-- Toggle Aktif -->
                    <div
                        class="toggle-row"
                        :class="{ 'is-disabled': isSelf }"
                        @click="!isSelf && (form.is_active = !form.is_active)"
                    >
                        <div class="toggle-info">
                            <div class="toggle-title">
                                Akun Aktif
                                <span v-if="isSelf" class="label-locked">
                                    <i class="bi bi-lock-fill"></i>
                                    Terkunci
                                </span>
                            </div>
                            <div class="toggle-sub">
                                {{ form.is_active
                                    ? 'Pengguna dapat login dan menggunakan sistem'
                                    : 'Pengguna tidak dapat login sampai diaktifkan kembali'
                                }}
                            </div>
                        </div>
                        <div :class="['toggle-switch', { 'is-on': form.is_active }]">
                            <div class="toggle-knob"></div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-actions">
                        <Link href="/admin/users" class="btn-cancel">
                            <i class="bi bi-arrow-left"></i>
                            Batal
                        </Link>
                        <button
                            type="submit"
                            class="btn-submit"
                            :disabled="form.processing"
                        >
                            <span v-if="!form.processing">
                                <i class="bi bi-check-lg"></i>
                                {{ isEdit ? 'Simpan Perubahan' : 'Simpan Pengguna' }}
                            </span>
                            <span v-else class="spinner"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ============ SIDEBAR ============ -->
            <aside class="form-aside">
                <!-- Live Preview -->
                <div class="aside-card">
                    <div class="aside-title">
                        <i class="bi bi-eye"></i> Preview
                    </div>

                    <div class="preview-user">
                        <div
                            class="preview-avatar"
                            :style="{
                                background: avatarColor.bg,
                                color: avatarColor.color,
                            }"
                        >
                            {{ initials }}
                        </div>
                        <div class="preview-info">
                            <div class="preview-name">
                                {{ form.name || 'Nama Pengguna' }}
                            </div>
                            <div class="preview-email">
                                {{ form.email || 'email@mamnungs.com' }}
                            </div>
                            <div class="preview-badges">
                                <span
                                    :class="[
                                        'preview-role',
                                        form.role === 'admin'
                                            ? 'preview-role-admin'
                                            : 'preview-role-kasir',
                                    ]"
                                >
                                    <i
                                        :class="[
                                            'bi',
                                            form.role === 'admin'
                                                ? 'bi-shield-fill-check'
                                                : 'bi-cash-coin',
                                        ]"
                                    ></i>
                                    {{ form.role === 'admin' ? 'Admin' : 'Kasir' }}
                                </span>
                                <span
                                    :class="[
                                        'preview-status',
                                        form.is_active
                                            ? 'preview-status-active'
                                            : 'preview-status-inactive',
                                    ]"
                                >
                                    <span class="preview-status-dot"></span>
                                    {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tips -->
                <div class="tips-card">
                    <div class="tips-icon">
                        <i class="bi bi-lightbulb"></i>
                    </div>
                    <div class="tips-title">Tips Pengguna</div>
                    <ul class="tips-list">
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Gunakan <strong>email aktif</strong> untuk reset password</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Password minimal <strong>8 karakter</strong>, kombinasi huruf & angka</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Role <strong>Admin</strong> bisa akses semua fitur</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Nonaktifkan akun yang <strong>tidak dipakai</strong> daripada dihapus</span>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </DashboardLayout>
</template>

<style scoped>
/* ============ BREADCRUMB ============ */
.breadcrumb-nav {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    font-size: 12.5px;
}

.breadcrumb-link {
    color: #6b7a5e;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.15s;
}
.breadcrumb-link:hover { color: #6ba324; }

.breadcrumb-sep { color: #cbd5b5; font-size: 10px; }

.breadcrumb-current { color: #14210a; font-weight: 700; }

/* ============ HEADER ============ */
.page-header { margin-bottom: 20px; }

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

/* ============ INFO BANNER ============ */
.info-banner {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 18px;
    background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
    border: 1px solid #bfdbfe;
    border-radius: 14px;
    margin-bottom: 20px;
    max-width: 900px;
}

.info-banner-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: #ffffff;
    color: #1d4ed8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.info-banner-text {
    font-size: 13px;
    color: #1e40af;
    line-height: 1.5;
    padding-top: 4px;
}

.info-banner-text strong { color: #1e3a8a; font-weight: 800; }

/* ============ FORM WRAP ============ */
.form-wrap {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
    align-items: start;
    max-width: 900px;
}

@media (max-width: 900px) {
    .form-wrap { grid-template-columns: 1fr; }
    .form-aside { order: -1; }
}

/* ============ FORM CARD ============ */
.form-card {
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 20px;
    padding: 28px 24px 24px;
    box-shadow: 0 4px 20px -12px rgba(20, 33, 10, 0.08);
}

/* Section title */
.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 800;
    color: #6ba324;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 1px dashed #e2e8d5;
}

.section-title-spaced { margin-top: 28px; }

.section-title i { font-size: 14px; }

/* Info hint */
.info-hint {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 12px;
    background: #f0f8e5;
    border: 1px solid #d8e8bf;
    border-radius: 10px;
    font-size: 12.5px;
    color: #4a5a3d;
    margin-bottom: 16px;
    line-height: 1.5;
}

.info-hint i { color: #6ba324; font-size: 13px; margin-top: 2px; flex-shrink: 0; }

/* ============ INPUT ============ */
.input-group-custom { margin-bottom: 20px; }

.input-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 8px;
}

.required { color: #ef4444; }

.label-optional {
    font-size: 11px;
    font-weight: 500;
    color: #94a3b8;
    background: #f4f7ee;
    padding: 2px 8px;
    border-radius: 100px;
    text-transform: none;
    letter-spacing: 0;
}

.label-locked {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10.5px;
    font-weight: 700;
    color: #94a3b8;
    background: #f4f7ee;
    padding: 2px 8px;
    border-radius: 100px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
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
    padding: 14px 44px 14px 44px;
    border: 1.5px solid #e2e8d5;
    border-radius: 12px;
    background: #f8fbf3;
    font-size: 14px;
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
.strength-wrap {
    margin-top: 10px;
}

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

.hint-text { color: #94a3b8; }
.error-text { color: #dc2626; font-weight: 600; }
.success-text { color: #15803d; font-weight: 700; }

/* ============ ROLE PICKER ============ */
.role-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

@media (max-width: 480px) {
    .role-grid { grid-template-columns: 1fr; }
}

.role-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #ffffff;
    border: 1.5px solid #e2e8d5;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    font-family: inherit;
    position: relative;
}

.role-card:hover:not(.is-disabled) {
    border-color: #84bd33;
    background: #fafcf6;
    transform: translateY(-1px);
}

.role-card.is-selected {
    border-color: #84bd33;
    background: #f0f8e5;
    box-shadow: 0 0 0 4px rgba(132, 189, 51, 0.12);
}

.role-card.is-disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.role-card-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #f4f7ee;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
    transition: all 0.2s;
}

.role-card.is-selected .role-card-icon {
    background: linear-gradient(135deg, #84bd33, #6ba324);
    color: #ffffff;
}

.role-card-body { flex: 1; min-width: 0; }

.role-card-label {
    font-size: 13.5px;
    font-weight: 800;
    color: #14210a;
    margin-bottom: 2px;
}

.role-card-desc {
    font-size: 11px;
    color: #94a3b8;
    line-height: 1.3;
}

.role-card-check {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #e2e8d5;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: transparent;
    font-size: 11px;
    flex-shrink: 0;
    transition: all 0.2s;
}

.role-card.is-selected .role-card-check {
    background: #84bd33;
    border-color: #84bd33;
    color: #ffffff;
}

/* ============ TOGGLE SWITCH ============ */
.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 16px;
    background: #fafcf6;
    border: 1.5px solid #eef2e6;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.toggle-row:hover:not(.is-disabled) {
    background: #f4f9f0;
    border-color: #d8e8bf;
}

.toggle-row.is-disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.toggle-info { flex: 1; min-width: 0; }

.toggle-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    font-weight: 700;
    color: #14210a;
    margin-bottom: 2px;
}

.toggle-sub {
    font-size: 11.5px;
    color: #94a3b8;
}

.toggle-switch {
    position: relative;
    width: 48px;
    height: 28px;
    border-radius: 100px;
    background: #e2e8d5;
    transition: background 0.25s ease;
    flex-shrink: 0;
}

.toggle-switch.is-on {
    background: linear-gradient(135deg, #84bd33, #6ba324);
}

.toggle-knob {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 22px;
    height: 22px;
    background: #ffffff;
    border-radius: 50%;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.toggle-switch.is-on .toggle-knob {
    transform: translateX(20px);
}

/* ============ FORM ACTIONS ============ */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 20px;
    margin-top: 24px;
    border-top: 1px solid #f4f7ee;
}

.btn-cancel,
.btn-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 22px;
    border-radius: 12px;
    font-family: inherit;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    border: 1.5px solid transparent;
    transition: all 0.2s;
    min-height: 44px;
}

.btn-cancel {
    background: #f4f7ee;
    border-color: #e2e8d5;
    color: #4a5a3d;
}
.btn-cancel:hover {
    background: #e8efdb;
    color: #14210a;
    transform: translateY(-1px);
}

.btn-submit {
    background: linear-gradient(135deg, #84bd33 0%, #6ba324 100%);
    color: #ffffff;
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

.btn-submit i, .btn-cancel i { font-size: 14px; }

.spinner {
    width: 18px;
    height: 18px;
    border: 2.5px solid rgba(255, 255, 255, 0.3);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ============ ASIDE ============ */
.form-aside {
    display: flex;
    flex-direction: column;
    gap: 16px;
    position: sticky;
    top: 90px;
}

.aside-card, .tips-card {
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 18px;
    padding: 18px;
}

.aside-title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 800;
    color: #6ba324;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
}

/* Preview user */
.preview-user {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: #fafcf6;
    border: 1px solid #eef2e6;
    border-radius: 14px;
}

.preview-avatar {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 800;
    flex-shrink: 0;
    letter-spacing: 0.3px;
}

.preview-info { flex: 1; min-width: 0; }

.preview-name {
    font-size: 13.5px;
    font-weight: 800;
    color: #14210a;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.preview-email {
    font-size: 11.5px;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 8px;
}

.preview-badges {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.preview-role,
.preview-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 100px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.preview-role i { font-size: 9px; }

.preview-role-admin {
    background: #14210a;
    color: #ffffff;
}

.preview-role-kasir {
    background: #dbeafe;
    color: #1d4ed8;
}

.preview-status-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
}

.preview-status-active {
    background: #dcfce7;
    color: #15803d;
}

.preview-status-inactive {
    background: #f4f7ee;
    color: #94a3b8;
}

/* Tips card */
.tips-card {
    background: linear-gradient(160deg, #f0f8e5 0%, #e8efdb 100%);
    border-color: #d8e8bf;
}

.tips-icon {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    background: #ffffff;
    color: #f59e0b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 12px;
    box-shadow: 0 4px 12px -4px rgba(245, 158, 11, 0.4);
}

.tips-title {
    font-size: 14px;
    font-weight: 800;
    color: #14210a;
    margin-bottom: 12px;
    letter-spacing: -0.2px;
}

.tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.tips-list li {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 12.5px;
    color: #4a5a3d;
    line-height: 1.5;
}

.tips-list i {
    color: #84bd33;
    font-size: 13px;
    margin-top: 2px;
    flex-shrink: 0;
}

.tips-list strong { color: #14210a; font-weight: 700; }

/* ============ RESPONSIVE ============ */
@media (max-width: 640px) {
    .page-title { font-size: 22px; }
    .form-card { padding: 22px 18px 18px; }
    .form-actions { flex-direction: column-reverse; }
    .btn-cancel, .btn-submit { width: 100%; }
}
</style>