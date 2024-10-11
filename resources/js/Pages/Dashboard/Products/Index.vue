<script setup>
import { ref } from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import NavLink from "@/Components/NavLink.vue";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
    products: Object,
    categories: Array,
});

const showModal = ref(false);
const editingProduct = ref(null);

const form = useForm({
    name: '',
    description: '',
    is_available: true,
    categories: [],
});

const openModal = (product = null) => {
    editingProduct.value = product;
    if (product) {
        form.name = product.name;
        form.description = product.description;
        form.is_available = product.is_available;
        form.categories = product.categories ? product.categories.map(c => c.id) : [];

    } else {
        form.reset();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingProduct.value = null;
    form.reset();
};

const submitForm = () => {
    if (editingProduct.value) {
        form.put(route('dashboard.products.update', editingProduct.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('dashboard.products.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};



const deleteProduct = (product) => {
    if (confirm('Вы уверены, что хотите удалить этот продукт?')) {
        router.delete(route('dashboard.products.destroy', product.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <DashboardLayout>
        <template  >
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <PrimaryButton @click="openModal()">Добавить товар</PrimaryButton>

                            <table class="mt-4 w-full">
                                <thead>
                                <tr>
                                    <th>Название</th>
                                    <th>Доступность</th>
                                    <th>Категории</th>
                                    <th>Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="product in products.data" :key="product.id">
                                    <td><NavLink :href="route('dashboard.products.show', product.id)">{{ product.name }}</NavLink></td>
                                    <td>{{ product.is_available ? 'Да' : 'Нет' }}</td>
                                    <td>{{ product.categories.map(c => c.name).join(', ') }}</td>
                                    <td>
                                        <PrimaryButton @click="openModal(product)">Редактировать</PrimaryButton>
                                        <PrimaryButton @click="deleteProduct(product)" class="ml-2 bg-red-500">Удалить</PrimaryButton>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <Modal :show="showModal" @close="closeModal">
                <form @submit.prevent="submitForm" class="p-6">
                    <h2 class="text-lg font-medium mb-4">
                        {{ editingProduct ? 'Редактировать товар' : 'Добавить новый товар' }}
                    </h2>

                    <div class="mb-4">
                        <InputLabel for="name" value="Название" />
                        <TextInput id="name" v-model="form.name" required />
                    </div>

                    <div class="mb-4">
                        <InputLabel for="description" value="Описание" />
                        <textarea id="description" v-model="form.description" class="mt-1 block w-full" rows="3"></textarea>
                    </div>

                    <div class="mb-4">
                        <InputLabel for="is_available" value="Доступен" />
                        <input type="checkbox" id="is_available" v-model="form.is_available" class="mt-1">
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Категории" />
                        <select v-model="form.categories" multiple class="mt-1 block w-full">
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>



                    <div class="flex justify-end mt-4">
                        <PrimaryButton :disabled="form.processing">
                            {{ editingProduct ? 'Сохранить' : 'Добавить' }}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </template>
    </DashboardLayout>
</template>
