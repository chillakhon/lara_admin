<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Страницы
                </h1>
                <PrimaryButton @click="openCreateModal">
                    <PlusIcon class="w-5 h-5 mr-2"/>
                    Добавить страницу
                </PrimaryButton>
            </div>
        </template>

        <!-- Список страниц -->
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
                <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-visible">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">Заголовок</th>
                                <th scope="col" class="px-6 py-3">URL</th>
                                <th scope="col" class="px-6 py-3">Статус</th>
                                <th scope="col" class="px-6 py-3">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="page in pages.data" :key="page.id" 
                                class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4">{{ page.title }}</td>
                                <td class="px-6 py-4">{{ page.slug }}</td>
                                <td class="px-6 py-4">
                                    <Badge :type="page.is_active ? 'success' : 'danger'">
                                        {{ page.is_active ? 'Активна' : 'Неактивна' }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4">
                                    <ContextMenu>
                                        <template #items>
                                            <button @click="editPage(page)" 
                                                    class="block w-full px-4 py-2 text-left hover:bg-gray-100">
                                                Редактировать
                                            </button>
                                            <button @click="deletePage(page)" 
                                                    class="block w-full px-4 py-2 text-left text-red-600 hover:bg-gray-100">
                                                Удалить
                                            </button>
                                        </template>
                                    </ContextMenu>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :data="pages" />
                </div>
            </div>
        </div>

        <!-- Модальное окно создания/редактирования -->
        <Modal :show="showModal" @close="closeModal" :maxWidth="'4xl'">
            <template #title>
                {{ editingPage ? 'Редактирование страницы' : 'Создание страницы' }}
            </template>
            
            <template #content>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Основные поля страницы -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="title" value="Заголовок" />
                            <TextInput id="title" v-model="form.title" type="text" required />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div>
                            <InputLabel for="slug" value="URL" />
                            <TextInput id="slug" v-model="form.slug" type="text" required />
                            <InputError :message="form.errors.slug" />
                        </div>

                        <div>
                            <InputLabel for="meta_title" value="Meta Title" />
                            <TextInput id="meta_title" v-model="form.meta_title" type="text" />
                            <InputError :message="form.errors.meta_title" />
                        </div>

                        <div>
                            <InputLabel for="meta_description" value="Meta Description" />
                            <textarea
                                id="meta_description"
                                v-model="form.meta_description"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <InputError :message="form.errors.meta_description" />
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Checkbox id="is_active" v-model:checked="form.is_active" />
                        <InputLabel for="is_active" value="Активна" />
                    </div>

                    <!-- Поля контента -->
                    <div class="border-t pt-4 mt-4">
                        <FieldRenderer
                            :fields="fields"
                            v-model="form.fields"
                            :errors="form.errors"
                        />
                    </div>

                    <div class="flex justify-end space-x-2">
                        <SecondaryButton @click="closeModal">Отмена</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="form.processing">
                            {{ editingPage ? 'Сохранить' : 'Создать' }}
                        </PrimaryButton>
                    </div>
                </form>
            </template>
        </Modal>
    </DashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Pagination from '@/Components/Pagination.vue';
import FieldRenderer from '@/Components/Content/FieldRenderer.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    pages: Object,
    fields: Array
});

const breadCrumbs = [
    { name: 'Главная', href: route('dashboard.index') },
    { name: 'Страницы', href: route('dashboard.content.pages.index') }
];

const showModal = ref(false);
const editingPage = ref(null);

const form = useForm({
    title: '',
    slug: '',
    meta_title: '',
    meta_description: '',
    is_active: true,
    fields: {}
});

const openCreateModal = () => {
    editingPage.value = null;
    form.reset();
    showModal.value = true;
};

const editPage = (page) => {
    editingPage.value = page;
    form.title = page.title;
    form.slug = page.slug;
    form.meta_title = page.meta_title;
    form.meta_description = page.meta_description;
    form.is_active = page.is_active;
    form.fields = page.field_values.reduce((acc, value) => {
        acc[value.field.key] = value.value;
        return acc;
    }, {});
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingPage.value = null;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    if (editingPage.value) {
        form.put(route('dashboard.content.pages.update', editingPage.value.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('dashboard.content.pages.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const deletePage = (page) => {
    if (confirm('Вы уверены, что хотите удалить эту страницу?')) {
        router.delete(route('dashboard.content.pages.destroy', page.id));
    }
};
</script>

<style>
.drag-handle {
    cursor: move;
    cursor: -webkit-grabbing;
}
</style>
