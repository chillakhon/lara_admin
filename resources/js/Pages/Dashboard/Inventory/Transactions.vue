<!-- resources/js/Pages/Inventory/Transactions.vue -->
<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">История транзакций</h1>
            <div class="sm:flex">
                <div
                    class="items-center hidden mb-3 sm:flex sm:divide-x sm:divide-gray-100 sm:mb-0 dark:divide-gray-700">
                    <form class="lg:pr-3" action="#" method="GET">
                        <label for="transactions-search" class="sr-only">Поиск</label>
                        <div class="relative mt-1 lg:w-64 xl:w-96">
                            <input type="text" name="search" id="transactions-search"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                   placeholder="Поиск по названию или типу" v-model="searchQuery">
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <div class="flex flex-col">
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden shadow">
                        <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    Дата
                                </th>
                                <th scope="col"
                                    class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    Название
                                </th>
                                <th scope="col"
                                    class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    Тип
                                </th>
                                <th scope="col"
                                    class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    Количество
                                </th>
                                <th scope="col"
                                    class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    Цена за ед.
                                </th>
                                <th scope="col"
                                    class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    Описание
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            <tr v-for="transaction in filteredTransactions" :key="transaction.id"
                                class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="p-4 text-sm font-normal text-gray-500 whitespace-nowrap dark:text-gray-400">
                                    {{ formatDate(transaction.created_at) }}
                                </td>
                                <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ transaction.item.title }}
                                </td>
                                <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ transaction.type }}
                                </td>
                                <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ transaction.quantity }} {{ transaction.unit.abbreviation }}
                                </td>
                                <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ transaction.price_per_unit }}
                                </td>
                                <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ transaction.description }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            <Pagination :links="transactions.links"/>
        </div>
    </DashboardLayout>
</template>

<script setup>
import {ref, computed} from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
    transactions: Object,
});

const breadCrumbs = [
    {
        name: 'Управление запасами',
        link: route('dashboard.inventory.index')
    },
    {
        name: 'История транзакций',
        link: route('dashboard.inventory.transactions')
    }
];

const searchQuery = ref('');

const filteredTransactions = computed(() => {
    return props.transactions.data.filter(transaction =>
        transaction.type.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};
</script>
