<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import logoEsteler from '../assets/logo_esteler.png';

const page = usePage();
const user = computed(() => page.props.auth.user);

// ===== STATE =====
const sidebarOpen = ref(false);         // mobile drawer
const sidebarCollapsed = ref(false);    // desktop collapse
const userMenuOpen = ref(false);
const userMenuRef = ref(null);

// ===== RESTORE COLLAPSE STATE =====
onMounted(() => {
    const saved = localStorage.getItem('sidebar_collapsed');
    if (saved !== null) {
        sidebarCollapsed.value = saved === '1';
    }
});

watch(sidebarCollapsed, (v) => {
    localStorage.setItem('sidebar_collapsed', v ? '1' : '0');
});

// ===== MENU CONFIG =====
const adminMenus = [
    { label: 'Dashboard',     href: '/admin/dashboard',    icon: 'bi-grid-1x2' },
    { label: 'Kategori',      href: '/admin/categories',   icon: 'bi-tags' },
    { label: 'Menu & Produk', href: '/admin/products',     icon: 'bi-cup-straw' },
    { label: 'Riwayat',       href: '/transaksi',          icon: 'bi-clock-history' },
    { label: 'Laporan',       href: '/admin/reports',      icon: 'bi-graph-up-arrow' },
    { label: 'Pengguna',      href: '/admin/users',        icon: 'bi-people' },
    { label: 'Log Aktivitas', href: '/admin/activity-logs', icon: 'bi-clock' },
];

const kasirMenus = [
    { label: 'Kasir / POS', href: '/kasir',     icon: 'bi-cash-coin' },
    { label: 'Riwayat',     href: '/transaksi', icon: 'bi-clock-history' },
];

const menus = computed(() =>
    user.value.role === 'admin' ? adminMenus : kasirMenus
);

const secondaryMenus = [
    { label: 'Profil', href: '/profil', icon: 'bi-person-circle' },
];

// ===== HELPERS =====
const isActive = (href) => page.url === href || page.url.startsWith(href + '/');

const initials = computed(() =>
    (user.value.name || 'U')
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase()
);

const logout = () => {
    userMenuOpen.value = false;
    router.post('/logout');
};

const toggleCollapse = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};

// ===== CLICK OUTSIDE =====
const handleClickOutside = (e) => {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        userMenuOpen.value = false;
    }
};

// ===== FLASH MESSAGE =====
const flashQueue = ref([]);

const flashConfig = {
    success: { icon: 'bi-check-circle-fill',         title: 'Berhasil' },
    error:   { icon: 'bi-x-circle-fill',             title: 'Gagal' },
    warning: { icon: 'bi-exclamation-triangle-fill', title: 'Perhatian' },
    info:    { icon: 'bi-info-circle-fill',          title: 'Info' },
};

const removeFlash = (id) => {
    flashQueue.value = flashQueue.value.filter((f) => f.id !== id);
};

const pushFlash = (type, message) => {
    const id = Date.now() + Math.random();
    flashQueue.value.push({ id, type, message });
    setTimeout(() => removeFlash(id), 4500);
};

watch(
    () => page.props.flash,
    (val) => {
        if (!val) return;
        if (val.success) pushFlash('success', val.success);
        if (val.error)   pushFlash('error', val.error);
        if (val.warning) pushFlash('warning', val.warning);
        if (val.info)    pushFlash('info', val.info);
    },
    { deep: true, immediate: true }
);

