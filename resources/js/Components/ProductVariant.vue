<script setup>
import {reactive, ref} from 'vue';
import {router} from '@inertiajs/vue3';

const props = defineProps({
    variant: Object,
    productId: Number
});

const isEditing = ref(false);
const editForm = reactive({
    name: props.variant.name,
    price: props.variant.price,
    stock: props.variant.stock
});

const updateVariant = () => {
    router.put(route('dashboard.products.variants.update', {
        product: props.productId,
        variant: props.variant.id
    }), editForm, {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
            // Обновляем локальное состояние варианта
            Object.assign(props.variant, editForm);
        }
    });
};

const setMainImage = (imageId) => {
    router.patch(route('dashboard.product.images.setMain', {
        product: props.productId,
        variant: props.variant.id,
        image: imageId,
    }), {
        variant_id: props.variant.id
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Обновляем локальное состояние после успешного запроса
            props.variant.images.forEach(img => {
                img.is_main = img.id === imageId;
            });
        }
    });
};
</script>

<template>
    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900">{{ variant.name }}</h3>
        <div>
            <button @click="isEditing = true" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
            <button @click="$emit('delete-variant', variant.id)" class="text-red-600 hover:text-red-900">Delete</button>
        </div>
    </div>
    <div class="border-t border-gray-200">
        <dl>
            <div v-if="!isEditing" class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Price</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ variant.price }}</dd>
            </div>
            <div v-if="!isEditing" class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Stock</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ variant.stock }}</dd>
            </div>
            <div v-if="isEditing" class="bg-gray-50 px-4 py-5 sm:px-6">
                <form @submit.prevent="updateVariant">
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" id="name" v-model="editForm.name"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="mb-4">
                        <label for="price" class="block text-sm font-medium text-gray-700">Price</label>
                        <input type="number" id="price" v-model="editForm.price" step="0.01"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="mb-4">
                        <label for="stock" class="block text-sm font-medium text-gray-700">Stock</label>
                        <input type="number" id="stock" v-model="editForm.stock"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="flex justify-end">
                        <button type="button" @click="isEditing = false"
                                class="mr-2 bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </button>
                        <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Save
                        </button>
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:px-6">
                <dt class="text-sm font-medium text-gray-500 mb-2">Images</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <div class="flex flex-wrap -mx-2">
                        <div v-for="image in variant.images" :key="image.id" class="px-2 mb-4">
                            <div class="relative">
                                <img :src="image.url" :alt="variant.name"
                                     class="w-32 h-32 object-cover rounded-lg shadow-md">
                                <button
                                    @click="setMainImage(image.id)"
                                    :class="[
                                    'absolute top-0 right-0 mt-2 mr-2 px-2 py-1 text-xs font-semibold rounded-full',
                                    image.is_main ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                ]"
                                    :disabled="image.is_main"
                                >
                                    {{ image.is_main ? 'Main' : 'Set Main' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </dd>
            </div>
        </dl>
    </div>
</template>

