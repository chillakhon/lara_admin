<script setup>
    import { ref, computed } from 'vue';
    import { useForm } from '@inertiajs/vue3';
    import DashboardLayout from '@/Layouts/DashboardLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import BreadCrumbs from '@/Components/BreadCrumbs.vue';
    import ContextMenu from '@/Components/ContextMenu.vue';
    import SelectInput from '@/Components/SelectInput.vue';

    const props = defineProps({
        levels: Object,
    });

    const breadCrumbs = [
        { name: 'Панель управления', link: route('dashboard') },
        { name: 'Уровни клиентов', link: route('dashboard.client-levels.index') },
    ];

    const showModal = ref(false);
    const modalMode = ref('create'); // create, edit, delete
    const editingLevel = ref(null);

    const form = useForm({
        name: '',
        threshold: '',
        calculation_type: '',
        discount_percentage: '',
        description: '',
    });

    const calculationTypes = [
        { value: 'order_sum', label: 'По общей сумме покупок' },
        { value: 'order_count', label: 'По количеству заказов' },
    ];

    const modalTitle = computed(() => {
        switch (modalMode.value) {
            case 'create':
                return 'Создать уровень клиента';
            case 'edit':
                return 'Редактировать уровень клиента';
            case 'delete':
                return 'Удалить уровень клиента';
            default:
                return '';
        }
    });

    const openCreateModal = () => {
        modalMode.value = 'create';
        editingLevel.value = null;
        form.reset();
        showModal.value = true;
    };

    const openEditModal = (level) => {
        modalMode.value = 'edit';
        editingLevel.value = level;
        form.name = level.name;
        form.threshold = level.threshold;
        form.calculation_type = level.calculation_type;
        form.discount_percentage = level.discount_percentage;
        form.description = level.description;
        showModal.value = true;
    };

    const openDeleteModal = (level) => {
        modalMode.value = 'delete';
        editingLevel.value = level;
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
        editingLevel.value = null;
        form.reset();
    };

    const submitForm = () => {
        if (modalMode.value === 'create') {
            form.post(route('dashboard.client-levels.store'), {
                onSuccess: () => closeModal(),
            });
        } else if (modalMode.value === 'edit') {
            form.put(route('dashboard.client-levels.update', editingLevel.value.id), {
                onSuccess: () => closeModal(),
            });
        } else if (modalMode.value === 'delete') {
            form.delete(route('dashboard.client-levels.destroy', editingLevel.value.id), {
                onSuccess: () => closeModal(),
            });
        }
    };
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs" />
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                    Уровни клиентов
                </h1>
                <PrimaryButton @click="openCreateModal">
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Добавить уровень
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                    >
                                        Название
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                    >
                                        Порог
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                    >
                                        Тип расчета
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                    >
                                        Скидка (%)
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                    >
                                        Описание
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Действия</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="bg-white divide-y divide-gray-200 dark:divide-gray-700 dark:bg-gray-800"
                            >
                                <tr v-for="level in levels" :key="level.id">
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200"
                                    >
                                        {{ level.name }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200"
                                    >
                                        {{ level.threshold }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200"
                                    >
                                        {{
                                            level.calculation_type === 'order_sum'
                                                ? 'По общей сумме покупок'
                                                : 'По количеству заказов'
                                        }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200"
                                    >
                                        {{ level.discount_percentage }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200"
                                    >
                                        {{ level.description }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                    >
                                        <ContextMenu>
                                            <template #trigger>
                                                <button
                                                    class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
                                                >
                                                    <svg
                                                        class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                    >
                                                        <path
                                                            d="M12 13C12.5523 13 13 12.5523 13 12C13 11.4477 12.5523 11 12 11C11.4477 11 11 11.4477 11 12C11 12.5523 11.4477 13 12 13Z"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                        />
                                                        <path
                                                            d="M19 13C19.5523 13 20 12.5523 20 12C20 11.4477 19.5523 11 19 11C18.4477 11 18 11.4477 18 12C18 12.5523 18.4477 13 19 13Z"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                        />
                                                        <path
                                                            d="M5 13C5.55228 13 6 12.5523 6 12C6 11.4477 5.55228 11 5 11C4.44772 11 4 11.4477 4 12C4 12.5523 4.44772 13 5 13Z"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                        />
                                                    </svg>
                                                </button>
                                            </template>

                                            <template #content>
                                                <button
                                                    @click="openEditModal(level)"
                                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                >
                                                    Редактировать
                                                </button>
                                                <button
                                                    @click="openDeleteModal(level)"
                                                    class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                >
                                                    Удалить
                                                </button>
                                            </template>
                                        </ContextMenu>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <template #title>
                {{ modalTitle }}
            </template>

            <template #content>
                <div v-if="modalMode !== 'delete'" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Название" />
                        <TextInput
                            id="name"
                            type="text"
                            v-model="form.name"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="threshold" value="Порог" />
                        <TextInput
                            id="threshold"
                            type="number"
                            v-model="form.threshold"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.threshold" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="calculation_type" value="Тип расчета" />
                        <SelectInput
                            id="calculation_type"
                            v-model="form.calculation_type"
                            :options="calculationTypes"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.calculation_type" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="discount_percentage" value="Процент скидки" />
                        <TextInput
                            id="discount_percentage"
                            type="number"
                            v-model="form.discount_percentage"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.discount_percentage" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Описание" />
                        <TextInput
                            id="description"
                            type="text"
                            v-model="form.description"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>
                </div>
                <div v-else>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Вы уверены, что хотите удалить этот уровень клиента? Это действие нельзя
                        отменить.
                    </p>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <PrimaryButton @click="closeModal" type="button"> Отмена </PrimaryButton>
                    <PrimaryButton
                        @click="submitForm"
                        :disabled="form.processing"
                        :class="{ 'opacity-25': form.processing }"
                    >
                        {{
                            modalMode === 'delete'
                                ? 'Удалить'
                                : modalMode === 'create'
                                ? 'Создать'
                                : 'Сохранить'
                        }}
                    </PrimaryButton>
                </div>
            </template>
        </Modal>
    </DashboardLayout>
</template>
