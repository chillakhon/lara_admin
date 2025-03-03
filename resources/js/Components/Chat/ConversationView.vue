<template>
    <div class="h-full flex flex-col">
        <!-- Заголовок -->
        <div class="p-4 border-b dark:border-gray-700">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-medium text-gray-900 dark:text-white">
                        {{ conversation.client?.full_name || 'Неизвестный клиент' }}
                    </h2>
                    <p class="text-sm text-gray-500">
                        {{ getSourceLabel(conversation.source) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :type="getStatusType(conversation.status)">
                        {{ getStatusLabel(conversation.status) }}
                    </Badge>
                    <ContextMenu
                        :items="menuItems"
                        @item-click="handleMenuAction"
                        :id="'conversation-' + conversation.id"
                    />
                </div>
            </div>
        </div>

        <!-- Сообщения -->
        <MessageList 
            :messages="conversation.messages"
            class="flex-1"
        />

        <!-- Ввод сообщения -->
        <MessageInput
            v-model="messageText"
            :loading="sending"
            @send="sendMessage"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import Badge from '@/Components/Badge.vue';
import ContextMenu from '@/Components/ContextMenu.vue';
import MessageList from '@/Components/Chat/MessageList.vue';
import MessageInput from '@/Components/Chat/MessageInput.vue';

const props = defineProps({
    conversation: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['message-sent']);

const messageText = ref('');
const sending = ref(false);

const menuItems = [
    { text: 'Закрыть диалог', action: 'close' },
    { text: 'Назначить менеджера', action: 'assign' }
];

const getSourceLabel = (source) => ({
    telegram: 'Telegram',
    whatsapp: 'WhatsApp',
    web_chat: 'Веб-чат'
})[source] || source;

const getStatusType = (status) => ({
    new: 'blue',
    active: 'green',
    closed: 'gray'
})[status] || 'gray';

const getStatusLabel = (status) => ({
    new: 'Новый',
    active: 'Активный',
    closed: 'Закрыт'
})[status] || status;

const sendMessage = async () => {
    if (!messageText.value.trim()) return;
    
    sending.value = true;
    try {
        await axios.post(route('dashboard.conversations.reply', props.conversation.id), {
            content: messageText.value
        });
        
        // Очищаем поле ввода
        messageText.value = '';
        
        // Обновляем диалог для получения нового сообщения
        const response = await axios.get(route('dashboard.conversations.show', props.conversation.id));
        props.conversation.messages = response.data.conversation.messages;
        
        emit('message-sent');
    } catch (error) {
        console.error('Error sending message:', error);
        // Добавить уведомление об ошибке для пользователя
    } finally {
        sending.value = false;
    }
};

const handleMenuAction = async (item) => {
    if (item.action === 'close') {
        try {
            await axios.post(route('dashboard.conversations.close', conversation.id));
            // Обновить статус диалога
        } catch (error) {
            console.error('Error closing conversation:', error);
        }
    } else if (item.action === 'assign') {
        // Добавить логику назначения менеджера
    }
};
</script> 