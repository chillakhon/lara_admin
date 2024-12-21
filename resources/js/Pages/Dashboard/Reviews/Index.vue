<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Управление отзывами
                </h1>
                <PrimaryButton @click="showCreateModal = true">
                    Создать отзыв
                </PrimaryButton>
            </div>
        </template>

        <!-- Фильтры и статистика -->
        <div class="grid gap-6 mb-6 md:grid-cols-4">
            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                        <DocumentTextIcon class="w-6 h-6 text-blue-600 dark:text-blue-300"/>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Всего отзывов</p>
                        <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ statistics.total }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                        <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-300"/>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Опубликовано</p>
                        <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ statistics.published }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                        <ClockIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-300"/>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">На модерации</p>
                        <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ statistics.pending }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                        <ExclamationCircleIcon class="w-6 h-6 text-red-600 dark:text-red-300"/>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Отклонено</p>
                        <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ statistics.rejected }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <TextInput
                    v-model="filters.search"
                    placeholder="Поиск по содержанию..."
                    class="md:w-64"
                >
                    <template #prefix>
                        <MagnifyingGlassIcon class="w-5 h-5 text-gray-400"/>
                    </template>
                </TextInput>
                
                <SelectDropdown
                    v-model="filters.rating"
                    :options="ratingOptions"
                    placeholder="Рейтинг"
                    option-value="id"
                    option-label="name"
                    class="md:w-40"
                />
                
                <SelectDropdown
                    v-model="filters.status"
                    :options="statusOptions"
                    placeholder="Статус"
                    option-value="id"
                    option-label="name"
                    class="md:w-48"
                />
            </div>
        </div>

        <!-- Таблица отзывов -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Клиент</th>
                            <th scope="col" class="px-6 py-3">Отзыв</th>
                            <th scope="col" class="px-6 py-3">Рейтинг</th>
                            <th scope="col" class="px-6 py-3">Статус</th>
                            <th scope="col" class="px-6 py-3">Дата</th>
                            <th scope="col" class="px-6 py-3">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="review in reviews.data" :key="review.id" 
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <img v-if="review.client.avatar" 
                                             :src="review.client.avatar" 
                                             class="h-10 w-10 rounded-full"
                                             :alt="review.client.name">
                                        <div v-else 
                                             class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-600">
                                                {{ review.client?.name?.charAt(0) || '?' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ review.client.name }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs truncate">{{ review.content }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <StarIcon v-for="i in 5" :key="i"
                                            :class="[
                                                'w-5 h-5',
                                                i <= review.rating 
                                                    ? 'text-yellow-400' 
                                                    : 'text-gray-300 dark:text-gray-600'
                                            ]"/>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <Badge :type="getStatusType(review)">
                                    {{ getStatusLabel(review) }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4">
                                {{ review.created_at }}
                            </td>
                            <td class="px-6 py-4">
                                <ContextMenu
                                    :items="getMenuItems(review)"
                                    @action="(action) => handleAction(action, review)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <Pagination :data="reviews"/>
            </div>
        </div>

        <!-- Модальное окно просмотра/модерации -->
        <Modal :show="showModal" @close="closeModal" max-width="3xl">
            <template #title>
                Просмотр отзыв��
            </template>
            <template #content>
                <ReviewDetails 
                    v-if="selectedReview"
                    :review="selectedReview"
                    @update="handleReviewUpdate"
                />
            </template>
        </Modal>

        <!-- Модальное окно создания отзыва -->
        <Modal :show="showCreateModal" @close="closeCreateModal" max-width="2xl">
            <template #title>
                Создание отзыва
            </template>
            <template #content>
                <form @submit.prevent="handleCreate" class="space-y-4">
                    <!-- Выбор клиента -->
                    <div>
                        <SearchSelect
                            v-model="createForm.client_id"
                            type="clients"
                            placeholder="Выберите клиента"
                            @change="handleClientSelect"
                            :error="createForm.errors.client_id"
                        />
                    </div>

                    <!-- Выбор товара -->
                    <div>
                        <SearchSelect
                            v-model="createForm.reviewable_id"
                            type="products"
                            placeholder="Выберите товар"
                            @change="handleProductSelect"
                            :error="createForm.errors.reviewable_id"
                        />
                    </div>

                    <!-- Рейтинг -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Рейтинг
                        </label>
                        <div class="flex items-center space-x-2">
                            <button 
                                v-for="rating in 5" 
                                :key="rating"
                                type="button"
                                @click="createForm.rating = rating"
                                class="focus:outline-none"
                            >
                                <StarIcon 
                                    class="w-8 h-8" 
                                    :class="rating <= createForm.rating ? 'text-yellow-400' : 'text-gray-300'"
                                />
                            </button>
                        </div>
                        <p v-if="createForm.errors.rating" class="mt-1 text-sm text-red-600">
                            {{ createForm.errors.rating }}
                        </p>
                    </div>

                    <!-- Текст отзыва -->
                    <div>
                        <TextArea
                            v-model="createForm.content"
                            label="Текст отзыва"
                            required
                            :error="createForm.errors.content"
                        />
                    </div>

                    <!-- Статус -->
                    <div class="flex items-center space-x-4">
                        <Toggle
                            v-model="createForm.is_verified"
                            label="Проверено"
                        />
                        <Toggle
                            v-model="createForm.is_published"
                            label="Опубликовано"
                        />
                    </div>
                </form>
            </template>
            <template #footer>
                <div class="flex justify-end space-x-3">
                    <SecondaryButton @click="closeCreateModal">
                        Отмена
                    </SecondaryButton>
                    <PrimaryButton 
                        type="button"
                        @click="handleCreate"
                        :disabled="createForm.processing"
                    >
                        Создать
                    </PrimaryButton>
                </div>
            </template>
        </Modal>
    </DashboardLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectDropdown from '@/Components/SelectDropdown.vue';
import Badge from '@/Components/Badge.vue';
import Modal from '@/Components/Modal.vue';
import ReviewDetails from '@/Components/Reviews/ReviewDetails.vue';
import ContextMenu from '@/Components/ContextMenu.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
    DocumentTextIcon, CheckCircleIcon, ClockIcon, ExclamationCircleIcon,
    StarIcon, MagnifyingGlassIcon, EyeIcon 
} from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextArea from '@/Components/TextArea.vue';
import Toggle from '@/Components/Toggle.vue';
import SearchSelect from '@/Components/SearchSelect.vue';

