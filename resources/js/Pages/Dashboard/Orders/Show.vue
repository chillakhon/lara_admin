<script setup>
import {ref} from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

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
            <div class="space-y-4">
                <BreadCrumbs :breadcrumbs="[
                    { name: 'Заказы', href: route('dashboard.orders.index') },
                    { name: `Заказ №${order.order_number}`, href: '#' }
                ]"/>
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <ShoppingBagIcon class="w-8 h-8 text-primary-600"/>
                        Заказ №{{ order.order_number }}
                    </h1>
                    <div class="flex gap-3">
                        <PrimaryButton
                            
                            class="bg-primary-600 hover:bg-primary-700"
                        >
                            Редактировать
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Основная информация -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Информация о заказе -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-medium mb-4 flex items-center gap-2">
                        <ClipboardDocumentListIcon class="w-5 h-5 text-primary-600"/>
                        Информация о заказе
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Статус</span>
                            <span :class="getStatusBadgeClass(order.status)"
                                  class="px-3 py-1 rounded-full text-sm font-medium">
                                {{ getStatusLabel(order.status) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Дата создания</span>
                            <span class="font-medium">{{ formatDate(order.created_at) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Сумма заказа</span>
                            <span class="font-medium text-primary-600">{{ formatPrice(order.total_amount) }}</span>
                        </div>
                        <div v-if="order.discount_amount > 0" class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Скидка</span>
                            <span class="font-medium text-green-600">-{{ formatPrice(order.discount_amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Информация о клиенте -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-medium mb-4 flex items-center gap-2">
                        <UserIcon class="w-5 h-5 text-primary-600"/>
                        Информация о клиенте
                    </h3>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                            <span class="text-lg font-medium text-primary-600">
                                {{ order.client.first_name[0] }}{{ order.client.last_name[0] }}
                            </span>
                        </div>
                        <div>
                            <div class="font-medium">{{ order.client.first_name }} {{ order.client.last_name }}</div>
                            <div class="text-sm text-gray-500">{{ order.client.email }}</div>
                        </div>
                    </div>
                </div>

                <!-- Промокод -->
                <div v-if="order.promo_code" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-medium mb-4 flex items-center gap-2">
                        <TagIcon class="w-5 h-5 text-primary-600"/>
                        Примененный промокод
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Код</span>
                            <span class="font-medium">{{ order.promo_code.code }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Скидка</span>
                            <span class="font-medium text-green-600">
                                {{ order.promo_code.discount_type === 'percentage' 
                                    ? order.promo_code.discount_value + '%' 
                                    : formatPrice(order.promo_code.discount_value) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Товары -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium">Товары в заказе</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Товар
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Вариант
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Цена
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Количество
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Сумма
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in order.items" :key="item.id" 
                                class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <img v-if="item.product.image" 
                                             :src="item.product.image" 
                                             class="h-10 w-10 rounded-lg object-cover mr-3"
                                             :alt="item.product.name"/>
                                        <div>
                                            <div class="font-medium">{{ item.product.name }}</div>
                                            <div class="text-sm text-gray-500">
                                                Артикул: {{ item.product.sku }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ item.variant?.name || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ formatPrice(item.price) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ item.quantity }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium">
                                    {{ formatPrice(item.price * item.quantity) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right font-medium">
                                    Итого:
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-primary-600">
                                    {{ formatPrice(order.total_amount) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Примечания -->
            <div v-if="order.notes" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-medium mb-4">Примечания к заказу</h3>
                <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ order.notes }}</p>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.table-row-hover:hover {
    @apply bg-gray-50 dark:bg-gray-700 transition-colors duration-150;
}
</style>