// ===== LIFECYCLE =====
onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div class="app-shell">
        <!-- ============ SIDEBAR ============ -->
        <aside
            class="sidebar"
            :class="{
                'sidebar-open': sidebarOpen,
                'is-collapsed': sidebarCollapsed,
            }"
        >
            <!-- Brand -->
            <div class="sidebar-brand">
                <div class="brand-mark">
                    <img :src="logoEsteler" alt="Mamnungs" class="brand-logo-img" />
                </div>
                <div class="brand-text">
                    <span class="brand-name">Mamnungs</span>
                    <span class="brand-tag">Es Teler Segar</span>
                </div>
                <button
                    class="sidebar-close d-lg-none"
                    @click="sidebarOpen = false"
                    title="Tutup"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Main menu -->
            <div class="sidebar-section">
                <span class="section-label">Menu Utama</span>
                <nav class="sidebar-nav">
                    <Link
                        v-for="m in menus"
                        :key="m.href"
                        :href="m.href"
                        class="nav-item"
                        :class="{ 'nav-item-active': isActive(m.href) }"
                        :title="sidebarCollapsed ? m.label : ''"
                        @click="sidebarOpen = false"
                    >
                        <i :class="['bi', m.icon, 'nav-icon']"></i>
                        <span class="nav-label">{{ m.label }}</span>
                        <i class="bi bi-chevron-right nav-arrow"></i>
                    </Link>
                </nav>
            </div>

            <!-- Secondary -->
            <div class="sidebar-section">
                <span class="section-label">Lainnya</span>
                <nav class="sidebar-nav">
                    <Link
                        v-for="m in secondaryMenus"
                        :key="m.href"
                        :href="m.href"
                        class="nav-item"
                        :class="{ 'nav-item-active': isActive(m.href) }"
                        :title="sidebarCollapsed ? m.label : ''"
                        @click="sidebarOpen = false"
                    >
                        <i :class="['bi', m.icon, 'nav-icon']"></i>
                        <span class="nav-label">{{ m.label }}</span>
                    </Link>
                </nav>
            </div>

            <!-- User card -->
            <div class="sidebar-user-card">
                <div class="user-card-top">
                    <div class="user-card-avatar" :title="user.name">
                        {{ initials }}
                    </div>
                    <div class="user-card-info">
                        <span class="user-card-name">{{ user.name }}</span>
                        <span class="user-card-role">{{ user.role }}</span>
                    </div>
                </div>
                <button
                    class="btn-logout"
                    @click="logout"
                    :title="sidebarCollapsed ? 'Keluar' : ''"
                >
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Keluar</span>
                </button>
            </div>
        </aside>

        <!-- Mobile backdrop -->
        <div
            v-if="sidebarOpen"
            class="sidebar-backdrop d-lg-none"
            @click="sidebarOpen = false"
        ></div>

        <!-- ============ MAIN AREA ============ -->
        <div class="main-area">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-left">
                    <!-- Mobile: hamburger -->
                    <button
                        class="hamburger d-lg-none"
                        @click="sidebarOpen = true"
                        title="Buka Menu"
                    >
                        <i class="bi bi-list"></i>
                    </button>

                    <!-- Desktop: toggle collapse -->
                    <button
                        class="collapse-toggle d-none d-lg-flex"
                        @click="toggleCollapse"
                        :title="sidebarCollapsed ? 'Perbesar Sidebar' : 'Perkecil Sidebar'"
                    >
                        <i :class="['bi', sidebarCollapsed ? 'bi-chevron-right' : 'bi-chevron-left']"></i>
                    </button>
                </div>

                <div class="topbar-right">
                    <!-- Notification -->
                    <button class="icon-btn" title="Notifikasi">
                        <i class="bi bi-bell"></i>
                        <span class="notif-dot"></span>
                    </button>

                    <!-- Divider -->
                    <div class="topbar-divider"></div>

                    <!-- User dropdown -->
                    <div class="user-menu-wrap" ref="userMenuRef">
                        <button class="user-trigger" @click.stop="userMenuOpen = !userMenuOpen">
                            <div class="user-avatar">{{ initials }}</div>
                            <div class="user-info d-none d-sm-block">
                                <span class="user-name">{{ user.name }}</span>
                            </div>
                            <i :class="['bi', userMenuOpen ? 'bi-chevron-up' : 'bi-chevron-down', 'user-caret']"></i>
                        </button>

                        <transition name="dropdown">
                            <div v-if="userMenuOpen" class="user-dropdown">
                                <div class="dropdown-header">
                                    <div class="user-avatar user-avatar-lg">{{ initials }}</div>
                                    <div>
                                        <div class="dropdown-name">{{ user.name }}</div>
                                        <div class="dropdown-email">{{ user.email || 'user@mamnungs.com' }}</div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <Link href="/profil" class="dropdown-item">
                                    <i class="bi bi-person"></i> Profil Saya
                                </Link>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item dropdown-danger" @click="logout">
                                    <i class="bi bi-box-arrow-right"></i> Keluar
                                </button>
                            </div>
                        </transition>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="page-content">
                <slot />
            </main>

            <!-- ============ FLASH TOASTS ============ -->
            <transition-group name="toast" tag="div" class="toast-container">
                <div
                    v-for="f in flashQueue"
                    :key="f.id"
                    :class="['toast-item', `toast-${f.type}`]"
                >
                    <div class="toast-icon">
                        <i :class="['bi', flashConfig[f.type].icon]"></i>
                    </div>
                    <div class="toast-body">
                        <div class="toast-title">{{ flashConfig[f.type].title }}</div>
                        <div class="toast-message">{{ f.message }}</div>
                    </div>
                    <button class="toast-close" @click="removeFlash(f.id)">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <div class="toast-progress"></div>
                </div>
            </transition-group>
        </div>
    </div>
