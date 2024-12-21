<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Типы полей
                </h1>
                <PrimaryButton @click="openCreateModal">
                    <PlusIcon class="w-5 h-5 mr-2"/>
                    Добавить тип поля
                </PrimaryButton>
            </div>
        </template>

        <!-- Таблица типов полей -->
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
                <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-visible">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3">Название</th>
                                <th scope="col" class="px-4 py-3">Тип</th>
                                <th scope="col" class="px-4 py-3">Настройки</th>
                                <th scope="col" class="px-4 py-3">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Строки таблицы -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Модальное окно создания/редактирования -->
        <Modal :show="showModal" @close="closeModal">
            <!-- Содержимое модального окна -->
        </Modal>
    </DashboardLayout>
</template>
