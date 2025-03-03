<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Badge from '@/Components/Badge.vue';
import Modal from '@/Components/Modal.vue';
import DiscountForm from './DiscountForm.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';
import { Table, TableHead, TableBody, TableRow, TableHeader, TableCell } from '@/Components/Table';

const props = defineProps({
    discounts: Object,
    products: Object,
    productVariants: Object
});

const showModal = ref(false);
const editingDiscount = ref(null);
const search = ref('');
const showDeleteModal = ref(false);
const discountToDelete = ref(null);
const formRef = ref(null);

const breadCrumbs = [
    { name: 'Маркетинг', link: route('dashboard') },
    { name: 'Скидки', link: route('dashboard.discounts.index') }
];

const discountTypes = {
    'percentage': 'Процент',
    'fixed': 'Фиксированная сумма',
    'special_price': 'Специальная цена'
};

const openCreateModal = () => {
    editingDiscount.value = null;
    showModal.value = true;
};

const openEditModal = (discount) => {
    editingDiscount.value = discount;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingDiscount.value = null;
};

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString('ru-RU') : '-';
};

const openDeleteModal = (discount) => {
    discountToDelete.value = discount;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    discountToDelete.value = null;
};

const confirmDelete = () => {
    router.delete(route('dashboard.discounts.destroy', discountToDelete.value.id), {
        onSuccess: () => {
            closeDeleteModal();
        }
    });
};

const submitForm = () => {
    formRef.value?.submit();
};
</script>

<template>
    <DashboardLayout>
        <Head title="Управление скидками" />

        <div class="p-4 bg-white block sm:flex items-center justify-between border-b border-gray-200 lg:mt-1.5 dark:bg-gray-800 dark:border-gray-700">
            <div class="w-full mb-1">
                <div class="mb-4">
                    <BreadCrumbs :breadcrumbs="breadCrumbs" />
                    <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                        Управление скидками
                    </h1>
                </div>
                <div class="sm:flex">
                    <div class="items-center hidden mb-3 sm:flex sm:divide-x sm:divide-gray-100 sm:mb-0 dark:divide-gray-700">
                        <form class="lg:pr-3">
                            <label for="search" class="sr-only">Поиск</label>
                            <div class="relative mt-1 lg:w-64 xl:w-96">
                                <input
                                    type="text"
                                    v-model="search"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                    placeholder="Поиск скидок"
                                >
                            </div>
                        </form>
                    </div>
                    <div class="flex items-center ml-auto space-x-2 sm:space-x-3">
                        <PrimaryButton @click="openCreateModal" class="inline-flex">
                            <PlusIcon class="w-5 h-5 mr-2" />
                            Создать скидку
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col">
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden shadow">
                        <Table>
                            <TableHead>
                                <TableRow>
                                    <TableHeader>Название</TableHeader>
                                    <TableHeader>Тип</TableHeader>
                                    <TableHeader>Значение</TableHeader>
                                    <TableHeader>Период действия</TableHeader>
                                    <TableHeader>Статус</TableHeader>
                                    <TableHeader align="right">Действия</TableHeader>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                <TableRow v-for="discount in discounts.data" :key="discount.id" hoverable>
                                    <TableCell>
                                        <div class="text-base font-semibold text-gray-900 dark:text-white">{{ discount.name }}</div>
                                    </TableCell>
                                    <TableCell>
                                        {{ discountTypes[discount.type] }}
                                    </TableCell>
                                    <TableCell>
                                        {{ discount.type === 'percentage' ? `${discount.value}%` : `${discount.value} ₽` }}
                                    </TableCell>
                                    <TableCell>
                                        {{ formatDate(discount.starts_at) }} - {{ formatDate(discount.ends_at) }}
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center">
                                            <div :class="[
                                                'h-2.5 w-2.5 rounded-full mr-2',
                                                discount.is_active ? 'bg-green-500' : 'bg-red-500'
                                            ]"></div>
                                            {{ discount.is_active ? 'Активна' : 'Неактивна' }}
                                        </div>
                                    </TableCell>
                                    <TableCell align="right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                @click="openEditModal(discount)"
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                                            >
                                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                                    <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                                </svg>
                                                Редактировать
                                            </button>
                                            <button
                                                @click="openDeleteModal(discount)"
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-red-600 rounded-lg hover:bg-red-800 focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900"
                                            >
                                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                                Удалить
                                            </button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальные окна -->
        <Modal :show="showModal" @close="closeModal" maxWidth="2xl">
            <template #title>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ editingDiscount ? 'Редактирование скидки' : 'Создание скидки' }}
                </h3>
            </template>
            <template #content>
                <DiscountForm
                    ref="formRef"
                    :discount="editingDiscount"
                    :products="products"
                    :product-variants="productVariants"
                    @saved="closeModal"
                    @cancelled="closeModal"
                />
            </template>
            <template #footer>
                <div class="flex justify-end space-x-3">
                    <PrimaryButton type="button" @click="closeModal" class="bg-gray-500">
                        Отмена
                    </PrimaryButton>
                    <PrimaryButton type="button" @click="submitForm">
                        {{ editingDiscount ? 'Сохранить' : 'Создать' }}
                    </PrimaryButton>
                </div>
            </template>
        </Modal>

        <Modal :show="showDeleteModal" @close="closeDeleteModal">
            <template #title>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Удаление скидки
                </h3>
            </template>
            <template #content>
                <div class="p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Вы уверены, что хотите удалить эту скидку? Это действие нельзя отменить.
                    </p>
                </div>
            </template>
            <template #footer>
                <div class="flex items-center space-x-3">
                    <button
                        @click="closeDeleteModal"
                        class="px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                    >
                        Отмена
                    </button>
                    <button
                        @click="confirmDelete"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:z-10 focus:ring-2 focus:ring-red-300 dark:focus:ring-red-600"
                    >
                        Удалить
                    </button>
                </div>
            </template>
        </Modal>
    </DashboardLayout>
</template>