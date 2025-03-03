<script setup>
import { Head } from '@inertiajs/vue3';
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { ref, onMounted } from 'vue';
import { default as ApexCharts } from 'vue3-apexcharts';
import axios from 'axios';

const analytics = ref({
    metrics: {
        total_revenue: 0,
        orders_count: 0,
        average_order: 0,
        new_clients: 0
    },
    sales_chart: [],
    order_statuses: [],
    top_products: [],
    order_sources: []
});

const salesChartData = ref({
    options: {
        chart: {
            type: 'area',
            height: 350,
            toolbar: {
                show: false
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth'
        },
        xaxis: {
            type: 'datetime'
        },
        tooltip: {
            x: {
                format: 'dd MMM yyyy'
            }
        }
    },
    series: [{
        name: 'Продажи',
        data: []
    }]
});

const statusChartData = ref({
    options: {
        chart: {
            type: 'donut',
            height: 300
        },
        labels: [],
        colors: ['#1A56DB', '#FDBA8C', '#16BDCA', '#9061F9']
    },
    series: []
});

onMounted(async () => {
    try {
        const response = await axios.get('/dashboard/analytics');
        analytics.value = response.data;
        
        // Форматируем данные для графиков
        salesChartData.value.series = [{
            name: 'Продажи',
            data: analytics.value.sales_chart.map(item => ({
                x: new Date(item.date).getTime(),
                y: item.total
            }))
        }];

        statusChartData.value.series = analytics.value.order_statuses.map(status => status.count);
        statusChartData.value.options.labels = analytics.value.order_statuses.map(status => status.status);
    } catch (error) {
        console.error('Ошибка при загрузке аналитики:', error);
    }
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
    }).format(value);
};
</script>

<template>
    <Head title="Dashboard" />

    <DashboardLayout>
        <div class="p-4 bg-white block sm:flex items-center justify-between border-b border-gray-200 lg:mt-1.5 dark:bg-gray-800 dark:border-gray-700">
            <div class="w-full mb-1">
                <div class="mb-4">
                    <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Аналитика</h1>
                </div>
            </div>
        </div>

        <!-- Карточки с метриками -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4 p-4">
            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-2xl sm:text-3xl leading-none font-bold text-gray-900 dark:text-white">
                            {{ formatCurrency(analytics.metrics.total_revenue) }}
                        </span>
                        <h3 class="text-base font-normal text-gray-500 dark:text-gray-400">Выручка</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-2xl sm:text-3xl leading-none font-bold text-gray-900 dark:text-white">
                            {{ analytics.metrics.orders_count }}
                        </span>
                        <h3 class="text-base font-normal text-gray-500 dark:text-gray-400">Заказов</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-2xl sm:text-3xl leading-none font-bold text-gray-900 dark:text-white">
                            {{ formatCurrency(analytics.metrics.average_order) }}
                        </span>
                        <h3 class="text-base font-normal text-gray-500 dark:text-gray-400">Средний чек</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-2xl sm:text-3xl leading-none font-bold text-gray-900 dark:text-white">
                            {{ analytics.metrics.new_clients }}
                        </span>
                        <h3 class="text-base font-normal text-gray-500 dark:text-gray-400">Новых клиентов</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Графики -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4 p-4">
            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <h3 class="text-xl font-bold mb-4 dark:text-white">Продажи</h3>
                <ApexCharts
                    width="100%"
                    height="350"
                    type="area"
                    :options="salesChartData.options"
                    :series="salesChartData.series"
                />
            </div>

            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <h3 class="text-xl font-bold mb-4 dark:text-white">Статусы заказов</h3>
                <ApexCharts
                    width="100%"
                    height="350"
                    type="donut"
                    :options="statusChartData.options"
                    :series="statusChartData.series"
                />
            </div>
        </div>

        <!-- Топ продуктов -->
        <div class="p-4">
            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <h3 class="text-xl font-bold mb-4 dark:text-white">Топ продаваемых товаров</h3>
                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">Товар</th>
                                <th scope="col" class="px-6 py-3">Количество</th>
                                <th scope="col" class="px-6 py-3">Выручка</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in analytics.top_products" :key="product.name" 
                                class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4">{{ product.name }}</td>
                                <td class="px-6 py-4">{{ product.total_quantity }}</td>
                                <td class="px-6 py-4">{{ formatCurrency(product.total_revenue) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
