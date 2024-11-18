<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Modal from '@/Components/Modal.vue';
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";

const props = defineProps({
    batches: Object,
    recipes: Array,
    units: Array
});

// Состояния модальных окон
const showDetailsModal = ref(false);
const showStartModal = ref(false);
const showCompleteModal = ref(false);
const showCancelModal = ref(false);
const selectedBatch = ref(null);

// Фильтры
const filters = ref({
    status: '',
    dateFrom: '',
    dateTo: ''
});

// Форма завершения производства
const completionForm = useForm({
    actual_quantity: 0,
    additional_costs: 0,
    notes: ''
});

// Форма отмены производства
const cancelForm = useForm({
    reason: ''
});

const statusOptions = [
    { label: 'Все статусы', value: '' },
    { label: 'Запланировано', value: 'planned' },
    { label: 'В процессе', value: 'in_progress' },
    { label: 'Завершено', value: 'completed' },
    { label: 'Отменено', value: 'cancelled' }
];

const filteredBatches = computed(() => {
    // Обращаемся к data внутри объекта пагинации
    return props.batches.data.filter(batch => {
        if (filters.value.status && batch.status !== filters.value.status) {
            return false;
        }
        if (filters.value.dateFrom && new Date(batch.planned_start_date) < new Date(filters.value.dateFrom)) {
            return false;
        }
        if (filters.value.dateTo && new Date(batch.planned_start_date) > new Date(filters.value.dateTo)) {
            return false;
        }
        return true;
    });
});

const showBatchDetails = (batch) => {
    selectedBatch.value = batch;
    showDetailsModal.value = true;
};

const startBatch = (batch) => {
    selectedBatch.value = batch;
    showStartModal.value = true;
    handleBatchStart();
};

const handleBatchStart = () => {
    axios.post(route('dashboard.production.start', selectedBatch.value.id))
        .then(() => {
            showStartModal.value = false;
            // Обновление данных
        })
        .catch(error => {
            console.error('Error starting batch:', error);
        });
};

const completeBatch = (batch) => {
    selectedBatch.value = batch;
    completionForm.actual_quantity = batch.planned_quantity;
    showCompleteModal.value = true;
};

const handleBatchCompletion = () => {
    completionForm.post(route('dashboard.production.complete', selectedBatch.value.id), {
        onSuccess: () => {
            showCompleteModal.value = false;
            completionForm.reset();
        }
    });
};

const cancelBatch = (batch) => {
    selectedBatch.value = batch;
    showCancelModal.value = true;
};

const handleBatchCancellation = () => {
    cancelForm.post(route('dashboard.production.cancel', selectedBatch.value.id), {
        onSuccess: () => {
            showCancelModal.value = false;
            cancelForm.reset();
        }
    });
};

