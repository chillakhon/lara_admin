<template>
    <div class="grid grid-cols-1 gap-6 mb-6 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="(stat, key) in stats" :key="key" 
             class="p-4 bg-white rounded-lg shadow-sm dark:bg-gray-800">
            <div class="flex items-center">
                <div :class="[
                    'flex-shrink-0 p-3 rounded-lg',
                    `bg-${stat.color}-100 dark:bg-${stat.color}-900`
                ]">
                    <svg class="w-6 h-6" 
                         :class="`text-${stat.color}-600 dark:text-${stat.color}-300`"
                         fill="none" 
                         stroke="currentColor" 
                         viewBox="0 0 24 24">
                        <path 
                            stroke-linecap="round" 
                            stroke-linejoin="round" 
                            stroke-width="2" 
                            :d="stat.icon"
                        />
                    </svg>
                </div>
                <div class="flex-1 ml-4">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ stat.label }}
                    </div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ stat.value }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    leads: {
        type: Array,
        required: true
    }
});

const stats = computed(() => ({
    new: {
        label: 'Новые заявки',
        value: props.leads.filter(l => l.status === 'new').length,
        color: 'blue',
        icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    },
    processing: {
        label: 'В обработке',
        value: props.leads.filter(l => l.status === 'processing').length,
        color: 'yellow',
        icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'
    },
    completed: {
        label: 'Завершенные',
        value: props.leads.filter(l => l.status === 'completed').length,
        color: 'green',
        icon: 'M5 13l4 4L19 7'
    },
    rejected: {
        label: 'Отклоненные',
        value: props.leads.filter(l => l.status === 'rejected').length,
        color: 'red',
        icon: 'M6 18L18 6M6 6l12 12'
    }
}));
</script> 