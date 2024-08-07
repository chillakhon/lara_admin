<!-- resources/js/Pages/Products/Index.vue -->
<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Products</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between mb-6">
                            <h3 class="text-lg font-semibold">Products List</h3>
                            <PrimaryButton @click="openModal('create')">Add Product</PrimaryButton>
                        </div>

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Description
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="product in products.data" :key="product.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <NavLink :href="route('dashboard.products.show', product.id)" >
                                        {{ product.name}}
                                    </NavLink>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ product.description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <SecondaryButton class="mr-2" @click="openModal('edit', product)">Edit
                                    </SecondaryButton>
                                    <DangerButton @click="openModal('delete', product)">Delete</DangerButton>
                                </td>
                            </tr>
                            </tbody>
                        </table>


                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Create/Edit/Delete -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900" v-if="modalMode === 'create'">
                    Create Product
                </h2>
                <h2 class="text-lg font-medium text-gray-900" v-else-if="modalMode === 'edit'">
                    Edit Product
                </h2>
                <h2 class="text-lg font-medium text-gray-900" v-else-if="modalMode === 'delete'">
                    Delete Product
                </h2>

                <form @submit.prevent="submitForm" v-if="modalMode !== 'delete'">
                    <div class="mt-6">
                        <InputLabel for="name" value="Name"/>
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autofocus
                        />
                        <InputError :message="form.errors.name" class="mt-2"/>
                    </div>

                    <div class="mt-6">
                        <InputLabel for="description" value="Description"/>
                        <textarea
                            id="description"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                            v-model="form.description"
                            rows="3"
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-2"/>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton class="mr-3" @click="closeModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                    </div>
                </form>

                <div v-else>
                    <p class="mt-1 text-sm text-gray-600">
                        Are you sure you want to delete this product?
                    </p>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton class="mr-3" @click="closeModal">Cancel</SecondaryButton>
                        <DangerButton :disabled="form.processing" @click="deleteProduct">Delete</DangerButton>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script>
import {ref} from 'vue'
import {useForm} from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Modal from '@/Components/Modal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import NavLink from "@/Components/NavLink.vue";

export default {
    components: {
        NavLink,
        AuthenticatedLayout,
        Modal,
        InputLabel,
        TextInput,
        InputError,
        PrimaryButton,
        SecondaryButton,
        DangerButton,
    },
    props: {
        products: Object,
    },
    setup(props) {
        const showModal = ref(false)
        const modalMode = ref('')
        const form = useForm({
            id: null,
            name: '',
            description: '',
        })

        const openModal = (mode, product = null) => {
            modalMode.value = mode
            if (mode === 'edit' || mode === 'delete') {
                form.id = product.id
                form.name = product.name
                form.description = product.description
            } else {
                form.reset()
            }
            showModal.value = true
        }

        const closeModal = () => {
            showModal.value = false
            form.reset()
        }

        const submitForm = () => {
            if (modalMode.value === 'create') {
                form.post(route('dashboard.products.store'), {
                    preserveScroll: true,
                    onSuccess: () => closeModal(),
                })
            } else if (modalMode.value === 'edit') {
                form.put(route('dashboard.products.update', form.id), {
                    preserveScroll: true,
                    onSuccess: () => closeModal(),
                })
            }
        }

        const deleteProduct = () => {
            form.delete(route('dashboard.products.destroy', form.id), {
                preserveScroll: true,
                onSuccess: () => closeModal(),
            })
        }

        return {
            showModal,
            modalMode,
            form,
            openModal,
            closeModal,
            submitForm,
            deleteProduct,
        }
    },
}
</script>
