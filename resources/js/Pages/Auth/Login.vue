<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue'; 
import logoEsteler from '../../assets/logo_esteler.png';

const form = useForm({ email: '', password: '', remember: false });
const showPassword = ref(false);

const submit = () => form.post('/login');
</script>

<template>
    <div class="login-wrapper">
        <div class="login-card">
            <!-- LEFT - BRANDING -->
            <div class="login-brand">
                <div class="brand-content">
                    <div class="brand-logo">
                        <img :src="logoEsteler" alt="Mamnungs" class="brand-logo-img" />
                    </div>
                    <h1 class="brand-title">Mamnungs</h1>
                    <p class="brand-tagline">Es Teler Segar Setiap Hari 🍧</p>
                    <p class="brand-subtitle">
                        Kelola pesanan, stok, dan pelanggan es teler Anda dengan mudah dan menyenangkan.
                    </p>

                    <div class="brand-features">
                        <div class="feature-item">
                            <div class="feature-icon"><i class="bi bi-check-lg"></i></div>
                            <span>Kelola Menu & Topping</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="bi bi-check-lg"></i></div>
                            <span>Laporan Penjualan Harian</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="bi bi-check-lg"></i></div>
                            <span>Kelola Pesanan Online</span>
                        </div>
                    </div>

                    <div class="brand-badge">
                        <span class="dot"></span>
                        <span>Fresh & Halal 100%</span>
                    </div>
                </div>

                <!-- Decorative tropical fruits -->
                <div class="brand-shapes">
                    <span class="shape shape-1">🍓</span>
                    <span class="shape shape-2">🥥</span>
                    <span class="shape shape-3">🍈</span>
                    <span class="shape shape-4">🍍</span>
                    <span class="blob blob-1"></span>
                    <span class="blob blob-2"></span>
                    <span class="blob blob-3"></span>
                </div>
            </div>

            <!-- RIGHT - FORM -->
            <div class="login-form">
                <div class="form-header">
                    <div class="form-header-badge">
                        <i class="bi bi-shop"></i> Dashboard Mamnungs
                    </div>
                    <h2>Selamat Datang Kembali 👋</h2>
                    <p>Masuk untuk mulai kelola es teler Anda hari ini</p>
                </div>

                <div v-if="form.errors.email" class="alert-custom">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    {{ form.errors.email }}
                </div>

                <form @submit.prevent="submit">
                    <div class="input-group-custom">
                        <label>Email</label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="nama@mamnungs.com"
                                autofocus
                                autocomplete="username"
                            />
                        </div>
                    </div>

                    <div class="input-group-custom">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                @keyup.enter="submit"
                            />
                            <button
                                type="button"
                                class="toggle-pass"
                                @click="showPassword = !showPassword"
                                tabindex="-1"
                            >
                                <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                            </button>
                        </div>
                        <div v-if="form.errors.password" class="error-text">
                            <i class="bi bi-exclamation-circle"></i> {{ form.errors.password }}
                        </div>
                    </div>

                    <div class="form-row">
                        <label class="checkbox-custom">
                            <input v-model="form.remember" type="checkbox" />
                            <span class="checkmark"></span>
                            <span>Ingat saya</span>
                        </label>
                        <a href="#" class="forgot-link">Lupa password?</a>
                    </div>

                    <button
                        type="submit"
                        class="btn-login"
                        :disabled="form.processing"
                    >
                        <span v-if="!form.processing">
                            Masuk Sekarang <i class="bi bi-arrow-right"></i>
                        </span>
                        <span v-else class="spinner"></span>
                    </button>
                </form>

            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.login-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: #f4f9f0;
    background-image:
        radial-gradient(at 15% 15%, rgba(132, 189, 51, 0.18) 0, transparent 45%),
        radial-gradient(at 85% 85%, rgba(244, 168, 42, 0.14) 0, transparent 45%),
        radial-gradient(at 50% 50%, rgba(157, 210, 90, 0.08) 0, transparent 50%);
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
}