const getStatusClass = (status) => {
    const classes = {
        'planned': 'bg-blue-100 text-blue-800',
        'in_progress': 'bg-yellow-100 text-yellow-800',
        'completed': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800',
        'failed': 'bg-gray-100 text-gray-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        'planned': 'Запланировано',
        'in_progress': 'В производстве',
        'completed': 'Завершено',
        'cancelled': 'Отменено',
        'failed': 'Ошибка'
    };
    return labels[status] || status;
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                Производственные партии
            </h1>
        </template>

        <div class="py-4">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Фильтры -->
                        <div class="flex space-x-4 mb-4">
                            <div class="w-1/4">
                                <select
                                    v-model="filters.status"
                                    class="w-full rounded-lg"
                                >
                                    <option
                                        v-for="option in statusOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex space-x-2">
                                <input
                                    type="date"
                                    v-model="filters.dateFrom"
                                    class="rounded-lg"
                                />
                                <span class="self-center">-</span>
                                <input
                                    type="date"
                                    v-model="filters.dateTo"
                                    class="rounded-lg"
                                />
                            </div>
                        </div>

                        <!-- Таблица партий -->
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                            <tr>
                                <th>Номер партии</th>
                                <th>Продукт</th>
                                <th>Количество</th>
                                <th>Статус</th>
                                <th>Дата начала</th>
                                <th>Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="batch in filteredBatches" :key="batch.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4">{{ batch.batch_number }}</td>
                                <td class="px-6 py-4">{{ batch.recipe.product_variant.name }}</td>
                                <td class="px-6 py-4">
                                    {{ batch.actual_quantity || batch.planned_quantity }}
                                    {{ batch.recipe.output_unit.name }}
                                </td>
                                <td class="px-6 py-4">
                                       <span :class="getStatusClass(batch.status)"
                                             class="px-2 py-1 rounded-full text-sm">
                                           {{ batch.status }}
                                       </span>
                                </td>
                                <td class="px-6 py-4">
                                    {{ new Date(batch.planned_start_date).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <PrimaryButton @click="showBatchDetails(batch)"
                                                class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-eye"></i> Детали
                                        </PrimaryButton>
                                        <PrimaryButton v-if="batch.status === 'planned'"
                                                @click="startBatch(batch)"
                                                class="text-green-600 hover:text-green-800">
                                            <i class="fas fa-play"></i> Запустить
                                        </PrimaryButton>
                                        <PrimaryButton v-if="batch.status === 'in_progress'"
                                                @click="completeBatch(batch)"
                                                class="text-green-600 hover:text-green-800">
                                            <i class="fas fa-check"></i> Завершить
                                        </PrimaryButton>
                                        <PrimaryButton type="red" v-if="['planned', 'in_progress'].includes(batch.status)"
                                                @click="cancelBatch(batch)"
                                                class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-times"></i> Отменить
                                        </PrimaryButton>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальные окна -->
        <!-- Детали партии -->
        <Modal :show="showDetailsModal" @close="showDetailsModal = false">
            <template #title>Детали производственной партии</template>
            <template #content>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h3 class="font-medium">Номер партии</h3>
                            <p>{{ selectedBatch?.batch_number }}</p>
                        </div>
                        <div>
                            <h3 class="font-medium">Статус</h3>
                            <span :class="getStatusClass(selectedBatch?.status)"
                                  class="px-2 py-1 rounded-full text-sm">
                               {{ selectedBatch?.status }}
                           </span>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-medium">Рецепт</h3>
                        <p>{{ selectedBatch?.recipe.name }}</p>
                    </div>
                    <!-- Другие детали -->
                </div>
            </template>
            <template #footer>
                <PrimaryButton @click="showDetailsModal = false">
                    Закрыть
                </PrimaryButton>
            </template>
        </Modal>

        <!-- Завершение партии -->
        <Modal :show="showCompleteModal" @close="showCompleteModal = false">
            <template #title>Завершить производственную партию</template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <TextInput
                            label="Фактическое количество"
                            id="actual_quantity"
                            v-model="completionForm.actual_quantity"
                            type="number"
                            step="0.001"
                            class="mt-1 block w-full"
                            required
                        />
                    </div>
                    <div>
                        <TextInput
                            label="Дополнительные затраты"
                            id="additional_costs"
                            v-model="completionForm.additional_costs"
                            type="number"
                            step="0.01"
                            class="mt-1 block w-full"
                        />
                    </div>
                    <div>
                        <InputLabel for="completion_notes" value="Примечания"/>
                        <textarea
                            id="completion_notes"
                            v-model="completionForm.notes"
                            class="mt-1 block w-full rounded-md"
                            rows="3"
                        ></textarea>
                    </div>
                </div>
            </template>
            <template #footer>
                <PrimaryButton type="red" @click="showCompleteModal = false">
                    Отмена
                </PrimaryButton>
                <PrimaryButton
                    @click="handleBatchCompletion"
                    :disabled="completionForm.processing"
                >
                    Завершить партию
                </PrimaryButton>
            </template>
        </Modal>

        <!-- Отмена партии -->
        <Modal :show="showCancelModal" @close="showCancelModal = false">
            <template #title>Отменить производственную партию</template>
            <template #content>
                <div>
                    <InputLabel for="cancel_reason" value="Причина отмены"/>
                    <textarea
                        id="cancel_reason"
                        v-model="cancelForm.reason"
                        class="mt-1 block w-full rounded-md"
                        rows="3"
                        required
                    ></textarea>
                </div>
            </template>
            <template #footer>
                <PrimaryButton type="alternative" @click="showCancelModal = false">
                    Нет
                </PrimaryButton>
                <PrimaryButton type="red"
                    @click="handleBatchCancellation"
                    :disabled="cancelForm.processing"
                >
                    Да, отменить
                </PrimaryButton>
            </template>
        </Modal>
    </DashboardLayout>
</template>
