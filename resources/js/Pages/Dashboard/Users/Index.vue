<script setup>
import {ref, computed, onMounted, watch} from 'vue';
import {useForm, router} from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import SelectDropdown from "@/Components/SelectDropdown.vue";
import Checkbox from '@/Components/Checkbox.vue';
import debounce from 'lodash/debounce';
import Badge from '@/Components/Badge.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    users: Object,
    roles: Array,
    permissions: Array,
    statuses: Array,
    filters: Object
});

const breadCrumbs = [
    {
        name: 'Пользователи',
        link: route('dashboard.users.index')
    }
];

const search = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || '');
const selectedStatus = ref(props.filters.status || 'all');

// Обновляем функцию updateFilters для более отзывчивого UI
const updateFilters = debounce(() => {
    router.get(
        route('dashboard.users.index'),
        { 
            search: search.value || null, 
            role: selectedRole.value || null,
            status: selectedStatus.value === 'all' ? null : selectedStatus.value
        },
        { 
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users']
        }
    );
}, 300);

// Следим за изменениями фильтров
watch([search, selectedRole, selectedStatus], updateFilters);

// Добавляем вычисляемое свойство для отображения статуса фильтрации
const isFiltering = computed(() => {
    return search.value || selectedRole.value || selectedStatus.value !== 'all';
});

// Функция сброса фильтров
const resetFilters = () => {
    search.value = '';
    selectedRole.value = '';
    selectedStatus.value = 'all';
};

const showModal = ref(false);
const showRolesModal = ref(false);
const modalMode = ref('create');
const selectedUser = ref(null);
const selectedRoles = ref([]);
const selectedPermissions = ref([]);

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    roles: [],
    permissions: [],
});

const filteredUsers = computed(() => {
    if (!props.users || !props.users.data) return [];
    return props.users.data;
});

const openModal = (mode, user = null) => {
    modalMode.value = mode;
    selectedUser.value = user;
    
    if (mode === 'edit' && user) {
        form.first_name = user.profile?.first_name || '';
        form.last_name = user.profile?.last_name || '';
        form.email = user.email;
        form.roles = user.roles?.map(role => role.id) || [];
        form.permissions = user.permissions?.map(permission => permission.id) || [];
    } else {
        form.reset();
        form.roles = [];
        form.permissions = [];
    }
    showModal.value = true;
};

const openRolesModal = (user) => {
    selectedUser.value = user;
    selectedRoles.value = user.roles?.map(role => role.id) || [];
    selectedPermissions.value = user.permissions?.map(permission => permission.id) || [];
    showRolesModal.value = true;
};

