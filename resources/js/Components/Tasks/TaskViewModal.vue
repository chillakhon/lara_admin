<script setup>
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import { formatDistanceToNow } from 'date-fns';
import { ru } from 'date-fns/locale';
import SelectDropdown from '@/Components/SelectDropdown.vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    task: Object,
    currentUser: Object,
    statuses: Array
});

const emit = defineEmits(['close', 'update']);

const newComment = ref('');

const formatDate = (date) => {
    if (!date) return '';
    return formatDistanceToNow(new Date(date), { addSuffix: true, locale: ru });
};

const canChangeStatus = computed(() => {
    return props.task?.assignee_id === props.currentUser?.id;
});

const updateStatus = async (statusId) => {
    try {
        await axios.put(route('dashboard.tasks.update', props.task.id), {
            status_id: statusId,
            title: props.task.title,
            description: props.task.description,
            priority_id: props.task.priority_id,
            assignee_id: props.task.assignee_id,
            due_date: props.task.due_date,
            labels: props.task.labels.map(l => l.id)
        });
        emit('update');
    } catch (error) {
        console.error('Error updating task status:', error);
    }
};

const submitComment = async () => {
    if (!newComment.trim()) return;

    try {
        const response = await axios.post(route('dashboard.tasks.comments.store', props.task.id), {
            content: newComment.value
        });
        
        // Добавляем новый комментарий в список
        if (!props.task.comments) {
            props.task.comments = [];
        }
        props.task.comments.push(response.data.comment);
        
        // Очищаем поле комментария
        newComment.value = '';
        
        // Обновляем задачу
        emit('update');
    } catch (error) {
        console.error('Error submitting comment:', error);
        // Здесь можно добавить обработку ошибки, например показать уведомление
    }
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')" max-width="2xl">
        <template #title>
            Просмотр задачи
        </template>
        <!-- Modal body -->
        <template #content>
            <div class="p-4 md:p-6" v-if="task">
            <div class="flex justify-between items-center mb-3">
                <div class="text-2xl font-semibold leading-none text-gray-900 dark:text-white">
                    {{ task.title }}
                </div>
                
                <!-- Добавляем выпадающий список для смены статуса -->
                <div v-if="canChangeStatus" class="w-48">
                    <SelectDropdown
                        v-model="task.status_id"
                        :options="statuses"
                        label=""
                        @update:modelValue="updateStatus"
                    >
                        <template #option="{ option: status }">
                            <div class="flex items-center gap-2">
                                <span 
                                    class="w-3 h-3 rounded-full"
                                    :style="{ backgroundColor: status.color }"
                                ></span>
                                {{ status.name }}
                            </div>
                        </template>
                    </SelectDropdown>
                </div>
            </div>

            <div class="flex flex-col justify-center items-start mb-5 space-y-3">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Создал <span class="text-primary-700">{{ task.creator?.name }}</span>, 
                    {{ formatDate(task.created_at) }}
                </div>

                <!-- Метки -->
                <div class="flex flex-wrap gap-2">
                    <span 
                        v-for="label in task.labels" 
                        :key="label.id"
                        class="px-2 py-1 text-xs font-medium rounded-full text-white"
                        :style="{ backgroundColor: label.color }"
                    >
                        {{ label.name }}
                    </span>
                </div>

                <!-- Исполнитель -->
                <div v-if="task.assignee" class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-gray-200 flex items-center justify-center">
                        {{ task.assignee.name?.[0] || task.assignee.email[0] }}
                    </div>
                    <span class="text-gray-600 dark:text-gray-400">
                        {{ task.assignee.name || task.assignee.email }}
                    </span>
                </div>
            </div>

            <!-- Описание -->
            <div class="mb-6">
                <div class="text-lg font-semibold mb-2">Описание</div>
                <div class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                    {{ task.description || 'Описание отсутствует' }}
                </div>
            </div>

            <!-- Комментарии -->
            <div class="mt-8">
                <h3 class="text-lg font-semibold mb-4">Комментарии</h3>
                
                <!-- Форма добавления комментария -->
                <div class="mb-6">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="p-4">
                            <textarea
                                v-model="newComment"
                                rows="3"
                                class="w-full bg-transparent border-0 focus:ring-0 text-gray-900 dark:text-white placeholder-gray-500"
                                placeholder="Написать комментарий..."
                            ></textarea>
                        </div>
                        <div class="flex items-center justify-between px-4 py-3 border-t dark:border-gray-600">
                            <div class="flex items-center space-x-2">
                                <button type="button" class="p-2 text-gray-500 rounded cursor-pointer hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-600">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                                <button type="button" class="p-2 text-gray-500 rounded cursor-pointer hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-600">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                            <button
                                @click="submitComment"
                                :disabled="!newComment.trim()"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-200 disabled:opacity-50"
                            >
                                Отправить
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Список комментариев -->
                <div class="space-y-4">
                    <div v-for="comment in task.comments" :key="comment.id" class="flex space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                {{ comment.user.name ? comment.user.name[0] : comment.user.email[0] }}
                            </div>
                        </div>
                        <div class="flex-grow">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ comment.user.name || comment.user.email }}
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(comment.created_at) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button 
                                            v-if="comment.user_id === currentUser.id"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                    {{ comment.content }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Если нет комментариев -->
                <div v-if="!task.comments?.length" class="text-center text-gray-500 dark:text-gray-400 py-8">
                    Нет комментариев
                </div>
            </div>
        </div>
        </template>
    </Modal>
</template> 