<script setup>
import {ref, watch} from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectDropdown from '@/Components/SelectDropdown.vue';
import debounce from 'lodash/debounce';
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import BreadCrumbs from "@/Components/BreadCrumbs.vue";

const props = defineProps({
    options: Object,
    categories: Array,
    filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');
const breadCrumbs = [
    {
        name: 'Опции',
        link: route('dashboard.options.index')
    }
];
const imageErrors = ref(new Set());
const deletedImages = ref(new Set());

// Управление модальным окном
const showModal = ref(false);
const showValueForm = ref(false);
const editingOption = ref(null);

// Основная форма для опции
const form = useForm({
    name: '',
    category_id: '',
    is_required: false,
    order: 0,
    values: [], // массив значений опции
});

// Форма для значений опции
const valueForm = useForm({
    name: '',
    value: '',
    color_code: '',
    order: 0,
    image: null,
    image_preview: null,
});

const editValue = (index) => {
    const value = form.values[index];
    valueForm.name = value.name;
    valueForm.value = value.value;
    valueForm.color_code = value.color_code;
    valueForm.image_preview = value.image_preview;
    valueForm.id = value.id;
    valueForm.editing = index;
    showValueForm.value = true;
};
// Дебаунс поиска
const performSearch = debounce(() => {
    router.get(
        route('dashboard.options.index'),
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

// Пагинация
const handlePageChange = (page) => {
    router.get(
        route('dashboard.options.index', {
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

const hasImage = (value) => {
    return value.images && value.images.length > 0;
};

// Получение URL изображения
const getValueImageUrl = (value) => {
    if (hasImage(value)) {
        return value.images[0].url;
    }
    return value.image_preview;
};
const getValueThumbnailUrl = (value) => {
    if (value.images && value.images[0]) {
        const url = value.images[0].url;
        const pathInfo = url.split('.');
        return `${pathInfo[0]}_thumb_200x200.${pathInfo[1]}`;
    }
    return value.image_preview;
};

// Обработчик загрузки изображения
const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) { // 2MB limit
            imageErrors.value.add('Размер файла не должен превышать 2MB');
            return;
        }

        if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
            imageErrors.value.add('Поддерживаются только форматы JPEG, PNG и GIF');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            valueForm.image_preview = e.target.result;
        };
        reader.readAsDataURL(file);

        valueForm.image = file;
        imageErrors.value.clear();
    }
};

// Открытие модального окна
const openModal = (option = null) => {
    editingOption.value = option;
    if (option) {
        form.name = option.name;
        form.category_id = option.category_id;
        form.is_required = option.is_required;
        form.order = option.order;
        // Преобразуем существующие значения в нужный формат
        form.values = option.values.map(value => ({
            id: value.id,
            name: value.name,
            value: value.value,
            color_code: value.color_code,
            order: value.order,
            image_preview: value.images?.[0]?.url,
            hasImage: value.images?.length > 0
        }));
    } else {
        form.reset();
        form.values = [];
    }
    showModal.value = true;
};

// Закрытие модального окна
const closeModal = () => {
    showModal.value = false;
    showValueForm.value = false;
    editingOption.value = null;
    form.reset();
    valueForm.reset();
};

// Отмена добавления значения
const cancelValue = () => {
    showValueForm.value = false;
    valueForm.reset();
};

// Добавление значения опции
const addValue = () => {
    if (!valueForm.name) {
        return;
    }

    const newValue = {
        name: valueForm.name,
        value: valueForm.value || valueForm.name,
        color_code: valueForm.color_code,
        order: form.values.length + 1,
        image: valueForm.image,
        image_preview: valueForm.image_preview,
        id: valueForm.id // если редактируем существующее значение
    };

    if (valueForm.editing !== undefined) {
        // Обновляем существующее значение
        form.values[valueForm.editing] = newValue;
    } else {
        // Добавляем новое значение
        form.values.push(newValue);
    }

    showValueForm.value = false;
    valueForm.reset();
};
// Удаление значения опции
const removeValue = (index) => {
    if (!confirm('Вы уверены, что хотите удалить это значение?')) {
        return;
    }

    const value = form.values[index];
    if (value.id) {
        deletedImages.value.add(value.id);
    }
    form.values.splice(index, 1);
    updateValueOrders();
};

const updateValueOrders = () => {
    form.values.forEach((value, index) => {
        value.order = index + 1;
    });
};

// Перемещение значения
const moveValue = (index, direction) => {
    if (direction === 'up' && index > 0) {
        [form.values[index], form.values[index - 1]] = [form.values[index - 1], form.values[index]];
    } else if (direction === 'down' && index < form.values.length - 1) {
        [form.values[index], form.values[index + 1]] = [form.values[index + 1], form.values[index]];
    }
    // Обновляем порядок
    form.values.forEach((value, idx) => {
        value.order = idx + 1;
    });
};

// Отправка формы
const submitForm = () => {
    if (form.values.length === 0) {
        alert('Добавьте хотя бы одно значение для опции');
        return;
    }

    // Подготавливаем данные для отправки
    const formData = new FormData();

    // Добавляем основные поля
    formData.append('name', form.name);
    formData.append('category_id', form.category_id);
    formData.append('is_required', form.is_required ? '1' : '0');
    formData.append('order', form.order);

    // Добавляем значения
    form.values.forEach((value, index) => {
        // Если есть id, это существующее значение
        if (value.id) {
            formData.append(`values[${index}][id]`, value.id);
        }
        formData.append(`values[${index}][name]`, value.name);
        formData.append(`values[${index}][value]`, value.value || value.name);
        formData.append(`values[${index}][color_code]`, value.color_code || '');
        formData.append(`values[${index}][order]`, value.order);

        // Если есть новое изображение
        if (value.image instanceof File) {
            formData.append(`values[${index}][image]`, value.image);
        }

        // Если нужно удалить изображение
        if (deletedImages.value.has(value.id)) {
            formData.append(`values[${index}][delete_image]`, '1');
        }
    });

    if (editingOption.value) {
        // При обновлении используем router.post с методом PUT
        router.post(route('dashboard.options.update', editingOption.value.id), formData, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: (errors) => {
                console.error('Ошибки при обновлении:', errors);
            },
            headers: {
                'X-HTTP-Method-Override': 'PUT'
            }
        });
    } else {
        router.post(route('dashboard.options.store'), formData, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: (errors) => {
                console.error('Ошибки при создании:', errors);
            }
        });
    }
};

// Удаление опции
const deleteOption = (option) => {
    if (confirm('Вы уверены, что хотите удалить эту опцию?')) {
        router.delete(route('dashboard.options.destroy', option.id), {
            preserveScroll: true,
        });
    }
};


watch(searchQuery, performSearch);
watch(selectedCategory, performSearch);

</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Опции товаров</h1>
            <div class="sm:flex">
                <div
                    class="items-center hidden mb-3 sm:flex sm:divide-x sm:divide-gray-100 sm:mb-0 dark:divide-gray-700">
                    <form class="lg:pr-3" action="#" method="GET">
                        <label for="options-search" class="sr-only">Поиск</label>
                        <div class="relative mt-1 lg:w-64 xl:w-96">
                            <input type="text"
                                   id="options-search"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                   placeholder="Поиск опций"
                                   v-model="searchQuery">
                        </div>
                    </form>
                </div>
                <div class="flex items-center ml-auto space-x-2 sm:space-x-3">
                    <button type="button" @click="openModal()"
                            class="inline-flex items-center justify-center w-1/2 px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 sm:w-auto dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                  d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                  clip-rule="evenodd"></path>
                        </svg>
                        Добавить опцию
                    </button>
                </div>
            </div>
        </template>

        <template #default>
            <div class="p-4">
                <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                    <!-- Фильтры -->
                    <div
                        class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                        <div class="w-full md:w-1/2">
                            <SelectDropdown
                                v-model="selectedCategory"
                                :options="[{value: '', label: 'Все категории'}, ...categories.map(c => ({value: c.id, label: c.name}))]"
                                placeholder="Фильтр по категории"
                            />
                        </div>
                    </div>

                    <!-- Таблица -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead
                                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3">Название</th>
                                <th scope="col" class="px-4 py-3">Категория</th>
                                <th scope="col" class="px-4 py-3">Обязательная</th>
                                <th scope="col" class="px-4 py-3">Порядок</th>
                                <th scope="col" class="px-4 py-3">Значения</th>
                                <th scope="col" class="px-4 py-3">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="option in options.data" :key="option.id"
                                class="border-b dark:border-gray-700">
                                <th class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ option.name }}
                                </th>
                                <td class="px-4 py-3">
                                    {{ option.category.name }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="option.is_required ? 'text-green-500' : 'text-red-500'">
                                        {{ option.is_required ? 'Да' : 'Нет' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    {{ option.order }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ option.values.length }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <button @click="openModal(option)"
                                                class="px-3 py-1 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800">
                                            Редактировать
                                        </button>
                                        <button @click="deleteOption(option)"
                                                class="px-3 py-1 text-sm font-medium text-white bg-red-700 rounded-lg hover:bg-red-800">
                                            Удалить
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Пагинация -->
                    <nav
                        class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4">
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            Показано {{ options.from }}-{{ options.to }} из {{ options.total }}
                        </span>
                        <ul class="inline-flex items-stretch -space-x-px">
                            <li>
                                <button
                                    @click="handlePageChange(options.current_page - 1)"
                                    :disabled="!options.prev_page_url"
                                    class="flex items-center justify-center h-full py-1.5 px-3 ml-0 text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100"
                                    :class="{ 'opacity-50 cursor-not-allowed': !options.prev_page_url }"
                                >
                                    <span class="sr-only">Previous</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </li>
                            <li v-for="page in options.links.slice(1, -1)" :key="page.label">
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
                                    @click="handlePageChange(options.current_page + 1)"
                                    :disabled="!options.next_page_url"
                                    class="flex items-center justify-center h-full py-1.5 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100"
                                    :class="{ 'opacity-50 cursor-not-allowed': !options.next_page_url }"
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
        </template>
    </DashboardLayout>

    <!-- Модальное окно создания/редактирования -->
    <Modal :show="showModal" @close="closeModal" max-width="2xl">
        <template #title>
            {{ editingOption ? 'Редактировать опцию' : 'Добавить опцию' }}
        </template>

        <template #content>
            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Основная информация об опции -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <TextInput
                            v-model="form.name"
                            label="Название опции"
                            :error="form.errors.name"
                            required
                        />
                    </div>

                    <div>
                        <SelectDropdown
                            v-model="form.category_id"
                            :options="categories.map(c => ({ id: c.id, name: c.name }))"
                            label="Категория"
                            :error="form.errors.category_id"
                            required
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <TextInput
                            v-model="form.order"
                            type="number"
                            label="Порядок сортировки"
                            :error="form.errors.order"
                        />
                    </div>

                    <div class="flex items-center">
                        <label class="relative inline-flex items-center cursor-pointer mt-6">
                            <input
                                type="checkbox"
                                v-model="form.is_required"
                                class="sr-only peer"
                            >
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Обязательная опция</span>
                        </label>
                    </div>
                </div>

                <!-- Секция значений опции -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Значения опции</h3>
                        <button
                            type="button"
                            @click="showValueForm = true"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800"
                        >
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/>
                            </svg>
                            Добавить значение
                        </button>
                    </div>

                    <!-- Форма добавления значения -->
                    <div v-if="showValueForm" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <TextInput
                                v-model="valueForm.name"
                                label="Название значения"
                                :error="valueForm.errors.name"
                                required
                            />
                            <TextInput
                                v-model="valueForm.value"
                                label="Значение"
                                :error="valueForm.errors.value"
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                    Код цвета
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        type="color"
                                        v-model="valueForm.color_code"
                                        class="h-10 w-20 rounded border border-gray-300 dark:border-gray-600"
                                    />
                                    <TextInput
                                        v-model="valueForm.color_code"
                                        placeholder="#000000"
                                        class="flex-1"
                                        :error="valueForm.errors.color_code"
                                    />
                                </div>
                            </div>

                            <div>
                                <TextInput
                                    v-model="valueForm.order"
                                    type="number"
                                    label="Порядок"
                                    :error="valueForm.errors.order"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Изображение
                            </label>
                            <div class="flex items-center justify-center w-full">
                                <label class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100">
                                    <div v-if="!valueForm.image_preview" class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                            Нажмите для загрузки или перетащите файл
                                        </p>
                                    </div>
                                    <img
                                        v-else
                                        :src="valueForm.image_preview"
                                        class="h-full w-full object-cover rounded-lg"
                                    />
                                    <input
                                        type="file"
                                        class="hidden"
                                        @change="handleImageUpload"
                                        accept="image/*"
                                    />
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2">
                            <button
                                type="button"
                                @click="cancelValue"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100"
                            >
                                Отмена
                            </button>
                            <button
                                type="button"
                                @click="addValue"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800"
                            >
                                Добавить
                            </button>
                        </div>
                    </div>

                    <!-- Список значений -->
                    <div class="space-y-2">
                        <div v-for="(value, index) in form.values" :key="index"
                             class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-4 items-center">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ value.name }}
                                </div>
                                <div v-if="value.color_code" class="flex items-center gap-2">
                                    <div
                                        class="w-6 h-6 rounded border"
                                        :style="{ backgroundColor: value.color_code }"
                                    ></div>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                       {{ value.color_code }}
                                   </span>
                                </div>
                                <div v-if="hasImage(value)" class="relative group">
                                    <img
                                        :src="getValueThumbnailUrl(value)"
                                        class="w-16 h-16 object-cover rounded"
                                        alt="Preview"
                                    />
                                    <!-- Превью при наведении -->
                                    <div class="hidden group-hover:block absolute z-10 bottom-full left-0 mb-2">
                                        <img
                                            :src="getValueImageUrl(value)"
                                            class="max-w-none w-48 h-48 object-cover rounded shadow-lg"
                                            alt="Full preview"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button"
                                        @click="editValue(index)"
                                        class="p-1 text-blue-500 hover:text-blue-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click="moveValue(index, 'up')"
                                    class="p-1 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                    :disabled="index === 0"
                                >
                                    ↑
                                </button>
                                <button
                                    type="button"
                                    @click="moveValue(index, 'down')"
                                    class="p-1 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                    :disabled="index === form.values.length - 1"
                                >
                                    ↓
                                </button>
                                <button
                                    type="button"
                                    @click="removeValue(index)"
                                    class="p-1 text-red-500 hover:text-red-700"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </template>

        <template #footer>
            <div class="flex justify-end space-x-3">
                <button
                    type="button"
                    @click="closeModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100"
                >
                    Отмена
                </button>
                <button
                    type="button"
                    @click="submitForm"
                    :disabled="form.processing"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800"
                >
                    {{ editingOption ? 'Сохранить' : 'Создать' }}
                </button>
            </div>
        </template>
    </Modal>
</template>
