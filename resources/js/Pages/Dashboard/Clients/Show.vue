<script setup>
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    client: Object,
    statistics: Object
});

const breadCrumbs = [
    { name: 'Клиенты', link: route('dashboard.clients.index') },
    { name: props.client.full_name, link: route('dashboard.clients.show', props.client.id) }
];

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatMoney = (amount) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
    }).format(amount);
};

const getStatusType = (status) => {
    const types = {
        new: 'blue',
        processing: 'yellow',
        completed: 'green',
        cancelled: 'red',
        pending: 'gray'
    };
    return types[status] || 'gray';
};

const getStatusLabel = (status) => {
    const labels = {
        new: 'Новый',
        processing: 'В обработке',
        completed: 'Завершён',
        cancelled: 'Отменён',
        pending: 'Ожидает'
    };
    return labels[status] || status;
};
</script>

<template>
    <DashboardLayout>
        <Head :title="client.full_name" />

        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs" />
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ client.full_name }}
                </h1>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Статистика -->
                <div class="grid grid-cols-1 gap-6 mb-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="p-4 bg-white rounded-lg shadow-sm dark:bg-gray-800">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Всего заказов
                        </h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ statistics.total_orders }}
                        </p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow-sm dark:bg-gray-800">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Общая сумма покупок
                        </h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ formatMoney(statistics.total_spent) }}
                        </p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow-sm dark:bg-gray-800">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Средний чек
                        </h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ formatMoney(statistics.average_order_value) }}
                        </p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow-sm dark:bg-gray-800">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Бонусный баланс
                        </h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ formatMoney(client.bonus_balance) }}
                        </p>
                    </div>
                </div>

                <!-- Информация о клиенте -->
                <div class="bg-white rounded-lg shadow-sm dark:bg-gray-800 mb-6">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Информация о клиенте
                        </h2>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <dl class="space-y-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Email
                                        </dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            {{ client.user.email }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Телефон
                                        </dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            {{ client.phone || 'Не указан' }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                            <div>
                                <dl class="space-y-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Адрес
                                        </dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            {{ client.address || 'Не указан' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Дата регистрации
                                        </dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            {{ formatDate(client.created_at) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- История заказов -->
                <div class="bg-white rounded-lg shadow-sm dark:bg-gray-800">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            История заказов
                        </h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            № Заказа
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Дата
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Сумма
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Статус
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="order in client.orders" :key="order.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ order.order_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ formatDate(order.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ formatMoney(order.total_amount) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <Badge :type="getStatusType(order.status)">
                                                {{ getStatusLabel(order.status) }}
                                            </Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template> 