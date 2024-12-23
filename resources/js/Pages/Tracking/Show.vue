<script setup>
import { ref } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    shipment: Object,
    trackingInfo: Object
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

function formatDate(date) {
    return new Date(date).toLocaleString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>

<template>
    <DashboardLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h1 class="text-2xl font-semibold mb-6">
                            Отслеживание отправления
                        </h1>

                        <!-- Информация об отправлении -->
                        <div class="mb-8">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <h3 class="text-lg font-medium mb-2">Детали отправления</h3>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Номер заказа</dt>
                                            <dd class="mt-1">{{ shipment.order.order_number }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Трек-номер</dt>
                                            <dd class="mt-1">{{ shipment.tracking_number }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Способ доставки</dt>
                                            <dd class="mt-1">{{ shipment.delivery_method.name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Текущий статус</dt>
                                            <dd class="mt-1">
                                                <Badge :type="getStatusBadgeType(shipment.status.code)">
                                                    {{ shipment.status.name }}
                                                </Badge>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium mb-2">Адрес доставки</h3>
                                    <p class="text-gray-700">
                                        {{ shipment.shipping_address }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- История отслеживания -->
                        <div>
                            <h3 class="text-lg font-medium mb-4">История отслеживани��</h3>
                            <div class="space-y-4">
                                <div v-for="(event, index) in trackingInfo.history" 
                                     :key="index"
                                     class="flex items-start">
                                    <div class="min-w-[150px] text-sm text-gray-500">
                                        {{ formatDate(event.datetime) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">{{ event.status }}</p>
                                        <p class="text-sm text-gray-500">{{ event.description }}</p>
                                        <p v-if="event.location" class="text-sm text-gray-500">
                                            {{ event.location }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template> 