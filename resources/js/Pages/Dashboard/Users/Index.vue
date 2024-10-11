<script setup>
import {ref, computed} from 'vue';
import {useForm} from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import BreadCrumbs from "@/Components/BreadCrumbs.vue";


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
const showAddUserModal = ref(false);
const showEditUserModal = ref(false);
const showDeleteUserModal = ref(false);

const newUser = ref({
    firstName: '',
    lastName: '',
    email: '',
    position: '',
    role: 'user',
});

const editingUser = ref(null);
const userToDelete = ref(null);

const filteredUsers = computed(() => {
    return users.value.filter(user =>
        user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        user.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        user.position.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const openAddUserModal = () => {
    showAddUserModal.value = true;
};

const closeAddUserModal = () => {
    showAddUserModal.value = false;
    newUser.value = {firstName: '', lastName: '', email: '', position: '', role: 'user'};
};

const addUser = () => {
    // Here you would typically make an API call to add the user
    const fullName = `${newUser.value.firstName} ${newUser.value.lastName}`;
    users.value.push({
        id: users.value.length + 1,
        name: fullName,
        email: newUser.value.email,
        position: newUser.value.position,
        role: newUser.value.role,
        avatar: '/path-to-default-avatar.jpg',
    });
    closeAddUserModal();
};

const openEditUserModal = (user) => {
    editingUser.value = {...user, firstName: user.name.split(' ')[0], lastName: user.name.split(' ')[1]};
    showEditUserModal.value = true;
};

const closeEditUserModal = () => {
    showEditUserModal.value = false;
    editingUser.value = null;
};

const updateUser = () => {
    // Here you would typically make an API call to update the user
    const index = users.value.findIndex(u => u.id === editingUser.value.id);
    if (index !== -1) {
        users.value[index] = {
            ...editingUser.value,
            name: `${editingUser.value.firstName} ${editingUser.value.lastName}`,
        };
    }
    closeEditUserModal();
};

const openDeleteUserModal = (user) => {
    userToDelete.value = user;
    showDeleteUserModal.value = true;
};

const closeDeleteUserModal = () => {
    showDeleteUserModal.value = false;
    userToDelete.value = null;
};

const deleteUser = () => {
    // Here you would typically make an API call to delete the user
    users.value = users.value.filter(u => u.id !== userToDelete.value.id);
    closeDeleteUserModal();
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
                    <PrimaryButton type="default" @click="openAddUserModal"
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
                                        Должность
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
                                <tr v-for="user in users.data" :key="user.id"
                                    class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <td class="flex items-center p-4 mr-12 space-x-6 whitespace-nowrap">
                                        <div class="w-10 h-10 rounded-full bg-gray-200">
                                            <img class="w-full h-full rounded-full" :src="user.avatar" :alt="user.name">
                                        </div>
                                        <div class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                            <div class="text-base font-semibold text-gray-900 dark:text-white">{{
                                                    user.name
                                                }}
                                            </div>
                                            <div class="text-sm font-normal text-gray-500 dark:text-gray-400">{{
                                                    user.email
                                                }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ user.position }}
                                    </td>
                                    <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ user.role.name }}
                                    </td>
                                    <td class="p-4 space-x-2 whitespace-nowrap flex gap-1">
                                        <PrimaryButton @click="openEditUserModal(user)">
                                            <template #icon-left>
                                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"
                                                     xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path>
                                                    <path fill-rule="evenodd"
                                                          d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                                                          clip-rule="evenodd"></path>
                                                </svg>
                                            </template>
                                            Редактировать
                                        </PrimaryButton>
                                        <PrimaryButton @click="openDeleteUserModal(user)" type="red">
                                            <template #icon-left>
                                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"
                                                     xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd"
                                                          d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                          clip-rule="evenodd"></path>
                                                </svg>
                                            </template>
                                            Удалить
                                        </PrimaryButton>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add User Modal -->
            <Modal :show="showAddUserModal" @close="closeAddUserModal">
                <template #title>Добавить нового пользователя</template>
                <template #content>
                    <form @submit.prevent="addUser">
                        <!-- Form fields for adding user -->
                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="first-name" value="Имя"/>
                                <TextInput id="first-name" v-model="newUser.firstName" type="text"
                                           class="mt-1 block w-full"
                                           required/>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="last-name" value="Фамилия"/>
                                <TextInput id="last-name" v-model="newUser.lastName" type="text"
                                           class="mt-1 block w-full"
                                           required/>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="email" value="Email"/>
                                <TextInput id="email" v-model="newUser.email" type="email" class="mt-1 block w-full"
                                           required/>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="position" value="Должность"/>
                                <TextInput id="position" v-model="newUser.position" type="text"
                                           class="mt-1 block w-full"
                                           required/>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="role" value="Роль"/>
                                <select id="role" v-model="newUser.role" class="mt-1 block w-full" required>
                                    <option value="admin">Администратор</option>
                                    <option value="manager">Менеджер</option>
                                    <option value="user">Пользователь</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </template>
                <template #footer>
                    <PrimaryButton @click="addUser" class="mr-3">Добавить пользователя</PrimaryButton>
                    <SecondaryButton @click="closeAddUserModal">Отмена</SecondaryButton>
                </template>
            </Modal>

            <!-- Edit User Modal -->
            <Modal :show="showEditUserModal" @close="closeEditUserModal">
                <template #title>Редактировать пользователя</template>
                <template #content>
                    <form @submit.prevent="updateUser">
                        <!-- Form fields for editing user -->
                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="edit-first-name" value="Имя"/>
                                <TextInput id="edit-first-name" v-model="editingUser.firstName" type="text"
                                           class="mt-1 block w-full" required/>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="edit-last-name" value="Фамилия"/>
                                <TextInput id="edit-last-name" v-model="editingUser.lastName" type="text"
                                           class="mt-1 block w-full" required/>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="edit-email" value="Email"/>
                                <TextInput id="edit-email" v-model="editingUser.email" type="email"
                                           class="mt-1 block w-full" required/>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="edit-position" value="Должность"/>
                                <TextInput id="edit-position" v-model="editingUser.position" type="text"
                                           class="mt-1 block w-full" required/>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="edit-role" value="Роль"/>
                                <select id="edit-role" v-model="editingUser.role" class="mt-1 block w-full" required>
                                    <option value="admin">Администратор</option>
                                    <option value="manager">Менеджер</option>
                                    <option value="user">Пользователь</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </template>
                <template #footer>
                    <PrimaryButton @click="updateUser" class="mr-3">Сохранить изменения</PrimaryButton>
                    <SecondaryButton @click="closeEditUserModal">Отмена</SecondaryButton>
                </template>
            </Modal>

            <!-- Delete User Modal -->
            <Modal :show="showDeleteUserModal" @close="closeDeleteUserModal">
                <template #title>Удалить пользователя</template>
                <template #content>
                    <p class="text-sm text-gray-500">
                        Вы уверены, что хотите удалить этого пользователя? Это действие нельзя отменить.
                    </p>
                </template>
                <template #footer>
                    <DangerButton @click="deleteUser" class="mr-3">Удалить</DangerButton>
                    <SecondaryButton @click="closeDeleteUserModal">Отмена</SecondaryButton>
                </template>
            </Modal>
        </template>
    </DashboardLayout>
</template>

