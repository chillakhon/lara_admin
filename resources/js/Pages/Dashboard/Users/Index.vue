<script setup>
import {ref, computed} from 'vue';
import {useForm} from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import SelectDropdown from "@/Components/SelectDropdown.vue";

const props = defineProps({
    users: Array, Object
})

const breadCrumbs = [
    {
        name: 'Пользователи',
        link: route('dashboard.users.index')
    }
]

const searchQuery = ref('');
const showModal = ref(false);
const modalMode = ref('create');
const selectedUser = ref(null);

const form = useForm({
    firstName: '',
    lastName: '',
    email: '',
    position: '',
    role: 'user',
});

const filteredUsers = computed(() => {
    if (!props.users || !props.users.data) return [];

    return props.users.data.filter(user =>
        (user.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ?? false) ||
        (user.email?.toLowerCase().includes(searchQuery.value.toLowerCase()) ?? false) ||
        (user.position?.toLowerCase().includes(searchQuery.value.toLowerCase()) ?? false)
    );
});

const openModal = (mode, user = null) => {
    modalMode.value = mode;
    selectedUser.value = user;
    if (mode === 'edit' && user) {
        const [firstName, ...lastNameParts] = (user.name ?? '').split(' ');
        form.firstName = firstName;
        form.lastName = lastNameParts.join(' ');
        form.email = user.email ?? '';
        form.position = user.position ?? '';
        form.role = user.role?.name?.toLowerCase() ?? 'user';
    } else {
        form.reset();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    selectedUser.value = null;
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
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Все пользователи</h1>
            <div class="sm:flex">
                <div
                    class="items-center hidden mb-3 sm:flex sm:divide-x sm:divide-gray-100 sm:mb-0 dark:divide-gray-700">
                    <form class="lg:pr-3" action="#" method="GET">
                        <label for="users-search" class="sr-only">Поиск</label>
                        <div class="relative mt-1 lg:w-64 xl:w-96">
                            <input type="text" name="email" id="users-search"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                   placeholder="Поиск пользователей" v-model="searchQuery">
                        </div>
                    </form>
                </div>
                <div class="flex items-center ml-auto space-x-2 sm:space-x-3">
                    <PrimaryButton type="default" @click="openModal('create')"
                                   class="inline-flex items-center justify-center w-1/2 px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 sm:w-auto dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                        <template #icon-left>
                            <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                      d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                      clip-rule="evenodd"></path>
                            </svg>
                        </template>
                        Добавить пользователя
                    </PrimaryButton>
                </div>
            </div>
        </template>
        <template #default>
            <div class="flex flex-col">
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full align-middle">
                        <div class="overflow-hidden shadow">
                            <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th scope="col"
                                        class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                        Имя
                                    </th>
                                    <th scope="col"
                                        class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                        Email
                                    </th>
                                    <th scope="col"
                                        class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                        Роль
                                    </th>
                                    <th scope="col" class="p-4">
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                <tr v-for="user in filteredUsers" :key="user.id"
                                    class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <td class="flex items-center p-4 mr-12 space-x-6 whitespace-nowrap">
                                        <div class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                            <div class="text-base font-semibold text-gray-900 dark:text-white">
                                                {{ user.name }}
                                            </div>
                                            <div class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                                {{ user.email }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ user.position }}
                                    </td>
                                    <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ user.role?.name }}
                                    </td>
                                    <!-- ... rest of your template ... -->
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Modal -->
            <Modal :show="showModal" @close="closeModal">
                <template #title v-if="modalMode === 'create'">
                    Добавить нового пользователя
                </template>
                <template #title v-if="modalMode === 'edit'">
                    Редактировать пользователя
                </template>
                <template #title v-if="modalMode === 'delete'">
                    Удалить пользователя
                </template>

                <template #content v-if="modalMode !== 'delete'">
                    <form @submit.prevent="submitForm">
                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-3">
                                <TextInput label="Имя" v-model="form.first_name" type="text" required/>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <TextInput label="Фамилия" v-model="form.last_name" type="text" required/>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <TextInput label="Email" v-model="form.email" type="email" class="mt-1 block w-full"
                                           required/>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="role" value="Роль"/>
                                <SelectDropdown
                                    v-model="form.role"
                                    :options="[{label: 'Администратор', value: 'admin'}, {label: 'Менеджер', value: 'manager'}]"
                                ></SelectDropdown>
                            </div>
                            <div v-if="modalMode === 'create'" class="col-span-6 sm:col-span-3">
                                <TextInput label="Пароль" v-model="form.password" type="password"
                                           class="mt-1 block w-full" required/>
                            </div>
                        </div>
                    </form>
                </template>
                <template v-else #content>
                    <p class="text-sm text-gray-500">
                        Вы уверены, что хотите удалить этого пользователя? Это действие нельзя отменить.
                    </p>
                </template>

                <template #footer v-if="modalMode !== 'delete'">
                    <PrimaryButton @click="submitForm" class="mr-3">{{
                            modalMode === 'create' ? 'Добавить' : 'Сохранить'
                        }}
                    </PrimaryButton>
                    <PrimaryButton type="red" @click="closeModal">Отмена</PrimaryButton>
                </template>
                <template #footer v-else>
                    <PrimaryButton @click="deleteUser" class="mr-3">Удалить</PrimaryButton>
                    <PrimaryButton type="red" @click="closeModal">Отмена</PrimaryButton>
                </template>
            </Modal>
        </template>
    </DashboardLayout>
</template>
