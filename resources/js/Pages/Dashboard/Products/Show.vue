<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Product Details: {{ product.name }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-semibold mb-4">Product Sizes and Components</h3>
                        <!-- Поле для ввода наценки -->
                        <div class="mb-4">
                            <label for="markup" class="block text-sm font-medium text-gray-700">Markup Percentage</label>
                            <input type="number" id="markup" v-model.number="markup" min="0" step="0.1" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Form to add new size -->
                        <form @submit.prevent="addSize" class="mb-6">
                            <input v-model="newSizeName" placeholder="New size name" class="mr-2">
                            <PrimaryButton type="submit">Add Size</PrimaryButton>
                        </form>

                        <!-- Sizes and their components -->
                        <div v-for="size in product.sizes" :key="size.id" class="mb-8">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="text-md font-semibold">Size: {{ size.name }}</h4>
                                <button @click="removeSize(size.id)" class="text-red-600 hover:text-red-800">
                                    Remove Size
                                </button>
                            </div>

                            <!-- Form to add component to size -->
                            <form @submit.prevent="addComponent(size.id)" class="mb-4">
                                <select v-model="newComponent.material_id" class="mr-2">
                                    <option value="">Select Material</option>
                                    <option v-for="material in materials" :key="material.id" :value="material.id">
                                        {{ material.title }}
                                    </option>
                                </select>
                                <input v-model.number="newComponent.quantity" type="number" min="0" step="0.01" placeholder="Quantity" class="mr-2">
                                <PrimaryButton type="submit">Add Component</PrimaryButton>
                            </form>

                            <!-- Components table -->
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th>Material</th>
                                    <th>Quantity</th>
                                    <th>Cost per unit</th>
                                    <th>Total Cost</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="component in size.components" :key="component.id">
                                    <td>{{ component.material.title }}</td>
                                    <td>{{ component.quantity }}</td>
                                    <td>{{ component.material.cost_per_unit }}</td>
                                    <td>{{ calculateComponentCost(component) }}</td>
                                    <td>
                                        <button @click="removeComponent(size.id, component.id)" class="text-red-600">Remove</button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <div class="mt-4 text-right">
                                <strong>Total Cost for {{ size.name }}: {{ calculateSizeTotalCost(size) }}</strong><br>
                                <strong>Price with Markup for {{ size.name }}: {{ calculateSizePriceWithMarkup(size) }}</strong>
                            </div>

                            <!-- Кнопка для создания вариантов товара -->
                            <div class="mt-4">
                                <PrimaryButton @click="createProductVariants(size)" :disabled="size.components.length === 0">
                                    Create Product Variants
                                </PrimaryButton>
                            </div>

                        </div>

                        <!-- Отображение всех вариантов продукта -->
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold mb-4">Product Variants</h3>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="variant in product.variants" :key="variant.id">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ variant.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ variant.price }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ variant.stock }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button @click="editVariant(variant)" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button @click="deleteVariant(variant.id)" class="text-red-600 hover:text-red-900">Delete</button>
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
import { ref } from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    product: Object,
    materials: Array,
});

const markup = ref(20);
const newSizeName = ref('');
const newComponent = ref({
    material_id: '',
    quantity: null,
});

const addSize = () => {
    useForm({
        name: newSizeName.value,
    }).post(route('products.sizes.store', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            newSizeName.value = '';
        },
    });
};

const removeSize = (sizeId) => {
    if (confirm('Are you sure you want to remove this size? All associated components will be deleted.')) {
        router.delete(route('products.sizes.destroy', [props.product.id, sizeId]), {
            preserveScroll: true,
        });
    }
};

const addComponent = (sizeId) => {
    useForm({
        material_id: newComponent.value.material_id,
        quantity: newComponent.value.quantity,
    }).post(route('products.sizes.components.store', [props.product.id, sizeId]), {
        preserveScroll: true,
        onSuccess: () => {
            newComponent.value = { material_id: '', quantity: null };
        },
    });
};

const removeComponent = (sizeId, componentId) => {
    if (confirm('Are you sure you want to remove this component?')) {
        router.delete(route('products.sizes.components.destroy', [props.product.id, sizeId, componentId]), {
            preserveScroll: true,
        });
    }
};

const calculateComponentCost = (component) => {
    return (component.quantity * component.material.cost_per_unit).toFixed(2);
};

const calculateSizeTotalCost = (size) => {
    return size.components.reduce((total, component) => {
        return total + (component.quantity * component.material.cost_per_unit);
    }, 0).toFixed(2);
};

const calculateSizePriceWithMarkup = (size) => {
    const totalCost = parseFloat(calculateSizeTotalCost(size));
    return (totalCost * (1 + markup.value / 100)).toFixed(2);
};

const createProductVariants = (size) => {
    const basePrice = calculateSizePriceWithMarkup(size);

    useForm({
        product_id: props.product.id,
        size_name: size.name,
        base_price: basePrice,
    }).post(route('products.variants.store', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Можно добавить уведомление об успешном создании вариантов
        },
    });
};

const editVariant = (variant) => {
    // Реализуйте логику редактирования варианта
    console.log('Edit variant:', variant);
};

const deleteVariant = (variantId) => {
    if (confirm('Are you sure you want to delete this variant?')) {
        router.delete(route('products.variants.destroy', [props.product.id, variantId]), {
            preserveScroll: true,
        });
    }
};
</script>
