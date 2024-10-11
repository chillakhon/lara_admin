<script setup>
import {ref, computed} from 'vue';
import {Head, router, useForm} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from "@/Components/SelectInput.vue";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
    orders: Array,
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const searchQuery = ref('');
const statusFilter = ref(props.filters.status || 'all');
const isModalOpen = ref(false);
const modalMode = ref('view'); // 'view', 'edit', or 'delete'
const currentOrder = ref(null);

const form = useForm({
    id: '',
    order_number: '',
    client_id: '',
    total_amount: '',
    status: '',
    notes: '',
    items: [],
});

const filteredOrders = computed(() => {
    return props.orders.data.filter(order => {
        const searchLower = searchQuery.value.toLowerCase();
        return (
            (order.order_number?.toLowerCase() || '').includes(searchLower) ||
            (order.client?.first_name?.toLowerCase() || '').includes(searchLower) ||
            (order.client?.last_name?.toLowerCase() || '').includes(searchLower) ||
            (order.client?.email?.toLowerCase() || '').includes(searchLower)
        );
    });
});
const openModal = (order, mode) => {
    currentOrder.value = order;
    modalMode.value = mode;
    if (mode === 'edit' || mode === 'delete') {
        form.id = order.id;
        form.order_number = order.order_number;
        form.client_id = order.client_id;
        form.total_amount = order.total_amount;
        form.status = order.status;
        form.notes = order.notes;
        form.items = order.items.map(item => ({
            id: item.id,
            product_id: item.product_id,
            quantity: item.quantity,
            price: item.price
        }));
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    currentOrder.value = null;
    form.reset();
};

const updateOrder = () => {
    form.put(route('dashboard.orders.update', form.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const deleteOrder = () => {
    form.delete(route('dashboard.orders.destroy', form.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const formatDate = (dateString) => {
    return dateString ? new Date(dateString).toLocaleString() : '';
};

const formatPrice = (price) => {
    return price ? new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(price) : '';
};

const statusOptions = [
    { value: 'pending', label: 'Ожидает обработки' },
    { value: 'processing', label: 'В обработке' },
    { value: 'completed', label: 'Выполнен' },
    { value: 'cancelled', label: 'Отменен' },
];

const filterOrders = () => {
    router.get(
        route('dashboard.orders.index'),
        { status: statusFilter.value },
        { preserveState: true, preserveScroll: true }
    );
};
</script>

<template>
    <Head title="Orders"/>

    <DashboardLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Заказы</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-4 flex space-x-4">
                            <TextInput
                                v-model="searchQuery"
                                type="text"
                                class="w-full"
                                placeholder="Поиск заказов..."
                            />
                            <SelectInput
                                v-model="statusFilter"
                                :options="[{ value: 'all', label: 'Все статусы' }, ...statusOptions]"
                                class="w-64"
                                @update:modelValue="filterOrders"
                            />
                        </div>

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                    Номер заказа
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                    Клиент
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                    Сумма
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                    Статус
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                    Дата
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                    Действия
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="order in filteredOrders" :key="order.id">
                                <td class="px-6 py-4 whitespace-no-wrap">{{ order.order_number }}</td>
                                <td class="px-6 py-4 whitespace-no-wrap">
                                    {{ order.client?.first_name }} {{ order.client?.last_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-no-wrap">{{ formatPrice(order.total_amount) }}</td>
                                <td class="px-6 py-4 whitespace-no-wrap">{{ order.status }}</td>
                                <td class="px-6 py-4 whitespace-no-wrap">{{ formatDate(order.created_at) }}</td>
                                <td class="px-6 py-4 whitespace-no-wrap">
                                    <PrimaryButton @click="openModal(order)">
                                        Просмотр
                                    </PrimaryButton>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6" v-if="currentOrder">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Детали заказа {{ currentOrder.order_number }}
                </h2>

                <div class="mb-4">
                    <strong>Клиент:</strong> {{ currentOrder.client.first_name }} {{ currentOrder.client.last_name }}
                </div>
                <div class="mb-4">
                    <strong>Сумма:</strong> {{ formatPrice(currentOrder.total_amount) }}
                </div>
                <div class="mb-4">
                    <strong>Статус:</strong> {{ currentOrder.status }}
                </div>
                <div class="mb-4">
                    <strong>Дата заказа:</strong> {{ formatDate(currentOrder.created_at) }}
                </div>
                <div class="mb-4">
                    <strong>Адрес доставки:</strong> {{ currentOrder.client.address }}
                </div>

                <h3 class="text-md font-medium text-gray-900 mt-6 mb-2">Состав заказа:</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                            Товар
                        </th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                            Количество
                        </th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                            Цена
                        </th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                            Сумма
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in currentOrder.items" :key="item.id">
                        <td class="px-6 py-4 whitespace-no-wrap">{{ item.product.name }}</td>
                        <td class="px-6 py-4 whitespace-no-wrap">{{ item.quantity }}</td>
                        <td class="px-6 py-4 whitespace-no-wrap">{{ formatPrice(item.price) }}</td>
                        <td class="px-6 py-4 whitespace-no-wrap">{{ formatPrice(item.price * item.quantity) }}</td>
                    </tr>
                    </tbody>
                </table>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Закрыть</SecondaryButton>
                </div>
            </div>
        </Modal>
    </DashboardLayout>
</template>
