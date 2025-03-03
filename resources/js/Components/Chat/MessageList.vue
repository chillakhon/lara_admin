<template>
    <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="messagesContainer">
        <div 
            v-for="message in messages" 
            :key="message.id"
            :class="[
                'max-w-[80%] rounded-lg p-3',
                message.direction === 'incoming' 
                    ? 'bg-gray-100 dark:bg-gray-700 mr-auto' 
                    : 'bg-blue-100 dark:bg-blue-900 ml-auto'
            ]"
        >
            <div class="flex items-center justify-between mb-1">
                <span class="text-sm font-medium">
                    {{ message.direction === 'incoming' ? 'Клиент' : 'Менеджер' }}
                </span>
                <div class="flex items-center gap-2">
                    <!-- Индикатор статуса -->
                    <span class="text-xs" :title="getStatusTitle(message.status)">
                        <CheckIcon v-if="message.status === 'read'" 
                            class="w-4 h-4 text-blue-500" />
                        <CheckIcon v-else-if="message.status === 'delivered'" 
                            class="w-4 h-4 text-gray-500" />
                        <ClockIcon v-else-if="message.status === 'sending'" 
                            class="w-4 h-4 text-gray-400 animate-spin" />
                        <ExclamationCircleIcon v-else-if="message.status === 'failed'" 
                            class="w-4 h-4 text-red-500" />
                    </span>
                    <span class="text-xs text-gray-500">
                        {{ formatDate(message.created_at) }}
                    </span>
                </div>
            </div>
            
            <p class="text-gray-900 dark:text-white">
                {{ message.content }}
            </p>

            <!-- Вложения -->
            <div v-if="message.attachments?.length" class="mt-2 space-y-2">
                <div 
                    v-for="attachment in message.attachments" 
                    :key="attachment.id"
                    class="flex items-center gap-2"
                >
                    <img 
                        v-if="isImage(attachment.mime_type)"
                        :src="attachment.url"
                        :alt="attachment.file_name"
                        class="max-w-xs rounded"
                    />
                    <a 
                        v-else
                        :href="attachment.url"
                        target="_blank"
                        class="text-blue-600 hover:underline"
                    >
                        {{ attachment.file_name }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { CheckIcon, ClockIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    messages: {
        type: Array,
        required: true
    }
});

const messagesContainer = ref(null);

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('ru', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const isImage = (mimeType) => {
    return mimeType?.startsWith('image/');
};

const getStatusTitle = (status) => ({
    'sending': 'Отправляется',
    'sent': 'Отправлено',
    'delivered': 'Доставлено',
    'read': 'Прочитано',
    'failed': 'Ошибка отправки'
})[status] || status;

onMounted(scrollToBottom);

watch(() => props.messages, scrollToBottom, { deep: true });
</script> 