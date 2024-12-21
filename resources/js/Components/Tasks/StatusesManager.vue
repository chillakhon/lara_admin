<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    statuses: Array
});

const form = useForm({
    name: '',
    color: '#6B7280',
    order: 0,
    is_default: false
});

const editingId = ref(null);

const submitForm = () => {
    if (editingId.value) {
        form.put(route('dashboard.task-statuses.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => resetForm()
        });
    } else {
        form.post(route('dashboard.task-statuses.store'), {
            preserveScroll: true,
            onSuccess: () => resetForm()
        });
    }
};

const editStatus = (status) => {
    editingId.value = status.id;
    form.name = status.name;
    form.color = status.color;
    form.order = status.order;
    form.is_default = status.is_default;
};

const resetForm = () => {
    editingId.value = null;
    form.reset();
};
</script>

<template>
    <div class="space-y-4">
        <!-- Форма добавления/редактирования -->
        <form @submit.prevent="submitForm" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <div class="space-y-4">
                <TextInput
                    v-model="form.name"
                    label="Название"
                    :error="form.errors.name"
                    required
                />

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Цвет
                    </label>
                    <div class="flex gap-2 mt-1">
                        <input
                            type="color"
                            v-model="form.color"
                            class="h-10 w-20"
                        />
                        <input
                            type="text"
                            v-model="form.color"
                            class="flex-1 rounded-md border-gray-300"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            v-model="form.is_default"
                            class="h-4 w-4 text-indigo-600 rounded"
                        />
                        <label class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            По умолчанию
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <PrimaryButton 
                            type="button" 
                            @click="resetForm"
                            variant="alternative"
                            v-if="editingId"
                        >
                            Отмена
                        </PrimaryButton>
                        <PrimaryButton type="submit" :loading="form.processing">
                            {{ editingId ? 'Сохранить' : 'Добавить' }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </form>

        <!-- Список статусов -->
        <div class="space-y-2">
            <div 
                v-for="status in statuses" 
                :key="status.id"
                class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm"
            >
                <div class="flex items-center gap-3">
                    <div 
                        class="w-6 h-6 rounded-full"
                        :style="{ backgroundColor: status.color }"
                    ></div>
                    <span class="font-medium">{{ status.name }}</span>
                    <span 
                        v-if="status.is_default"
                        class="text-xs text-green-600 dark:text-green-400"
                    >
                        По умолчанию
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        @click="editStatus(status)"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </button>
                    <button 
                        v-if="!status.is_default"
                        @click="$emit('delete', status.id)"
                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template> 