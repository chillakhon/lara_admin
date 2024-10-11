<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
    promoCodes: Array,
});

const form = useForm({
    code: '',
    discount_amount: '',
    discount_type: 'percentage',
    starts_at: '',
    expires_at: '',
    max_uses: '',
    is_active: true,
});

const editForm = useForm({
    id: '',
    code: '',
    discount_amount: '',
    discount_type: 'percentage',
    starts_at: '',
    expires_at: '',
    max_uses: '',
    is_active: true,
});

const deleteForm = useForm({});

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);

const openCreateModal = () => {
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    form.reset();
};

const createPromoCode = () => {
    form.post(route('dashboard.promo-codes.store'), {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
};

const openEditModal = (promoCode) => {
    editForm.id = promoCode.id;
    editForm.code = promoCode.code;
    editForm.discount_amount = promoCode.discount_amount;
    editForm.discount_type = promoCode.discount_type;
    editForm.starts_at = promoCode.starts_at;
    editForm.expires_at = promoCode.expires_at;
    editForm.max_uses = promoCode.max_uses;
    editForm.is_active = promoCode.is_active;
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editForm.reset();
};

const updatePromoCode = () => {
    editForm.put(route('dashboard.promo-codes.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
};

const openDeleteModal = (promoCode) => {
    deleteForm.id = promoCode.id;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    deleteForm.reset();
};

const deletePromoCode = () => {
    deleteForm.delete(route('dashboard.promo-codes.destroy', deleteForm.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};
const isUsageModalOpen = ref(false);
const currentPromoCodeUsage = ref(null);

const openUsageModal = async (promoCode) => {
    try {
        const response = await axios.get(route('dashboard.promo-codes.usage', promoCode.id));
        currentPromoCodeUsage.value = response.data;
        isUsageModalOpen.value = true;
    } catch (error) {
        console.error('Error fetching promo code usage:', error);
        // Здесь вы можете добавить обработку ошибок, например, показать уведомление пользователю
    }
};

const closeUsageModal = () => {
    isUsageModalOpen.value = false;
    currentPromoCodeUsage.value = null;
};
</script>

<template>
    <DashboardLayout >
        <template #content>
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <PrimaryButton @click="openCreateModal" class="mb-4">
                                Add Promo Code
                            </PrimaryButton>

                            <div class="mt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Promo Codes List</h3>
                                <div v-for="promoCode in promoCodes" :key="promoCode.id" class="mb-4 p-4 bg-gray-50 rounded-lg">
                                    <h3 class="text-lg font-medium text-gray-900">{{ promoCode.code }}</h3>
                                    <p class="text-sm text-gray-600">Discount: {{ promoCode.discount_amount }}{{ promoCode.discount_type === 'percentage' ? '%' : ' (fixed)' }}</p>
                                    <p class="text-sm text-gray-600">Valid: {{ promoCode.starts_at }} - {{ promoCode.expires_at }}</p>
                                    <p class="text-sm text-gray-600">Max uses: {{ promoCode.max_uses || 'Unlimited' }}</p>
                                    <p class="text-sm text-gray-600">Status: {{ promoCode.is_active ? 'Active' : 'Inactive' }}</p>
                                    <div class="mt-2">
                                        <PrimaryButton @click="openEditModal(promoCode)" class="mr-2">Edit</PrimaryButton>
                                        <SecondaryButton @click="openUsageModal(promoCode)" class="mr-2">View Usage</SecondaryButton>
                                        <DangerButton @click="openDeleteModal(promoCode)">Delete</DangerButton>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Modal -->
            <Modal :show="isCreateModalOpen" @close="closeCreateModal">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900">
                        Create Promo Code
                    </h2>

                    <form @submit.prevent="createPromoCode" class="mt-6">
                        <div>
                            <InputLabel for="code" value="Code"/>
                            <TextInput
                                id="code"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.code"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.code"/>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="discount_amount" value="Discount Amount"/>
                            <TextInput
                                id="discount_amount"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                                v-model="form.discount_amount"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.discount_amount"/>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="discount_type" value="Discount Type"/>
                            <select
                                id="discount_type"
                                class="mt-1 block w-full"
                                v-model="form.discount_type"
                                required
                            >
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.discount_type"/>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="starts_at" value="Start Date"/>
                            <TextInput
                                id="starts_at"
                                type="datetime-local"
                                class="mt-1 block w-full"
                                v-model="form.starts_at"
                            />
                            <InputError class="mt-2" :message="form.errors.starts_at"/>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="expires_at" value="Expiry Date"/>
                            <TextInput
                                id="expires_at"
                                type="datetime-local"
                                class="mt-1 block w-full"
                                v-model="form.expires_at"
                            />
                            <InputError class="mt-2" :message="form.errors.expires_at"/>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="max_uses" value="Max Uses"/>
                            <TextInput id="max_uses"
                                       type="number"
                                       class="mt-1 block w-full"
                                       v-model="form.max_uses"
                            />
                            <InputError class="mt-2" :message="form.errors.max_uses"/>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="is_active" class="inline-flex items-center">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    v-model="form.is_active"
                                >
                                <span class="ml-2">Is Active</span>
                            </InputLabel>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <SecondaryButton @click="closeCreateModal" class="mr-3">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Create
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- Edit Modal -->
            <Modal :show="isEditModalOpen" @close="closeEditModal">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900">
                        Edit Promo Code
                    </h2>

                    <form @submit.prevent="updatePromoCode" class="mt-6">
                        <div>
                            <InputLabel for="edit-code" value="Code"/>
                            <TextInput
                                id="edit-code"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="editForm.code"
                                required
                            />
                            <InputError class="mt-2" :message="editForm.errors.code"/>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="edit-discount_amount" value="Discount Amount"/>
                            <TextInput
                                id="edit-discount_amount"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                                v-model="editForm.discount_amount"
                                required
                            />
                            <InputError class="mt-2" :message="editForm.errors.discount_amount"/>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="edit-discount_type" value="Discount Type"/>
                            <select
                                id="edit-discount_type"
                                class="mt-1 block w-full"
                                v-model="editForm.discount_type"
                                required
                            >
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                            <InputError class="mt-2" :message="editForm.errors.discount_type"/>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="edit-starts_at" value="Start Date"/>
                            <TextInput
                                id="edit-starts_at"
                                type="datetime-local"
                                class="mt-1 block w-full"
                                v-model="editForm.starts_at"
                            />
                            <InputError class="mt-2" :message="editForm.errors.starts_at"/>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="edit-expires_at" value="Expiry Date"/>
                            <TextInput
                                id="edit-expires_at"
                                type="datetime-local"
                                class="mt-1 block w-full"
                                v-model="editForm.expires_at"
                            />
                            <InputError class="mt-2" :message="editForm.errors.expires_at"/>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="edit-max_uses" value="Max Uses"/>
                            <TextInput
                                id="edit-max_uses"
                                type="number"
                                class="mt-1 block w-full"
                                v-model="editForm.max_uses"
                            />
                            <InputError class="mt-2" :message="editForm.errors.max_uses"/>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="edit-is_active" class="inline-flex items-center">
                                <input
                                    id="edit-is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    v-model="editForm.is_active"
                                >
                                <span class="ml-2">Is Active</span>
                            </InputLabel>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <SecondaryButton @click="closeEditModal" class="mr-3">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton :class="{ 'opacity-25': editForm.processing }" :disabled="editForm.processing">
                                Update
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- Delete Confirmation Modal -->
            <Modal :show="isDeleteModalOpen" @close="closeDeleteModal">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900">
                        Delete Promo Code
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Are you sure you want to delete this promo code? This action cannot be undone.
                    </p>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="closeDeleteModal" class="mr-3">
                            Cancel
                        </SecondaryButton>
                        <DangerButton @click="deletePromoCode" :class="{ 'opacity-25': deleteForm.processing }" :disabled="deleteForm.processing">
                            Delete Promo Code
                        </DangerButton>
                    </div>
                </div>
            </Modal>

            <!-- Usage Modal -->
            <Modal :show="isUsageModalOpen" @close="closeUsageModal">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        Promo Code Usage
                    </h2>
                    <div v-if="currentPromoCodeUsage">
                        <p class="mb-2"><strong>Code:</strong> {{ currentPromoCodeUsage.code }}</p>
                        <p class="mb-2"><strong>Total Uses:</strong> {{ currentPromoCodeUsage.total_uses }}</p>
                        <h3 class="text-md font-medium text-gray-800 mt-4 mb-2">Usage Details:</h3>
                        <ul class="list-disc pl-5">
                            <li v-for="usage in currentPromoCodeUsage.usages" :key="usage.id" class="mb-2">
                                <p><strong>Order:</strong> {{ usage.order.order_number }}</p>
                                <p><strong>Client:</strong> {{ usage.client.name }}</p>
                                <p><strong>Discount Amount:</strong> ${{ usage.discount_amount }}</p>
                                <p><strong>Date:</strong> {{ new Date(usage.created_at).toLocaleDateString() }}</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </Modal>
        </template>
    </DashboardLayout>
</template>
