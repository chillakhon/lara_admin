<script setup>
import {ref, computed} from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import {Draggable} from '@braks/revue-draggable';
const props = defineProps({
    product: {
        type: Object,
        required: true
    }
});

const showUploadModal = ref(false);
const selectedFiles = ref([]);
const uploadProgress = ref(0);
const isDragging = ref(false);
const previewImages = ref([]);
const selectedVariant = ref(null);

const form = useForm({
    images: [],
    product_id: props.product.id,
    product_variant_id: null,
    is_main: false
});

// Получаем сгруппированные изображения по вариантам
const groupedImages = computed(() => {
    const groups = {
        main: props.product.images.filter(img => !img.pivot.product_variant_id)
    };

    props.product.variants?.forEach(variant => {
        groups[variant.id] = props.product.images.filter(
            img => img.pivot.product_variant_id === variant.id
        );
    });

    return groups;
});

const handleFileDrop = (e) => {
    e.preventDefault();
    isDragging.value = false;

    const files = Array.from(e.dataTransfer?.files || []);
    handleFileSelect(files);
};

const handleFileSelect = (files) => {
    const validFiles = Array.from(files).filter(file =>
        file.type.startsWith('image/')
    );

    previewImages.value = [];
    selectedFiles.value = validFiles;

    validFiles.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImages.value.push(e.target.result);
        };
        reader.readAsDataURL(file);
    });

    showUploadModal.value = true;
};

const uploadImages = async () => {
    const formData = new FormData();
    selectedFiles.value.forEach(file => formData.append('images[]', file));
    formData.append('product_variant_id', selectedVariant.value);
    formData.append('is_main', form.is_main);

    form.images = formData;

    form.post(route('dashboard.products.images.store', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            showUploadModal.value = false;
            selectedFiles.value = [];
            previewImages.value = [];
            uploadProgress.value = 0;
        },
        onProgress: (progress) => {
            uploadProgress.value = progress.percentage;
        }
    });
};

const deleteImage = async (imageId) => {
    if (confirm('Вы уверены, что хотите удалить это изображение?')) {
        await router.delete(route('dashboard.products.images.destroy', [props.product.id, imageId]), {
            preserveScroll: true
        });
    }
};

const setMainImage = async (imageId) => {
    await router.put(route('dashboard.products.images.main', [props.product.id, imageId]), {}, {
        preserveScroll: true
    });
};

