<script setup>
import DropDownItem from '@/Components/Sidebar/DropDownItem.vue';
import { 
    HomeIcon,
    Squares2X2Icon,
    CubeIcon,
    BeakerIcon,
    TagIcon,
    BuildingStorefrontIcon,
    UserGroupIcon,
    ClipboardDocumentListIcon,
    ShoppingCartIcon,
    TicketIcon,
    DocumentTextIcon,
    UsersIcon,
    ClipboardDocumentCheckIcon,
    ChatBubbleLeftRightIcon,
    Cog6ToothIcon,
    MegaphoneIcon,
    ChevronLeftIcon,
    ChevronRightIcon
} from '@heroicons/vue/24/outline';
import { useSidebar } from '@/composables/useSidebar';

const { isCollapsed } = useSidebar();

const menuItems = [
    {
        title: 'Обзор',
        route: 'dashboard',
        icon: HomeIcon,
        type: 'link'
    },
    {
        title: 'Базовое',
        type: 'dropdown',
        icon: Squares2X2Icon,
        links: [
            { title: 'Материалы', route: 'dashboard.materials.index' },
            { title: 'Опции', route: 'dashboard.options.index' }
        ]
    },
    {
        title: 'Ресурсы',
        type: 'dropdown',
        icon: CubeIcon,
        links: [
            { title: 'Склад', route: 'dashboard.inventory.index' },
            { title: 'Движения', route: 'dashboard.inventory.transactions' },
            { title: 'Инвентаризации', route: 'inventory-audits.index' }
        ]
    },
    {
        title: 'Производство',
        type: 'dropdown',
        icon: BeakerIcon,
        links: [
            { title: 'Рецепты', route: 'dashboard.recipes.index' },
            { title: 'Производственные партии', route: 'dashboard.production.index' },
            { title: 'Статистика', route: 'dashboard.production.statistics' }
        ]
    },
    {
        title: 'Категории',
        route: 'dashboard.categories.index',
        icon: TagIcon,
        type: 'link'
    },
    {
        title: 'Товары',
        route: 'dashboard.products.index',
        icon: BuildingStorefrontIcon,
        type: 'link'
    },
    {
        title: 'Клиенты',
        route: 'dashboard.clients.index',
        icon: UserGroupIcon,
        type: 'link'
    },
    {
        title: 'Заявки',
        type: 'dropdown',
        icon: ClipboardDocumentListIcon,
        links: [
            { title: 'Все заявки', route: 'dashboard.leads.index' },
            { title: 'Типы заявок', route: 'dashboard.lead-types.index' }
        ]
    },
    {
        title: 'Заказы',
        route: 'dashboard.orders.index',
        icon: ShoppingCartIcon,
        type: 'link'
    },
    {
        title: 'Акции и скидки',
        type: 'dropdown',
        icon: TicketIcon,
        links: [
            { title: 'Промокоды', route: 'dashboard.promo-codes.index' },
            { title: 'Уровни клиентов', route: 'dashboard.client-levels.index' }
        ]
    },
    {
        title: 'Контент',
        type: 'dropdown',
        icon: DocumentTextIcon,
        links: [
            { title: 'Страницы', route: 'dashboard.content.index' }
        ]
    },
    {
        title: 'Пользователи',
        route: 'dashboard.users.index',
        icon: UsersIcon,
        type: 'link'
    },
    {
        title: 'Задачи',
        type: 'dropdown',
        icon: ClipboardDocumentCheckIcon,
        links: [
            { title: 'Все задачи', route: 'dashboard.tasks.index' },
            { title: 'Настройки задач', route: 'dashboard.task-statuses.index' }
        ]
    },
    {
        title: 'Коммуникации',
        type: 'dropdown',
        icon: ChatBubbleLeftRightIcon,
        links: [
            { title: 'Диалоги', route: 'dashboard.conversations.index' },
            { title: 'Отзывы', route: 'dashboard.reviews.index' }
        ]
    },
    {
        title: 'Настройки',
        type: 'dropdown',
        icon: Cog6ToothIcon,
        links: [
            { title: 'Общие настройки', route: 'dashboard.settings.general' },
            { title: 'Интеграции', route: 'dashboard.settings.integrations' },
            { title: 'API ключи', route: 'dashboard.settings.api-keys' },
            { title: 'Уведомления', route: 'dashboard.settings.notifications' },
            { title: 'Платежные системы', route: 'dashboard.settings.payment' },
            { title: 'Службы доставки', route: 'dashboard.settings.delivery' }
        ]
    },
    {
        title: 'Маркетинг',
        type: 'dropdown',
        icon: MegaphoneIcon,
        links: [
            { title: 'Скидки', route: 'dashboard.discounts.index' },
            { title: 'Промокоды', route: 'dashboard.promo-codes.index' }
        ]
    }
];
</script>

<template>
    <aside
        id="sidebar"
        :class="[
            'flex fixed top-0 left-0 z-20 flex-col flex-shrink-0 pt-16 h-full duration-300 lg:flex transition-all',
            isCollapsed ? 'w-20' : 'w-64'
        ]"
        aria-label="Sidebar"
    >
        

        <div class="flex relative flex-col flex-1 pt-0 min-h-0 bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex flex-col flex-1 pt-5 pb-4">
                <div class="flex-1 px-3 space-y-1 bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <ul class="pb-2 space-y-2">
                        <li v-for="item in menuItems" :key="item.title">
                            <a
                                v-if="item.type === 'link'"
                                :href="route(item.route)"
                                :class="[
                                    'flex items-center  p-2 text-base font-normal rounded-lg transition-colors duration-200',
                                    'hover:bg-primary-50 dark:hover:bg-primary-900/20 group',
                                    route().current(item.route) 
                                        ? 'bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400'
                                        : 'text-gray-900 dark:text-gray-200',
                                    isCollapsed ? 'justify-center' : 'justify-start'
                                ]"
                                :title="isCollapsed ? item.title : ''"
                            >
                                <component 
                                    :is="item.icon"
                                    :class="[
                                        'w-6 h-6 transition duration-75',
                                        route().current(item.route)
                                            ? 'text-primary-600 dark:text-primary-400'
                                            : 'text-gray-500 group-hover:text-primary-600 dark:text-gray-400 dark:group-hover:text-primary-400'
                                    ]"
                                />
                                <span 
                                    :class="[
                                        'ml-3 whitespace-nowrap transition-opacity duration-300',
                                        isCollapsed ? 'opacity-0 hidden' : 'opacity-100'
                                    ]"
                                >
                                    {{ item.title }}
                                </span>
                            </a>

                            <DropDownItem
                                v-else-if="item.type === 'dropdown'"
                                :title="item.title"
                                :links="item.links"
                                :collapsed="isCollapsed"
                            >
                                <template #prefix>
                                    <component 
                                        :is="item.icon"
                                        class="w-6 h-6 text-gray-500 transition duration-75 group-hover:text-primary-600 dark:text-gray-400 dark:group-hover:text-primary-400"
                                    />
                                </template>
                            </DropDownItem>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.scrollbar-thin {
    scrollbar-width: thin;
}

.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #CBD5E0;
    border-radius: 3px;
}

.dark .scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #4B5563;
}

a:hover, button:hover {
    animation: menuItemHover 0.3s ease-in-out;
}
</style>
