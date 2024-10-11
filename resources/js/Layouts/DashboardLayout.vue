<script setup>

import Navbar from "@/Components/NavBar/Navbar.vue";
import Sidebar from "@/Components/Sidebar/Sidebar.vue";

const props = defineProps({
    title: String
})
</script>

<template>
    <Navbar></Navbar>

    <div class="flex relative overflow-hidden pt-16 bg-gray-50 dark:bg-gray-900 h-screen">
        <Sidebar></Sidebar>
        <div class="hidden fixed inset-0 z-10 bg-gray-900/50 dark:bg-gray-900/90" id="sidebarBackdrop"></div>
        <div id="main-content" class="overflow-y-auto relative w-full h-full bg-gray-50 lg:ml-64 dark:bg-gray-900">
            <main>
                <div v-if="$slots.header" class="p-4 bg-white">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>

</style>