.login-card {
    display: flex;
    width: 100%;
    max-width: 1000px;
    min-height: 620px;
    background: #ffffff;
    border-radius: 28px;
    overflow: hidden;
    box-shadow:
        0 24px 70px -24px rgba(60, 100, 20, 0.28),
        0 0 0 1px rgba(132, 189, 51, 0.08);
}

/* ============ LEFT BRAND PANEL ============ */
.login-brand {
    flex: 1.05;
    position: relative;
    padding: 52px 44px;
    background: linear-gradient(160deg, #84bd33 0%, #6ba324 55%, #558a1a 100%);
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    overflow: hidden;
}

/* Subtle pattern overlay */
.login-brand::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 20% 30%, rgba(255,255,255,0.12) 0, transparent 3px),
        radial-gradient(circle at 70% 60%, rgba(255,255,255,0.1) 0, transparent 3px),
        radial-gradient(circle at 40% 80%, rgba(255,255,255,0.08) 0, transparent 3px);
    background-size: 90px 90px, 120px 120px, 70px 70px;
    opacity: 0.6;
    z-index: 1;
}

.brand-content {
    position: relative;
    z-index: 3;
}

.brand-logo {
    width: 96px;
    height: 96px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.22);
    backdrop-filter: blur(12px);
    border: 1.5px solid rgba(255, 255, 255, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden; 
    padding: -2px;  
    margin-bottom: 22px;
    box-shadow: 0 8px 24px -6px rgba(0, 0, 0, 0.2);
}

.brand-logo-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.brand-title {
    font-size: 44px;
    font-weight: 800;
    margin: 0 0 6px;
    letter-spacing: -1.2px;
    line-height: 1;
}

.brand-tagline {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 22px;
    color: #fef9c3;
    letter-spacing: 0.3px;
}

.brand-subtitle {
    font-size: 15px;
    line-height: 1.65;
    color: #f5f9e8;
    font-weight: 500;
    text-shadow: 0 1px 3px rgba(60, 100, 20, 0.35);
    margin-bottom: 32px;
    max-width: 340px;
}

.brand-features {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-bottom: 32px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14.5px;
    font-weight: 500;
}

.feature-icon {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}

.brand-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(8px);
    border-radius: 100px;
    font-size: 12.5px;
    font-weight: 600;
    width: fit-content;
}

.brand-badge .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #fef9c3;
    box-shadow: 0 0 0 4px rgba(254, 249, 195, 0.3);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 4px rgba(254, 249, 195, 0.3); }
    50% { box-shadow: 0 0 0 8px rgba(254, 249, 195, 0.1); }
}

/* Decorative shapes */
.brand-shapes { position: absolute; inset: 0; z-index: 1; pointer-events: none; }

.blob {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.09);
    filter: blur(1px);
}
.blob-1 { width: 260px; height: 260px; top: -80px; right: -80px; }
.blob-2 { width: 160px; height: 160px; bottom: -40px; left: -60px; }
.blob-3 { width: 100px; height: 100px; top: 45%; right: 15%; background: rgba(254, 249, 195, 0.1); }

.shape {
    position: absolute;
    font-size: 42px;
    opacity: 0.85;
    filter: drop-shadow(0 6px 12px rgba(0,0,0,0.15));
    animation: float 6s ease-in-out infinite;
}
.shape-1 { top: 8%; right: 10%; animation-delay: 0s; }
.shape-2 { top: 30%; right: 6%; font-size: 36px; animation-delay: 1.2s; }
.shape-3 { bottom: 12%; right: 14%; font-size: 38px; animation-delay: 2.4s; }
.shape-4 { bottom: 30%; right: 4%; font-size: 32px; animation-delay: 0.6s; }

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(-3deg); }
    50% { transform: translateY(-14px) rotate(4deg); }
}

/* ============ RIGHT FORM PANEL ============ */
.login-form {
    flex: 1;
    padding: 52px 48px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: #ffffff;
}

.form-header { margin-bottom: 28px; }

