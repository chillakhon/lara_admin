<template>
    <div class="relative">
        <button
            @click="toggleMenu"
            class="inline-flex items-center p-0.5 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"
            type="button"
        >
            <svg
                class="w-5 h-5"
                aria-hidden="true"
                fill="currentColor"
                viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"
                />
            </svg>
        </button>
        <div
            v-if="isOpen"
            class="absolute right-0 z-10 w-44 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600"
        >
            <ul
                class="py-1 text-sm text-gray-700 dark:text-gray-200"
                :aria-labelledby="`${id}-dropdown-button`"
            >
                <li v-for="item in regularItems" :key="item.text">
                    <a @click.prevent="handleItemClick(item)" >
                        {{ item.text }}
                    </a>
                </li>
            </ul>
            <div v-if="dangerousItems.length" class="py-1">
                <a
                    v-for="item in dangerousItems"
                    :key="item.text"
                    href="#"
                    @click.prevent="handleItemClick(item)"
                    class="block py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-red-500 dark:hover:text-red-400"
                >
                    {{ item.text }}
                </a>
            </div>
        </div>
    </div>
</template>

<script>
import {ref, onMounted, onUnmounted, computed} from 'vue';

export default {
    name: 'ContextMenu',
    props: {
        items: {
            type: Array,
            required: true
        },
        id: {
            type: String,
            required: true
        },
        contextData: {
            type: Object,
            default: () => ({})
        }
    },
    setup(props, {emit}) {
        const isOpen = ref(false);
        const menuRef = ref(null);

        const regularItems = computed(() => props.items.filter(item => !item.isDangerous));
        const dangerousItems = computed(() => props.items.filter(item => item.isDangerous));

        const toggleMenu = () => {
            isOpen.value = !isOpen.value;
        };

        const handleItemClick = (item) => {
            emit('item-click', item, props.contextData);
            isOpen.value = false;
        };

        const handleClickOutside = (event) => {
            if (menuRef.value && !menuRef.value.contains(event.target)) {
                isOpen.value = false;
            }
        };

        onMounted(() => {
            document.addEventListener('click', handleClickOutside);
        });

        onUnmounted(() => {
            document.removeEventListener('click', handleClickOutside);
        });

        return {
            isOpen,
            menuRef,
            regularItems,
            dangerousItems,
            toggleMenu,
            handleItemClick
        };
    }
};
</script>
