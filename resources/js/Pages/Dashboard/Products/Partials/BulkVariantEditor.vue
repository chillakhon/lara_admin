<script setup>
import {ref, computed, watch} from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import SelectDropdown from '@/Components/SelectDropdown.vue';

const props = defineProps({
    product: {
        type: Object,
        required: true
    },
    variants: {
        type: Array,
        required: true
    }
});

const showBulkEditModal = ref(false);
const selectedFilters = ref([]);
const selectedAction = ref('images');
const actionValue = ref(null);
const selectedFiles = ref([]);
const dragOver = ref(false);
const presetName = ref('');
const showPresetModal = ref(false);

// Структура фильтра
const addFilter = () => {
    selectedFilters.value.push({
        optionId: null,
        valueIds: [],
    });
};

// Удаление фильтра
const removeFilter = (index) => {
    selectedFilters.value.splice(index, 1);
};

// Валидация на стороне клиента
const errors = computed(() => {
    const errors = {};

    if (selectedFilters.value.length === 0) {
        errors.filters = 'Необходимо выбрать хотя бы один фильтр';
    }

    if (selectedAction.value === 'price' && (!actionValue.value || actionValue.value < 0)) {
        errors.price = 'Укажите корректную цену';
    }

    if (selectedAction.value === 'images' && selectedFiles.value.length === 0) {
        errors.images = 'Выберите хотя бы одно изображение';
    }

    if (selectedAction.value === 'sku' && (!actionValue.value || actionValue.value.length < 3)) {
        errors.sku = 'SKU должен содержать минимум 3 символа';
    }

    return errors;
});

const hasErrors = computed(() => Object.keys(errors.value).length > 0);

// Формируем группы вариантов на основе выбранных фильтров
const filteredVariants = computed(() => {
    if (!selectedFilters.value.length) return [];

    return props.variants.filter(variant => {
        return selectedFilters.value.every(filter => {
            if (!filter.optionId || !filter.valueIds.length) return true;

            const variantValue = variant.option_values.find(
                ov => ov.option_id === filter.optionId
            );

            return filter.valueIds.includes(variantValue?.id);
        });
    });
});

// Расширенные действия для массового обновления
const bulkUpdateActions = [
    {id: 'images', name: 'Загрузить изображения'},
    {id: 'price', name: 'Установить цену'},
    {id: 'additional_cost', name: 'Установить доп. стоимость'},
    {id: 'active', name: 'Изменить статус'},
    {id: 'sku', name: 'Обновить SKU'},
    {id: 'name', name: 'Обновить название'}
];

// Обработка drag-and-drop
const handleDragOver = (event) => {
    event.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = () => {
    dragOver.value = false;
};

const handleDrop = (event) => {
    event.preventDefault();
    dragOver.value = false;

    const files = Array.from(event.dataTransfer.files).filter(
        file => file.type.startsWith('image/')
    );

    if (files.length) {
        selectedFiles.value = [...selectedFiles.value, ...files];
    }
};

// Предпросмотр изображений
const imagesPreviews = computed(() => {
    return selectedFiles.value.map(file => ({
        name: file.name,
        url: URL.createObjectURL(file),
        file: file
    }));
});


// Очистка URL при удалении файлов
watch(selectedFiles, (newFiles, oldFiles) => {
    if (oldFiles) {
        oldFiles.forEach(file => {
            if (!newFiles.includes(file)) {
                URL.revokeObjectURL(file.preview);
            }
        });
    }
});

const applyPreset = (preset) => {
    selectedFilters.value = JSON.parse(JSON.stringify(preset.filters));
};

// Форма для обновления
const form = useForm({
    variants: [],
    action: '',
    value: null,
    images: [],
    nameTemplate: '',
    skuTemplate: ''
});

const applyBulkUpdate = () => {
    if (hasErrors.value) return;

    // Создаем FormData для правильной отправки файлов
    const formData = new FormData();
    formData.append('action', selectedAction.value);
    formData.append('variants', JSON.stringify(filteredVariants.value.map(v => v.id)));

    if (selectedAction.value === 'images') {
        // Добавляем каждый файл отдельно
        selectedFiles.value.forEach((file, index) => {
            formData.append(`images[${index}]`, file);
        });
    } else {
        formData.append('value', actionValue.value);
        if (selectedAction.value === 'name') {
            formData.append('name_template', form.nameTemplate);
        }
        if (selectedAction.value === 'sku') {
            formData.append('sku_template', form.skuTemplate);
        }
    }

    // Отправляем через Inertia
    router.post(
        route('dashboard.products.variants.bulk-update', props.product.id),
        formData,
        {
            preserveScroll: true,
            onSuccess: () => {
                showBulkEditModal.value = false;
                resetForm();
            },
            forceFormData: true,
            onError: (errors) => {
                console.error('Errors:', errors);
            }
        }
    );
};

const resetForm = () => {
    selectedFilters.value = [];
    selectedAction.value = 'images';
    actionValue.value = null;
    selectedFiles.value = [];
    selectedFiles.value.forEach(file => URL.revokeObjectURL(file.preview));
};

// Подсказки для шаблонов
const availablePlaceholders = computed(() => {
    const base = [
        {value: '{product_name}', description: 'Название товара'},
        {value: '{variant_id}', description: 'ID варианта'}
    ];

    props.product.options.forEach(option => {
        base.push({
            value: `{${option.name.toLowerCase()}}`,
            description: `Значение опции ${option.name}`
        });
    });

    return base;
});

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files).filter(
        file => file.type.startsWith('image/')
    );
    selectedFiles.value = [...selectedFiles.value, ...files];
};
</script>

