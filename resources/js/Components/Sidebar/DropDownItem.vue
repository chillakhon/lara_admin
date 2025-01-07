<script setup>
import { ref, watch, onBeforeUnmount } from "vue";

const props = defineProps({
    title: String,
    links: Array,
    collapsed: {
        type: Boolean,
        default: false
    }
});

const isOpen = ref(false);
const isHovered = ref(false);
const closeTimeout = ref(null);

const toggleDropDown = () => {
    if (!props.collapsed) {
        isOpen.value = !isOpen.value;
    }
};

// Закрываем выпадающее меню при сворачивании сайдбара
watch(() => props.collapsed, (newValue) => {
    if (newValue) {
        isOpen.value = false;
    }
});

const handleMouseEnter = () => {
    if (closeTimeout.value) {
        clearTimeout(closeTimeout.value);
        closeTimeout.value = null;
    }
    isHovered.value = true;
};

const handleMouseLeave = () => {
    closeTimeout.value = setTimeout(() => {
        isHovered.value = false;
    }, 300); // Задержка в 300мс
};

// Очистка таймера при уничтожении компонента
onBeforeUnmount(() => {
    if (closeTimeout.value) {
        clearTimeout(closeTimeout.value);
    }
});
</script>

<template>
    <div class="relative" @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave">
        <button type="button" @click="toggleDropDown"
                class="flex items-center justify-center p-2 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
            <slot name="prefix"></slot>
            <span v-if="!collapsed" class="flex-1 ml-3 text-left whitespace-nowrap">{{ title }}</span>
            <svg v-if="!collapsed" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd"
                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                      clip-rule="evenodd"></path>
            </svg>
        </button>

        <!-- Выпадающее меню для развернутого состояния -->
        <ul v-if="isOpen && !collapsed" class="py-2 space-y-2">
            <li v-for="link in links" :key="link.title">
                <a :href="route(link.route)"
                   :class="route().current(link.route) ? 'bg-gray-100' : ''"
                   class="flex items-center p-2 pl-11 text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ link.title }}
                </a>
            </li>
        </ul>

        <!-- Контекстное меню для свернутого состояния -->
        <div v-if="collapsed && isHovered"
             :id="`dropdown-${title}`"
             class="fixed ml-2 w-48 py-2 bg-white rounded-lg shadow-lg border border-gray-200 dark:bg-gray-800 dark:border-gray-700 z-50"
             :style="{ left: '5rem', top: $el.getBoundingClientRect().top + 'px' }"
             @mouseenter="handleMouseEnter"
             @mouseleave="handleMouseLeave">
            <div class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700">
                {{ title }}
            </div>
            <ul class="py-2">
                <li v-for="link in links" :key="link.title">
                    <a :href="route(link.route)"
                       :class="route().current(link.route) ? 'bg-gray-100' : ''"
                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        {{ link.title }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</template>

<style scoped>
/* Анимация появления контекстного меню */
.fixed {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style>