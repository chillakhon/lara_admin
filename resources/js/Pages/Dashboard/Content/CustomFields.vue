<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Кастомные поля контента</h1>
                <PrimaryButton @click="() => { showModal = true }" class="transform hover:scale-105 transition-transform">
                    Добавить поле
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
                <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-visible">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3">Название поля</th>
                                <th scope="col" class="px-4 py-3">Значение</th>
                                <th scope="col" class="px-4 py-3">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="field in fields.data" :key="field.id" class="border-b dark:border-gray-700">
                                <td class="px-4 py-3">{{ field.field_name }}</td>
                                <td class="px-4 py-3">{{ field.field_value }}</td>
                                <td class="px-4 py-3">
                                    <ContextMenu 
                                        :items="menuItems" 
                                        @action="(action) => handleMenuAction(action, field)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :data="fields" @page-changed="handlePageChange"/>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal" max-width="2xl">
            <template #title>
                {{ editingField ? 'Редактировать поле' : 'Добавить поле' }}
            </template>
            <template #content>
                <form @submit.prevent="submitForm" class="space-y-6">
                    <TextInput v-model="form.field_name" label="Название поля" :error="form.errors.field_name" required/>
                    <TextInput v-model="form.field_value" label="Значение" :error="form.errors.field_value" required/>
                </form>
            </template>
            <template #footer>
                <PrimaryButton @click="submitForm">{{ editingField ? 'Сохранить' : 'Создать' }}</PrimaryButton>
                <PrimaryButton type="red" @click="closeModal" class="ml-2">Отмена</PrimaryButton>
            </template>
        </Modal>
    </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Modal from '@/Components/Modal.vue';
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import TextInput from "@/Components/TextInput.vue";
import Pagination from "@/Components/Pagination.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ContextMenu from "@/Components/ContextMenu.vue";

const props = defineProps({
    fields: Object, // Пагинированные данные от сервера
});

const breadCrumbs = [
    { name: 'Контент', link: route('dashboard.content.index') },
    { name: 'Кастомные поля', link: route('dashboard.content.custom-fields') }
];

const showModal = ref(false);
const editingField = ref(null);

const menuItems = [
    {text: 'Редактировать', action: 'edit'},
    {text: 'Удалить', action: 'delete', isDangerous: true}
];

const form = useForm({
    field_name: '',
    field_value: ''
});

const handlePageChange = (page) => {
    router.get(
        route('dashboard.content.custom-fields', { page: page }),
        {},
        { preserveState: true, preserveScroll: true }
    );
};

const handleMenuAction = (action, field) => {
    if (action === 'edit') {
        editField(field);
    } else if (action === 'delete') {
        deleteField(field);
    }
};

const editField = (field) => {
    editingField.value = field;
    form.field_name = field.field_name;
    form.field_value = field.field_value;
    showModal.value = true;
};

const deleteField = (field) => {
    if (confirm('Вы уверены, что хотите удалить это поле?')) {
        form.delete(route('dashboard.content.fields.destroy', field.id));
    }
};

const submitForm = () => {
    if (editingField.value) {
        form.put(route('dashboard.content.fields.update', editingField.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('dashboard.content.fields.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    editingField.value = null;
    form.reset();
};
</script>