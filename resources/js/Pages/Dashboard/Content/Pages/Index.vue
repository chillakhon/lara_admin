<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs" />
            <div class="flex justify-between items-center">
                <h1
                    class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2"
                >
                    Страницы
                </h1>
                <PrimaryButton
                    @click="openCreateModal"
                    class="transform hover:scale-105 transition-transform"
                >
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Создать страницу
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
                <div
                    class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-visible"
                >
                    <table
                        class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600"
                    >
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3">Заголовок</th>
                                <th scope="col" class="px-4 py-3">URL</th>
                                <th scope="col" class="px-4 py-3">Статус</th>
                                <th scope="col" class="px-4 py-3">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-if="pages?.data?.length"
                                v-for="page in pages.data"
                                :key="page.id"
                                class="border-b dark:border-gray-700"
                            >
                                <td class="px-4 py-3">{{ page.title }}</td>
                                <td class="px-4 py-3">{{ page.slug }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'px-2 py-1 rounded text-sm',
                                            page.is_active
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-red-100 text-red-800',
                                        ]"
                                    >
                                        {{ page.is_active ? 'Активна' : 'Неактивна' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <ContextMenu
                                        :items="menuItems"
                                        @item-click="(action) => handleMenuAction(action, page)"
                                    />
                                </td>
                            </tr>
                            <tr v-else>
                                <td colspan="4" class="px-4 py-3 text-center">
                                    Страницы не найдены
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination
                        v-if="pages?.data?.length"
                        :data="pages"
                        @page-changed="handlePageChange"
                    />
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal" max-width="2xl">
            <template #title>
                {{ editingPage ? 'Редактировать страницу' : 'Создать страницу' }}
            </template>
            <template #content>
                <form @submit.prevent="submitForm" class="space-y-6">
                    <TextInput
                        v-model="form.title"
                        label="Заголовок"
                        :error="form.errors.title"
                        required
                    />

                    <TextInput v-model="form.slug" label="URL (slug)" :error="form.errors.slug" />

                    <TextInput
                        v-model="form.meta_title"
                        label="META заголовок"
                        :error="form.errors.meta_title"
                    />

                    <TextInput
                        v-model="form.meta_description"
                        label="META описание"
                        :error="form.errors.meta_description"
                    />

                    <div class="flex items-center gap-2">
                        <Checkbox v-model:checked="form.is_active" :error="form.errors.is_active" />
                        <label>Активна</label>
                    </div>
                </form>
            </template>
            <template #footer>
                <PrimaryButton @click="submitForm">
                    {{ editingPage ? 'Сохранить' : 'Создать' }}
                </PrimaryButton>
                <PrimaryButton type="red" @click="closeModal" class="ml-2"> Отмена </PrimaryButton>
            </template>
        </Modal>
    </DashboardLayout>
</template>

<script setup>
    import { ref } from 'vue';
    import { useForm, router } from '@inertiajs/vue3';
    import DashboardLayout from '@/Layouts/DashboardLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import TextInput from '@/Components/TextInput.vue';
    import Checkbox from '@/Components/Checkbox.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import Pagination from '@/Components/Pagination.vue';
    import ContextMenu from '@/Components/ContextMenu.vue';
    //import { PlusIcon } from '@heroicons/vue/24/outline';

    const props = defineProps({
        pages: Object, // Пагинированные данные
    });

    const breadCrumbs = [
        { name: 'Контент', link: route('dashboard.content.index') },
        { name: 'Страницы', link: route('dashboard.content.pages.index') },
    ];

    const showModal = ref(false);
    const editingPage = ref(null);

    const form = useForm({
        title: '',
        slug: '',
        meta_title: '',
        meta_description: '',
        is_active: true,
    });

    const menuItems = [
        { text: 'Редактировать', action: 'edit' },
        { text: 'Управление контентом', action: 'content' },
        { text: 'Удалить', action: 'delete', isDangerous: true },
    ];

    const handleMenuAction = (action, page) => {
        console.log(action);
        if (action.action === 'edit') {
            editingPage.value = page;
            form.reset();
            form.fill(page);
            showModal.value = true;
        } else if (action.action === 'content') {
            router.visit(route('dashboard.content.pages.content', page.id));
        } else if (action.action === 'delete') {
            if (confirm('Вы уверены, что хотите удалить эту страницу?')) {
                router.delete(route('dashboard.content.pages.destroy', page.id));
            }
        }
    };

    const handlePageChange = (page) => {
        router.get(
            route('dashboard.content.pages.index', { page: page }),
            {},
            { preserveState: true, preserveScroll: true }
        );
    };

    const submitForm = () => {
        if (editingPage.value) {
            form.put(route('dashboard.content.pages.update', editingPage.value.id), {
                onSuccess: () => {
                    closeModal();
                    // Обновляем данные после успешного сохранения
                    router.reload({ preserveScroll: true });
                },
                onError: () => {
                    console.error('Ошибка при сохранении');
                },
            });
        } else {
            form.post(route('dashboard.content.pages.store'), {
                onSuccess: () => {
                    closeModal();
                    // Обновляем данные после успешного создания
                    router.reload({ preserveScroll: true });
                },
                onError: () => {
                    console.error('Ошибка при создании');
                },
            });
        }
    };

    const closeModal = () => {
        showModal.value = false;
        editingPage.value = null;
        form.reset();
    };

    const openCreateModal = () => {
        editingPage.value = null;
        form.reset();
        showModal.value = true;
    };
</script>
