<script setup>
import {ref, computed, watch} from 'vue';
import {useForm, Link} from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Modal from '@/Components/Modal.vue';
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import SelectInput from "@/Components/SelectInput.vue";
import {Transition} from 'vue';
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
    orders: Object,
    clients: Array,
    products: Array,
    filters: Object,
});

//const toast = useToast();

// Состояния
// Состояния
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const showCreateModal = ref(false);
const isLoading = ref(false);
const showConfirmDelete = ref(false);
const orderToDelete = ref(null);
 
let searchTimeout;
watch(searchQuery, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        filterOrders();
    }, 300);
});

// Опции статусов с иконками
const statusOptions = [
    {value: 'pending', label: 'Ожидает обработки', icon: 'ClockIcon', color: 'text-yellow-500'},
    {value: 'processing', label: 'В обработке', icon: 'RefreshIcon', color: 'text-blue-500'},
    {value: 'completed', label: 'Выполнен', icon: 'CheckCircleIcon', color: 'text-green-500'},
    {value: 'cancelled', label: 'Отменен', icon: 'XCircleIcon', color: 'text-red-500'}
];

// Форма заказа с валидацией
const form = useForm({
    id: null,
    client_id: '',
    status: 'pending',
    notes: '',
    items: [
        {
            product_id: '',
            variant_id: null,
            quantity: 1,
            price: 0
        }
    ]
});

// Вычисляемые свойства
const totalOrderAmount = computed(() => {
    return form.items.reduce((total, item) => {
        return total + (item.price * item.quantity);
    }, 0);
});

// Методы
const handleProductSelect = async (item) => {
    isLoading.value = true;
    try {
        updateProductDetails(item);
        // Имитация загрузки вариантов товара
        await new Promise(resolve => setTimeout(resolve, 300));
    } finally {
        isLoading.value = false;
    }
};

const submitForm = async () => {
    try {
        if (form.id) {
            await form.put(route('dashboard.orders.update', form.id));
            //toast.success('Заказ успешно обновлен');
        } else {
            await form.post(route('dashboard.orders.store'));
            //toast.success('Заказ успешно создан');
        }
        closeModal();
    } catch (error) {
        //toast.error('Произошла ошибка при сохранении заказа');
    }
};


// Фильтрация заказов
const filteredOrders = computed(() => {
    return props.orders.data.filter(order => {
        const matchesSearch = !searchQuery.value || 
            order.order_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            `${order.client?.first_name} ${order.client?.last_name}`.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        const matchesStatus = statusFilter.value === 'all' || order.status === statusFilter.value;
        
        return matchesSearch && matchesStatus;
    });
});

// Функция для применения фильтров
const filterOrders = () => {
    router.get(route('dashboard.orders.index'), {
        search: searchQuery.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const confirmDelete = (order) => {
    orderToDelete.value = order;
    showConfirmDelete.value = true;
};

const deleteOrder = async () => {
    try {
        await form.delete(route('dashboard.orders.destroy', orderToDelete.value.id));
        //toast.success('Заказ успешно удален');
        showConfirmDelete.value = false;
    } catch (error) {
        //toast.error('Произошла ошибка при удалении заказа');
    }
};

const formatPrice = (value) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
    }).format(value);
};

// Также добавим функцию форматирования даты
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// И добавим функцию получения класса для статуса
const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
    };
    return classes[status] || '';
};

// Методы для работы с товарами
const getProductVariants = (productId) => {
    if (!productId) return [];
    const product = props.products.find(p => p.id === parseInt(productId));
    return product?.variants?.map(v => ({
        value: v.id,
        label: v.name
    })) || [];
};

const updateProductDetails = (item) => {
    item.variant_id = null;
    const product = props.products.find(p => p.id === parseInt(item.product_id));
    if (product) {
        item.price = product.price || 0;
    }
};

const updateVariantPrice = (item) => {
    if (item.variant_id) {
        const product = props.products.find(p => p.id === parseInt(item.product_id));
        const variant = product?.variants?.find(v => v.id === parseInt(item.variant_id));
        if (variant) {
            item.price = variant.price || 0;
        }
    }
};

const hasVariants = (productId) => {
    if (!productId) return false;
    const product = props.products.find(p => p.id === parseInt(productId));
    return product?.has_variants && Array.isArray(product?.variants) && product.variants.length > 0;
};

// Добавление и удаление элементов заказа
const addOrderItem = () => {
    form.items.push({
        product_id: '',
        variant_id: null,
        quantity: 1,
        price: 0
    });
};

