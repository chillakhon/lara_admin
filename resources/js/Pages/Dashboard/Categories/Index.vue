<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import CategoryTable from "./CategoryTable.vue";
import Modal from "@/Components/Modal.vue";
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    categories: Array
})

const breadCrumbs = [
    {
        name: 'Категории',
        link: route('dashboard.categories.index')
    }
]

const showModal = ref(false)
const modalMode = ref('create')
const selectedCategory = ref(null)
const form = useForm({
    name: '',
    parent_id: null
})

const flattenedCategories = computed(() => {
    const flatten = (categories, depth = 0) => {
        return categories.reduce((acc, category) => {
            const flatCategory = {
                ...category,
                depth,
                label: '- '.repeat(depth) + category.name
            };
            acc.push(flatCategory);
            if (category.children && category.children.length) {
                acc.push(...flatten(category.children, depth + 1));
            }
            return acc;
        }, []);
    };
    return flatten(props.categories);
});

const openModal = (mode, category = null) => {
    modalMode.value = mode
    selectedCategory.value = category
    if (mode === 'edit' && category) {
        form.name = category.name
        form.parent_id = category.parent_id
    } else {
        form.reset()
    }
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    form.reset()
    selectedCategory.value = null
}

const submitForm = () => {
    if (modalMode.value === 'create') {
        form.post(route('dashboard.categories.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    } else if (modalMode.value === 'edit') {
        form.put(route('dashboard.categories.update', selectedCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    }
}

const deleteCategory = () => {
    if (selectedCategory.value) {
        form.delete(route('dashboard.categories.destroy', selectedCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    }
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white mb-4 sm:mb-0">Категории</h1>
            <div class="sm:flex sm:justify-end">

                <div class="flex items-center ml-auto space-x-2 sm:space-x-3">
                    <PrimaryButton type="default" @click="openModal('create')"
                                   class="w-full sm:w-auto flex justify-center">
                        <template #icon-left>
                            <svg class="w-5 h-5  -ml-1" fill="currentColor" viewBox="0 0 20 20"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                      d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                      clip-rule="evenodd"></path>
                            </svg>
                        </template>
                        <span > Создать категорию</span>
                    </PrimaryButton>
                </div>
            </div>
        </template>
        <template #default>
            <CategoryTable
                :categories="categories"
                @edit="openModal('edit', $event)"
                @delete="openModal('delete', $event)"
            />

            <Modal :show="showModal" @close="closeModal">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900" v-if="modalMode === 'create'">
                        Создать категорию
                    </h2>
                    <h2 class="text-lg font-medium text-gray-900" v-else-if="modalMode === 'edit'">
                        Редактировать категорию
                    </h2>
                    <h2 class="text-lg font-medium text-gray-900" v-else-if="modalMode === 'delete'">
                        Удалить категорию
                    </h2>
                    <form @submit.prevent="submitForm" v-if="modalMode !== 'delete'">
                        <div class="mt-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Название</label>
                            <input type="text" id="name" v-model="form.name"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        <div class="mt-4">
                            <label for="parent" class="block text-sm font-medium text-gray-700">Родительская
                                категория</label>
                            <select id="parent" v-model="form.parent_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option :value="null">Без родительской категории</option>
                                <option v-for="category in flattenedCategories" :key="category.id" :value="category.id">
                                    {{ category.label }}
                                </option>
                            </select>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                {{ modalMode === 'create' ? 'Создать' : 'Обновить' }}
                            </button>
                        </div>
                    </form>
                    <div v-else>
                        <p class="mt-4">Вы уверены, что хотите удалить эту категорию?</p>
                        <div class="mt-4 flex justify-end">
                            <button @click="deleteCategory"
                                    class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
                                Удалить
                            </button>
                        </div>
                    </div>
                </div>
            </Modal>
        </template>
    </DashboardLayout>
</template>
