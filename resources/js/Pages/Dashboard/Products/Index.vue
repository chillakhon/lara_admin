<script setup>
import {ref, watch} from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import debounce from 'lodash/debounce';
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import SelectDropdown from "@/Components/SelectDropdown.vue";

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
    units: Array
});

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');

// Debounced search
const performSearch = debounce(() => {
    router.get(
        route('dashboard.products.index'),
        {
            search: searchQuery.value,
            category: selectedCategory.value
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true
        }
    );
}, 300);

watch(searchQuery, performSearch);
watch(selectedCategory, performSearch);

// Pagination handler
const handlePageChange = (page) => {
    router.get(
        route('dashboard.products.index', {
            page: page,
            search: searchQuery.value,
            category: selectedCategory.value
        }),
        {},
        {
            preserveState: true,
            preserveScroll: true
        }
    );
};

const breadCrumbs = [
    {
        name: 'Товары',
        link: route('dashboard.products.index')
    }
]

const showModal = ref(false);
const editingProduct = ref(null);

const form = useForm({
    name: '',
    description: '',
    type: 'simple',
    default_unit_id: '',
    is_active: true,
    has_variants: false,
    allow_preorder: false,
    after_purchase_processing_time: 0,
    categories: [],
});

const typeOptions = [
    {value: 'simple', label: 'Простой товар'},
    {value: 'manufactured', label: 'Производимый товар'},
    {value: 'composite', label: 'Составной товар'}
];