const updateImagesOrder = async (images) => {
    await router.put(route('dashboard.products.images.reorder', props.product.id), {
        images: images.map((image, index) => ({
            id: image.id,
            order: index
        }))
    }, {
        preserveScroll: true
    });
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Изображения товара
                </h2>
                <button
                    @click="showUploadModal = true"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Загрузить изображения
                </button>
            </div>

            <!-- Tabs for variants if product has them -->
            <div v-if="product.has_variants" class="mb-6">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex space-x-4" aria-label="Tabs">
                        <button
                            @click="selectedVariant = null"
                            class="px-3 py-2 text-sm font-medium"
                            :class="{
                                'border-b-2 border-blue-500 text-blue-600': !selectedVariant,
                                'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300': selectedVariant
                            }"
                        >
                            Основные
                        </button>
                        <button
                            v-for="variant in product.variants"
                            :key="variant.id"
                            @click="selectedVariant = variant.id"
                            class="px-3 py-2 text-sm font-medium"
                            :class="{
                                'border-b-2 border-blue-500 text-blue-600': selectedVariant === variant.id,
                                'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300': selectedVariant !== variant.id
                            }"
                        >
                            {{ variant.name }}
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Images Grid -->
            <div
                class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
                @dragover.prevent
                @drop.prevent="handleFileDrop"
                @dragenter.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
            >
                <Draggable
                    v-model="groupedImages[selectedVariant || 'main']"
                    @end="updateImagesOrder"
                    item-key="id"
                    class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
                >
                    <template #item="{ element: image }">
                        <div
                            class="relative group aspect-square rounded-lg overflow-hidden border-2 dark:border-gray-700"
                            :class="{ 'border-blue-500': image.is_main, 'border-gray-200': !image.is_main }"
                        >
                            <img
                                :src="image.url"
                                :alt="product.name"
                                class="w-full h-full object-cover"
                            />

                            <!-- Image Actions Overlay -->
                            <div
                                class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-2">
                                <button
                                    v-if="!image.is_main"
                                    @click="setMainImage(image.id)"
                                    class="p-2 text-white hover:text-blue-400"
                                    title="Сделать основным"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                                <button
                                    @click="deleteImage(image.id)"
                                    class="p-2 text-white hover:text-red-400"
                                    title="Удалить"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Main Image Badge -->
                            <div
                                v-if="image.is_main"
                                class="absolute top-2 left-2 px-2 py-1 bg-blue-500 text-white text-xs rounded-full"
                            >
                                Основное
                            </div>
                        </div>
                    </template>
                </Draggable>

                <!-- Drop Zone -->
                <div
                    v-if="isDragging"
                    class="absolute inset-0 bg-blue-500 bg-opacity-10 border-2 border-blue-500 border-dashed rounded-lg flex items-center justify-center"
                >
                    <p class="text-blue-500 text-lg font-medium">
                        Отпустите файлы для загрузки
                    </p>
                </div>
            </div>
        </div>

        <!-- Upload Modal -->
        <Modal :show="showUploadModal" @close="showUploadModal = false" max-width="2xl">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Загрузка изображений
                </h2>

                <!-- Preview Grid -->
                <div v-if="previewImages.length" class="grid grid-cols-3 gap-4 mb-4">
                    <div
                        v-for="(preview, index) in previewImages"
                        :key="index"
                        class="relative aspect-square rounded-lg overflow-hidden"
                    >
                        <img
                            :src="preview"
                            class="w-full h-full object-cover"
                        />
                        <button
                            @click="previewImages.splice(index, 1); selectedFiles.splice(index, 1)"
                            class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Upload Form -->
                <div class="space-y-4">
                    <div
                        class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center"
                        @dragover.prevent
                        @drop.prevent="handleFileDrop"
                        @dragenter.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                    >
                        <input
                            type="file"
                            multiple
                            accept="image/*"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            @change="handleFileSelect($event.target.files)"
                        />
                        <div class="space-y-2">
                            <svg class="mx-auto w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">
                                Перетащите файлы сюда или кликните для выбора
                            </p>
                        </div>
                    </div>

                    <!-- Variant Selection -->
                    <div v-if="product.has_variants">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Вариант товара
                        </label>
                        <select
                            v-model="selectedVariant"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option :value="null">Основной товар</option>
                            <option v-for="variant in product.variants" :key="variant.id" :value="variant.id">
                                {{ variant.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Main Image Toggle -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Сделать основным изображением
                        </span>
                        <Switch
                            v-model="form.is_main"
                            class="relative inline-flex h-6 w-11 items-center rounded-full"
                            :class="form.is_main ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                        >
                            <span class="sr-only">Сделать основным</span>
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                                :class="form.is_main ? 'translate-x-6' : 'translate-x-1'"
                            />
                        </Switch>
                    </div>

                    <!-- Upload Progress -->
                    <div v-if="form.processing" class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div
                            class="bg-blue-600 h-2.5 rounded-full"
                            :style="{ width: `${uploadProgress}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        @click="showUploadModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Отмена
                    </button>
                    <button
                        @click="uploadImages"
                        :disabled="form.processing || !selectedFiles.length"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Загрузка...' : 'Загрузить' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Empty State -->
        <div
            v-if="!product.images.length"
            class="text-center py-12"
        >
            <svg
                class="mx-auto h-12 w-12 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                Нет изображений
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Начните с загрузки изображений для вашего товара
            </p>
            <div class="mt-6">
                <button
                    @click="showUploadModal = true"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <svg
                        class="-ml-1 mr-2 h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    Загрузить изображения
                </button>
            </div>
        </div>
    </div>
</template>
