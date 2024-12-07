<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    Группы полей
                </h1>
                <PrimaryButton @click="openCreateModal" class="transform hover:scale-105 transition-transform">
                    <PlusIcon class="w-5 h-5 mr-2"/>
                    Добавить группу
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
                <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-visible">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3">Название</th>
                                <th scope="col" class="px-4 py-3">Количество полей</th>
                                <th scope="col" class="px-4 py-3">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="group in fieldGroups.data" :key="group.id" class="border-b dark:border-gray-700">
                                <td class="px-4 py-3">{{ group.name }}</td>
                                <td class="px-4 py-3">{{ group.fields?.length || 0 }}</td>
                                <td class="px-4 py-3">
                                    <ContextMenu 
                                        :items="menuItems" 
                                        @action="(action) => handleMenuAction(action, group)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :data="fieldGroups" @page-changed="handlePageChange"/>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal" max-width="2xl">
            <template #title>
                {{ editingGroup ? 'Редактировать группу' : 'Добавить группу' }}
            </template>
            <template #content>
                <form @submit.prevent="submitForm" class="space-y-6">
                    <TextInput 
                        v-model="form.name" 
                        label="Название группы" 
                        :error="form.errors.name" 
                        required
                    />
                    
                    <div class="space-y-4">
                        <h3 class="font-medium">Поля группы</h3>
                        <div v-for="(field, index) in form.fields" :key="index" class="space-y-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex justify-between">
                                <h4 class="font-medium">Поле #{{ index + 1 }}</h4>
                                <button @click.prevent="removeField(index)" class="text-red-600">
                                    Удалить
                                </button>
                            </div>
                            
                            <SelectInput
                                v-model="field.field_type_id"
                                :options="availableFieldTypes"
                                label="Тип поля"
                                :error="form.errors[`fields.${index}.field_type_id`]"
                                required
                            />
                            
                            <TextInput 
                                v-model="field.name"
                                label="Название поля"
                                :error="form.errors[`fields.${index}.name`]"
                                required
                            />
                            
                            <TextInput 
                                v-model="field.key"
                                label="Ключ поля"
                                :error="form.errors[`fields.${index}.key`]"
                                required
                            />
                            
                            <div class="flex items-center gap-2">
                                <input 
                                    type="checkbox" 
                                    v-model="field.required" 
                                    :id="'required-' + index"
                                />
                                <label :for="'required-' + index">Обязательное поле</label>
                            </div>
                        </div>
                        
                        <button 
                            @click.prevent="addField" 
                            class="w-full py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 hover:text-gray-700 dark:hover:text-gray-400"
                        >
                            Добавить поле
                        </button>
                    </div>
                </form>
            </template>
            <template #footer>
                <PrimaryButton @click="submitForm">
                    {{ editingGroup ? 'Сохранить' : 'Создать' }}
                </PrimaryButton>
                <PrimaryButton type="red" @click="closeModal" class="ml-2">
                    Отмена
                </PrimaryButton>
            </template>
        </Modal>
    </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import ContextMenu from '@/Components/ContextMenu.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    fieldGroups: Object,
    fieldTypes: Array,
});

const breadCrumbs = [
    { name: 'Контент', link: route('dashboard.content.index') },
    { name: 'Группы полей', link: route('dashboard.content.field-groups') }
];

const showModal = ref(false);
const editingGroup = ref(null);

const availableFieldTypes = computed(() => 
    props.fieldTypes.map(type => ({
        id: type.id,
        name: type.name
    }))
);

const form = useForm({
    name: '',
    fields: []
});

const menuItems = [
    {text: 'Редактировать', action: 'edit'},
    {text: 'Удалить', action: 'delete', isDangerous: true}
];

const handleMenuAction = (action, group) => {
    if (action === 'edit') {
        editGroup(group);
    } else if (action === 'delete') {
        deleteGroup(group);
    }
};

const handlePageChange = (page) => {
    router.get(
        route('dashboard.content.field-groups', { page }),
        {},
        { preserveState: true, preserveScroll: true }
    );
};

const addField = () => {
    form.fields.push({
        field_type_id: '',
        name: '',
        key: '',
        required: false
    });
};

const removeField = (index) => {
    form.fields.splice(index, 1);
};

const submitForm = () => {
    if (editingGroup.value) {
        form.put(route('dashboard.content.field-groups.update', editingGroup.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('dashboard.content.field-groups.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    editingGroup.value = null;
    form.reset();
};

const editGroup = (group) => {
    editingGroup.value = group;
    form.name = group.name;
    form.fields = group.fields;
    showModal.value = true;
};

const deleteGroup = (group) => {
    if (confirm('Вы уверены, что хотите удалить эту группу полей?')) {
        form.delete(route('dashboard.content.field-groups.destroy', group.id));
    }
};

const openCreateModal = () => {
    editingGroup.value = null;
    form.reset();
    showModal.value = true;
};
</script>