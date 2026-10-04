<script setup>
import { computed, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '../../../Layouts/DashboardLayout.vue';

const props = defineProps({ category: Object });

const isEdit = computed(() => !!props.category);

const form = useForm({
    name: props.category?.name ?? '',
});

// ===== CHARACTER COUNTER =====
const maxLength = 100;
const charCount = computed(() => form.name.length);
const charPercent = computed(() => (charCount.value / maxLength) * 100);
const nearLimit = computed(() => charCount.value >= maxLength - 10);

// ===== FOCUS HANDLING =====
const inputRef = ref(null);

// ===== SUBMIT =====
const submit = () => {
    if (isEdit.value) {
        form.put(`/admin/categories/${props.category.id}`);
    } else {
        form.post('/admin/categories');
    }
};
</script>

<template>
    <DashboardLayout>
        <!-- ============ BREADCRUMB ============ -->
        <nav class="breadcrumb-nav">
            <Link href="/admin/dashboard" class="breadcrumb-link">Dashboard</Link>
            <i class="bi bi-chevron-right breadcrumb-sep"></i>
            <Link href="/admin/categories" class="breadcrumb-link">Kategori</Link>
            <i class="bi bi-chevron-right breadcrumb-sep"></i>
            <span class="breadcrumb-current">
                {{ isEdit ? 'Ubah' : 'Tambah' }}
            </span>
        </nav>

        <!-- ============ HEADER ============ -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    {{ isEdit ? 'Ubah Kategori' : 'Tambah Kategori Baru' }}
                </h1>
                <p class="page-subtitle">
                    {{ isEdit
                        ? 'Perbarui nama kategori sesuai kebutuhan menu Anda.'
                        : 'Buat kategori baru untuk mengelompokkan menu es teler Anda.'
                    }}
                </p>
            </div>
        </div>

        <!-- ============ FORM WRAP ============ -->
        <div class="form-wrap">
            <!-- FORM CARD -->
            <div class="form-card">
                <div class="form-card-icon">
                    <i :class="['bi', isEdit ? 'bi-pencil-square' : 'bi-plus-circle']"></i>
                </div>

                <form @submit.prevent="submit">
                    <!-- Nama Kategori -->
                    <div class="input-group-custom">
                        <label class="input-label" for="category-name">
                            Nama Kategori <span class="required">*</span>
                        </label>
                        <div
                            class="input-wrapper"
                            :class="{ 'has-error': form.errors.name }"
                        >
                            <i class="bi bi-tag input-icon"></i>
                            <input
                                id="category-name"
                                ref="inputRef"
                                v-model="form.name"
                                type="text"
                                :placeholder="isEdit ? 'Ubah nama kategori...' : 'Contoh: Es Teler, Minuman, Topping'"
                                :maxlength="maxLength"
                                autofocus
                                autocomplete="off"
                                @keyup.enter="submit"
                            />
                        </div>

                        <!-- Counter & Error -->
                        <div class="input-footer">
                            <div v-if="form.errors.name" class="error-text">
                                <i class="bi bi-exclamation-circle"></i>
                                {{ form.errors.name }}
                            </div>
                            <div v-else class="hint-text">
                                <i class="bi bi-info-circle"></i>
                                Nama singkat yang jelas, maks. {{ maxLength }} karakter
                            </div>

                            <div :class="['char-counter', { 'near-limit': nearLimit }]">
                                {{ charCount }}/{{ maxLength }}
                            </div>
                        </div>

                        <!-- Progress bar -->
                        <div class="char-track">
                            <div
                                class="char-fill"
                                :class="{ 'near-limit': nearLimit }"
                                :style="{ width: charPercent + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div v-if="form.name.trim()" class="preview-card">
                        <div class="preview-label">Preview</div>
                        <div class="preview-content">
                            <div class="preview-icon">
                                <i class="bi bi-tag-fill"></i>
                            </div>
                            <div>
                                <div class="preview-name">{{ form.name }}</div>
                                <div class="preview-meta">
                                    {{ isEdit ? 'Setelah diubah' : 'Akan dibuat' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-actions">
                        <Link href="/admin/categories" class="btn-cancel">
                            <i class="bi bi-arrow-left"></i>
                            Batal
                        </Link>
                        <button
                            type="submit"
                            class="btn-submit"
                            :disabled="form.processing || !form.name.trim()"
                        >
                            <span v-if="!form.processing">
                                <i class="bi bi-check-lg"></i>
                                {{ isEdit ? 'Simpan Perubahan' : 'Simpan Kategori' }}
                            </span>
                            <span v-else class="spinner"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- TIPS SIDEBAR -->
            <aside class="form-tips">
                <div class="tips-card">
                    <div class="tips-icon">
                        <i class="bi bi-lightbulb"></i>
                    </div>
                    <div class="tips-title">Tips Kategori</div>
                    <ul class="tips-list">
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Gunakan nama singkat, maks. 2 kata</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Kelompokkan menu sejenis, mis. <strong>"Es Teler"</strong></span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Nama harus unik, tidak boleh sama</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Kategori dapat diubah kapan saja</span>
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

.breadcrumb-current {
    color: #14210a;
    font-weight: 700;
}

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

/* ============ FORM WRAP ============ */
.form-wrap {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 20px;
    align-items: start;
    max-width: 900px;
}

@media (max-width: 860px) {
    .form-wrap { grid-template-columns: 1fr; }
    .form-tips { order: -1; }
}

/* ============ FORM CARD ============ */
.form-card {
    position: relative;
    background: #ffffff;
    border: 1px solid #eef2e6;
    border-radius: 20px;
    padding: 28px 24px 24px;
    box-shadow: 0 4px 20px -12px rgba(20, 33, 10, 0.08);
}

.form-card-icon {
    width: 52px;
    height: 52px;
    border-radius: 15px;
    background: linear-gradient(135deg, #eef7e0 0%, #e0f0c8 100%);
    color: #6ba324;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 20px;
}

/* ============ INPUT ============ */
.input-group-custom { margin-bottom: 20px; }

.input-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 8px;
}

.required { color: #ef4444; }

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

.input-wrapper:focus-within .input-icon { color: #6ba324; }

.input-wrapper.has-error input {
    border-color: #ef4444;
    background: #fef7f7;
}

.input-wrapper.has-error input:focus {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
}

.input-wrapper.has-error .input-icon { color: #ef4444; }

/* ============ INPUT FOOTER ============ */
.input-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: 8px;
    min-height: 18px;
}

.hint-text,
.error-text {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 500;
}

.hint-text { color: #94a3b8; }
.error-text { color: #dc2626; font-weight: 600; }

.char-counter {
    font-size: 11.5px;
    font-weight: 700;
    color: #94a3b8;
    font-family: ui-monospace, monospace;
    transition: color 0.2s;
}

.char-counter.near-limit { color: #f59e0b; }

/* Progress bar */
.char-track {
    height: 3px;
    background: #f0f4e8;
    border-radius: 100px;
    overflow: hidden;
    margin-top: 6px;
}

.char-fill {
    height: 100%;
    background: #84bd33;
    border-radius: 100px;
    transition: width 0.25s ease, background 0.25s;
}

.char-fill.near-limit { background: #f59e0b; }

/* ============ PREVIEW ============ */
.preview-card {
    background: #fafcf6;
    border: 1.5px dashed #d8e8bf;
    border-radius: 14px;
    padding: 14px;
    margin-bottom: 24px;
}

.preview-label {
    font-size: 10.5px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 10px;
}

.preview-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.preview-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    background: linear-gradient(135deg, #eef7e0 0%, #e0f0c8 100%);
    color: #6ba324;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.preview-name {
    font-size: 14px;
    font-weight: 700;
    color: #14210a;
    margin-bottom: 2px;
    word-break: break-word;
}

.preview-meta {
    font-size: 11.5px;
    color: #94a3b8;
    font-family: ui-monospace, monospace;
}

/* ============ ACTIONS ============ */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 20px;
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

.btn-submit i,
.btn-cancel i { font-size: 14px; }

.spinner {
    width: 18px;
    height: 18px;
    border: 2.5px solid rgba(255, 255, 255, 0.3);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* ============ TIPS SIDEBAR ============ */
.tips-card {
    background: linear-gradient(160deg, #f0f8e5 0%, #e8efdb 100%);
    border: 1px solid #d8e8bf;
    border-radius: 18px;
    padding: 20px;
    position: sticky;
    top: 90px;
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