const props = defineProps({
    reviews: Object,
    filters: {
        type: Object,
        default: () => ({
            search: '',
            rating: '',
            status: ''
        })
    }
});

// Статистика
const statistics = ref({
    total: props.reviews?.total || 0,
    published: props.reviews?.data.filter(r => r.is_published).length || 0,
    pending: props.reviews?.data.filter(r => !r.is_verified).length || 0,
    rejected: props.reviews?.data.filter(r => r.is_verified && !r.is_published).length || 0
});

// Фильтры
const filters = ref(props.filters);

const ratingOptions = [
    { id: '', name: 'Все оценки' },
    { id: '5', name: '5 звезд' },
    { id: '4', name: '4 звезды' },
    { id: '3', name: '3 звезды' },
    { id: '2', name: '2 звезды' },
    { id: '1', name: '1 звезда' }
];

const statusOptions = [
    { id: '', name: 'Все статусы' },
    { id: 'published', name: 'Опубликованные' },
    { id: 'pending', name: 'На модерации' },
    { id: 'rejected', name: 'Отклоненные' }
];

// Хлебные крошки
const breadCrumbs = [
    { name: 'Главная', href: route('dashboard') },
    { name: 'Отзывы', href: route('dashboard.reviews.index') }
];

// Модальное окно
const showModal = ref(false);
const selectedReview = ref(null);

// Модальное окно создания
const showCreateModal = ref(false);

// Форма создания
const createForm = useForm({
    client_id: null,
    client: null,
    reviewable_id: null,
    reviewable: null,
    reviewable_type: 'App\\Models\\Product',
    rating: 5,
    content: '',
    is_verified: true,
    is_published: true
});

// Методы
const getStatusType = (review) => {
    if (review.is_published) return 'success';
    if (!review.is_verified) return 'warning';
    return 'danger';
};

const getStatusLabel = (review) => {
    if (review.is_published) return 'Опубликован';
    if (!review.is_verified) return 'На модерации';
    return 'Отклонен';
};

const getMenuItems = (review) => {
    return [
        {
            label: 'Просмотреть',
            icon: EyeIcon,
            action: 'view'
        },
        {
            label: review.is_verified ? 'Снять проверку' : 'Проверить',
            action: 'toggle-verify'
        },
        {
            label: review.is_published ? 'Снять с публикации' : 'Опубликовать',
            action: 'toggle-publish'
        },
        {
            label: 'Удалить',
            action: 'delete',
            dangerous: true
        }
    ];
};

const handleAction = (action, review) => {
    switch (action) {
        case 'view':
            selectedReview.value = review;
            showModal.value = true;
            break;
        case 'toggle-verify':
            router.put(route('dashboard.reviews.verify', review.id), {
                is_verified: !review.is_verified
            });
            break;
        case 'toggle-publish':
            router.put(route('dashboard.reviews.publish', review.id), {
                is_published: !review.is_published
            });
            break;
        case 'delete':
            if (confirm('Вы уверены, что хотите удалить этот отзыв?')) {
                router.delete(route('dashboard.reviews.destroy', review.id));
            }
            break;
    }
};

const closeModal = () => {
    showModal.value = false;
    selectedReview.value = null;
};

const handleReviewUpdate = () => {
    closeModal();
    router.reload();
};

// Методы для работы с формой создания
const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const handleCreate = () => {
    createForm.post(route('dashboard.reviews.store'), {
        onSuccess: () => {
            closeCreateModal();
            // Опционально показать уведомление об успехе
        }
    });
};

// Отслеживание изменений фильтров
watch(filters, (newFilters) => {
    router.get(route('dashboard.reviews.index'), newFilters, {
        preserveState: true,
        preserveScroll: true
    });
}, { deep: true });

// Обработчики выбора
const handleClientSelect = (client) => {
    createForm.client = client;
    createForm.client_id = client?.id;
};

const handleProductSelect = (product) => {
    createForm.reviewable = product;
    createForm.reviewable_id = product?.id;
    createForm.reviewable_type = 'App\\Models\\Product';
};
</script> 