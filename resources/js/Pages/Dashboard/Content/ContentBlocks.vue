<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    Блоки контента
                </h1>
                <PrimaryButton @click="openCreateModal" class="transform hover:scale-105 transition-transform">
                    <PlusIcon class="w-5 h-5 mr-2"/>
                    Добавить блок
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
                <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-visible">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3">Название блока</th>
                                <th scope="col" class="px-4 py-3">Группа полей</th>
                                <th scope="col" class="px-4 py-3">Описание</th>
                                <th scope="col" class="px-4 py-3">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="block in blocks.data" :key="block.id" class="border-b dark:border-gray-700">
                                <td class="px-4 py-3">{{ block.name }}</td>
                                <td class="px-4 py-3">{{ block.fieldGroup?.name }}</td>
                                <td class="px-4 py-3">{{ block.description }}</td>
                                <td class="px-4 py-3">
                                    <ContextMenu 
                                        :items="menuItems" 
                                        @action="(action) => handleMenuAction(action, block)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :data="blocks" @page-changed="handlePageChange"/>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal" max-width="2xl">
            <template #title>
                {{ editingBlock ? 'Редактировать блок' : 'Добавить блок' }}
            </template>
            <template #content>
                <form @submit.prevent="submitForm" class="space-y-6">
                    <TextInput 
                        v-model="form.name" 
                        label="Название блока" 
                        :error="form.errors.name" 
                        required
                    />
                    
                    <TextInput 
                        v-model="form.key" 
                        label="Ключ блока" 
                        :error="form.errors.key" 
                        required
                    />
                    
                    <SelectInput
                        v-model="form.field_group_id"
                        :options="fieldGroups"
                        label="Группа полей"
                        :error="form.errors.field_group_id"
                        required
                    />
                    
                    <TextArea
                        v-model="form.description"
                        label="Описание"
                        :error="form.errors.description"
                    />
                </form>
            </template>
            <template #footer>
                <PrimaryButton @click="submitForm">
                    {{ editingBlock ? 'Сохранить' : 'Создать' }}
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
import TextArea from '@/Components/TextArea.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import ContextMenu from '@/Components/ContextMenu.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    blocks: Object,
    fieldGroups: Array,
});

const breadCrumbs = [
    { name: 'Контент', link: route('dashboard.content.index') },
    { name: 'Блоки контента', link: route('dashboard.content.blocks') }
];

const showModal = ref(false);
const editingBlock = ref(null);

const form = useForm({
    name: '',
    key: '',
    field_group_id: '',
    description: ''
});

const menuItems = [
    {text: 'Редактировать', action: 'edit'},
    {text: 'Удалить', action: 'delete', isDangerous: true}
];

const handleMenuAction = (action, block) => {
    if (action === 'edit') {
        editBlock(block);
    } else if (action === 'delete') {
        deleteBlock(block);
    }
};

const handlePageChange = (page) => {
    router.get(
        route('dashboard.content.blocks', { page }),
        {},
        { preserveState: true, preserveScroll: true }
    );
};

const submitForm = () => {
    if (editingBlock.value) {
        form.put(route('dashboard.content.blocks.update', editingBlock.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('dashboard.content.blocks.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    editingBlock.value = null;
    form.reset();
};

const editBlock = (block) => {
    editingBlock.value = block;
    form.name = block.name;
    form.key = block.key;
    form.field_group_id = block.field_group_id;
    form.description = block.description;
    showModal.value = true;
};

const deleteBlock = (block) => {
    if (confirm('Вы уверены, что хотите удалить этот блок?')) {
        form.delete(route('dashboard.content.blocks.destroy', block.id));
    }
};

const openCreateModal = () => {
    editingBlock.value = null;
    form.reset();
    showModal.value = true;
};
</script>