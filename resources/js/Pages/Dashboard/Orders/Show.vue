<script setup>
import {ref} from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";

const props = defineProps({
    order: {
        type: Object,
        required: true
    }
});

// Опции статусов с иконками (такие же как в Index.vue)
const statusOptions = [
    {value: 'pending', label: 'Ожидает обработки', icon: 'ClockIcon', color: 'text-yellow-500'},
    {value: 'processing', label: 'В обработке', icon: 'RefreshIcon', color: 'text-blue-500'},
    {value: 'completed', label: 'Выполнен', icon: 'CheckCircleIcon', color: 'text-green-500'},
    {value: 'cancelled', label: 'Отменен', icon: 'XCircleIcon', color: 'text-red-500'}
];

const formatPrice = (value) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
    }).format(value);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
    };
    return classes[status] || '';
};

const getStatusLabel = (status) => {
    const option = statusOptions.find(s => s.value === status);
    return option ? option.label : status;
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                    Заказ #{{ order.order_number }}
                </h1>
                <div class="flex gap-2">
                    <Badge :type="getStatusBadgeType(order.status)">
                        {{ getStatusLabel(order.status) }}
                    </Badge>
                    <Badge :type="getPaymentStatusBadgeType(order.payment_status)">
                        {{ getPaymentStatusLabel(order.payment_status) }}
                    </Badge>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Информация о заказе -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Состав заказа -->
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                            <div class="px-4 py-5 sm:px-6 border-b dark:border-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    Состав заказа
                                </h3>
                            </div>
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                <div v-for="item in order.items" :key="item.id" 
                                     class="p-4 flex items-center">
                                    <div class="flex-shrink-0 w-16 h-16">
                                        <img v-if="item.product.image" 
                                             :src="item.product.image.url"
                                             class="w-full h-full object-cover rounded-lg"
                                             :alt="item.product.name">
                                        <div v-else 
                                             class="w-full h-full bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" 
                                                 stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" 
                                                      stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <div class="flex justify-between">
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ item.product.name }}
                                                </h4>
                                                <p v-if="item.variant" 
                                                   class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                    {{ item.variant.name }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ formatPrice(item.price) }}
                                                </p>
                                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                    × {{ item.quantity }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="px-4 py-5 sm:px-6 border-t dark:border-gray-700">
                                <dl class="space-y-3">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">Подытог</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ formatPrice(order.subtotal) }}
                                        </dd>
                                    </div>
                                    <div v-if="order.discount_amount" class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">Скидка</dt>
                                        <dd class="text-sm font-medium text-green-600 dark:text-green-400">
                                            -{{ formatPrice(order.discount_amount) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between pt-3 border-t dark:border-gray-700">
                                        <dt class="text-base font-medium text-gray-900 dark:text-white">Итого</dt>
                                        <dd class="text-base font-medium text-gray-900 dark:text-white">
                                            {{ formatPrice(order.total_amount) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- История заказа -->
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                            <div class="px-4 py-5 sm:px-6 border-b dark:border-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    История заказа
                                </h3>
                            </div>
                            <div class="flow-root">
                                <ul class="-mb-8">
                                    <li v-for="(event, eventIdx) in order.history" :key="event.id">
                                        <div class="relative pb-8">
                                            <span v-if="eventIdx !== order.history.length - 1" 
                                                  class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" 
                                                  aria-hidden="true"/>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span :class="[
                                                        getHistoryEventIconClass(event),
                                                        'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800'
                                                    ]">
                                                        <!-- Иконка события -->
                                                    </span>
                                                </div>
                                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                    <div>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            {{ event.comment }}
                                                        </p>
                                                    </div>
                                                    <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                                        <time :datetime="event.created_at">
                                                            {{ formatDate(event.created_at) }}
                                                        </time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Боковая панель -->
                    <div class="space-y-6">
                        <!-- Информация о клиенте -->
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                            <div class="px-4 py-5 sm:px-6 border-b dark:border-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    Информация о клиенте
                                </h3>
                            </div>
                            <div class="px-4 py-5 sm:px-6">
                                <div v-if="order.client" class="space-y-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Имя
                                        </p>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ order.client.full_name }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Email
                                        </p>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ order.client.email }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Телефон
                                        </p>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ order.client.phone }}
                                        </p>
                                    </div>
                                </div>
                                <div v-else-if="order.lead" class="space-y-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Лид #{{ order.lead.id }}
                                        </p>
                                        <PrimaryButton @click="createClient" class="mt-4">
                                            Создать клиента
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Информация о платеже -->
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                            <div class="px-4 py-5 sm:px-6 border-b dark:border-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    Информация о платеже
                                </h3>
                            </div>
                            <div class="px-4 py-5 sm:px-6">
                                <dl class="space-y-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Метод оплаты
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ getPaymentMethodLabel(order.payment_method) }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Статус оплаты
                                        </dt>
                                        <dd class="mt-1">
                                            <Badge :type="getPaymentStatusBadgeType(order.payment_status)">
                                                {{ getPaymentStatusLabel(order.payment_status) }}
                                            </Badge>
                                        </dd>
                                    </div>
                                    <div v-if="order.payment_id">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            ID транзакции
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ order.payment_id }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.table-row-hover:hover {
    @apply bg-gray-50 dark:bg-gray-700 transition-colors duration-150;
}
</style>