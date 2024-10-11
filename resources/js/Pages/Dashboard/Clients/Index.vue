<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import BreadCrumbs from "@/Components/BreadCrumbs.vue";

const props = defineProps({
    clients: Array,
});

const searchQuery = ref('');
const isModalOpen = ref(false);
const modalMode = ref('view'); // 'view', 'edit', or 'delete'

const editableFields = ['system_id', 'username', 'first_name', 'last_name', 'email', 'phone', 'address'];

const form = useForm({
    id: '',
    system_id: '',
    tg_id: '',
    username: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    address: '',
});

const filteredClients = computed(() => {
    return props.clients.filter(client =>
        client.system_id?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        client.first_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        client.last_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        client.email?.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const openModal = (client, mode) => {
    editableFields.forEach(field => {
        form[field] = client[field];
    });
    form.id = client.id;
    form.tg_id = client.tg_id;
    modalMode.value = mode;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const updateClient = () => {
    form.put(route('dashboard.clients.update', form.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const deleteClient = () => {
    form.delete(route('dashboard.clients.destroy', form.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const breadCrumbs = [
    {
        name: 'Клиенты',
        link:  route('dashboard.clients.index')
    }
]

</script>

<template>
    <Head title="Clients" />

    <DashboardLayout>
        <template #header >
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Клиенты</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-4">
                            <TextInput
                                v-model="searchQuery"
                                type="text"
                                class="w-full"
                                placeholder="Search clients..."
                            />
                        </div>

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">ID </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Имя</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="client in filteredClients" :key="client.id">
                                <td class="px-6 py-4 whitespace-no-wrap">{{ client.system_id }}</td>
                                <td class="px-6 py-4 whitespace-no-wrap">{{ client.first_name }} {{ client.last_name }}</td>
                                <td class="px-6 py-4 whitespace-no-wrap">{{ client.email }}</td>
                                <td class="px-6 py-4 whitespace-no-wrap flex gap-4">
                                    <PrimaryButton @click.prevent="openModal(client, 'view')">
                                        Показать
                                    </PrimaryButton>
                                    <SecondaryButton @click="openModal(client, 'edit')">
                                        Редактировать
                                    </SecondaryButton>
                                    <DangerButton @click="openModal(client, 'delete')">
                                        Удалить
                                    </DangerButton>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900" v-if="modalMode === 'view'">
                    Просмотр клиента
                </h2>
                <h2 class="text-lg font-medium text-gray-900" v-else-if="modalMode === 'edit'">
                    Редактирование клиента
                </h2>
                <h2 class="text-lg font-medium text-gray-900" v-else>
                    Удаление клиента
                </h2>

                <div v-if="modalMode === 'view'" class="mt-6">
                    <div v-for="field in editableFields" :key="field" class="mb-4">
                        <strong class="text-gray-700">{{ field.charAt(0).toUpperCase() + field.slice(1) }}:</strong>
                        {{ form[field] }}
                    </div>
                </div>

                <form v-else-if="modalMode === 'edit'" @submit.prevent="updateClient" class="mt-6">
                    <div v-for="field in editableFields" :key="field" class="mb-4">
                        <InputLabel :for="field" :value="field.charAt(0).toUpperCase() + field.slice(1)" />
                        <TextInput
                            :id="field"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form[field]"
                            :disabled="field === 'tg_id'"
                        />
                        <InputError :message="form.errors[field]" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="closeModal" class="mr-3">Отмена</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Обновить
                        </PrimaryButton>
                    </div>
                </form>

                <div v-else class="mt-6">
                    <p class="mb-4">Вы уверены, что хотите удалить этого клиента?</p>
                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="closeModal" class="mr-3">Отмена</SecondaryButton>
                        <DangerButton @click="deleteClient" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Удалить
                        </DangerButton>
                    </div>
                </div>
            </div>
        </Modal>
    </DashboardLayout>
</template>
