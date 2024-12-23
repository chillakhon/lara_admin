<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Блоки контента
                </h1>
                <PrimaryButton @click="openCreateModal">
                    <PlusIcon class="w-5 h-5 mr-2"/>
                    Добавить блок
                </PrimaryButton>
            </div>
        </template>

        <!-- Список блоков -->
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
                <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-visible">
                    <!-- Содержимое блоков -->
                </div>
            </div>
        </div>

        <!-- Модальное окно создания/редактирования -->
        <Modal :show="showModal" @close="closeModal">
            <!-- Форма блока -->
        </Modal>
    </DashboardLayout>
</template>
<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
</script>
