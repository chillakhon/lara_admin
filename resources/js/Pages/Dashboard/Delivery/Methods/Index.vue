<script setup>
import { ref } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Modal from '@/Components/Modal.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    methods: Array
});

const showModal = ref(false);
const editingMethod = ref(null);

const form = useForm({
    name: '',
    code: '',
    description: '',
    provider_class: '',
    settings: {},
    is_active: true
});

const breadCrumbs = [
    { name: 'Панель управления', link: route('dashboard') },
    { name: 'Методы доставки', link: route('dashboard.delivery.methods.index') }
];

const providerClasses = [
    { value: 'App\\Services\\Delivery\\CdekDeliveryService', label: 'СДЭК' },
    { value: 'App\\Services\\Delivery\\BoxberryDeliveryService', label: 'Boxberry' },
    { value: 'App\\Services\\Delivery\\RussianPostDeliveryService', label: 'Почта России' }
];

function openCreateModal() {
    editingMethod.value = null;
    form.reset();
    showModal.value = true;
}

function openEditModal(method) {
    editingMethod.value = method;
    form.name = method.name;
    form.code = method.code;
    form.description = method.description;
    form.provider_class = method.provider_class;
    form.settings = method.settings;
    form.is_active = method.is_active;
    showModal.value = true;
}

function handleSubmit() {
    if (editingMethod.value) {
        form.put(route('dashboard.delivery.methods.update', editingMethod.value.id), {
            onSuccess: () => showModal.value = false
        });
    } else {
        form.post(route('dashboard.delivery.methods.store'), {
            onSuccess: () => showModal.value = false
        });
    }
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs" />
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Методы доставки
                </h1>
                <PrimaryButton @click="openCreateModal">
                    Добавить метод
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Таблица методов доставки -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Название
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Код
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Провайдер
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Статус
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Действия
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="method in methods" :key="method.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ method.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ method.code }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ providerClasses.find(p => p.value === method.provider_class)?.label }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="{
                                        'px-2 py-1 text-xs rounded-full': true,
                                        'bg-green-100 text-green-800': method.is_active,
                                        'bg-red-100 text-red-800': !method.is_active
                                    }">
                                        {{ method.is_active ? 'Активен' : 'Неактивен' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <PrimaryButton @click="openEditModal(method)" class="mr-2">
                                        Редактировать
                                    </PrimaryButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Модальное окно создания/редактирования -->
        <Modal :show="showModal" @close="showModal = false">
            <template #title>
                {{ editingMethod ? 'Редактирование метода доставки' : 'Создание метода доставки' }}
            </template>

            <template #content>
                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Название
                        </label>
                        <TextInput
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Код
                        </label>
                        <TextInput
                            v-model="form.code"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            :disabled="!!editingMethod"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Описание
                        </label>
                        <textarea
                            v-model="form.description"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            rows="3"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Провайдер
                        </label>
                        <select
                            v-model="form.provider_class"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            required
                        >
                            <option v-for="provider in providerClasses" 
                                    :key="provider.value" 
                                    :value="provider.value">
                                {{ provider.label }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            v-model="form.is_active"
                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                        <label class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                            Активен
                        </label>
                    </div>
                </form>
            </template>

            <template #footer>
                <div class="flex justify-end space-x-2">
                    <PrimaryButton @click="showModal = false" type="button" class="bg-gray-500">
                        Отмена
                    </PrimaryButton>
                    <PrimaryButton @click="handleSubmit" :disabled="form.processing">
                        {{ editingMethod ? 'Сохранить' : 'Создать' }}
                    </PrimaryButton>
                </div>
            </template>
        </Modal>
    </DashboardLayout>
</template> 