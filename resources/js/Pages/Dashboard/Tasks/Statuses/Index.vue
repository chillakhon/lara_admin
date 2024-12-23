<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    statuses: Array
});

const breadCrumbs = [
    { name: 'Задачи', link: route('dashboard.tasks.index') },
    { name: 'Статусы', link: route('dashboard.task-statuses.index') }
];

const showModal = ref(false);
const editingStatus = ref(null);

const form = useForm({
    name: '',
    color: '#6B7280',
    order: 0,
    is_default: false
});

const openCreateModal = () => {
    editingStatus.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (status) => {
    editingStatus.value = status;
    form.name = status.name;
    form.color = status.color;
    form.order = status.order;
    form.is_default = status.is_default;
    showModal.value = true;
};

const submitForm = () => {
    if (editingStatus.value) {
        form.put(route('dashboard.task-statuses.update', editingStatus.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('dashboard.task-statuses.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    editingStatus.value = null;
    form.reset();
};

const deleteStatus = (status) => {
    if (confirm('Вы уверены? Это действие нельзя отменить.')) {
        router.delete(route('dashboard.task-statuses.destroy', status.id));
    }
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Статусы задач
                </h1>
                <PrimaryButton @click="openCreateModal">
                    <template #icon-left>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </template>
                    Добавить статус
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Цвет
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Название
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Порядок
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            По умолчанию
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Действия</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="status in statuses" :key="status.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div 
                                                    class="w-6 h-6 rounded-full"
                                                    :style="{ backgroundColor: status.color }"
                                                ></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ status.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ status.order }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="status.is_default" class="text-green-600">Да</span>
                                            <span v-else>Нет</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button 
                                                @click="openEditModal(status)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                                            >
                                                Редактировать
                                            </button>
                                            <button 
                                                @click="deleteStatus(status)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Удалить
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно создания/редактирования -->
        <Modal :show="showModal" @close="closeModal">
            <template #title>
                {{ editingStatus ? 'Редактирование статуса' : 'Создание статуса' }}
            </template>
            <template #content>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <TextInput
                        v-model="form.name"
                        label="Название"
                        :error="form.errors.name"
                        required
                    />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Цвет
                        </label>
                        <input
                            type="color"
                            v-model="form.color"
                            class="mt-1 block w-full h-10"
                        />
                        <p v-if="form.errors.color" class="mt-2 text-sm text-red-600">
                            {{ form.errors.color }}
                        </p>
                    </div>

                    <TextInput
                        v-model="form.order"
                        type="number"
                        label="Порядок"
                        :error="form.errors.order"
                    />

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            v-model="form.is_default"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                            Статус по умолчанию
                        </label>
                    </div>
                </form>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <PrimaryButton @click="closeModal" type="alternative">
                        Отмена
                    </PrimaryButton>
                    <PrimaryButton @click="submitForm" :loading="form.processing">
                        {{ editingStatus ? 'Сохранить' : 'Создать' }}
                    </PrimaryButton>
                </div>
            </template>
        </Modal>
    </DashboardLayout>
</template> 