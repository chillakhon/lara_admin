<script setup>

import Navbar from "@/Components/NavBar/Navbar.vue";
import Sidebar from "@/Components/Sidebar/Sidebar.vue";
import { useSidebar } from '@/composables/useSidebar';

const props = defineProps({
    title: String
})

const { isCollapsed } = useSidebar();
</script>

<template>
    <Navbar></Navbar>

    <div class="flex relative overflow-hidden pt-16 bg-gray-50 dark:bg-gray-900 h-screen">
        <Sidebar></Sidebar>
        <div class="hidden fixed inset-0 z-10 bg-gray-900/50 dark:bg-gray-900/90" id="sidebarBackdrop"></div>
        <div id="main-content" 
             class="overflow-y-auto relative w-full h-full bg-gray-50 dark:bg-gray-900 transition-all duration-300"
             :class="[isCollapsed ? 'lg:ml-20' : 'lg:ml-64']">
            <main>
                <div v-if="$slots.header" class="p-4 bg-white dark:bg-gray-900">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>

</style>
