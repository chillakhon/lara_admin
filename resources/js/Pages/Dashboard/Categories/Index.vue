<script setup>
import {ref, computed} from 'vue'
import {useForm} from '@inertiajs/vue3'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import CategoryItem from "@/Pages/Dashboard/Categories/CategoryItem.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    categories: Array
})

const showModal = ref(false)
const modalMode = ref('create')
const selectedCategory = ref(null)
const form = useForm({
    name: '',
    parent_id: null
})

const flattenedCategories = computed(() => {
    const flatten = (cats) => {
        return cats.reduce((acc, cat) => {
            acc.push(cat)
            if (cat.children && cat.children.length) {
                acc.push(...flatten(cat.children))
            }
            return acc
        }, [])
    }
    return flatten(props.categories)
})

const openModal = (mode, category = null) => {
    modalMode.value = mode
    selectedCategory.value = category
    if (mode === 'edit' && category) {
        form.name = category.name
        form.parent_id = category.parent_id
    } else if (mode === 'create') {
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
    form.delete(route('dashboard.categories.destroy', selectedCategory.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal()
    })
}
</script>
<template>
    <AuthenticatedLayout title="Categories">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Categories
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <button @click="openModal('create')"
                                class="mb-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Add Category
                        </button>

                        <div class="space-y-2">
                            <CategoryItem
                                v-for="category in categories"
                                :key="category.id"
                                :category="category"
                                @edit="openModal('edit', $event)"
                                @delete="openModal('delete', $event)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900" v-if="modalMode === 'create'">
                    Create Category
                </h2>
                <h2 class="text-lg font-medium text-gray-900" v-else-if="modalMode === 'edit'">
                    Edit Category
                </h2>
                <h2 class="text-lg font-medium text-gray-900" v-else-if="modalMode === 'delete'">
                    Delete Category
                </h2>
                <form @submit.prevent="submitForm" v-if="modalMode !== 'delete'">
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" id="name" v-model="form.name"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    <div class="mb-4">
                        <label for="parent" class="block text-sm font-medium text-gray-700">Parent Category</label>
                        <select id="parent" v-model="form.parent_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option :value="null">No parent</option>
                            <option v-for="cat in flattenedCategories" :key="cat.id" :value="cat.id">
                                {{ '-'.repeat(cat.depth) }} {{ cat.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ modalMode === 'create' ? 'Create' : 'Update' }}
                        </button>
                    </div>
                </form>
                <div v-else>
                    <p class="mb-4">Are you sure you want to delete this category?</p>
                    <div class="flex justify-end">
                        <button @click="deleteCategory"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Delete
                        </button>
                    </div>
                </div>
            </div>

        </Modal>
    </AuthenticatedLayout>
</template>
