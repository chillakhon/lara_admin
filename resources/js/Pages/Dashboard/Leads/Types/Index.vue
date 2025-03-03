<template>
    <DashboardLayout>
        <template #header>
            <div class="px-4 py-6 sm:px-6 lg:px-8">
                <BreadCrumbs :breadcrumbs="breadCrumbs"/>
                <div class="flex justify-between items-center mt-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Типы лидов
                        </h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Управление типами заявок и их обязательными полями
                        </p>
                    </div>
                    <PrimaryButton @click="openCreateModal" class="gap-2">
                        <PlusIcon class="w-5 h-5"/>
                        Добавить тип
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Таблица -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Название</th>
                                    <th scope="col" class="px-6 py-3">Код</th>
                                    <th scope="col" class="px-6 py-3">Описание</th>
                                    <th scope="col" class="px-6 py-3">Обязательные поля</th>
                                    <th scope="col" class="px-6 py-3">Статус</th>
                                    <th scope="col" class="px-6 py-3">
                                        <span class="sr-only">Действия</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="type in leadTypes" :key="type.id" 
                                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ type.name }}
                                    </th>
                                    <td class="px-6 py-4">
                                        <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                            {{ type.code }}
                                        </code>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs truncate">
                                        {{ type.description || '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <Badge 
                                                v-for="field in type.required_fields" 
                                                :key="field"
                                                :type="getBadgeType(field)"
                                            >
                                                {{ getFieldLabel(field) }}
                                            </Badge>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Badge :type="type.is_active ? 'success' : 'danger'">
                                            {{ type.is_active ? 'Активен' : 'Неактивен' }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <button @click="editType(type)"
                                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                                            >
                                                Редактировать
                                            </button>
                                            <button @click="deleteType(type)"
                                                class="font-medium text-red-600 dark:text-red-500 hover:underline"
                                            >
                                                Удалить
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно создания/редактирования -->
        <Modal :show="showModal" @close="closeModal">
            <template #title>
                {{ editingType ? 'Редактировать тип лида' : 'Создать тип лида' }}
            </template>

            <template #content>
                <form @submit.prevent="submitForm" class="space-y-6">
                    <div>
                        <InputLabel for="name" value="Название" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            placeholder="Например: Обратный звонок"
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="code" value="Код" />
                        <TextInput
                            id="code"
                            v-model="form.code"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            :disabled="!!editingType"
                            placeholder="Например: callback"
                        />
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Уникальный идентификатор типа лида (только латинские буквы, цифры и дефис)
                        </p>
                        <InputError :message="form.errors.code" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Описание" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm"
                            rows="3"
                            placeholder="Опишите назначение данного типа лида"
                        />
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Обязательные поля" />
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="{ 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': form.required_fields.includes('name') }">
                                <Checkbox v-model="form.required_fields" value="name" class="mr-3"/>
                                <div>
                                    <div class="font-medium">Имя</div>
                                    <div class="text-sm text-gray-500">ФИО клиента</div>
                                </div>
                            </label>
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="{ 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': form.required_fields.includes('phone') }">
                                <Checkbox v-model="form.required_fields" value="phone" class="mr-3"/>
                                <div>
                                    <div class="font-medium">Телефон</div>
                                    <div class="text-sm text-gray-500">Контактный номер</div>
                                </div>
                            </label>
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="{ 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': form.required_fields.includes('email') }">
                                <Checkbox v-model="form.required_fields" value="email" class="mr-3"/>
                                <div>
                                    <div class="font-medium">Email</div>
                                    <div class="text-sm text-gray-500">Электронная почта</div>
                                </div>
                            </label>
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="{ 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': form.required_fields.includes('message') }">
                                <Checkbox v-model="form.required_fields" value="message" class="mr-3"/>
                                <div>
                                    <div class="font-medium">Сообщение</div>
                                    <div class="text-sm text-gray-500">Текст заявки</div>
                                </div>
                            </label>
                        </div>
                        <InputError :message="form.errors.required_fields" class="mt-2" />
                    </div>

                    <div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">Активен</span>
                        </label>
                    </div>
                </form>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal" type="button" class="mr-2">
                    Отмена
                </SecondaryButton>
                <PrimaryButton @click="submitForm" :disabled="form.processing">
                    {{ editingType ? 'Сохранить' : 'Создать' }}
                </PrimaryButton>
            </template>
        </Modal>
    </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import Badge from '@/Components/Badge.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    leadTypes: Array
});

const breadCrumbs = [
    { name: 'Панель управления', link: route('dashboard') },
    { name: 'Типы лидов', link: route('dashboard.lead-types.index') }
];

const showModal = ref(false);
const editingType = ref(null);

const form = useForm({
    name: '',
    code: '',
    description: '',
    required_fields: [],
    is_active: true
});

const getFieldLabel = (field) => {
    const labels = {
        name: 'Имя',
        phone: 'Телефон',
        email: 'Email',
        message: 'Сообщение'
    };
    return labels[field] || field;
};

const getBadgeType = (field) => {
    const types = {
        name: 'info',
        phone: 'warning',
        email: 'success',
        message: 'purple'
    };
    return types[field] || 'default';
};

const openCreateModal = () => {
    editingType.value = null;
    form.reset();
    showModal.value = true;
};

const editType = (type) => {
    editingType.value = type;
    form.name = type.name;
    form.code = type.code;
    form.description = type.description;
    form.required_fields = type.required_fields;
    form.is_active = type.is_active;
    showModal.value = true;
};

const deleteType = (type) => {
    if (confirm(`Вы уверены, что хотите удалить тип лида "${type.name}"?`)) {
        form.delete(route('dashboard.lead-types.destroy', type.id));
    }
};

const submitForm = () => {
    if (editingType.value) {
        form.put(route('dashboard.lead-types.update', editingType.value.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('dashboard.lead-types.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    editingType.value = null;
    form.reset();
    form.clearErrors();
};
</script> 