<script setup>
import { ref, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import ContextMenu from '@/Components/ContextMenu.vue';

const props = defineProps({
    clients: Object,
    filters: Object
});

const search = ref(props.filters.search);

const breadCrumbs = [
    { name: 'Клиенты', link: route('dashboard.clients.index') }
];

watch(search, (value) => {
    router.get(route('dashboard.clients.index'), 
        { search: value }, 
        { preserveState: true, preserveScroll: true }
    );
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ru-RU');
};

const formatMoney = (amount) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
    }).format(amount);
};

const menuItems = [
    { text: 'Просмотр', action: 'view' },
    { text: 'Редактировать', action: 'edit' },
    { text: 'Удалить', action: 'delete', isDangerous: true }
];

const handleAction = (action, client) => {
    if (action.action === 'view') {
        router.visit(route('dashboard.clients.show', client.id));
    } else if (action.action === 'edit') {
        // Добавим позже функционал редактирования
    } else if (action.action === 'delete') {
        // Добавим позже функционал удаления
    }
};
</script>

<template>
    <DashboardLayout>
        <Head title="Клиенты" />

        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs" />
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Клиенты
                </h1>
                <PrimaryButton @click="$inertia.visit(route('dashboard.clients.create'))">
                    Добавить клиента
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <!-- Поиск -->
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <TextInput
                            v-model="search"
                            type="search"
                            placeholder="Поиск по имени или email..."
                            class="w-full md:w-1/3"
                        />
                    </div>

                    <!-- Таблица -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Клиент
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Контакты
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Бонусный баланс
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Заказов
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Дата регистрации
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="client in clients.data" 
                                    :key="client.id" 
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                    @click="router.visit(route('dashboard.clients.show', client.id))"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-gray-600">
                                                        {{ client.full_name.split(' ').map(n => n[0]).join('') }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ client.full_name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ client.email }}</div>
                                        <div class="text-sm text-gray-500">{{ client.phone }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ formatMoney(client.bonus_balance) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ client.total_orders }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ formatDate(client.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div @click.stop>
                                            <ContextMenu
                                                :items="menuItems"
                                                :context-data="client"
                                                @item-click="handleAction"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Пагинация -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <Pagination :data="clients" />
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
