<!-- resources/js/Pages/Dashboard/Products/Show.vue -->
<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Product Details: {{ product.name }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-semibold mb-4">Product Components</h3>
                        <form @submit.prevent="addComponent" class="my-6">
                            <div class="flex gap-4 items-center">
                                <select v-model="form.material_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select Material</option>
                                    <option v-for="material in materials" :key="material.id" :value="material.id">{{ material.title }}</option>
                                </select>
                                <input v-model="form.quantity" type="number" min="0" step="0.01" placeholder="Quantity" class="mt-1 ml-2 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <PrimaryButton >Add</PrimaryButton>
                            </div>
                        </form>

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost per unit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="component in product.components" :key="component.id">
                                <td class="px-6 py-4 whitespace-nowrap">{{ component.material.title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ roundToFirstNonZero(component.material.cost_per_unit) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ roundToFirstNonZero(component.quantity) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ multiplyPrecise(component.material.cost_per_unit, component.quantity) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button @click="removeComponent(component.id)" class="text-red-600 hover:text-red-900">Remove</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="flex justify-end w-full">
                            <span class="font-bold text-2xl">Total: {{ totalCost }}</span>
                        </div>
                        <div class="mt-6">
                            <button @click="calculateCost" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Calculate Cost
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import {router, useForm} from '@inertiajs/vue3'

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {computed} from "vue";

const props = defineProps({
    product: Object,
    materials: Array,
})

const form = useForm({
    material_id: '',
    quantity: '',
})

const addComponent = () => {
    form.post(route('dashboard.products.addComponent', props.product.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}

const removeComponent = (componentId) => {
    if (confirm('Are you sure you want to remove this component?')) {
        router.delete(route('dashboard.products.removeComponent', [props.product.id, componentId]), {
            preserveScroll: true,
        })
    }
}

const calculateCost = () => {
    router.get(route('dashboard.products.calculateCost', props.product.id), {}, {
        preserveScroll: true,
    })
}

const multiplyPrecise = (a, b) => {
    // Преобразуем строки в числа
    const numA = parseFloat(a);
    const numB = parseFloat(b);

    // Умножаем числа
    const result = numA * numB;

    // Округляем до 6 знаков после запятой для избежания ошибок с плавающей точкой
    return Math.round(result * 1000000) / 1000000;
}

const roundToFirstNonZero = (number) => {
    if (typeof number === 'string') {
        number = parseFloat(number);
    }

    if (Number.isInteger(number)) {
        return number.toString();
    }

    const str = number.toFixed(6); // Фиксируем 6 знаков после запятой
    const [intPart, decPart] = str.split('.');
    if (!decPart) return intPart;

    let significantPart = '';
    for (let i = 0; i < decPart.length; i++) {
        significantPart += decPart[i];
        if (decPart[i] !== '0') {
            return `${intPart}.${significantPart}`;
        }
    }

    return intPart;
}

const totalCost = computed(() => {
    return props.product.components.reduce((sum, component) => {
        const componentCost = multiplyPrecise(component.material.cost_per_unit, component.quantity);
        return sum + componentCost;
    }, 0);
})
</script>
