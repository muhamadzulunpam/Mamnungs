<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '../../../Layouts/DashboardLayout.vue';

const props = defineProps({
    product: Object,
    categories: Array,
});

const isEdit = computed(() => !!props.product);

const form = useForm({
    _method: isEdit.value ? 'put' : 'post',
    category_id: props.product?.category_id ?? '',
    name: props.product?.name ?? '',
    description: props.product?.description ?? '',
    price: props.product?.price ?? '',
    is_available: props.product?.is_available ?? true,
    image: null,
});

const preview = ref(
    props.product?.image ? `/storage/${props.product.image}` : null
);

// ===== FILE INPUT =====
const fileInputRef = ref(null);
const isDragging = ref(false);

const handleFile = (file) => {
    if (!file) return;
    if (!file.type.startsWith('image/')) return;
    if (file.size > 2 * 1024 * 1024) {
        alert('Ukuran file maksimal 2 MB');
        return;
    }
    form.image = file;
    preview.value = URL.createObjectURL(file);
};

const pilihFoto = (e) => {
    handleFile(e.target.files[0]);
};

const onDrop = (e) => {
    isDragging.value = false;
    handleFile(e.dataTransfer.files[0]);
};

const onDragOver = () => { isDragging.value = true; };
const onDragLeave = () => { isDragging.value = false; };

const removeImage = () => {
    form.image = null;
    preview.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
};

// ===== PRICE INPUT =====
const priceDisplay = computed({
    get: () => {
        if (!form.price && form.price !== 0) return '';
        return new Intl.NumberFormat('id-ID').format(form.price);
    },
    set: (val) => {
        const num = String(val).replace(/\D/g, '');
        form.price = num ? parseInt(num, 10) : '';
    },
});

// ===== PREVIEW KATEGORI =====
const selectedCategory = computed(() =>
    props.categories?.find((c) => c.id === form.category_id)
);

// ===== SUBMIT =====
const submit = () => {
    const url = isEdit.value
        ? `/admin/products/${props.product.id}`
        : '/admin/products';
    form.post(url, { forceFormData: true });
};
</script>

