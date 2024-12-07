<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Modal from '@/Components/Modal.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectDropdown from '@/Components/SelectDropdown.vue';
import Badge from '@/Components/Badge.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchSelect from '@/Components/SearchSelect.vue';

// Props и начальные состояния
const props = defineProps({
    orders: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const showModal = ref(false);
const selectedOrder = ref(null);
const modalMode = ref('view'); // view, edit, delete

// Хлебные крошки
const breadCrumbs = [
    { name: 'Панель управления', link: route('dashboard') },
    { name: 'Заказы', link: route('dashboard.orders.index') }
];

// Статусы заказа
const orderStatuses = [
    { value: 'new', label: 'Новый', color: 'blue' },
    { value: 'processing', label: 'В обработке', color: 'yellow' },
    { value: 'completed', label: 'Завершен', color: 'green' },
    { value: 'cancelled', label: 'Отменен', color: 'red' }
];

// Статусы оплаты
const paymentStatuses = [
    { value: 'pending', label: 'Ожидает оплаты', color: 'yellow' },
    { value: 'paid', label: 'Оплачен', color: 'green' },
    { value: 'failed', label: 'Ошибка оплаты', color: 'red' },
    { value: 'refunded', label: 'Возврат', color: 'purple' }
];

// Форма для фильтров
const filters = ref({
    status: props.filters?.status || '',
    payment_status: props.filters?.payment_status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || ''
});

// Форма для создания/редактирования заказа
const form = useForm({
    client_id: '',
    items: [],
    status: 'new',
    payment_status: 'pending',
    payment_method: '',
    notes: '',
    promo_code: ''
});

// Вычисляемые свойства
const filteredOrders = computed(() => {
    return props.orders.data.filter(order => {
        const matchesSearch = !searchQuery.value || 
            order.order_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            order.client?.full_name.toLowerCase().includes(searchQuery.value.toLowerCase());
            
        const matchesStatus = !filters.value.status || order.status === filters.value.status;
        const matchesPaymentStatus = !filters.value.payment_status || 
            order.payment_status === filters.value.payment_status;

        return matchesSearch && matchesStatus && matchesPaymentStatus;
    });
});

// Статистика заказов
const orderStats = computed(() => {
    const stats = {
        total: filteredOrders.value.length,
        new: 0,
        processing: 0,
        completed: 0,
        cancelled: 0
    };

    filteredOrders.value.forEach(order => {
        stats[order.status]++;
    });

    return stats;
});

// Методы
const openModal = (mode, order = null) => {
    modalMode.value = mode;
    selectedOrder.value = order;
    
    if (order && mode === 'edit') {
        form.clearErrors();
        form.fill({
            client_id: order.client_id,
            status: order.status,
            payment_status: order.payment_status,
            payment_method: order.payment_method,
            notes: order.notes
        });
    }
    
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedOrder.value = null;
    form.reset();
};

const handleSubmit = () => {
    if (modalMode.value === 'edit') {
        form.put(route('dashboard.orders.update', selectedOrder.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

const confirmDelete = (order) => {
    openModal('delete', order);
};

const handleDelete = () => {
    if (selectedOrder.value) {
        form.delete(route('dashboard.orders.destroy', selectedOrder.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0
    }).format(price);
};

// Наблюдатели
watch([searchQuery, filters], () => {
    router.get(route('dashboard.orders.index'), {
        search: searchQuery.value,
        ...filters.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, { deep: true });
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                    Заказы
                </h1>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Статистика -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div v-for="status in orderStatuses" :key="status.value"
                         class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div :class="`text-${status.color}-500 dark:text-${status.color}-400`">
                                <Badge :type="status.color">{{ status.label }}</Badge>
                            </div>
                            <div class="ml-auto text-2xl font-semibold">
                                {{ orderStats[status.value] || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Фильтры -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <TextInput
                            v-model="searchQuery"
                            placeholder="Поиск по номеру заказа или клиенту..."
                        />
                        <SelectDropdown
                            v-model="filters.status"
                            :options="orderStatuses"
                            placeholder="Статус заказа"
                        />
                        <SelectDropdown
                            v-model="filters.payment_status"
                            :options="paymentStatuses"
                            placeholder="Статус оплаты"
                        />
                        <div class="flex gap-4">
                            <TextInput
                                v-model="filters.date_from"
                                type="date"
                                class="w-1/2"
                            />
                            <TextInput
                                v-model="filters.date_to"
                                type="date"
                                class="w-1/2"
                            />
                        </div>
                    </div>
                </div>

                <!-- Таблица заказов -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Номер заказа
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Клиент
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Сумма
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Статус
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Оплата
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Дата
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Действия</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="order in filteredOrders" :key="order.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('dashboard.orders.show', order.id)"
                                              class="text-primary-600 hover:text-primary-900">
                                            {{ order.order_number }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                                    <span class="text-sm font-medium">
                                                        {{ order.client?.first_name?.[0] }}{{ order.client?.last_name?.[0] }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ order.client?.full_name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ order.client?.email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ formatPrice(order.total_amount) }}
                                        </div>
                                        <div v-if="order.discount_amount > 0" 
                                             class="text-sm text-green-600">
                                            -{{ formatPrice(order.discount_amount) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Badge :type="order.status">
                                            {{ orderStatuses.find(s => s.value === order.status)?.label }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Badge :type="order.payment_status">
                                            {{ paymentStatuses.find(s => s.value === order.payment_status)?.label }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(order.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <PrimaryButton @click="openModal('edit', order)" size="sm">
                                                Редактировать
                                            </PrimaryButton>
                                            <PrimaryButton @click="confirmDelete(order)" 
                                                         size="sm" type="danger">
                                                Удалить
                                            </PrimaryButton>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Пагинация -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <Pagination :data="orders"/>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальные окна -->
        <Modal :show="showModal" @close="closeModal" :maxWidth="modalMode === 'delete' ? 'md' : '2xl'">
            <template #title>
                {{ 
                    modalMode === 'edit' ? 'Редактирование заказа' : 
                    modalMode === 'delete' ? 'Удаление заказа' : 
                    'Просмотр заказа'
                }}
            </template>

            <template #content>
                <div v-if="modalMode === 'delete'" class="p-6">
                    <p class="text-sm text-gray-500">
                        Вы уверены, что хотите удалить заказ {{ selectedOrder?.order_number }}? 
                        Это действие нельзя будет отменить.
                    </p>
                </div>

                <form v-else @submit.prevent="handleSubmit" class="p-6">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Статус заказа
                            </label>
                            <SelectDropdown
                                v-model="form.status"
                                :options="orderStatuses"
                                :disabled="modalMode === 'view'"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Статус оплаты
                            </label>
                            <SelectDropdown
                                v-model="form.payment_status"
                                :options="paymentStatuses"
                                :disabled="modalMode === 'view'"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Примечания
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                :disabled="modalMode === 'view'"
                            ></textarea>
                        </div>
                    </div>
                </form>
            </template>

            <template #footer>
                <div class="flex justify-end space-x-2">
                    <PrimaryButton @click="closeModal" type="secondary">
                        Отмена
                    </PrimaryButton>
                    <PrimaryButton 
                        v-if="modalMode === 'edit'"
                        @click="handleSubmit"
                        :disabled="form.processing"
                    >
                        Сохранить
                    </PrimaryButton>
                    <PrimaryButton
                        v-if="modalMode === 'delete'"
                        @click="handleDelete"
                        :disabled="form.processing"
                        type="danger"
                    >
                        Удалить
                    </PrimaryButton>
                </div>
            </template>
        </Modal>
    </DashboardLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