const removeOrderItem = (index) => {
    form.items.splice(index, 1);
};

// Закрытие модального окна
const closeModal = () => {
    showCreateModal.value = false;
    form.reset();
    form.clearErrors();
};


// Добавляем функцию получения метки статуса
const getStatusLabel = (status) => {
    const option = statusOptions.find(s => s.value === status);
    return option ? option.label : status;
};

const openModal = (order = null) => {
    if (order) {
        form.id = order.id;
        form.client_id = order.client_id;
        form.status = order.status;
        form.notes = order.notes;
        form.items = order.items.map(item => ({
            product_id: item.product_id,
            variant_id: item.variant_id,
            quantity: item.quantity,
            price: item.price
        }));
    }
    showCreateModal.value = true;
};

</script>

<template>
    <DashboardLayout>
        <!-- Шапка с улучшенной навигацией -->
        <template #header>
            <div class="space-y-4">
                <BreadCrumbs :breadcrumbs="breadCrumbs" class="text-sm"/>
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <ShoppingBagIcon class="w-8 h-8 text-primary-600"/>
                        Заказы
                    </h1>
                    <PrimaryButton @click="showCreateModal = true"
                                   class="transform hover:scale-105 transition-transform">
                        <PlusIcon class="w-5 h-5 mr-2"/>
                        Создать заказ
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <section class="bg-gray-50 dark:bg-gray-900 pt-4">
            <div class="mx-auto px-4">
                <!-- Карточка с фильтрами и таблицей -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <!-- Улучшенные фильтры -->
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <TextInput
                                    v-model="searchQuery"
                                    type="search"
                                    placeholder="Поиск по номеру заказа или клиенту..."
                                    class="w-full"
                                    prepend-icon="MagnifyingGlassIcon"
                                >
                                    <template #prefix>
                                        <MagnifyingGlassIcon class="w-5 h-5 text-gray-400"/>
                                    </template>
                                </TextInput>
                            </div>
                            <div class="w-full md:w-48">
                                <SelectInput
                                    v-model="statusFilter"
                                    :options="[{ value: 'all', label: 'Все статусы' }, ...statusOptions]"
                                    class="w-full"
                                >
                                    <template #option="{ option }">
                                        <div class="flex items-center gap-2">
                                            <component :is="option.icon"
                                                       v-if="option.icon"
                                                       class="w-5 h-5"
                                                       :class="option.color"/>
                                            {{ option.label }}
                                        </div>
                                    </template>
                                </SelectInput>
                            </div>
                        </div>
                    </div>

                    <!-- Улучшенная таблица -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Номер заказа
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Клиент
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Сумма
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Статус
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Дата
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Действия
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <TransitionGroup
                                enter-active-class="transition-all duration-300"
                                enter-from-class="opacity-0 transform -translate-x-4"
                                enter-to-class="opacity-100 transform translate-x-0"
                                leave-active-class="transition-all duration-300"
                                leave-from-class="opacity-100 transform translate-x-0"
                                leave-to-class="opacity-0 transform translate-x-4"
                            >
                                <tr v-for="order in filteredOrders"
                                    :key="order.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link
                                            :href="route('dashboard.orders.show', order.id)"
                                            class="inline-block cursor-pointer text-primary-600 hover:text-primary-900 dark:text-primary-400 font-medium"
                                        >
                                            {{ order.order_number }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                                    <span class="text-sm font-medium">
                                                        {{ order.client?.first_name[0] }}{{
                                                            order.client?.last_name[0]
                                                        }}
                                                    </span>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium">
                                                    {{ order.client?.first_name }} {{ order.client?.last_name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ formatPrice(order.total_amount) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div :class="getStatusBadgeClass(order.status)"
                                             class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                                            <component
                                                :is="statusOptions.find(s => s.value === order.status)?.icon"
                                                class="w-4 h-4 mr-2"
                                            />
                                            {{ getStatusLabel(order.status) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(order.created_at) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <PrimaryButton @click="openModal(order)"
                                                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors">
                                                <EyeIcon class="w-5 h-5"/>
                                            </PrimaryButton>
                                            <PrimaryButton @click="openModal(order)"
                                                    class="text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition-colors">
                                                <PencilIcon class="w-5 h-5"/>
                                            </PrimaryButton>
                                            <PrimaryButton type="red" @click="confirmDelete(order)"
                                                    class="text-red-400 hover:text-red-500 dark:hover:text-red-300 transition-colors">
                                                <TrashIcon class="w-5 h-5"/>
                                            </PrimaryButton>
                                        </div>
                                    </td>
                                </tr>
                            </TransitionGroup>
                            </tbody>
                        </table>
                    </div>

                    <!-- Улучшенная пагинация -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Показано <span class="font-medium">{{ orders.from }}</span> -
                                <span class="font-medium">{{ orders.to }}</span> из
                                <span class="font-medium">{{ orders.total }}</span> заказов
                            </p>
                            <Pagination
                                :links="orders.links"
                                class="pagination-custom"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Улучшенное модальное окно создания/редактирования -->
        <Modal
            :show="showCreateModal"
            @close="closeModal"
            max-width="4xl"
            class="modal-custom"
        >
            <template #title>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary-100 dark:bg-primary-900 rounded-lg">
                        <ShoppingBagIcon class="w-6 h-6 text-primary-600 dark:text-primary-400"/>
                    </div>
                    <h3 class="text-xl font-semibold">
                        {{ form.id ? 'Редактировать заказ' : 'Создать заказ' }}
                    </h3>
                </div>
            </template>

            <template #content>
                <form @submit.prevent="submitForm" class="space-y-6">
                    <!-- Основная информация -->
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <SelectInput
                                    label="Клиент"
                                    v-model="form.client_id"
                                    :options="clients.map(client => ({
                                        value: client.id,
                                        label: `${client.first_name} ${client.last_name}`
                                    }))"
                                    :error="form.errors.client_id"
                                    required
                                    class="w-full"
                                >
                                    <template #option="{ option }">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="h-6 w-6 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center text-xs">
                                                {{ option.label.split(' ').map(n => n[0]).join('') }}
                                            </div>
                                            <span>{{ option.label }}</span>
                                        </div>
                                    </template>
                                </SelectInput>

                                <SelectInput
                                    label="Статус"
                                    v-model="form.status"
                                    :options="statusOptions"
                                    :error="form.errors.status"
                                    required
                                >
                                    <template #option="{ option }">
                                        <div class="flex items-center gap-2">
                                            <component :is="option.icon"
                                                       class="w-5 h-5"
                                                       :class="option.color"/>
                                            {{ option.label }}
                                        </div>
                                    </template>
                                </SelectInput>
                            </div>

                            <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow-sm">
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                                    Сводка заказа
                                </h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-300">Количество товаров:</span>
                                        <span class="font-medium">{{ form.items.length }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-300">Общая сумма:</span>
                                        <span class="font-medium text-primary-600 dark:text-primary-400">
                                            {{ formatPrice(totalOrderAmount) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Товары -->
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium flex items-center gap-2">
                                <ShoppingCartIcon class="w-5 h-5 text-primary-600"/>
                                Товары
                            </h3>
                            <PrimaryButton
                                type="button"
                                @click="addOrderItem"
                                class="transform hover:scale-105 transition-transform"
                            >
                                <PlusIcon class="w-5 h-5 mr-2"/>
                                Добавить товар
                            </PrimaryButton>
                        </div>

                        <TransitionGroup
                            name="list"
                            enter-active-class="transition-all duration-300"
                            enter-from-class="opacity-0 transform -translate-y-4"
                            enter-to-class="opacity-100 transform translate-y-0"
                            leave-active-class="transition-all duration-300"
                            leave-from-class="opacity-100 transform translate-y-0"
                            leave-to-class="opacity-0 transform translate-y-4"
                        >
                            <div v-for="(item, index) in form.items"
                                 :key="index"
                                 class="bg-white dark:bg-gray-700 rounded-lg p-6 mb-4 shadow-sm relative group">
                                <button
                                    @click="removeOrderItem(index)"
                                    class="absolute top-2 right-2 p-1 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                                    type="button"
                                >
                                    <XMarkIcon class="w-5 h-5"/>
                                </button>

                                <div class="grid grid-cols-12 gap-6">
                                    <div class="col-span-12 md:col-span-4">
                                        <SelectInput
                                            label="Товар"
                                            v-model="item.product_id"
                                            :options="products.map(product => ({
                                                value: product.id,
                                                label: product.name,
                                                price: product.price
                                            }))"
                                            @update:modelValue="() => handleProductSelect(item)"
                                            :loading="isLoading"
                                            required
                                        >
                                            <template #option="{ option }">
                                                <div>
                                                    <div class="font-medium">{{ option.label }}</div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ formatPrice(option.price) }}
                                                    </div>
                                                </div>
                                            </template>
                                        </SelectInput>
                                    </div>

                                    <div class="col-span-12 md:col-span-3">
                                        <SelectInput
                                            v-if="getProductVariants(item.product_id).length"
                                            label="Вариант"
                                            v-model="item.variant_id"
                                            :options="getProductVariants(item.product_id)"
                                            @update:modelValue="() => updateVariantPrice(item)"
                                            :loading="isLoading"
                                        />
                                    </div>

                                    <div class="col-span-6 md:col-span-2">
                                        <TextInput
                                            type="number"
                                            label="Количество"
                                            v-model="item.quantity"
                                            min="1"
                                            step="1"
                                            required
                                        >
                                            <template #append>
                                                <div class="flex">
                                                    <button
                                                        type="button"
                                                        @click="item.quantity = Math.max(1, parseInt(item.quantity) - 1)"
                                                        class="px-2 border-r border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600"
                                                    >
                                                        <MinusIcon class="w-4 h-4"/>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="item.quantity = parseInt(item.quantity) + 1"
                                                        class="px-2 hover:bg-gray-100 dark:hover:bg-gray-600"
                                                    >
                                                        <PlusIcon class="w-4 h-4"/>
                                                    </button>
                                                </div>
                                            </template>
                                        </TextInput>
                                    </div>

                                    <div class="col-span-6 md:col-span-3">
                                        <TextInput
                                            type="number"
                                            label="Цена"
                                            v-model="item.price"
                                            min="0"
                                            step="0.01"
                                            required
                                        >
                                            <template #prefix>
                                                <span class="text-gray-500">₽</span>
                                            </template>
                                        </TextInput>
                                    </div>
                                </div>

                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                    <div class="flex justify-end">
                                        <span class="text-gray-500 dark:text-gray-400">
                                            Сумма: {{ formatPrice(item.price * item.quantity) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </TransitionGroup>
                    </div>

                    <!-- Примечания -->
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                        <TextInput
                            label="Примечания"
                            v-model="form.notes"
                            type="textarea"
                            rows="3"
                            placeholder="Введите дополнительную информацию о заказе..."
                        />
                    </div>
                </form>
            </template>

            <template #footer>
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        * Обязательные поля
                    </div>
                    <div class="flex gap-3">
                        <SecondaryButton @click="closeModal">
                            Отмена
                        </SecondaryButton>
                        <PrimaryButton
                            @click="submitForm"
                            :disabled="form.processing"
                            :loading="form.processing"
                        >
                            <template #icon>
                                <CheckIcon v-if="!form.processing" class="w-5 h-5 mr-2"/>
                                <SpinnerIcon v-else class="w-5 h-5 mr-2 animate-spin"/>
                            </template>
                            {{ form.id ? 'Сохранить изменения' : 'Создать заказ' }}
                        </PrimaryButton>
                    </div>
                </div>
            </template>
        </Modal>

        <!-- Модальное окно подтверждения удаления -->
        <Modal
            :show="showConfirmDelete"
            @close="showConfirmDelete = false"
            max-width="md"
        >
            <template #title>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                        <ExclamationTriangleIcon class="w-6 h-6 text-red-600 dark:text-red-400"/>
                    </div>
                    <h3 class="text-xl font-semibold">Подтверждение удаления</h3>
                </div>
            </template>

            <template #content>
                <p class="text-gray-600 dark:text-gray-300">
                    Вы уверены, что хотите удалить заказ
                    <span class="font-medium">{{ orderToDelete?.order_number }}</span>?
                    Это действие нельзя будет отменить.
                </p>
            </template>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="showConfirmDelete = false">
                        Отмена
                    </SecondaryButton>
                    <PrimaryButton
                        @click="deleteOrder"
                        :disabled="form.processing"
                        :loading="form.processing"
                        class="bg-red-600 hover:bg-red-700"
                    >
                        <TrashIcon class="w-5 h-5 mr-2"/>
                        Удалить заказ
                    </PrimaryButton>
                </div>
            </template>
        </Modal>
    </DashboardLayout>
</template>
<style>
.pagination-custom {
    @apply inline-flex -space-x-px;
}

.pagination-custom .page-link {
    @apply px-3 py-2 text-sm leading-tight text-gray-500 bg-white border border-gray-300
    hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700
    dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white transition-colors;
}

.pagination-custom .page-item.active .page-link {
    @apply z-10 text-white bg-primary-600 border-primary-600 hover:bg-primary-700
    dark:border-primary-500 dark:bg-primary-500 dark:hover:bg-primary-600;
}

.list-move,
.list-enter-active,
.list-leave-active {
    transition: all 0.5s ease;
}

.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

.list-leave-active {
    position: absolute;
}
</style>
