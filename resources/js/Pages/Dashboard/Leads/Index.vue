<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Заявки</h1>
                <div class="flex gap-2">
                    <PrimaryButton @click="openFiltersModal" type="alternative">
                        <template #icon-left>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                        </template>
                        {{ activeFiltersCount ? `Фильтры (${activeFiltersCount})` : 'Фильтры' }}
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Статистика -->
                <LeadStats v-if="leads.data" :leads="leads.data" />

                <!-- Фильтры и поиск -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg mb-6">
                    <div class="p-4 space-y-4">
                        <div class="flex flex-col md:flex-row gap-4">
                            <TextInput
                                v-model="search"
                                placeholder="Поиск по заявкам..."
                                class="flex-1"
                            >
                                <template #prefix>
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </template>
                            </TextInput>
                            <div class="flex gap-4">
                                <SelectDropdown
                                    v-model="filters.type"
                                    :options="leadTypes"
                                    placeholder="Тип заявки"
                                    class="w-48"
                                />
                                <SelectDropdown
                                    v-model="filters.status"
                                    :options="statusOptions"
                                    placeholder="Статус"
                                    class="w-48"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Таблица заявок -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        Дата
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        Тип
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        Клиент
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        Контакты
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        Статус
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Действия</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        <div>{{ formatDate(lead.created_at) }}</div>
                                        <div class="text-xs text-gray-500">{{ formatTime(lead.created_at) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        {{ lead.type.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="lead.client" class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                                        {{ getInitials(lead.client.full_name) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ lead.client.full_name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    ID: {{ lead.client.id }}
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="flex items-center">
                                            <div class="text-sm text-gray-500">
                                                {{ lead.data.name || 'Нет данных' }}
                                                <PrimaryButton 
                                                    @click="createClient(lead)"
                                                    size="xs"
                                                    type="alternative"
                                                    class="ml-2"
                                                >
                                                    Создать клиента
                                                </PrimaryButton>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div v-if="lead.data.phone">
                                            <a :href="`tel:${lead.data.phone}`" class="flex items-center hover:text-gray-700 dark:hover:text-gray-300">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                </svg>
                                                {{ lead.data.phone }}
                                            </a>
                                        </div>
                                        <div v-if="lead.data.email">
                                            <a :href="`mailto:${lead.data.email}`" class="flex items-center hover:text-gray-700 dark:hover:text-gray-300">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                </svg>
                                                {{ lead.data.email }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Badge :type="getStatusBadgeType(lead.status)">
                                            {{ getStatusLabel(lead.status) }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <ContextMenu
                                            :items="menuItems"
                                            @item-click="(action) => handleAction(action, lead)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                        <Pagination :data="leads" @page-changed="handlePageChange"/>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно просмотра/редактирования заявки -->
        <Modal :show="showLeadModal" @close="closeLeadModal" max-width="4xl">
            <template #title>
                {{ modalMode === 'view' ? 'Просмотр заявки' : 'Редактирование заявки' }}
            </template>
            <template #content>
                <div class="space-y-4">
                    <!-- Информация о заявке -->
                    <div v-if="selectedLead">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium">Тип заявки</label>
                                <p>{{ selectedLead.type.name }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Дата создания</label>
                                <p>{{ formatDate(selectedLead.created_at) }}</p>
                            </div>
                        </div>

                        <!-- Данные заявки -->
                        <div class="mt-4">
                            <label class="text-sm font-medium">Данные заявки</label>
                            <div class="mt-2 space-y-2">
                                <div v-for="(value, key) in selectedLead.data" :key="key">
                                    <label class="text-sm text-gray-500">{{ key }}</label>
                                    <p>{{ value }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- История -->
                        <div class="mt-4">
                            <label class="text-sm font-medium">История обработки</label>
                            <div class="mt-2 space-y-2">
                                <div v-for="history in selectedLead.history" :key="history.id"
                                     class="p-2 bg-gray-50 rounded">
                                    <div class="flex justify-between">
                                        <span>{{ getStatusLabel(history.status) }}</span>
                                        <span class="text-sm text-gray-500">
                                            {{ formatDate(history.created_at) }}
                                        </span>
                                    </div>
                                    <p v-if="history.comment" class="text-sm mt-1">
                                        {{ history.comment }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-between">
                    <div>
                        <SelectDropdown
                            v-if="modalMode === 'edit'"
                            v-model="form.status"
                            :options="statusOptions"
                            placeholder="Изменить статус"
                        />
                    </div>
                    <div class="flex gap-2">
                        <PrimaryButton @click="closeLeadModal">
                            Закрыть
                        </PrimaryButton>
                        <PrimaryButton 
                            v-if="modalMode === 'edit'"
                            @click="updateLead"
                            :loading="form.processing"
                        >
                            Сохранить
                        </PrimaryButton>
                    </div>
                </div>
            </template>
        </Modal>

        <!-- Модальное окно создания клиента -->
        <Modal :show="showCreateClientModal" @close="closeCreateClientModal">
            <template #title>
                Создание клиента из заявки
            </template>
            <template #content>
                <form @submit.prevent="submitCreateClient" class="space-y-4">
                    <TextInput
                        v-model="createClientForm.first_name"
                        label="Имя"
                        :error="createClientForm.errors.first_name"
                        required
                    />
                    <TextInput
                        v-model="createClientForm.last_name"
                        label="Фамилия"
                        :error="createClientForm.errors.last_name"
                    />
                    <TextInput
                        v-model="createClientForm.phone"
                        label="Телефон"
                        :error="createClientForm.errors.phone"
                        required
                    />
                    <TextInput
                        v-model="createClientForm.email"
                        label="Email"
                        type="email"
                        :error="createClientForm.errors.email"
                        required
                    />
                </form>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <PrimaryButton @click="closeCreateClientModal" type="alternative">
                        Отмена
                    </PrimaryButton>
                    <PrimaryButton @click="submitCreateClient" :loading="createClientForm.processing">
                        Создать клиента
                    </PrimaryButton>
                </div>
            </template>
        </Modal>
    </DashboardLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import LeadStats from '@/Components/LeadStats.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectDropdown from '@/Components/SelectDropdown.vue';
import Badge from '@/Components/Badge.vue';
import ContextMenu from '@/Components/ContextMenu.vue';
import Pagination from '@/Components/Pagination.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    leads: {
        type: Object,
        required: true
    },
    leadTypes: {
        type: Array,
        required: true
    }
});

// Состояния
const search = ref('');
const filters = ref({
    type: '',
    status: ''
});
const showLeadModal = ref(false);
const modalMode = ref('view'); // 'view' или 'edit'
const selectedLead = ref(null);

// Форма для обновления статуса
const form = useForm({
    status: '',
    comment: ''
});

// Хлебные крошки
const breadCrumbs = [
    { name: 'Панель управления', link: route('dashboard') },
    { name: 'Заявки', link: route('dashboard.leads.index') }
];

// Опции для статусов
const statusOptions = [
    { value: 'new', label: 'Новая' },
    { value: 'processing', label: 'В обработке' },
    { value: 'completed', label: 'Завершена' },
    { value: 'rejected', label: 'Отклонена' }
];

// Пункты контекстного меню
const menuItems = [
    { text: 'Просмотреть', action: 'view' },
    { text: 'Редактировать', action: 'edit' },
    { text: 'Удалить', action: 'delete', isDangerous: true }
];

// Методы
const formatDate = (date) => {
    return new Date(date).toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusLabel = (status) => {
    return statusOptions.find(option => option.value === status)?.label || status;
};

const getStatusBadgeType = (status) => {
    const types = {
        new: 'blue',
        processing: 'yellow',
        completed: 'green',
        rejected: 'red'
    };
    return types[status] || 'default';
};

const handleAction = (action, lead) => {
    selectedLead.value = lead;
    
    switch (action.action) {
        case 'view':
            modalMode.value = 'view';
            showLeadModal.value = true;
            break;
        case 'edit':
            modalMode.value = 'edit';
            form.status = lead.status;
            showLeadModal.value = true;
            break;
        case 'delete':
            if (confirm('Вы уверены, что хотите удалить эту заявку?')) {
                router.delete(route('dashboard.leads.destroy', lead.id));
            }
            break;
    }
};

const closeLeadModal = () => {
    showLeadModal.value = false;
    selectedLead.value = null;
    form.reset();
};

const updateLead = () => {
    form.put(route('dashboard.leads.update', selectedLead.value.id), {
        preserveScroll: true,
        onSuccess: () => closeLeadModal()
    });
};

const handlePageChange = (page) => {
    router.get(
        route('dashboard.leads.index', { page }),
        { 
            ...filters.value,
            search: search.value 
        },
        { 
            preserveState: true,
            preserveScroll: true 
        }
    );
};

// Наблюдатели за фильтрами
watch([search, filters], ([newSearch, newFilters], [oldSearch, oldFilters]) => {
    if (JSON.stringify([newSearch, newFilters]) !== JSON.stringify([oldSearch, oldFilters])) {
        router.get(
            route('dashboard.leads.index'),
            { 
                ...newFilters,
                search: newSearch 
            },
            { 
                preserveState: true,
                preserveScroll: true 
            }
        );
    }
}, { deep: true });

// Добавляем computed для активных фильтров
const activeFiltersCount = computed(() => {
    return Object.values(filters.value).filter(Boolean).length + (search.value ? 1 : 0);
});

// Добавляем метод для форматирования времени
const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Добавляем метод для получения инициалов
const getInitials = (name) => {
    if (!name) return '';
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase();
};

// Добавляем состояния для создания клиента
const showCreateClientModal = ref(false);
const selectedLeadForClient = ref(null);

const createClientForm = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    lead_id: null
});

// Методы для работы с клиентами
const createClient = (lead) => {
    selectedLeadForClient.value = lead;
    createClientForm.lead_id = lead.id;
    
    // Предзаполняем форму данными из заявки
    const nameParts = (lead.data.name || '').split(' ');
    createClientForm.first_name = nameParts[0] || '';
    createClientForm.last_name = nameParts[1] || '';
    createClientForm.phone = lead.data.phone || '';
    createClientForm.email = lead.data.email || '';
    
    showCreateClientModal.value = true;
};

const closeCreateClientModal = () => {
    showCreateClientModal.value = false;
    selectedLeadForClient.value = null;
    createClientForm.reset();
};

const submitCreateClient = () => {
    createClientForm.post(route('dashboard.leads.create-client'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateClientModal();
            // Обновляем данные на странице
            router.reload();
        }
    });
};
</script> 