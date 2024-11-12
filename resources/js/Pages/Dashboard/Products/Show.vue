<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import ProductGeneralInfo from './Partials/ProductGeneralInfo.vue';
import ProductOptions from './Partials/ProductOptions.vue';
import ProductVariants from './Partials/ProductVariants.vue';

const props = defineProps({
    product: Object,
    categories: Array,
    units: Array,
});

const activeTab = ref('general');

const tabs = [
    { key: 'general', name: 'Основная информация', icon: 'InformationCircle' },
    { key: 'options', name: 'Опции', icon: 'ListBullet' },
    { key: 'variants', name: 'Варианты', icon: 'Square2Stack' },
    { key: 'images', name: 'Изображения', icon: 'Photo' },
];

const breadcrumbs = computed(() => [
    { name: 'Товары', href: route('dashboard.products.index') },
    { name: props.product.name, href: route('dashboard.products.show', props.product.id) },
]);

const statusColors = {
    draft: 'gray',
    active: 'green',
    inactive: 'red'
};

const getStatusColor = (status) => statusColors[status] || 'gray';

const getProductStatus = computed(() => {
    if (!props.product.is_active) return 'draft';
    return props.product.is_active ? 'active' : 'inactive';
});

</script>

<template>
    <Head :title="product.name" />

    <DashboardLayout>
        <template #header>
            <div class="mb-4">
                <BreadCrumbs :breadcrumbs="breadcrumbs" />
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                    {{ product.name }}
                    <span
                        class="ms-2 text-sm font-medium px-2.5 py-0.5 rounded"
                        :class="{
                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': getProductStatus === 'draft',
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': getProductStatus === 'active',
                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': getProductStatus === 'inactive'
                        }"
                    >
                        {{ getProductStatus === 'draft' ? 'Черновик' : (getProductStatus === 'active' ? 'Активен' : 'Неактивен') }}
                    </span>
                </h1>
            </div>
        </template>

        <div class="p-4">
            <!-- Tabs -->
            <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
                    <li v-for="tab in tabs" :key="tab.key" class="mr-2">
                        <button
                            @click="activeTab = tab.key"
                            class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg group"
                            :class="[
                                activeTab === tab.key
                                    ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500'
                                    : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'
                            ]"
                        >
                            <span>{{ tab.name }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <!-- Main Content Area -->
                <div class="lg:col-span-3">
                    <div v-show="activeTab === 'general'">
                        <ProductGeneralInfo
                            :product="product"
                            :categories="categories"
                            :units="units"
                        />
                    </div>

                    <div v-show="activeTab === 'options'">
                        <ProductOptions
                            :product="product"
                            :categories="categories"
                        />
                    </div>

                    <div v-show="activeTab === 'variants'">
                        <ProductVariants
                            :product="product"
                            :units="units"
                        />
                    </div>

<!--                    <div v-show="activeTab === 'images'">-->
<!--                        <ProductImages-->
<!--                            :product="product"-->
<!--                        />-->
<!--                    </div>-->
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <!-- Product Statistics -->
                    <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                        <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-white">
                            Статистика
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500 dark:text-gray-400">Всего вариантов</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ product.variants?.length || 0 }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500 dark:text-gray-400">Активных вариантов</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ product.variants?.filter(v => v.is_active).length || 0 }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500 dark:text-gray-400">Опций</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ product.options?.length || 0 }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                        <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-white">
                            Быстрые действия
                        </h3>
                        <div class="space-y-2">
                            <button
                                type="button"
                                class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                            >
                                Предпросмотр товара
                            </button>
                            <button
                                type="button"
                                class="w-full text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                            >
                                Дублировать товар
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
