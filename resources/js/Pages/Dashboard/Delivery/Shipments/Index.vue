<script setup>
import { ref } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Badge from '@/Components/Badge.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    shipments: Object,
    statuses: Array
});

const searchQuery = ref('');
const selectedStatus = ref('');

const breadCrumbs = [
    { name: 'Панель управления', link: route('dashboard') },
    { name: 'Отправления', link: route('dashboard.delivery.shipments.index') }
];

const form = useForm({
    status_id: '',
    tracking_number: '',
    notes: ''
});

function getStatusBadgeType(status) {
    const types = {
        'new': 'info',
        'processing': 'warning',
        'ready_for_pickup': 'primary',
        'in_transit': 'warning',
        'delivered': 'success',
        'cancelled': 'danger',
        'returned': 'danger'
    };
    return types[status] || 'default';
}

function printLabel(shipment) {
    window.open(route('dashboard.delivery.shipments.print-label', shipment.id), '_blank');
}

function updateStatus(shipment, statusId) {
    form.status_id = statusId;
    form.tracking_number = shipment.tracking_number;
    form.notes = shipment.notes;
    
    form.put(route('dashboard.delivery.shipments.update', shipment.id));
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs" />
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Отправления
                </h1>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Фильтры -->
                <div class="mb-6 flex gap-4">
                    <div class="flex-1">
                        <TextInput
                            v-model="searchQuery"
                            type="search"
                            placeholder="Поиск по номеру отправления или заказа"
                            class="w-full"
                        />
                    </div>
                    <div class="w-64">
                        <select
                            v-model="selectedStatus"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option value="">Все статусы</option>
                            <option v-for="status in statuses" 
                                    :key="status.id" 
                                    :value="status.id">
                                {{ status.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Таблица отправлений -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Номер заказа
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Трек-номер
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Способ доставки
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Статус
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Действия
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="shipment in shipments.data" :key="shipment.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ shipment.order.order_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ shipment.tracking_number || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ shipment.delivery_method.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Badge :type="getStatusBadgeType(shipment.status.code)">
                                        {{ shipment.status.name }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex space-x-2">
                                        <select
                                            v-model="shipment.status_id"
                                            class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                            @change="updateStatus(shipment, $event.target.value)"
                                        >
                                            <option v-for="status in statuses" 
                                                    :key="status.id" 
                                                    :value="status.id">
                                                {{ status.name }}
                                            </option>
                                        </select>
                                        <PrimaryButton 
                                            v-if="shipment.tracking_number"
                                            @click="printLabel(shipment)"
                                        >
                                            Печать
                                        </PrimaryButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Пагинация -->
                <div class="mt-4">
                    <Pagination :links="shipments.links" />
                </div>
            </div>
        </div>
    </DashboardLayout>
</template> 