</template>

<style scoped>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

/* ============ SHELL ============ */
.app-shell {
    display: flex;
    min-height: 100vh;
    background: #f4f9f0;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    color: #14210a;
}

/* ============ SIDEBAR ============ */
.sidebar {
    width: 260px;
    background: linear-gradient(180deg, #558a1a 0%, #3d6510 100%);
    color: #ffffff;
    display: flex;
    flex-direction: column;
    padding: 22px 16px;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    overflow-x: hidden;
    flex-shrink: 0;
    z-index: 40;
    transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                padding 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar::-webkit-scrollbar { width: 5px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }

/* Brand */
.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 4px 8px 24px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    margin-bottom: 20px;
    position: relative;
    transition: all 0.25s ease;
}

.brand-mark {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #fef9c3;
    flex-shrink: 0;
    transition: all 0.25s ease;
    overflow: hidden;
    padding: -2px;
}
.brand-logo-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.15;
    transition: opacity 0.15s ease;
}

.brand-name { font-size: 17px; font-weight: 800; letter-spacing: -0.3px; }
.brand-tag { font-size: 11px; color: rgba(254, 249, 195, 0.85); font-weight: 500; margin-top: 2px; }

.sidebar-close {
    position: absolute;
    right: 0;
    top: 4px;
    background: rgba(255,255,255,0.15);
    border: none;
    color: #fff;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Sections */
.sidebar-section { margin-bottom: 22px; transition: margin 0.25s ease; }

.section-label {
    display: block;
    font-size: 10.5px;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.5);
    text-transform: uppercase;
    letter-spacing: 0.9px;
    padding: 0 12px 8px;
    transition: opacity 0.15s ease;
}

.sidebar-nav { display: flex; flex-direction: column; gap: 3px; }

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.82);
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 500;
    transition: all 0.18s ease;
    position: relative;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
}

.nav-item-active {
    background: #ffffff;
    color: #3d6510 !important;
    font-weight: 700;
    box-shadow: 0 6px 16px -6px rgba(0, 0, 0, 0.3);
}

.nav-item-active::before {
    content: '';
    position: absolute;
    left: -16px;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 22px;
    background: #fef9c3;
    border-radius: 0 4px 4px 0;
    transition: all 0.25s ease;
}

.nav-icon {
    font-size: 17px;
    width: 20px;
    text-align: center;
    flex-shrink: 0;
    transition: all 0.25s ease;
}

.nav-label {
    flex: 1;
    transition: opacity 0.15s ease;
    white-space: nowrap;
    overflow: hidden;
}

.nav-arrow {
    font-size: 11px;
    opacity: 0;
    transform: translateX(-4px);
    transition: all 0.2s;
}
.nav-item:hover .nav-arrow,
.nav-item-active .nav-arrow { opacity: 1; transform: translateX(0); }

