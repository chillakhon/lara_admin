<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, Link, useForm} from '@inertiajs/vue3';

const props = defineProps({
    rawMaterials: Array
})

const form = useForm({
    name: '',
    price: '',
    unit: ''
});
const submit = () => {
    form.post(route('dashboard.raw.store'));
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="mb-10">
                <h1>Add New Raw Material</h1>
                <form @submit.prevent="submit">
                    <div>
                        <label for="name">Name:</label>
                        <input id="name" v-model="form.name" type="text" required>
                    </div>
                    <div>
                        <label for="price">Price:</label>
                        <input id="price" v-model="form.price" type="number" step="0.01" required>
                    </div>
                    <div>
                        <label for="unit">Unit:</label>
                        <input id="unit" v-model="form.unit" type="text" required>
                    </div>
                    <button type="submit">Create Raw Material</button>
                </form>
            </div>
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <h1>Raw Materials</h1>
                <Link :href="route('dashboard.raw')">Add New Raw Material</Link>
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Unit</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="material in rawMaterials" :key="material.id">
                        <td>{{ material.name }}</td>
                        <td>{{ material.price }}</td>
                        <td>{{ material.unit }}</td>

                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
