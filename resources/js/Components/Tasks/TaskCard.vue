<script setup>
import { computed } from 'vue';
import { formatDistanceToNow } from 'date-fns';
import { ru } from 'date-fns/locale';

const props = defineProps({
    task: {
        type: Object,
        required: true
    }
});

const dueDate = computed(() => {
    if (!props.task.due_date) return null;
    const date = new Date(props.task.due_date);
    return formatDistanceToNow(date, { addSuffix: true, locale: ru });
});

const emit = defineEmits(['edit', 'delete', 'view']);
</script>

<template>
    <div class="flex flex-col p-5 max-w-md bg-white rounded-lg shadow transform cursor-move dark:bg-gray-800"
        @click="$emit('view', task)"
    >
        <div class="flex justify-between items-center pb-4">
            <div class="text-base font-semibold text-gray-900 dark:text-white">
                {{ task.title }}
            </div>
            <div class="flex gap-2">
                <button 
                    @click.stop="$emit('edit', task)"
                    class="p-2 text-sm text-gray-500 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"/>
                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                    </svg>
                </button>
                <button 
                    @click.stop="$emit('delete', task.id)"
                    class="p-2 text-sm text-red-500 rounded-lg hover:bg-gray-100 dark:text-red-400 dark:hover:bg-gray-700"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex flex-col">
            <div class="pb-4 text-sm font-normal text-gray-700 dark:text-gray-400">
                {{ task.description }}
            </div>

            <div class="flex flex-wrap gap-2 mb-4">
                <span 
                    v-for="label in task.labels" 
                    :key="label.id"
                    class="px-2 py-1 text-xs font-medium rounded-full"
                    :style="{ backgroundColor: label.color, color: 'white' }"
                >
                    {{ label.name }}
                </span>
            </div>

            <div class="flex justify-between items-center">
                <div class="flex -space-x-2">
                    <template v-if="task.assignee">
                        <div class="flex items-center">
                            <div class="w-7 h-7 rounded-full bg-gray-200 flex items-center justify-center text-sm font-medium">
                                {{ task.assignee.name?.[0] || task.assignee.email[0] }}
                            </div>
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ task.assignee.name || task.assignee.email }}
                            </span>
                        </div>
                    </template>
                </div>

                <div v-if="dueDate" class="flex items-center px-3 py-1 text-sm font-medium rounded-lg"
                    :class="task.isOverdue ? 'text-red-800 bg-red-100' : 'text-purple-800 bg-purple-100'"
                >
                    <svg class="mr-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                    </svg>
                    {{ dueDate }}
                </div>
            </div>
        </div>
    </div>
</template> 