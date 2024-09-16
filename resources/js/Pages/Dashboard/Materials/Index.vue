<!-- resources/js/Pages/Materials/Index.vue -->
<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Materials</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between mb-6">
                            <h3 class="text-lg font-semibold">Materials List</h3>
                            <PrimaryButton @click="openModal('create')">Add Material</PrimaryButton>
                        </div>

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="material in materials.data" :key="material.id">
                                <td class="px-6 py-4 whitespace-nowrap">{{ material.title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ material.unit_of_measurement }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ material.cost_per_unit }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <SecondaryButton class="mr-2" @click="openModal('edit', material)">Edit</SecondaryButton>
                                    <DangerButton @click="openModal('delete', material)">Delete</DangerButton>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <!-- Pagination -->
                        <div class="mt-6">
                            <Pagination :links="materials.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Create/Edit/Delete -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900" v-if="modalMode === 'create'">
                    Create Material
                </h2>
                <h2 class="text-lg font-medium text-gray-900" v-else-if="modalMode === 'edit'">
                    Edit Material
                </h2>
                <h2 class="text-lg font-medium text-gray-900" v-else-if="modalMode === 'delete'">
                    Delete Material
                </h2>

                <form @submit.prevent="submitForm" v-if="modalMode !== 'delete'">
                    <div class="mt-6">
                        <InputLabel for="title" value="Title" />
                        <TextInput
                            id="title"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.title"
                            required
                            autofocus
                        />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div class="mt-6">
                        <InputLabel for="unit_of_measurement" value="Unit of Measurement" />
                        <TextInput
                            id="unit_of_measurement"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.unit_of_measurement"
                            required
                        />
                        <InputError :message="form.errors.unit_of_measurement" class="mt-2" />
                    </div>

                    <div class="mt-6">
                        <InputLabel for="cost_per_unit" value="Cost per Unit" />
                        <TextInput
                            id="cost_per_unit"
                            type="number"
                            step="0.01"
                            class="mt-1 block w-full"
                            v-model="form.cost_per_unit"
                            required
                        />
                        <InputError :message="form.errors.cost_per_unit" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton class="mr-3" @click="closeModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                    </div>
                </form>

                <div v-else>
                    <p class="mt-1 text-sm text-gray-600">
                        Are you sure you want to delete this material?
                    </p>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton class="mr-3" @click="closeModal">Cancel</SecondaryButton>
                        <DangerButton :disabled="form.processing" @click="deleteMaterial">Delete</DangerButton>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Modal from '@/Components/Modal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import Pagination from "@/Components/Pagination.vue";

export default {
    components: {
        Pagination,
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
        materials: Object,
    },
    setup(props) {
        const showModal = ref(false)
        const modalMode = ref('')
        const form = useForm({
            id: null,
            title: '',
            unit_of_measurement: '',
            cost_per_unit: '',
        })

        const openModal = (mode, material = null) => {
            modalMode.value = mode
            if (mode === 'edit' || mode === 'delete') {
                form.id = material.id
                form.title = material.title
                form.unit_of_measurement = material.unit_of_measurement
                form.cost_per_unit = material.cost_per_unit
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
                form.post(route('dashboard.materials.store'), {
                    preserveScroll: true,
                    onSuccess: () => closeModal(),
                })
            } else if (modalMode.value === 'edit') {
                form.put(route('dashboard.materials.update', form.id), {
                    preserveScroll: true,
                    onSuccess: () => closeModal(),
                })
            }
        }

        const deleteMaterial = () => {
            form.delete(route('dashboard.materials.destroy', form.id), {
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
            deleteMaterial,
        }
    },
}
</script>
