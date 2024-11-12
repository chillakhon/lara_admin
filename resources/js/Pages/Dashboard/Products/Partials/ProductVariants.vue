<script setup>
import {ref, computed, onMounted} from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import SelectDropdown from '@/Components/SelectDropdown.vue';
import BulkVariantEditor from "@/Pages/Dashboard/Products/Partials/BulkVariantEditor.vue";
import {Accordion} from "flowbite";

const props = defineProps({
    product: Object,
    units: Array,
});

const showVariantModal = ref(false);
const showGenerateModal = ref(false);
const editingVariant = ref(null);
const selectedVariants = ref([]);



// Функция для переключения состояния аккордеона
const toggleDetails = (variantId) => {
    const row = document.querySelector(`#variant-row-${variantId}`);
    const details = document.querySelector(`#variant-details-${variantId}`);

    if (details.classList.contains('hidden')) {
        details.classList.remove('hidden');
        row.setAttribute('aria-expanded', 'true');
    } else {
        details.classList.add('hidden');
        row.setAttribute('aria-expanded', 'false');
    }
}
// Форма для создания/редактирования варианта
const form = useForm({
    name: '',
    sku: '',
    price: '',
    additional_cost: 0,
    type: 'simple',
    unit_id: '',
    is_active: true,
    option_values: [],
});

// Состояние для генерации вариантов
const generationForm = useForm({
    selected_options: [],
    base_price: '',
    generate_skus: true,
    include_options_in_name: false,
});

const typeOptions = [
    {value: 'simple', label: 'Простой товар'},
    {value: 'manufactured', label: 'Производимый товар'},
    {value: 'composite', label: 'Составной товар'},
];

// Получаем все возможные комбинации значений опций
const generateCombinations = (arrays) => {
    return arrays.reduce((acc, curr) => {
        if (acc.length === 0) return curr.map(x => [x]);
        return acc.flatMap(x => curr.map(y => [...x, y]));
    }, []);
};

// Генерация SKU на основе названия и значений опций
const generateSKU = (name, optionValues, index) => {
    const baseSlug = name
        .toLowerCase()
        .replace(/[^a-z0-9]/g, '')
        .substring(0, 5)
        .toUpperCase();

    // Даже если мы не включаем опции в название, нам нужно использовать их для SKU
    const optionSlug = optionValues
        .map(v => v.name.substring(0, 2).toUpperCase())
        .join('');

    // Добавляем случайное число для уникальности
    const randomPart = Math.floor(Math.random() * 1000).toString().padStart(3, '0');

    return `${baseSlug}-${optionSlug}-${randomPart}`;
};

// Генерация вариантов на основе выбранных опций
const generateVariants = () => {
    const selectedOptions = props.product.options.filter(opt =>
        generationForm.selected_options.includes(opt.id)
    );

    const optionValueArrays = selectedOptions.map(opt => opt.values);
    const combinations = generateCombinations(optionValueArrays);

    const variants = combinations.map((combination, index) => {
        // Название может быть простым или с опциями
        const name = generationForm.include_options_in_name
            ? [props.product.name, ...combination.map(v => v.name)].join(' - ')
            : props.product.name;

        // SKU всегда должен включать информацию о вариантах для уникальности
        const sku = generationForm.generate_skus
            ? generateSKU(props.product.name, combination, index)
            : '';

        return {
            name,
            sku,
            price: generationForm.base_price,
            additional_cost: 0,
            type: props.product.type,
            unit_id: props.product.default_unit_id,
            is_active: true,
            option_values: combination.map(v => v.id)
        };
    });

    return variants;
};

const saveGeneratedVariants = () => {
    const variants = generateVariants();
    useForm({variants}).post(route('dashboard.products.variants.generate', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            showGenerateModal.value = false;
            generationForm.reset();
        },
    });
};

const openVariantModal = (variant = null) => {
    editingVariant.value = variant;
    if (variant) {
        form.name = variant.name;
        form.sku = variant.sku;
        form.price = variant.price;
        form.additional_cost = variant.additional_cost;
        form.type = variant.type;
        form.unit_id = variant.unit_id;
        form.is_active = variant.is_active;
        form.option_values = variant.option_values.map(v => v.id);
    } else {
        form.reset();
    }
    showVariantModal.value = true;
};

const closeVariantModal = () => {
    showVariantModal.value = false;
    editingVariant.value = null;
    form.reset();
};

