<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                Диалоги
            </h1>
        </template>

        <div class="container mx-auto py-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                <div class="grid grid-cols-12 min-h-[600px]">
                    <!-- Список диалогов -->
                    <div class="col-span-4 border-r dark:border-gray-700">
                        <ConversationList 
                            :conversations="conversations"
                            :active-id="activeConversation?.id"
                            @select="selectConversation"
                        />
                    </div>

                    <!-- Область сообщений -->
                    <div class="col-span-8">
                        <template v-if="activeConversation">
                            <ConversationView 
                                :conversation="activeConversation"
                                @message-sent="handleMessageSent"
                            />
                        </template>
                        <div v-else class="h-full flex items-center justify-center text-gray-500">
                            Выберите диалог для просмотра
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import ConversationList from '@/Components/Chat/ConversationList.vue';
import ConversationView from '@/Components/Chat/ConversationView.vue';
import axios from 'axios';

const props = defineProps({
    conversations: {
        type: Object,
        required: true
    }
});

const breadCrumbs = [
    { name: 'Диалоги', link: route('dashboard.conversations.index') }
];

const activeConversation = ref(null);

const selectConversation = async (conversation) => {
    console.log('Selecting conversation:', conversation);
    try {
        const response = await axios.get(route('dashboard.conversations.show', conversation.id));
        console.log('Response:', response.data);
        activeConversation.value = response.data.conversation;
    } catch (error) {
        console.error('Error loading conversation:', error);
    }
};

const handleMessageSent = () => {
    // Обновление списка сообщений
};
</script>