const openModal = (product = null) => {
    editingProduct.value = product;
    if (product) {
        form.name = product.name;
        form.description = product.description;
        form.type = product.type;
        form.default_unit_id = product.default_unit_id;
        form.is_active = product.is_active;
        form.has_variants = product.has_variants;
        form.allow_preorder = product.allow_preorder;
        form.after_purchase_processing_time = product.after_purchase_processing_time;
        form.categories = product.categories ? product.categories.map(c => c.id) : [];
    } else {
        form.reset();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingProduct.value = null;
    form.reset();
};

const submitForm = () => {
    if (editingProduct.value) {
        form.put(route('dashboard.products.update', editingProduct.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('dashboard.products.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};


const deleteProduct = (product) => {
    if (confirm('Вы уверены, что хотите удалить этот продукт?')) {
        router.delete(route('dashboard.products.destroy', product.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Товары</h1>
            <div class="sm:flex">
                <div
                    class="items-center hidden mb-3 sm:flex sm:divide-x sm:divide-gray-100 sm:mb-0 dark:divide-gray-700">
                    <form class="lg:pr-3" action="#" method="GET">
                        <label for="users-search" class="sr-only">Поиск</label>
                        <div class="relative mt-1 lg:w-64 xl:w-96">
                            <input type="text" name="email" id="users-search"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                   placeholder="Поиск товаров" v-model="searchQuery">
                        </div>
                    </form>
                </div>
                <div class="flex items-center ml-auto space-x-2 sm:space-x-3">
                    <PrimaryButton type="default" @click="openModal()"
                                   class="inline-flex items-center justify-center w-1/2 px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 sm:w-auto dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                        <template #icon-left>
                            <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                      d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                      clip-rule="evenodd"></path>
                            </svg>
                        </template>
                        Добавить товар
                    </PrimaryButton>
                </div>
            </div>
        </template>
        <template #default>
            <section class="bg-gray-50 dark:bg-gray-900 pt-4">
                <div class="mx-auto px-4">
                    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                        <!-- Search and Filter Section -->
                        <div
                            class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                            <div class="w-full md:w-1/2">
                                <form class="flex items-center">
                                    <label for="simple-search" class="sr-only">Search</label>
                                    <div class="relative w-full">
                                        <div
                                            class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                                                 viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                                      clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <input
                                            type="text"
                                            v-model="searchQuery"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                            placeholder="Поиск товаров"
                                        >
                                    </div>
                                </form>
                            </div>
                            <div
                                class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0">
                                <button @click="openModal()" type="button"
                                        class="flex items-center justify-center text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800">
                                    <svg class="h-3.5 w-3.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path clip-rule="evenodd" fill-rule="evenodd"
                                              d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"/>
                                    </svg>
                                    Добавить товар
                                </button>

                                <!-- Category Filter -->
                                <select
                                    v-model="selectedCategory"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                >
                                    <option value="">Все категории</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead
                                    class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Название</th>
                                    <th scope="col" class="px-4 py-3">Категории</th>
                                    <th scope="col" class="px-4 py-3">Варианты</th>
                                    <th scope="col" class="px-4 py-3">Опции</th>
                                    <th scope="col" class="px-4 py-3">Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="product in products.data" :key="product.id"
                                    class="border-b dark:border-gray-700">
                                    <th class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ product.name }}
                                    </th>
                                    <td class="px-4 py-3">
                                        {{ product.categories.map(c => c.name).join(', ') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ product.variants.length }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ product.options.length }}
                                    </td>
                                    <td class="px-4 py-3 flex items-center justify-end">
                                        <div class="inline-flex items-center">
                                            <button
                                                @click="$inertia.visit(route('dashboard.products.show', product.id))"
                                                class="px-3 py-1 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                                                Просмотр
                                            </button>
                                            <button @click="openModal(product)"
                                                    class="px-3 py-1 ml-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800">
                                                Редактировать
                                            </button>
                                            <button @click="deleteProduct(product)"
                                                    class="px-3 py-1 ml-2 text-sm font-medium text-white bg-red-700 rounded-lg hover:bg-red-800">
                                                Удалить
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4">
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            Показано {{ products.from }}-{{ products.to }} из {{ products.total }}
                        </span>
                            <ul class="inline-flex items-stretch -space-x-px">
                                <li>
                                    <button
                                        @click="handlePageChange(products.current_page - 1)"
                                        :disabled="!products.prev_page_url"
                                        class="flex items-center justify-center h-full py-1.5 px-3 ml-0 text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100"
                                        :class="{ 'opacity-50 cursor-not-allowed': !products.prev_page_url }"
                                    >
                                        <span class="sr-only">Previous</span>
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </li>
                                <li v-for="page in products.links.slice(1, -1)" :key="page.label">
                                    <button
                                        @click="handlePageChange(page.label)"
                                        :class="[
                                        page.active ? 'text-primary-600 bg-primary-50' : 'text-gray-500 bg-white',
                                        'flex items-center justify-center text-sm py-2 px-3 leading-tight border border-gray-300 hover:bg-gray-100'
                                    ]"
                                    >
                                        {{ page.label }}
                                    </button>
                                </li>
                                <li>
                                    <button
                                        @click="handlePageChange(products.current_page + 1)"
                                        :disabled="!products.next_page_url"
                                        class="flex items-center justify-center h-full py-1.5 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100"
                                        :class="{ 'opacity-50 cursor-not-allowed': !products.next_page_url }"
                                    >
                                        <span class="sr-only">Next</span>
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </section>
            <Modal :show="showModal" @close="closeModal" max-width="xl">
                <template #title>
                    {{ editingProduct ? 'Редактировать товар' : 'Добавить новый товар' }}
                </template>

                <template #content>
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Основная информация -->
                            <div class="space-y-4">
                                <TextInput
                                    v-model="form.name"
                                    label="Название товара"
                                    :error="form.errors.name"
                                    required
                                />

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Описание
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    ></textarea>
                                </div>

                                <SelectDropdown
                                    v-model="form.type"
                                    :options="typeOptions"
                                    label="Тип товара"
                                    :error="form.errors.type"
                                    required
                                />

                                <SelectDropdown
                                    v-model="form.default_unit_id"
                                    :options="units.map(unit => ({ value: unit.id, label: unit.name }))"
                                    label="Единица измерения"
                                    :error="form.errors.default_unit_id"
                                    placeholder="Выберите единицу измерения"
                                />
                            </div>

                            <!-- Настройки -->
                            <div class="space-y-4">
                                <div class="flex flex-col gap-3">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_active"
                                            class="sr-only peer"
                                        >
                                        <div
                                            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                        <span
                                            class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Активен</span>
                                    </label>

                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="form.has_variants"
                                            class="sr-only peer"
                                        >
                                        <div
                                            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Имеет варианты</span>
                                    </label>

                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="form.allow_preorder"
                                            class="sr-only peer"
                                        >
                                        <div
                                            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Разрешить предзаказ</span>
                                    </label>
                                </div>

                                <TextInput
                                    v-model="form.after_purchase_processing_time"
                                    type="number"
                                    label="Время обработки после покупки (в днях)"
                                    :error="form.errors.after_purchase_processing_time"
                                />

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Категории
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.categories"
                                        multiple
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    >
                                        <option v-for="category in categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.categories"
                                       class="mt-2 text-sm text-red-600 dark:text-red-500">
                                        {{ form.errors.categories }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </form>
                </template>

                <template #footer>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                        >
                            Отмена
                        </button>
                        <button
                            type="button"
                            @click="submitForm"
                            :disabled="form.processing"
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                        >
                            {{ editingProduct ? 'Сохранить' : 'Добавить' }}
                        </button>
                    </div>
                </template>
            </Modal>
        </template>
    </DashboardLayout>
</template>
