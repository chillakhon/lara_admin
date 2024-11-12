<script setup>
import {computed, ref} from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    product: Object,
    categories: {
        type: Array,
        default: () => []
    }
});

const showAddOptionModal = ref(false);
const editingOption = ref(null);
const showCategoryOptionsModal = ref(false);

const form = useForm({
    options: [],
});
const openAddModal = () => {
    editingOption.value = null;
    form.reset();
    showAddOptionModal.value = true;
};

const openEditModal = (option) => {
    editingOption.value = option;
    form.name = option.name;
    form.category_id = option.category_id;
    form.is_required = option.is_required;
    form.order = option.order;
    showAddOptionModal.value = true;
};

const closeModal = () => {
    showAddOptionModal.value = false;
    editingOption.value = null;
    form.reset();
};

const availableCategoryOptions = computed(() => {
    const productCategoryIds = props.product.categories.map(c => c.id);
    let options = [];

    // Фильтруем категории, которые принадлежат товару
    const productCategories = props.categories.filter(category =>
        productCategoryIds.includes(category.id)
    );

    // Собираем все опции из отфильтрованных категорий
    productCategories.forEach(category => {
        if (category.options && Array.isArray(category.options)) {
            options = [...options, ...category.options.map(option => ({
                ...option,
                category_name: category.name
            }))];
        }
    });

    // Исключаем уже добавленные опции
    return options.filter(option =>
        !props.product.options.some(po => po.id === option.id)
    );
});

const openCategoryOptionsModal = () => {
    selectedOptions.value = [];
    showCategoryOptionsModal.value = true;
};

const saveCategoryOptions = () => {
    form.options = selectedOptions.value.map(optionId => {
        const option = availableCategoryOptions.value.find(o => o.id === optionId);
        return {
            option_id: optionId,
            is_required: option.is_required
        };
    });

    form.post(route('dashboard.products.options.attach', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCategoryOptionsModal.value = false;
            selectedOptions.value = [];
        },
    });
};

const selectedOptions = ref([]);

const submit = () => {
    if (editingOption.value) {
        form.put(route('dashboard.products.options.update', [props.product.id, editingOption.value.id]), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('dashboard.products.options.store', props.product.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteOption = async (optionId) => {
    if (confirm('Вы уверены, что хотите удалить эту опцию?')) {
        await router.delete(route('dashboard.products.options.destroy', [props.product.id, optionId]), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <div class="bg-white rounded-lg shadow dark:bg-gray-800">
        <div class="p-4 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    Опции товара
                </h2>
                <!-- Кнопка выбора опций из категорий -->
                <button
                    v-if="availableCategoryOptions.length"
                    @click="openCategoryOptionsModal"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Добавить из категорий
                </button>
                <button
                    @click="openAddModal"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Добавить опцию
                </button>
            </div>

            <!-- Options List -->
            <div class="space-y-4">
                <div v-for="option in product.options" :key="option.id"
                     class="p-4 border border-gray-200 rounded-lg dark:border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ option.name }}
                            </h3>
                            <div class="mt-2">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ option.values.length }} значений
                                </span>
                                <span v-if="option.is_required"
                                      class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                    Обязательно
                                </span>
                            </div>
                            <!-- Option Values -->
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span v-for="value in option.values" :key="value.id"
                                      class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ value.name }}
                                </span>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                @click="openEditModal(option)"
                                class="p-2 text-blue-600 hover:text-blue-700 dark:text-blue-500 dark:hover:text-blue-400"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button
                                @click="deleteOption(option.id)"
                                class="p-2 text-red-600 hover:text-red-700 dark:text-red-500 dark:hover:text-red-400"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!product.options.length"
                     class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium">Нет опций</h3>
                    <p class="mt-1 text-sm">Начните с добавления новой опции для товара.</p>
                    <div class="mt-6">
                        <button
                            @click="openAddModal"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 4v16m8-8H4"/>
                            </svg>
                            Добавить опцию
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Модальное окно выбора опций из категорий -->
        <Modal :show="showCategoryOptionsModal" @close="showCategoryOptionsModal = false">
            <template #title>
                Выберите опции из категорий
            </template>
            <template #content>
                <!-- Показываем сообщение, если нет доступных опций -->
                <div v-if="!availableCategoryOptions.length"
                     class="text-gray-500 text-center py-4">
                    Нет доступных опций в выбранных категориях
                </div>

                <div v-else class="space-y-4">
                    <div v-for="option in availableCategoryOptions"
                         :key="option.id"
                         class="flex items-start p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center h-5">
                            <input
                                type="checkbox"
                                :id="'option-' + option.id"
                                v-model="selectedOptions"
                                :value="option.id"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            >
                        </div>
                        <label :for="'option-' + option.id"
                               class="ml-3 flex-1">
                            <div class="font-medium text-gray-900">{{ option.name }}</div>
                            <div class="text-sm text-gray-500">
                                Категория: {{ option.category_name }}
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                    <span v-for="value in option.values"
                                          :key="value.id"
                                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ value.name }}
                                        <div v-if="value.color_code"
                                             :style="{ backgroundColor: value.color_code }"
                                             class="ml-1 w-3 h-3 rounded-full">
                                        </div>
                                    </span>
                            </div>
                        </label>
                    </div>
                </div>
            </template>
            <template #footer>
                <button
                    type="button"
                    @click="showCategoryOptionsModal = false"
                    class="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-gray-700 hover:bg-gray-50"
                >
                    Отмена
                </button>
                <button
                    @click="saveCategoryOptions"
                    :disabled="!selectedOptions.length || form.processing"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700 disabled:opacity-50"
                >
                    Добавить выбранные опции
                </button>
            </template>
        </Modal>
        <!-- Add/Edit Option Modal -->
        <Modal :show="showAddOptionModal" @close="closeModal">
            <template #title>{{ editingOption ? 'Редактировать опцию' : 'Добавить новую опцию' }}</template>
            <template #content>
                <form @submit.prevent="submit" class="mt-6 space-y-6">
                    <div>
                        <InputLabel for="name" value="Название опции"/>
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2"/>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.is_required" class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:border-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">Обязательная опция</span>
                    </label>

                    <div class="mt-6 flex justify-end space-x-3">

                    </div>
                </form>
            </template>
            <template #footer>
                <button
                    type="button"
                    @click="closeModal"
                    class="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Отмена
                </button>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                >
                    {{ editingOption ? 'Сохранить' : 'Добавить' }}
                </button>
            </template>
        </Modal>
    </div>
</template>