<template>
    <button
        @click="showBulkEditModal = true"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
    >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16m-7 6h7"/>
        </svg>
        Массовое редактирование
    </button>
    <Modal :show="showBulkEditModal" @close="showBulkEditModal = false" max-width="2xl">
        <template #title>
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                Массовое редактирование вариантов
            </h2>
        </template>
        <template #content >
            <div class="space-y-6">
                <!-- Множественные фильтры -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-medium text-gray-700">Фильтры</h3>
                        <button
                            @click="addFilter"
                            class="text-sm text-blue-600 hover:text-blue-700"
                        >
                            + Добавить фильтр
                        </button>
                    </div>

                    <div v-for="(filter, index) in selectedFilters" :key="index"
                         class="flex items-center space-x-4 p-4 border rounded-lg">
                        <div class="flex-1">
                            <SelectDropdown
                                v-model="filter.optionId"
                                :options="props.product.options.map(opt => ({
                                    id: opt.id,
                                    name: opt.name
                                }))"
                                class="mb-2"
                                placeholder="Выберите опцию"
                            />

                            <div v-if="filter.optionId" class="mt-2">
                                <template v-for="option in props.product.options" :key="option.id">
                                    <template v-if="option.id === filter.optionId">
                                        <div class="flex flex-wrap gap-2">
                                            <label v-for="value in option.values"
                                                   :key="value.id"
                                                   class="inline-flex items-center">
                                                <input
                                                    type="checkbox"
                                                    v-model="filter.valueIds"
                                                    :value="value.id"
                                                    class="rounded border-gray-300"
                                                >
                                                <span class="ml-2">{{ value.name }}</span>
                                            </label>
                                        </div>
                                    </template>
                                </template>
                            </div>
                        </div>
                        <button
                            @click="removeFilter(index)"
                            class="text-red-600 hover:text-red-700"
                        >
                            ×
                        </button>
                    </div>
                </div>

                <!-- Предпросмотр выбранных вариантов -->
                <div v-if="filteredVariants.length" class="border rounded-lg p-4">
                    <h3 class="font-medium mb-2">
                        Выбрано вариантов: {{ filteredVariants.length }}
                    </h3>
                    <div class="max-h-40 overflow-y-auto">
                        <div v-for="variant in filteredVariants"
                             :key="variant.id"
                             class="text-sm text-gray-600 py-1">
                            {{ variant.name }}
                        </div>
                    </div>
                </div>

                <!-- Выбор действия и значения -->
                <div v-if="filteredVariants.length">
                    <InputLabel value="Выберите действие"/>
                    <SelectDropdown
                        v-model="selectedAction"
                        :options="bulkUpdateActions.map(action => ({
                            id: action.id,
                            name: action.name
                        }))"
                        class="mt-1"
                    />

                    <!-- Поля для конкретного действия -->
                    <div class="mt-4">
                        <!-- Загрузка изображений -->
                        <template v-if="selectedAction === 'images'">
                            <div
                                @dragover="handleDragOver"
                                @dragleave="handleDragLeave"
                                @drop="handleDrop"
                                :class="[
                                        'border-2 border-dashed rounded-lg p-6 text-center',
                                        dragOver ? 'border-blue-500 bg-blue-50' : 'border-gray-300'
                                    ]"
                            >
                                <div class="space-y-2">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24">
                                        <!-- иконка загрузки -->
                                    </svg>
                                    <div class="text-sm text-gray-600">
                                        Перетащите изображения сюда или
                                        <label class="text-blue-600 hover:text-blue-700 cursor-pointer">
                                            выберите файлы
                                            <input
                                                type="file"
                                                @change="handleFileSelect"
                                                multiple
                                                accept="image/*"
                                                class="hidden"
                                            >
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Предпросмотр изображений -->
                            <div v-if="imagesPreviews.length" class="mt-4 grid grid-cols-4 gap-4">
                                <div v-for="(preview, index) in imagesPreviews"
                                     :key="index"
                                     class="relative group">
                                    <img
                                        :src="preview.url"
                                        :alt="preview.name"
                                        class="h-24 w-24 object-cover rounded-lg"
                                    >
                                    <button
                                        @click="selectedFiles.value.splice(index, 1)"
                                        class="absolute top-0 right-0 hidden group-hover:block p-1 bg-red-500 text-white rounded-full"
                                    >
                                        ×
                                    </button>
                                    <span class="text-xs text-gray-500 truncate block mt-1">
                                            {{ preview.name }}
                                        </span>
                                </div>
                            </div>
                        </template>

                        <!-- Обновление цены -->
                        <template v-if="selectedAction === 'price' || selectedAction === 'additional_cost'">
                            <InputLabel :value="selectedAction === 'price' ? 'Цена' : 'Доп. стоимость'"/>
                            <TextInput
                                v-model="actionValue"
                                type="number"
                                step="0.01"
                                class="mt-1"
                            />
                        </template>

                        <!-- Обновление SKU -->
                        <template v-if="selectedAction === 'sku'">
                            <div class="space-y-4">
                                <div>
                                    <InputLabel value="Шаблон SKU"/>
                                    <TextInput
                                        v-model="form.skuTemplate"
                                        type="text"
                                        class="mt-1"
                                        placeholder="Например: {product_name}-{color}-{size}"
                                    />
                                </div>

                                <div class="text-sm text-gray-600">
                                    <div class="mb-2">Доступные placeholder'ы:</div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div v-for="placeholder in availablePlaceholders"
                                             :key="placeholder.value"
                                             class="flex items-center space-x-2">
                                            <code class="bg-gray-100 px-2 py-1 rounded">
                                                {{ placeholder.value }}
                                            </code>
                                            <span class="text-gray-500">
                                                    {{ placeholder.description }}
                                                </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Обновление названия -->
                        <template v-if="selectedAction === 'name'">
                            <div class="space-y-4">
                                <div>
                                    <InputLabel value="Шаблон названия"/>
                                    <TextInput
                                        v-model="form.nameTemplate"
                                        type="text"
                                        class="mt-1"
                                        placeholder="Например: {product_name} - {color}"
                                    />
                                </div>

                                <div class="text-sm text-gray-600">
                                    <div class="mb-2">Доступные placeholder'ы:</div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div v-for="placeholder in availablePlaceholders"
                                             :key="placeholder.value"
                                             class="flex items-center space-x-2">
                                            <code class="bg-gray-100 px-2 py-1 rounded">
                                                {{ placeholder.value }}
                                            </code>
                                            <span class="text-gray-500">
                                                    {{ placeholder.description }}
                                                </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Изменение статуса -->
                        <template v-if="selectedAction === 'active'">
                            <div class="flex items-center mt-2">
                                <input
                                    type="checkbox"
                                    v-model="actionValue"
                                    class="rounded border-gray-300"
                                />
                                <span class="ml-2">Активен</span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Отображение ошибок -->
                <div v-if="hasErrors" class="mt-4 p-4 bg-red-50 rounded-lg">
                    <div v-for="(error, key) in errors" :key="key" class="text-red-600">
                        {{ error }}
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <button
                type="button"
                @click="showBulkEditModal = false"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
                Отмена
            </button>
            <button
                @click="applyBulkUpdate"
                :disabled="hasErrors || !filteredVariants.length || form.processing"
                class="ml-3 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
                Применить
            </button>
        </template>
    </Modal>

    <!-- Модальное окно сохранения предустановки -->
    <Modal :show="showPresetModal" @close="showPresetModal = false">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                Сохранить предустановку
            </h3>

            <div class="space-y-4">
                <div>
                    <InputLabel value="Название предустановки"/>
                    <TextInput
                        v-model="presetName"
                        type="text"
                        class="mt-1"
                        placeholder="Введите название"
                    />
                </div>
            </div>
        </div>

        <template #footer>
            <button
                type="button"
                @click="showPresetModal = false"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
                Отмена
            </button>
            <button
                @click="saveCurrentFiltersAsPreset"
                :disabled="!presetName"
                class="ml-3 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
                Сохранить
            </button>
        </template>
    </Modal>
</template>