<template>
    <DashboardLayout>
        <!-- ============ BREADCRUMB ============ -->
        <nav class="breadcrumb-nav">
            <Link href="/admin/dashboard" class="breadcrumb-link">Dashboard</Link>
            <i class="bi bi-chevron-right breadcrumb-sep"></i>
            <Link href="/admin/products" class="breadcrumb-link">Produk</Link>
            <i class="bi bi-chevron-right breadcrumb-sep"></i>
            <span class="breadcrumb-current">{{ isEdit ? 'Ubah' : 'Tambah' }}</span>
        </nav>

        <!-- ============ HEADER ============ -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    {{ isEdit ? 'Ubah Produk' : 'Tambah Produk Baru' }}
                </h1>
                <p class="page-subtitle">
                    {{ isEdit
                        ? 'Perbarui informasi menu, harga, atau ketersediaan stok.'
                        : 'Tambahkan menu es teler baru ke katalog Anda.'
                    }}
                </p>
            </div>
        </div>

        <!-- ============ FORM WRAP ============ -->
        <div class="form-wrap">
            <!-- ============ FORM CARD ============ -->
            <div class="form-card">
                <form @submit.prevent="submit">
                    <!-- SECTION: Info Dasar -->
                    <div class="section-title">
                        <i class="bi bi-info-circle"></i>
                        Informasi Dasar
                    </div>

                    <!-- Nama Produk -->
                    <div class="input-group-custom">
                        <label class="input-label" for="product-name">
                            Nama Produk <span class="required">*</span>
                        </label>
                        <div class="input-wrapper" :class="{ 'has-error': form.errors.name }">
                            <i class="bi bi-cup-straw input-icon"></i>
                            <input
                                id="product-name"
                                v-model="form.name"
                                type="text"
                                placeholder="Contoh: Es Teler Special"
                                autofocus
                                autocomplete="off"
                            />
                        </div>
                        <div v-if="form.errors.name" class="error-text">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Kategori -->
                    <div class="input-group-custom">
                        <label class="input-label" for="product-category">
                            Kategori <span class="required">*</span>
                        </label>
                        <div class="input-wrapper input-wrapper-select" :class="{ 'has-error': form.errors.category_id }">
                            <i class="bi bi-tags input-icon"></i>
                            <select
                                id="product-category"
                                v-model="form.category_id"
                            >
                                <option value="" disabled>Pilih kategori</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">
                                    {{ c.name }}
                                </option>
                            </select>
                            <i class="bi bi-chevron-down select-caret"></i>
                        </div>
                        <div v-if="form.errors.category_id" class="error-text">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ form.errors.category_id }}
                        </div>
                    </div>

                    <!-- Harga -->
                    <div class="input-group-custom">
                        <label class="input-label" for="product-price">
                            Harga <span class="required">*</span>
                        </label>
                        <div class="input-wrapper" :class="{ 'has-error': form.errors.price }">
                            <span class="input-prefix">Rp</span>
                            <input
                                id="product-price"
                                v-model="priceDisplay"
                                type="text"
                                inputmode="numeric"
                                placeholder="0"
                                class="input-with-prefix"
                            />
                            <span v-if="form.price" class="input-suffix">
                                {{ new Intl.NumberFormat('id-ID').format(form.price) }}
                            </span>
                        </div>
                        <div v-if="form.errors.price" class="error-text">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ form.errors.price }}
                        </div>
                        <div v-else class="hint-text">
                            <i class="bi bi-info-circle"></i>
                            Masukkan angka saja, format otomatis
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="input-group-custom">
                        <label class="input-label" for="product-desc">
                            Deskripsi
                            <span class="label-optional">opsional</span>
                        </label>
                        <div class="input-wrapper input-wrapper-textarea" :class="{ 'has-error': form.errors.description }">
                            <textarea
                                id="product-desc"
                                v-model="form.description"
                                rows="4"
                                placeholder="Ceritakan keunggulan menu ini, bahan utama, atau varian topping..."
                            ></textarea>
                            <div class="textarea-counter">
                                {{ form.description.length }}/500
                            </div>
                        </div>
                        <div v-if="form.errors.description" class="error-text">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ form.errors.description }}
                        </div>
                    </div>

                    <!-- SECTION: Foto -->
                    <div class="section-title section-title-spaced">
                        <i class="bi bi-image"></i>
                        Foto Produk
                    </div>

                    <!-- Upload Foto -->
                    <div class="input-group-custom">
                        <label class="input-label">
                            Foto
                            <span class="label-optional">JPG, PNG, WebP · maks 2MB</span>
                        </label>

                        <!-- Kalau belum ada foto -->
                        <div
                            v-if="!preview"
                            class="upload-zone"
                            :class="{ 'is-dragging': isDragging, 'has-error': form.errors.image }"
                            @click="fileInputRef?.click()"
                            @drop.prevent="onDrop"
                            @dragover.prevent="onDragOver"
                            @dragleave.prevent="onDragLeave"
                        >
                            <div class="upload-icon">
                                <i class="bi bi-cloud-arrow-up"></i>
                            </div>
                            <div class="upload-title">
                                <strong>Klik untuk upload</strong> atau drag & drop
                            </div>
                            <div class="upload-hint">
                                Format JPG, PNG, atau WebP · Maks 2 MB
                            </div>
                        </div>

                        <!-- Kalau sudah ada foto -->
                        <div v-else class="image-preview">
                            <img :src="preview" alt="Preview" class="image-preview-img" />
                            <div class="image-preview-overlay">
                                <button type="button" class="preview-btn" @click="fileInputRef?.click()">
                                    <i class="bi bi-arrow-repeat"></i> Ganti
                                </button>
                                <button type="button" class="preview-btn preview-btn-danger" @click="removeImage">
                                    <i class="bi bi-trash3"></i> Hapus
                                </button>
                            </div>
                        </div>

                        <input
                            ref="fileInputRef"
                            type="file"
                            accept="image/*"
                            class="d-none"
                            @change="pilihFoto"
                        />

                        <div v-if="form.errors.image" class="error-text">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ form.errors.image }}
                        </div>
                    </div>

                    <!-- SECTION: Pengaturan -->
                    <div class="section-title section-title-spaced">
                        <i class="bi bi-gear"></i>
                        Pengaturan
                    </div>

                    <!-- Toggle Tersedia -->
                    <div class="toggle-row" @click="form.is_available = !form.is_available">
                        <div class="toggle-info">
                            <div class="toggle-title">Tersedia Dijual</div>
                            <div class="toggle-sub">
                                {{ form.is_available
                                    ? 'Produk akan muncul di menu kasir'
                                    : 'Produk disembunyikan dari menu kasir'
                                }}
                            </div>
                        </div>
                        <div :class="['toggle-switch', { 'is-on': form.is_available }]">
                            <div class="toggle-knob"></div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-actions">
                        <Link href="/admin/products" class="btn-cancel">
                            <i class="bi bi-arrow-left"></i>
                            Batal
                        </Link>
                        <button
                            type="submit"
                            class="btn-submit"
                            :disabled="form.processing || !form.name.trim() || !form.category_id || !form.price"
                        >
                            <span v-if="!form.processing">
                                <i class="bi bi-check-lg"></i>
                                {{ isEdit ? 'Simpan Perubahan' : 'Simpan Produk' }}
                            </span>
                            <span v-else class="spinner"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ============ SIDEBAR ============ -->
            <aside class="form-aside">
                <!-- Live preview -->
                <div class="aside-card">
                    <div class="aside-title">
                        <i class="bi bi-eye"></i> Preview
                    </div>

                    <div class="product-preview">
                        <div class="product-preview-img">
                            <img v-if="preview" :src="preview" alt="Preview" />
                            <div v-else class="product-preview-placeholder">
                                <i class="bi bi-cup-straw"></i>
                            </div>
                        </div>

                        <div class="product-preview-body">
                            <div class="product-preview-name">
                                {{ form.name || 'Nama Produk' }}
                            </div>
                            <div v-if="selectedCategory" class="product-preview-cat">
                                <i class="bi bi-tag-fill"></i>
                                {{ selectedCategory.name }}
                            </div>
                            <div class="product-preview-price">
                                {{ form.price
                                    ? 'Rp ' + new Intl.NumberFormat('id-ID').format(form.price)
                                    : 'Rp 0'
                                }}
                            </div>
                            <div :class="['product-preview-status', form.is_available ? 'available' : 'empty']">
                                <span class="status-dot"></span>
                                {{ form.is_available ? 'Tersedia' : 'Habis' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tips -->
                <div class="tips-card">
                    <div class="tips-icon">
                        <i class="bi bi-lightbulb"></i>
                    </div>
                    <div class="tips-title">Tips Produk</div>
                    <ul class="tips-list">
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Nama produk singkat & mudah dibaca kasir</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Foto produk terang & background bersih</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Harga pembulatan 500 untuk memudahkan</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Toggle <strong>Tersedia</strong> untuk kontrol stok cepat</span>
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
    grid-template-columns: 1fr 320px;
    gap: 20px;
    align-items: start;
    max-width: 1100px;
}

@media (max-width: 960px) {
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

/* ============ INPUT ============ */
.input-group-custom { margin-bottom: 20px; }

.input-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.required { color: #ef4444; }

.label-optional {
    font-size: 11px;
    font-weight: 500;
    color: #94a3b8;
    text-transform: none;
    letter-spacing: 0;
    background: #f4f7ee;
    padding: 2px 8px;
    border-radius: 100px;
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
    z-index: 2;
}

.input-wrapper input,
.input-wrapper select,
.input-wrapper textarea {
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

.input-wrapper input::placeholder,
.input-wrapper textarea::placeholder { color: #a3b190; }

.input-wrapper input:focus,
.input-wrapper select:focus,
.input-wrapper textarea:focus {
    border-color: #84bd33;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(132, 189, 51, 0.15);
}

.input-wrapper:focus-within .input-icon { color: #6ba324; }

.input-wrapper.has-error input,
.input-wrapper.has-error select,
.input-wrapper.has-error textarea {
    border-color: #ef4444;
    background: #fef7f7;
}

.input-wrapper.has-error input:focus,
.input-wrapper.has-error select:focus,
.input-wrapper.has-error textarea:focus {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
}

.input-wrapper.has-error .input-icon { color: #ef4444; }

/* Select */
.input-wrapper-select select {
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 40px;
}

.select-caret {
    position: absolute;
    right: 16px;
    color: #a3b190;
    font-size: 12px;
    pointer-events: none;
}

.input-wrapper-select:focus-within .select-caret { color: #6ba324; }

/* Textarea */
.input-wrapper-textarea { position: relative; }

.input-wrapper-textarea textarea {
    padding: 14px 16px;
    resize: vertical;
    min-height: 100px;
    line-height: 1.5;
}

.textarea-counter {
    position: absolute;
    bottom: 10px;
    right: 12px;
    font-size: 11px;
    color: #a3b190;
    font-family: ui-monospace, monospace;
    background: #ffffff;
    padding: 2px 6px;
    border-radius: 6px;
    pointer-events: none;
}

/* Prefix Rp */
.input-prefix {
    position: absolute;
    left: 16px;
    color: #6ba324;
    font-weight: 800;
    font-size: 14px;
    pointer-events: none;
    z-index: 2;
}

.input-wrapper .input-with-prefix {
    padding-left: 44px;
    padding-right: 16px;
    font-family: ui-monospace, monospace;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.input-suffix {
    position: absolute;
    right: 16px;
    font-size: 11px;
    color: #94a3b8;
    font-family: ui-monospace, monospace;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s;
}

/* Hide suffix karena redundant dengan prefix */
.input-suffix { display: none; }

/* ============ HINT / ERROR ============ */
.hint-text,
.error-text {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 500;
    margin-top: 6px;
}

.hint-text { color: #94a3b8; }
.error-text { color: #dc2626; font-weight: 600; }

/* ============ UPLOAD ZONE ============ */
.upload-zone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 20px;
    border: 2px dashed #d8e8bf;
    border-radius: 14px;
    background: #fafcf6;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.upload-zone:hover,
.upload-zone.is-dragging {
    border-color: #84bd33;
    background: #f0f8e5;
    transform: translateY(-1px);
}

.upload-zone.has-error {
    border-color: #ef4444;
    background: #fef7f7;
}

.upload-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: linear-gradient(135deg, #eef7e0 0%, #e0f0c8 100%);
    color: #6ba324;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    margin-bottom: 12px;
}

.upload-title {
    font-size: 13.5px;
    color: #4a5a3d;
    margin-bottom: 4px;
}

.upload-title strong { color: #6ba324; font-weight: 800; }

.upload-hint {
    font-size: 11.5px;
    color: #94a3b8;
}

/* ============ IMAGE PREVIEW ============ */
.image-preview {
    position: relative;
    width: 100%;
    max-width: 320px;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 8px 24px -12px rgba(20, 33, 10, 0.2);
}

.image-preview-img {
    width: 100%;
    height: auto;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    display: block;
}

.image-preview-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(20, 33, 10, 0.75), transparent 50%);
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    opacity: 0;
    transition: opacity 0.2s;
}

.image-preview:hover .image-preview-overlay { opacity: 1; }

.preview-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 9px;
    border: none;
    background: rgba(255, 255, 255, 0.95);
    color: #14210a;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s;
}

.preview-btn:hover {
    background: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.preview-btn-danger {
    background: rgba(239, 68, 68, 0.95);
    color: #ffffff;
}

.preview-btn-danger:hover { background: #dc2626; }

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
    margin-bottom: 8px;
}

.toggle-row:hover {
    background: #f4f9f0;
    border-color: #d8e8bf;
}

.toggle-info { flex: 1; min-width: 0; }

.toggle-title {
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

/* Product preview card */
.product-preview {
    background: #fafcf6;
    border: 1px solid #eef2e6;
    border-radius: 14px;
    overflow: hidden;
}

.product-preview-img {
    aspect-ratio: 4 / 3;
    background: #f0f4e8;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-preview-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-preview-placeholder {
    font-size: 44px;
    color: #cbd5b5;
}

.product-preview-body {
    padding: 14px;
}

.product-preview-name {
    font-size: 14px;
    font-weight: 800;
    color: #14210a;
    margin-bottom: 6px;
    line-height: 1.3;
    word-break: break-word;
}

.product-preview-cat {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: #558a1a;
    background: #f0f8e5;
    padding: 3px 8px;
    border-radius: 100px;
    font-weight: 700;
    margin-bottom: 10px;
}

.product-preview-cat i { font-size: 10px; }

.product-preview-price {
    font-size: 18px;
    font-weight: 800;
    color: #14210a;
    font-family: ui-monospace, monospace;
    letter-spacing: -0.5px;
    margin-bottom: 10px;
}

.product-preview-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 700;
}

.product-preview-status.available {
    background: #dcfce7;
    color: #15803d;
}
.product-preview-status.empty {
    background: #fee2e2;
    color: #b91c1c;
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
    .form-card { padding: 20px 16px; }
    .form-actions { flex-direction: column-reverse; }
    .btn-cancel, .btn-submit { width: 100%; }
    .image-preview { max-width: 100%; }
}
</style>