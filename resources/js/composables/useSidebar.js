import { ref, watch } from 'vue';

const isCollapsed = ref(JSON.parse(localStorage.getItem('isSidebarCollapsed')) || false);

watch(isCollapsed, (newValue) => {
    localStorage.setItem('isSidebarCollapsed', JSON.stringify(newValue));
});

export function useSidebar() {
    return { isCollapsed };
} 