/* User card */
.sidebar-user-card {
    margin-top: auto;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 16px;
    padding: 14px;
    backdrop-filter: blur(8px);
    transition: all 0.25s ease;
}

.user-card-top {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    transition: all 0.25s ease;
}

.user-card-avatar {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: rgba(254, 249, 195, 0.9);
    color: #3d6510;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 13px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.25s ease;
}

.user-card-info {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
    min-width: 0;
    transition: opacity 0.15s ease;
}

.user-card-name {
    font-size: 13px;
    font-weight: 700;
    color: #ffffff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-card-role {
    font-size: 10.5px;
    color: rgba(254, 249, 195, 0.9);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
}

.btn-logout {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 10px 14px;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 10px;
    color: #fecaca;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn-logout:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px -6px rgba(239, 68, 68, 0.6);
}

.btn-logout:active { transform: translateY(0); }
.btn-logout i { font-size: 15px; }

/* ============ SIDEBAR COLLAPSED STATE ============ */
.sidebar.is-collapsed {
    width: 72px;
    padding: 22px 10px;
}

.sidebar.is-collapsed .sidebar-brand {
    justify-content: center;
    padding: 4px 0 20px;
    gap: 0;
}

.sidebar.is-collapsed .brand-text {
    display: none;
}

.sidebar.is-collapsed .brand-mark {
    width: 40px;
    height: 40px;
    font-size: 20px;
}

.sidebar.is-collapsed .section-label {
    display: none;
}

.sidebar.is-collapsed .sidebar-section {
    margin-bottom: 12px;
}

.sidebar.is-collapsed .nav-item {
    justify-content: center;
    padding: 11px 0;
    gap: 0;
    position: relative;
}

.sidebar.is-collapsed .nav-label,
.sidebar.is-collapsed .nav-arrow {
    display: none;
}

.sidebar.is-collapsed .nav-icon {
    font-size: 19px;
    width: auto;
}

.sidebar.is-collapsed .nav-item-active::before {
    left: -10px;
    height: 20px;
}