.form-header-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #f0f8e5;
    color: #558a1a;
    font-size: 12px;
    font-weight: 700;
    border-radius: 100px;
    margin-bottom: 14px;
    letter-spacing: 0.3px;
}

.form-header h2 {
    font-size: 27px;
    font-weight: 700;
    color: #14210a;
    margin: 0 0 6px;
    letter-spacing: -0.6px;
}

.form-header p {
    color: #6b7a5e;
    font-size: 14px;
    margin: 0;
}

.alert-custom {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    padding: 12px 14px;
    border-radius: 12px;
    font-size: 13px;
    margin-bottom: 20px;
    font-weight: 500;
}

.input-group-custom { margin-bottom: 20px; }

.input-group-custom label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
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
    font-size: 16px;
    pointer-events: none;
    transition: color 0.2s;
}

.input-wrapper input {
    width: 100%;
    padding: 14px 16px 14px 44px;
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

.input-wrapper:focus-within .input-icon {
    color: #6ba324;
}

.toggle-pass {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: #a3b190;
    cursor: pointer;
    padding: 6px;
    font-size: 16px;
    transition: color 0.2s;
}
.toggle-pass:hover { color: #6ba324; }

.error-text {
    display: block;
    color: #dc2626;
    font-size: 12px;
    margin-top: 6px;
    font-weight: 500;
}

.form-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    font-size: 13px;
}

.checkbox-custom {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
    color: #4a5a3d;
    font-weight: 500;
}

.checkbox-custom input { display: none; }

.checkmark {
    width: 18px;
    height: 18px;
    border-radius: 6px;
    border: 1.5px solid #cbd5b5;
    background: #fff;
    position: relative;
    transition: all 0.2s;
    flex-shrink: 0;
}

.checkbox-custom input:checked + .checkmark {
    background: linear-gradient(135deg, #84bd33, #6ba324);
    border-color: #6ba324;
}

.checkbox-custom input:checked + .checkmark::after {
    content: '';
    position: absolute;
    left: 5px;
    top: 1.5px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.forgot-link {
    color: #6ba324;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}
.forgot-link:hover { color: #558a1a; text-decoration: underline; }

.btn-login {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #84bd33 0%, #6ba324 100%);
    color: white;
    font-size: 15px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.25s ease;
    box-shadow: 0 10px 24px -8px rgba(107, 163, 36, 0.55);
    position: relative;
    overflow: hidden;
    letter-spacing: 0.2px;
}

.btn-login::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #6ba324 0%, #558a1a 100%);
    opacity: 0;
    transition: opacity 0.25s;
}

.btn-login span {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-login:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px -8px rgba(107, 163, 36, 0.65);
}
.btn-login:hover:not(:disabled)::before { opacity: 1; }
.btn-login:active:not(:disabled) { transform: translateY(0); }

.btn-login:disabled {
    opacity: 0.75;
    cursor: not-allowed;
    transform: none;
}

.spinner {
    width: 20px;
    height: 20px;
    border: 2.5px solid rgba(255, 255, 255, 0.3);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.divider {
    position: relative;
    text-align: center;
    margin: 22px 0 14px;
    color: #a3b190;
    font-size: 12px;
    font-weight: 500;
}
.divider::before,
.divider::after {
    content: '';
    position: absolute;
    top: 50%;
    width: calc(50% - 24px);
    height: 1px;
    background: #e2e8d5;
}
.divider::before { left: 0; }
.divider::after { right: 0; }

/* ============ RESPONSIVE ============ */
@media (max-width: 900px) {
    .login-card { flex-direction: column; max-width: 460px; min-height: auto; }
    .login-brand { padding: 40px 32px; text-align: center; align-items: center; }
    .brand-logo { margin: 0 auto 16px; }
    .brand-title { font-size: 34px; }
    .brand-subtitle { margin-left: auto; margin-right: auto; margin-bottom: 22px; font-size: 14px; }
    .brand-features { display: none; }
    .brand-badge { margin: 0 auto; }
    .login-form { padding: 36px 28px; }
    .form-header h2 { font-size: 22px; }
    .form-header-badge { display: inline-flex; }
}
</style>