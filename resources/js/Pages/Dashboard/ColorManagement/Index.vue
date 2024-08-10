<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Color Management</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <!-- Color Category Form -->
                        <form @submit.prevent="submitCategoryForm" class="mb-6">
                            <input v-model="categoryForm.title" placeholder="Category Title" class="mr-2">
                            <PrimaryButton type="submit">{{ editingCategory ? 'Update' : 'Add' }} Category</PrimaryButton>
                        </form>

                        <!-- Color Categories and Colors -->
                        <div v-for="category in categories" :key="category.id" class="mb-8">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-lg font-semibold">{{ category.title }}</h3>
                                <div>
                                    <button @click="editCategory(category)" class="text-blue-600 mr-2">Edit</button>
                                    <button @click="deleteCategory(category.id)" class="text-red-600">Delete</button>
                                </div>
                            </div>

                            <!-- Color Form -->
                            <form @submit.prevent="submitColorForm(category.id)" class="mb-4" enctype="multipart/form-data">
                                <input v-model="colorForm.title" placeholder="Color Title" class="mr-2">
                                <input v-model="colorForm.code" placeholder="Color Code" class="mr-2">
                                <input type="file" @change="handleFileUpload" accept="image/*" class="mr-2">
                                <PrimaryButton type="submit">{{ editingColor ? 'Update' : 'Add' }} Color</PrimaryButton>
                            </form>

                            <!-- Colors Table -->
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Code</th>
                                    <th>Image</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="color in category.colors" :key="color.id">
                                    <td>{{ color.title }}</td>
                                    <td>
                                        <div class="flex items-center">
                                            <div :style="{ backgroundColor: `#${color.code}`, width: '20px', height: '20px', marginRight: '8px' }"></div>
                                            {{ color.code }}
                                        </div>
                                    </td>
                                    <td>
                                        <img v-if="color.images && color.images.length" :src="color.images[0].url" alt="Color image" class="w-16 h-16 object-cover">
                                    </td>
                                    <td>
                                        <button @click="editColor(color)" class="text-blue-600 mr-2">Edit</button>
                                        <button @click="deleteColor(color.id)" class="text-red-600">Delete</button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import {ref} from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    categories: Array,
});

const categoryForm = useForm({
    id: null,
    title: '',
});

const colorForm = useForm({
    id: null,
    title: '',
    code: '',
    color_category_id: '',
    image: null,
});

const editingCategory = ref(false);
const editingColor = ref(false);

const handleFileUpload = (event) => {
    colorForm.image = event.target.files[0];
};

const submitCategoryForm = () => {
    if (editingCategory.value) {
        categoryForm.put(route('dashboard.color-categories.update', categoryForm.id), {
            preserveScroll: true,
            onSuccess: () => {
                editingCategory.value = false;
                categoryForm.reset();
            },
        });
    } else {
        categoryForm.post(route('dashboard.color-categories.store'), {
            preserveScroll: true,
            onSuccess: () => categoryForm.reset(),
        });
    }
};

const submitColorForm = (categoryId) => {
    colorForm.color_category_id = categoryId;

    const formData = new FormData();

    for (let key in colorForm) {
        if (colorForm.hasOwnProperty(key) && colorForm[key] !== null) {
            formData.append(key, colorForm[key]);
        }
    }

    if (editingColor.value) {
        formData.append('_method', 'PUT');

        router.post(route('dashboard.colors.update', colorForm.id), formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                editingColor.value = false;
                colorForm.reset();
            },
        });
    } else {
        router.post(route('dashboard.colors.store'), formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => colorForm.reset(),
        });
    }
};

const editCategory = (category) => {
    categoryForm.id = category.id;
    categoryForm.title = category.title;
    editingCategory.value = true;
};

const deleteCategory = (id) => {
    if (confirm('Are you sure you want to delete this category? All associated colors will be deleted.')) {
        router.delete(route('dashboard.color-categories.destroy', id), {
            preserveScroll: true,
        });
    }
};

const editColor = (color) => {
    colorForm.id = color.id;
    colorForm.title = color.title;
    colorForm.code = color.code;
    colorForm.color_category_id = color.color_category_id;
    editingColor.value = true;
};

const deleteColor = (id) => {
    if (confirm('Are you sure you want to delete this color?')) {
        router.delete(route('dashboard.colors.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>