/* Tooltip saat collapsed */
.sidebar.is-collapsed .nav-item::after {
    content: attr(title);
    position: absolute;
    left: calc(100% + 12px);
    top: 50%;
    transform: translateY(-50%) translateX(-4px);
    padding: 6px 12px;
    background: #14210a;
    color: #ffffff;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    border-radius: 8px;
    opacity: 0;
    pointer-events: none;
    transition: all 0.18s ease;
    z-index: 100;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.sidebar.is-collapsed .nav-item:hover::after {
    opacity: 1;
    transform: translateY(-50%) translateX(0);
}

/* User card collapsed */
.sidebar.is-collapsed .sidebar-user-card {
    padding: 10px 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.sidebar.is-collapsed .user-card-top {
    justify-content: center;
    padding-bottom: 8px;
    margin-bottom: 8px;
    gap: 0;
}

.sidebar.is-collapsed .user-card-info {
    display: none;
}

.sidebar.is-collapsed .user-card-avatar {
    width: 40px;
    height: 40px;
    font-size: 14px;
}

.sidebar.is-collapsed .btn-logout {
    padding: 10px 0;
    justify-content: center;
    width: 40px;
    margin: 0 auto;
}

.sidebar.is-collapsed .btn-logout span {
    display: none;
}

/* Backdrop */
.sidebar-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(20, 33, 10, 0.5);
    backdrop-filter: blur(2px);
    z-index: 35;
}

/* ============ MAIN AREA ============ */
.main-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

/* ============ TOPBAR ============ */
.topbar {
    position: sticky;
    top: 0;
    z-index: 30;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 12px 24px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(14px);
    border-bottom: 1px solid #eef2e6;
}

.topbar-left, .topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.hamburger {
    width: 38px;
    height: 38px;
    border: 1px solid #e2e8d5;
    background: #fff;
    border-radius: 10px;
    font-size: 18px;
    cursor: pointer;
    color: #4a5a3d;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.hamburger:hover {
    border-color: #84bd33;
    color: #6ba324;
    background: #f8fbf3;
}

.collapse-toggle {
    width: 38px;
    height: 38px;
    border: 1px solid #e2e8d5;
    background: #fff;
    border-radius: 10px;
    font-size: 15px;
    color: #4a5a3d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.collapse-toggle:hover {
    border-color: #84bd33;
    color: #6ba324;
    background: #f8fbf3;
    transform: scale(1.05);
}

.collapse-toggle:active {
    transform: scale(0.95);
}

/* Icon button */
.icon-btn {
    width: 40px;
    height: 40px;
    border: 1px solid #e2e8d5;
    background: #fff;
    border-radius: 12px;
    font-size: 17px;
    color: #4a5a3d;
    cursor: pointer;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.icon-btn:hover { border-color: #84bd33; color: #6ba324; background: #f8fbf3; }

.notif-dot {
    position: absolute;
    top: 9px;
    right: 10px;
    width: 8px;
    height: 8px;
    background: #ef4444;
    border-radius: 50%;
    border: 2px solid #fff;
    animation: pulse-dot 2s ease-in-out infinite;
}

@keyframes pulse-dot {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
    50% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
}

.topbar-divider {
    width: 1px;
    height: 28px;
    background: #e2e8d5;
}

/* ============ USER TRIGGER ============ */
.user-menu-wrap { position: relative; }

.user-trigger {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 10px 5px 5px;
    background: #fff;
    border: 1.5px solid #e2e8d5;
    border-radius: 100px;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}

.user-trigger:hover { border-color: #84bd33; background: #f8fbf3; }

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #84bd33 0%, #558a1a 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 12px;
    letter-spacing: 0.3px;
    flex-shrink: 0;
}

.user-avatar-lg {
    width: 42px;
    height: 42px;
    font-size: 15px;
    border-radius: 12px;
}

.user-info { display: flex; flex-direction: column; line-height: 1.15; text-align: left; }
.user-name { font-size: 13px; font-weight: 700; color: #14210a; }

.user-caret { font-size: 11px; color: #94a3b8; }

/* ============ DROPDOWN ============ */
.user-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 260px;
    background: #fff;
    border: 1px solid #eef2e6;
    border-radius: 16px;
    box-shadow: 0 20px 40px -12px rgba(20, 33, 10, 0.18);
    padding: 8px;
    z-index: 50;
    overflow: hidden;
}

.dropdown-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 10px 12px;
}

.dropdown-name { font-size: 13.5px; font-weight: 700; color: #14210a; }
.dropdown-email { font-size: 11.5px; color: #94a3b8; margin-top: 1px; }

.dropdown-divider { height: 1px; background: #f0f4e8; margin: 4px 0; }

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 9px 10px;
    border: none;
    background: transparent;
    text-align: left;
    font-size: 13px;
    font-weight: 500;
    color: #4a5a3d;
    text-decoration: none;
    border-radius: 10px;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.15s;
}

.dropdown-item i { font-size: 15px; color: #94a3b8; transition: color 0.15s; }

.dropdown-item:hover { background: #f8fbf3; color: #14210a; }
.dropdown-item:hover i { color: #6ba324; }

.dropdown-danger { color: #dc2626; }
.dropdown-danger:hover { background: #fef2f2; color: #b91c1c; }
.dropdown-danger:hover i { color: #dc2626; }

/* Dropdown animation */
.dropdown-enter-active, .dropdown-leave-active {
    transition: opacity 0.18s, transform 0.18s;
}
.dropdown-enter-from, .dropdown-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
}

/* ============ PAGE CONTENT ============ */
.page-content {
    flex: 1;
    padding: 24px;
}

/* ============ FLASH TOASTS ============ */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 380px;
    width: calc(100% - 40px);
    pointer-events: none;
}

.toast-item {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 44px 14px 14px;
    background: #ffffff;
    border-radius: 14px;
    box-shadow:
        0 16px 40px -12px rgba(20, 33, 10, 0.25),
        0 0 0 1px rgba(20, 33, 10, 0.05);
    overflow: hidden;
    pointer-events: auto;
}

.toast-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    border-radius: 4px 0 0 4px;
}

.toast-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.toast-body { flex: 1; min-width: 0; padding-top: 1px; }

.toast-title {
    font-size: 13px;
    font-weight: 800;
    color: #14210a;
    margin-bottom: 2px;
    letter-spacing: -0.2px;
}

.toast-message {
    font-size: 12.5px;
    color: #6b7a5e;
    line-height: 1.5;
    word-wrap: break-word;
}

.toast-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 24px;
    height: 24px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: #94a3b8;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    transition: all 0.15s;
}

.toast-close:hover { background: #f4f7ee; color: #14210a; }

.toast-progress {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 3px;
    background: rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

.toast-progress::after {
    content: '';
    position: absolute;
    inset: 0;
    transform-origin: left;
    animation: toastProgress 4.5s linear forwards;
}

@keyframes toastProgress {
    from { transform: scaleX(1); }
    to   { transform: scaleX(0); }
}

/* Variants */
.toast-success::before { background: #84bd33; }
.toast-success .toast-icon { background: #eef7e0; color: #6ba324; }
.toast-success .toast-progress::after { background: #84bd33; }

.toast-error::before { background: #ef4444; }
.toast-error .toast-icon { background: #fee2e2; color: #dc2626; }
.toast-error .toast-progress::after { background: #ef4444; }

.toast-warning::before { background: #f59e0b; }
.toast-warning .toast-icon { background: #fef3c7; color: #d97706; }
.toast-warning .toast-progress::after { background: #f59e0b; }

.toast-info::before { background: #3b82f6; }
.toast-info .toast-icon { background: #dbeafe; color: #1d4ed8; }
.toast-info .toast-progress::after { background: #3b82f6; }

/* Animations */
.toast-enter-active {
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-leave-active {
    transition: all 0.25s ease;
    position: absolute;
    right: 0;
    width: 100%;
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(100%) scale(0.9);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(100%) scale(0.9);
}
.toast-move {
    transition: transform 0.3s ease;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 991.98px) {
    /* Mobile: sidebar tetap drawer overlay */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        box-shadow: 0 0 40px rgba(0, 0, 0, 0.3);
        width: 260px !important;
        padding: 22px 16px !important;
    }

    .sidebar.sidebar-open {
        transform: translateX(0);
    }

    /* Reset semua collapsed style di mobile */
    .sidebar.is-collapsed .brand-text,
    .sidebar.is-collapsed .section-label,
    .sidebar.is-collapsed .nav-label,
    .sidebar.is-collapsed .user-card-info,
    .sidebar.is-collapsed .btn-logout span {
        display: block !important;
    }

    .sidebar.is-collapsed .nav-item {
        justify-content: flex-start !important;
        padding: 10px 12px !important;
        gap: 12px !important;
    }

    .sidebar.is-collapsed .nav-icon {
        font-size: 17px !important;
        width: 20px !important;
    }

    .sidebar.is-collapsed .sidebar-brand {
        justify-content: flex-start !important;
        padding: 4px 8px 24px !important;
        gap: 12px !important;
    }

    .sidebar.is-collapsed .user-card-top {
        justify-content: flex-start !important;
        padding-bottom: 12px !important;
        margin-bottom: 12px !important;
        gap: 10px !important;
    }

    .sidebar.is-collapsed .btn-logout {
        width: 100% !important;
        padding: 10px 14px !important;
        justify-content: center !important;
    }

    .sidebar.is-collapsed .sidebar-user-card {
        padding: 14px !important;
    }

    .sidebar.is-collapsed .nav-item::after {
        display: none !important;
    }

    .page-content { padding: 18px; }
    .topbar { padding: 10px 16px; }
}

@media (max-width: 575.98px) {
    .topbar-divider { display: none; }
    .user-info { display: none !important; }
}

@media (max-width: 480px) {
    .toast-container {
        top: 12px;
        right: 12px;
        left: 12px;
        width: auto;
        max-width: none;
    }
}
</style>