const submitVariant = () => {
    if (editingVariant.value) {
        form.put(route('dashboard.products.variants.update', [props.product.id, editingVariant.value.id]), {
            preserveScroll: true,
            onSuccess: () => closeVariantModal(),
        });
    } else {
        form.post(route('dashboard.products.variants.store', props.product.id), {
            preserveScroll: true,
            onSuccess: () => closeVariantModal(),
        });
    }
};

const deleteVariant = async (variant) => {
    if (confirm('Вы уверены, что хотите удалить этот вариант?')) {
        await router.delete(route('dashboard.products.variants.destroy', [props.product.id, variant.id]), {
            preserveScroll: true,
        });
    }
};

const deleteImage = async (imageId, variantId) => {
    if (!confirm('Вы уверены, что хотите удалить это изображение?')) {
        return;
    }

    try {
        await router.delete(
            route('dashboard.products.variants.images.destroy', [
                props.product.id,
                variantId,
                imageId
            ]),
            {
                preserveScroll: true,
            }
        );
    } catch (error) {
        console.error('Error deleting image:', error);
    }
};

// Добавим компонент Flowbite для работы аккордеона
onMounted(() => {
    const accordion = new Accordion(document.querySelector('[data-accordion="table-column"]'));
});
</script>

<template>
    <div class="bg-white rounded-lg shadow dark:bg-gray-800">
        <div class="p-4 sm:p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    Варианты товара
                </h2>
            </div>
            <div class="flex space-x-2 mb-4">
                <button
                    @click="showGenerateModal = true"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    v-if="product.options.length > 0"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Сгенерировать варианты
                </button>
                <BulkVariantEditor
                    v-if="product.variants?.length > 0"
                    :product="product"
                    :variants="product.variants"
                />
                <button
                    @click="openVariantModal()"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Добавить вариант
                </button>

            </div>

            <!-- Variants Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="p-4">
                            <div class="flex items-center">
                                <input type="checkbox" class="w-4 h-4 text-primary-600 bg-gray-100 rounded border-gray-300">
                            </div>
                        </th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Expand/Collapse Row</span>
                        </th>
                        <th scope="col" class="px-4 py-3">Название/SKU</th>
                        <th scope="col" class="px-4 py-3">Тип</th>
                        <th scope="col" class="px-4 py-3">Цена</th>
                        <th scope="col" class="px-4 py-3">Доп. стоимость</th>
                        <th scope="col" class="px-4 py-3">Статус</th>
                        <th scope="col" class="px-4 py-3">Действия</th>
                    </tr>
                    </thead>
                    <tbody data-accordion="table-column">
                    <template v-for="variant in product.variants" :key="variant.id">
                        <!-- Основная строка варианта -->
                        <tr
                            class="border-b dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700 cursor-pointer transition"
                            :id="'variant-row-' + variant.id"
                            :data-accordion-target="'#variant-details-' + variant.id"
                            aria-expanded="false"
                        >
                            <td class="px-4 py-3 w-4">
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        :value="variant.id"
                                        v-model="selectedVariants"
                                        @click.stop
                                        class="w-4 h-4 text-primary-600 bg-gray-100 rounded border-gray-300"
                                    >
                                </div>
                            </td>
                            <td class="p-3 w-4">
                                <svg class="w-6 h-6 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </td>
                            <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                <div class="flex items-center">
                                    <img
                                        v-if="variant.images?.length"
                                        :src="variant.images.find(img => img.is_main)?.url || variant.images[0].url"
                                        :alt="variant.name"
                                        class="h-8 w-8 object-cover rounded-full mr-3"
                                    >
                                    <div>
                                        <div>{{ variant.name }}</div>
                                        <div class="text-sm text-gray-500">{{ variant.sku }}</div>
                                    </div>
                                </div>
                            </th>
                            <td class="px-4 py-3">{{ variant.type }}</td>
                            <td class="px-4 py-3">{{ variant.price }}</td>
                            <td class="px-4 py-3">{{ variant.additional_cost }}</td>
                            <td class="px-4 py-3">
                                <div class="w-fit" :class="{
                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': variant.is_active,
                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': !variant.is_active
                    }">
                                    {{ variant.is_active ? 'Активен' : 'Неактивен' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-2">
                                    <button @click.stop="openVariantModal(variant)" class="text-blue-600 hover:text-blue-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click.stop="deleteVariant(variant)" class="text-red-600 hover:text-red-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <!-- Детальная информация варианта -->
                        <tr
                            :id="'variant-details-' + variant.id"
                            class="hidden"
                            :aria-labelledby="'variant-row-' + variant.id"
                        >
                            <td colspan="8" class="p-4 border-b dark:border-gray-700">
                                <!-- Изображения варианта -->
                                <div class="grid grid-cols-4 gap-4 mb-4">
                                    <div v-for="image in variant.images"
                                         :key="image.id"
                                         class="relative group p-2 bg-gray-100 rounded-lg h-32 dark:bg-gray-700">
                                        <img
                                            :src="image.url"
                                            :alt="variant.name"
                                            class="h-full w-full object-contain"
                                        >
                                        <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <button
                                                @click.stop="deleteImage(image.id, variant.id)"
                                                class="text-white bg-red-600 p-2 rounded-full hover:bg-red-700"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <span v-if="image.is_main"
                                              class="absolute top-1 left-1 bg-blue-500 text-white text-xs px-2 py-1 rounded">
                                            Главное
                                        </span>
                                    </div>
                                </div>
                                <!-- Загрузка новых изображений -->
                                <div
                                    class="border-2 border-dashed border-gray-300 rounded-lg p-4 flex flex-col items-center justify-center cursor-pointer hover:border-blue-500"
                                    @click="openFileInput(variant.id)"
                                    @dragover.prevent="dragOver = true"
                                    @dragleave.prevent="dragOver = false"
                                    @drop.prevent="handleDrop($event, variant.id)"
                                >
                                    <input
                                        :ref="'fileInput_' + variant.id"
                                        type="file"
                                        multiple
                                        accept="image/*"
                                        class="hidden"
                                        @change="handleFileSelect($event, variant.id)"
                                    >
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span class="mt-2 text-sm text-gray-500">Добавить изображения</span>
                                </div>
                                <!-- Опции варианта -->
                                <div class="grid grid-cols-4 gap-4 mt-4">
                                    <template v-for="optionValue in variant.option_values" :key="optionValue.id">
                                        <div v-if="optionValue.option"
                                             class="relative p-3 bg-gray-100 rounded-lg dark:bg-gray-700">
                                            <h6 class="mb-2 text-base font-medium text-gray-900 dark:text-white">
                                                {{ optionValue.option?.name }}
                                            </h6>
                                            <div class="flex items-center">
                                                <div v-if="optionValue.color_code"
                                                     :style="{ backgroundColor: optionValue.color_code }"
                                                     class="w-6 h-6 rounded-full mr-2">
                                                </div>
                                                {{ optionValue.name }}
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Дополнительная информация -->
                                <div class="grid grid-cols-4 gap-4 mt-4">
                                    <div class="p-3 bg-gray-100 rounded-lg dark:bg-gray-700">
                                        <h6 class="mb-2 text-base font-medium">Единица измерения</h6>
                                        <div>{{ variant.unit?.name || 'Не указана' }}</div>
                                    </div>
                                    <div class="p-3 bg-gray-100 rounded-lg dark:bg-gray-700">
                                        <h6 class="mb-2 text-base font-medium">Тип варианта</h6>
                                        <div>{{ variant.type }}</div>
                                    </div>
                                </div>

                                <!-- Кнопки действий -->
                                <div class="flex items-center space-x-3 mt-4">
                                    <button @click="openVariantModal(variant)"
                                            class="py-2 px-3 flex items-center text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                        Редактировать
                                    </button>
                                    <button class="py-2 px-3 flex items-center text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100">
                                        Загрузить изображения
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Variant Modal -->
        <Modal :show="showVariantModal" @close="closeVariantModal" max-width="2xl">
            <template #title>
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ editingVariant ? 'Редактировать вариант' : 'Добавить новый вариант' }}
                </h2>
            </template>
            <template #content>
                <form class="space-y-4">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="name" value="Название"/>
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.name" class="mt-2"/>
                        </div>

                        <div>
                            <InputLabel for="sku" value="SKU"/>
                            <TextInput
                                id="sku"
                                v-model="form.sku"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.sku" class="mt-2"/>
                        </div>

                        <div>
                            <InputLabel for="price" value="Цена"/>
                            <TextInput
                                id="price"
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.price" class="mt-2"/>
                        </div>

                        <div>
                            <InputLabel for="additional_cost" value="Дополнительная стоимость"/>
                            <TextInput
                                id="additional_cost"
                                v-model="form.additional_cost"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.additional_cost" class="mt-2"/>
                        </div>

                        <div>
                            <InputLabel for="type" value="Тип"/>
                            <SelectDropdown
                                id="type"
                                v-model="form.type"
                                :options="typeOptions"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.type" class="mt-2"/>
                        </div>

                        <div>
                            <InputLabel for="unit_id" value="Единица измерения"/>
                            <SelectDropdown
                                id="unit_id"
                                v-model="form.unit_id"
                                :options="units.map(unit => ({ value: unit.id, label: unit.name }))"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.unit_id" class="mt-2"/>
                        </div>
                    </div>

                    <!-- Option Values -->
                    <div v-if="product.options.length > 0">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            Значения опций
                        </h3>
                        <div class="space-y-4">
                            <div v-for="option in product.options"
                                 :key="option.id"
                                 class="border p-4 rounded-lg"
                            >
                                <InputLabel :for="'option_' + option.id" :value="option.name"/>
                                <SelectDropdown
                                    :id="'option_' + option.id"
                                    v-model="form.option_values[option.id]"
                                    :options="option.values.map(value => ({
                                    value: value.id,
                                    label: value.name
                                }))"
                                    class="mt-1 block w-full"
                                    :required="option.is_required"
                                />
                                <InputError :message="form.errors[`option_values.${option.id}`]" class="mt-2"/>
                            </div>
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                <span class="ms-3 text-sm font-medium text-gray-900 dark:text-white">Активен</span>
                            </label>
                        </div>

                    </div>
                </form>
            </template>
            <template #footer>
                <button
                    type="button"
                    @click="closeVariantModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Отмена
                </button>
                <button
                    type="submit"
                    @click.prevent="submitVariant"
                    :disabled="form.processing"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    {{ editingVariant ? 'Сохранить' : 'Добавить' }}
                </button>
            </template>
        </Modal>

        <!-- Generate Variants Modal -->
        <Modal :show="showGenerateModal" @close="showGenerateModal = false" max-width="2xl">
            <template #title>
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Генерация вариантов
                </h2>
            </template>
            <template #content>

                <form @submit.prevent="" class="space-y-4">
                    <!-- Select Options for Generation -->
                    <div>
                        <InputLabel value="Выберите опции для генерации"/>
                        <div class="mt-2 space-y-2">
                            <label v-for="option in product.options" :key="option.id" class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="generationForm.selected_options"
                                    :value="option.id"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                >
                                <span class="ml-2 text-gray-700 dark:text-gray-300">{{ option.name }}</span>
                            </label>
                        </div>
                        <InputError :message="generationForm.errors.selected_options" class="mt-2"/>
                    </div>
                    <div class="flex items-center">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="generationForm.include_options_in_name"
                                   class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            <span class="ms-3 text-sm font-medium text-gray-900 dark:text-white">
                                Добавлять опции в название
                            </span>
                        </label>
                    </div>
                    <!-- Base Price -->
                    <div>
                        <InputLabel for="base_price" value="Базовая цена"/>
                        <TextInput
                            id="base_price"
                            v-model="generationForm.base_price"
                            type="number"
                            step="0.01"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="generationForm.errors.base_price" class="mt-2"/>
                    </div>

                    <!-- Generate SKUs -->
                    <div class="flex items-center">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="generationForm.generate_skus" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            <span class="ms-3 text-sm font-medium text-gray-900 dark:text-white">Генерировать SKU автоматически</span>
                        </label>
                    </div>

                    <!-- Preview Generated Variants -->
                    <div v-if="generationForm.selected_options.length > 0" class="mt-4">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Предварительный просмотр (будет создано {{ generateVariants().length }} вариантов)
                        </h3>
                        <div class="max-h-60 overflow-y-auto">
                            <div v-for="(variant, index) in generateVariants()" :key="index"
                                 class="p-2 border-b last:border-b-0 text-sm text-gray-600 dark:text-gray-400">
                                {{ variant.name }}
                            </div>
                        </div>
                    </div>

                </form>
            </template>
            <template #footer>
                <button
                    type="button"
                    @click="showGenerateModal = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Отмена
                </button>
                <button
                    type="submit" @click.prevent="saveGeneratedVariants"
                    :disabled="generationForm.processing || generationForm.selected_options.length === 0"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                >
                    Сгенерировать варианты
                </button>
            </template>
        </Modal>
    </div>
</template>
