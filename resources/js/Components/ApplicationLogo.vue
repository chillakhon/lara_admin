<script setup>
defineProps({
    src: {
        type: String,
    }
})
</script>
<template>
    <img v-if="src" :src="src" class="mr-3 h-8" alt="Dashboard Logo" />
    <slot v-else></slot>
    <span
        class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">Панель</span>
</template>
