<template>
    <div class="h-full flex flex-col">
        <!-- Поиск -->
        <div class="p-4 border-b dark:border-gray-700">
            <TextInput
                v-model="search"
                type="search"
                placeholder="Поиск диалогов..."
                class="w-full"
            />
        </div>

        <!-- Список -->
        <div class="flex-1 overflow-y-auto">
            <div 
                v-for="conversation in filteredConversations" 
                :key="conversation.id"
                @click="$emit('select', conversation)"
                :class="[
                    'p-4 border-b dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700',
                    { 
                        'bg-gray-50 dark:bg-gray-700': conversation.id === activeId,
                        'bg-blue-50 dark:bg-blue-900/20': conversation.unread_messages_count > 0
                    }
                ]"
            >
                <div class="flex justify-between items-start mb-1">
                    <h3 class="font-medium" :class="{
                        'text-gray-900 dark:text-white': !conversation.unread_messages_count,
                        'text-blue-600 dark:text-blue-400': conversation.unread_messages_count > 0
                    }">
                        {{ conversation.client?.full_name || 'Неизвестный клиент' }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <Badge 
                            v-if="conversation.unread_messages_count"
                            type="primary"
                            size="sm"
                            rounded
                        >
                            {{ conversation.unread_messages_count }}
                        </Badge>
                        <span class="text-sm text-gray-500">
                            {{ formatDate(conversation.last_message_at) }}
                        </span>
                    </div>
                </div>
                
                <div class="flex justify-between items-center">
                    <p class="text-sm" :class="{
                        'text-gray-600 dark:text-gray-300': !conversation.unread_messages_count,
                        'text-blue-600 dark:text-blue-400 font-medium': conversation.unread_messages_count > 0
                    }">
                        {{ conversation.last_message?.content || 'Нет сообщений' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    conversations: {
        type: Object,
        required: true
    },
    activeId: {
        type: Number,
        default: null
    }
});

const search = ref('');

const filteredConversations = computed(() => {
    if (!search.value) return props.conversations.data;
    
    const query = search.value.toLowerCase();
    return props.conversations.data.filter(conversation => 
        conversation.client?.full_name?.toLowerCase().includes(query) ||
        conversation.last_message?.content?.toLowerCase().includes(query)
    );
});

const formatDate = (date) => {
    return new Date(date).toLocaleString('ru', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>