const submitForm = () => {
    if (modalMode.value === 'create') {
        form.post(route('dashboard.users.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    } else if (modalMode.value === 'edit') {
        form.put(route('dashboard.users.update', selectedUser.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

const updateUserRoles = () => {
    const formData = {
        roles: selectedRoles.value,
        permissions: selectedPermissions.value,
    };
    
    form.put(route('dashboard.users.update', selectedUser.value.id), formData, {
        preserveScroll: true,
        onSuccess: () => {
            showRolesModal.value = false;
            selectedUser.value = null;
        }
    });
};

const closeModal = () => {
    showModal.value = false;
    showRolesModal.value = false;
    form.reset();
    selectedUser.value = null;
    selectedRoles.value = [];
    selectedPermissions.value = [];
};

const deleteUser = () => {
    if (selectedUser.value) {
        form.delete(route('dashboard.users.destroy', selectedUser.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                    Управление пользователями
                </h1>
                <PrimaryButton @click="openModal('create')" class="hidden md:flex">
                    <template #icon-left>
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 4v16m8-8H4"/>
                        </svg>
                    </template>
                    Добавить пользователя
                </PrimaryButton>
            </div>
            
            <!-- Фильтры -->
            <div class="mt-4 bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Поиск -->
                    <div class="flex-1">
                        <input
                            type="text"
                            v-model="search"
                            placeholder="Поиск по имени, email или роли..."
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                    </div>

                    <!-- Фильтр по ролям -->
                    <div class="w-full md:w-48">
                        <select
                            v-model="selectedRole"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option value="">Все роли</option>
                            <option v-for="role in roles" :key="role.id" :value="role.slug">
                                {{ role.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Фильтр по статусу -->
                    <div class="w-full md:w-48">
                        <select
                            v-model="selectedStatus"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Кнопка сброса фильтров -->
                    <PrimaryButton 
                        v-if="isFiltering" 
                        @click="resetFilters" 
                        type="secondary" 
                        class="md:w-auto"
                    >
                        Сбросить фильтры
                    </PrimaryButton>
                </div>

                <!-- Мобильная кнопка добавления -->
                <div class="mt-4 md:hidden">
                    <PrimaryButton @click="openModal('create')" class="w-full">
                        Добавить пользователя
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <template #default>
            <!-- Таблица пользователей -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full align-middle">
                        <div class="overflow-hidden shadow-sm rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Пользователь
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Роли
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Статус
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Действия
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ user.profile?.first_name }} {{ user.profile?.last_name }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ user.email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-wrap gap-1">
                                                <Badge 
                                                    v-for="role in user.roles" 
                                                    :key="role.id"
                                                    :type="getBadgeType(role.slug)"
                                                    size="sm"
                                                >
                                                    {{ role.name }}
                                                </Badge>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <Badge 
                                                :type="user.email_verified_at ? 'green' : 'yellow'"
                                                size="sm"
                                            >
                                                {{ user.email_verified_at ? 'Подтвержден' : 'Не подтвержден' }}
                                            </Badge>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <PrimaryButton @click="openRolesModal(user)" size="sm">
                                                    Роли
                                                </PrimaryButton>
                                                <PrimaryButton @click="openModal('edit', user)" size="sm">
                                                    Изменить
                                                </PrimaryButton>
                                                <PrimaryButton @click="openModal('delete', user)" type="red" size="sm">
                                                    Удалить
                                                </PrimaryButton>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Информация о количестве записей -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Показано {{ users.from }} - {{ users.to }} из {{ users.total }} записей
                    </div>
                </div>
            </div>

            <!-- Пагинация -->
            <div class="mt-6">
                <Pagination :links="users.links" />
            </div>

            <!-- Модальные окна остаются без изменений -->
            <Modal :show="showModal" @close="closeModal">
                <template #title>
                    {{ modalMode === 'create' ? 'Добавить пользователя' : 
                       modalMode === 'edit' ? 'Редактировать пользователя' : 'Удалить пользователя' }}
                </template>

                <template #content v-if="modalMode !== 'delete'">
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="first_name" value="Имя"/>
                                <TextInput 
                                    id="first_name" 
                                    v-model="form.first_name" 
                                    type="text" 
                                    :error="form.errors.first_name"
                                    required
                                />
                                <div v-if="form.errors.first_name" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.first_name }}
                                </div>
                            </div>
                            <div>
                                <InputLabel for="last_name" value="Фамилия"/>
                                <TextInput 
                                    id="last_name" 
                                    v-model="form.last_name" 
                                    type="text" 
                                    :error="form.errors.last_name"
                                    required
                                />
                                <div v-if="form.errors.last_name" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.last_name }}
                                </div>
                            </div>
                        </div>
                        <div>
                            <InputLabel for="email" value="Email"/>
                            <TextInput 
                                id="email" 
                                v-model="form.email" 
                                type="email" 
                                :error="form.errors.email"
                                required
                            />
                            <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">
                                {{ form.errors.email }}
                            </div>
                        </div>
                        <div v-if="modalMode === 'create'">
                            <InputLabel for="password" value="Пароль"/>
                            <TextInput 
                                id="password" 
                                v-model="form.password" 
                                type="password" 
                                :error="form.errors.password"
                                required
                            />
                            <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">
                                {{ form.errors.password }}
                            </div>
                        </div>
                        
                        <!-- Добавляем выбор ролей при создании -->
                        <div>
                            <InputLabel value="Роли"/>
                            <div class="mt-2 space-y-2">
                                <div v-for="role in props.roles" :key="role.id" class="flex items-center">
                                    <input
                                        type="checkbox"
                                        :id="'create-role-' + role.id"
                                        :value="role.id"
                                        v-model="form.roles"
                                        class="mr-2"
                                    />
                                    <label :for="'create-role-' + role.id">{{ role.name }}</label>
                                </div>
                            </div>
                            <div v-if="form.errors.roles" class="text-red-500 text-sm mt-1">
                                {{ form.errors.roles }}
                            </div>
                        </div>

                        <!-- Добавляем выбор разрешений при создании -->
                        <div>
                            <InputLabel value="Разрешения"/>
                            <div class="mt-2 space-y-2">
                                <div v-for="permission in props.permissions" :key="permission.id" class="flex items-center">
                                    <input
                                        type="checkbox"
                                        :id="'create-permission-' + permission.id"
                                        :value="permission.id"
                                        v-model="form.permissions"
                                        class="mr-2"
                                    />
                                    <label :for="'create-permission-' + permission.id">{{ permission.name }}</label>
                                </div>
                            </div>
                            <div v-if="form.errors.permissions" class="text-red-500 text-sm mt-1">
                                {{ form.errors.permissions }}
                            </div>
                        </div>
                    </form>
                </template>
                <template v-else #content>
                    <p class="text-sm text-gray-600">
                        Вы уверены, что хотите удалить пользователя? Это действие нельзя отменить.
                    </p>
                </template>

                <template #footer>
                    <div class="flex justify-end space-x-2">
                        <PrimaryButton v-if="modalMode !== 'delete'" @click="submitForm">
                            {{ modalMode === 'create' ? 'Добавить' : 'Сохранить' }}
                        </PrimaryButton>
                        <PrimaryButton v-else @click="deleteUser" type="red">
                            Удалить
                        </PrimaryButton>
                        <PrimaryButton @click="closeModal" type="secondary">
                            Отмена
                        </PrimaryButton>
                    </div>
                </template>
            </Modal>

            <!-- Roles Modal -->
            <Modal :show="showRolesModal" @close="closeModal">
                <template #title>
                    Управление ролями и разрешениями
                </template>

                <template #content>
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-medium">Роли</h3>
                            <div class="mt-2 space-y-2">
                                <div v-for="role in props.roles" :key="role.id" class="flex items-center">
                                    <Checkbox
                                        :id="'role-' + role.id"
                                        v-model:checked="selectedRoles"
                                        :value="role.id"
                                        class="mr-2"
                                    />
                                    <label :for="'role-' + role.id">{{ role.name }}</label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium">Прямые разрешения</h3>
                            <div class="mt-2 space-y-2">
                                <div v-for="permission in props.permissions" :key="permission.id" 
                                     class="flex items-center">
                                    <Checkbox
                                        :id="'permission-' + permission.id"
                                        v-model:checked="selectedPermissions"
                                        :value="permission.id"
                                        class="mr-2"
                                    />
                                    <label :for="'permission-' + permission.id">{{ permission.name }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template #footer>
                    <div class="flex justify-end space-x-2">
                        <PrimaryButton @click="updateUserRoles">
                            Сохранить
                        </PrimaryButton>
                        <PrimaryButton @click="closeModal" type="secondary">
                            Отмена
                        </PrimaryButton>
                    </div>
                </template>
            </Modal>
        </template>
    </DashboardLayout>
</template>

<script>
// Вспомогательная функция для определения типа бейджа роли
const getBadgeType = (roleSlug) => {
    const types = {
        'super-admin': 'red',
        'admin': 'purple',
        'manager': 'blue',
        'client': 'green',
        'default': 'gray'
    };
    return types[roleSlug] || types.default;
};
</script>
