<script setup>
import { ref, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import ContextMenu from '@/Components/ContextMenu.vue';
import Badge from '@/Components/Badge.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    clients: Object,
    filters: Object,
    levels: Array,
    statuses: Array,
    sortOptions: Array,
});

const search = ref(props.filters.search || '');
const selectedLevel = ref(props.filters.level || '');
const selectedStatus = ref(props.filters.status || '');
const selectedSort = ref(props.filters.sort || 'created_at,desc');

const showModal = ref(false);
const modalMode = ref('create');
const selectedClient = ref(null);

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    address: '',
    password: '',
    level_id: '',
    bonus_balance: 0,
});

const openModal = (mode, client = null) => {
    modalMode.value = mode;
    selectedClient.value = client;
    
    if (mode === 'edit' && client) {
        form.first_name = client.profile.first_name;
        form.last_name = client.profile.last_name;
        form.email = client.user.email;
        form.phone = client.phone;
        form.address = client.address;
        form.level_id = client.level?.id;
        form.bonus_balance = client.bonus_balance;
    } else {
        form.reset();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    selectedClient.value = null;
};

const submitForm = () => {
    if (modalMode.value === 'create') {
        form.post(route('dashboard.clients.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    } else {
        form.put(route('dashboard.clients.update', selectedClient.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

// Функции форматирования
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
        currency: 'RUB',
        minimumFractionDigits: 0
    }).format(amount);
};

// Функции для работы с данными клиента
const getInitials = (client) => {
    const firstName = client.profile.first_name || '';
    const lastName = client.profile.last_name || '';
    return (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
};

const getFullName = (client) => {
    return `${client.profile.first_name} ${client.profile.last_name}`;
};

// Функция для получения типа бейджа уровня клиента
const getLevelBadgeType = (level) => {
    const types = {
        'bronze': 'yellow',
        'silver': 'gray',
        'gold': 'yellow',
        'platinum': 'purple',
        'default': 'blue'
    };
    return types[level?.slug] || types.default;
};

// Обработка фильтров
const updateFilters = () => {
    router.get(
        route('dashboard.clients.index'),
        {
            search: search.value || null,
            level: selectedLevel.value || null,
            status: selectedStatus.value || null,
            sort: selectedSort.value || null,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

watch([search, selectedLevel, selectedStatus, selectedSort], updateFilters);

// Действия с контекстным меню
const menuItems = [
    { text: 'Просмотр', action: 'view' },
    { text: 'Редактировать', action: 'edit' },
    { text: 'Удалить', action: 'delete', isDangerous: true }
];

const handleAction = (action, client) => {
    switch (action.action) {
        case 'view':
            router.visit(route('dashboard.clients.show', client.id));
            break;
        case 'edit':
            openModal('edit', client);
            break;
        case 'delete':
            if (confirm('Вы уверены, что хотите удалить этого клиента?')) {
                router.delete(route('dashboard.clients.destroy', client.id));
            }
            break;
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
                <PrimaryButton @click="openModal('create')">
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
                                                        {{ getInitials(client) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ getFullName(client) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ client.user.email }}</div>
                                        <div class="text-sm text-gray-500">{{ client.phone }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ formatMoney(client.bonus_balance) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ client.orders_count }}
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

        <!-- Модальное окно создания/редактирования -->
        <Modal :show="showModal" @close="closeModal">
            <template #title>
                {{ modalMode === 'create' ? 'Добавить клиента' : 'Редактировать клиента' }}
            </template>

            <template #content>
                <form @submit.prevent="submitForm" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Основная информация -->
                        <div class="space-y-6">
                            <div>
                                <InputLabel for="first_name" value="Имя" />
                                <TextInput
                                    id="first_name"
                                    v-model="form.first_name"
                                    type="text"
                                    required
                                    class="mt-1 block w-full"
                                    :error="form.errors.first_name"
                                />
                            </div>

                            <div>
                                <InputLabel for="last_name" value="Фамилия" />
                                <TextInput
                                    id="last_name"
                                    v-model="form.last_name"
                                    type="text"
                                    required
                                    class="mt-1 block w-full"
                                    :error="form.errors.last_name"
                                />
                            </div>

                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="mt-1 block w-full"
                                    :error="form.errors.email"
                                />
                            </div>

                            <div v-if="modalMode === 'create'">
                                <InputLabel for="password" value="Пароль" />
                                <TextInput
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    required
                                    class="mt-1 block w-full"
                                    :error="form.errors.password"
                                />
                            </div>
                        </div>

                        <!-- Дополнительная информация -->
                        <div class="space-y-6">
                            <div>
                                <InputLabel for="phone" value="Телефон" />
                                <TextInput
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="mt-1 block w-full"
                                    :error="form.errors.phone"
                                />
                            </div>

                            <div>
                                <InputLabel for="address" value="Адрес" />
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    rows="3"
                                ></textarea>
                            </div>

                            <div>
                                <InputLabel for="level_id" value="Уровень клиента" />
                                <select
                                    id="level_id"
                                    v-model="form.level_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option value="">Выберите уровень</option>
                                    <option v-for="level in levels" :key="level.id" :value="level.id">
                                        {{ level.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <InputLabel for="bonus_balance" value="Бонусный баланс" />
                                <TextInput
                                    id="bonus_balance"
                                    v-model="form.bonus_balance"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="mt-1 block w-full"
                                    :error="form.errors.bonus_balance"
                                />
                            </div>
                        </div>
                    </div>
                </form>
            </template>

            <template #footer>
                <div class="flex justify-end space-x-2">
                    <PrimaryButton @click="submitForm" :disabled="form.processing">
                        {{ modalMode === 'create' ? 'Создать' : 'Сохранить' }}
                    </PrimaryButton>
                    <PrimaryButton @click="closeModal" type="secondary">
                        Отмена
                    </PrimaryButton>
                </div>
            </template>
        </Modal>
    </DashboardLayout>
</template>
