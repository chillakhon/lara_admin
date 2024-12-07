<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs" />
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Управление контентом
                </h1>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Карточка страниц -->
                    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold">Страницы</h2>
                            <Link
                                :href="route('dashboard.content.pages.index')"
                                class="text-blue-600 hover:text-blue-800"
                            >
                                Перейти →
                            </Link>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            Управление страницами и их контентом
                        </p>
                    </div>

                    <!-- Карточка блоков контента -->
                    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold">Блоки контента</h2>
                            <Link
                                :href="route('dashboard.content.blocks.index')"
                                class="text-blue-600 hover:text-blue-800"
                            >
                                Перейти →
                            </Link>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            Управление блоками контента и их структурой
                        </p>
                    </div>

                    <!-- Карточка групп полей -->
                    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold">Группы полей</h2>
                            <Link
                                :href="route('dashboard.content.field-groups.index')"
                                class="text-blue-600 hover:text-blue-800"
                            >
                                Перейти →
                            </Link>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            Управление группами полей для блоков
                        </p>
                    </div>

                    <!-- Карточка типов полей -->
                    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold">Типы полей</h2>
                            <Link
                                :href="route('dashboard.content.field-types.index')"
                                class="text-blue-600 hover:text-blue-800"
                            >
                                Перейти →
                            </Link>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            Управление типами полей и их настройками
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup>
    import { Link } from '@inertiajs/vue3';
    import DashboardLayout from '@/Layouts/DashboardLayout.vue';
    const breadCrumbs = [{ name: 'Контент', link: route('dashboard.content.pages.index') }];
</script>
