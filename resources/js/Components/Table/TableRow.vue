<template>
    <tr :class="[
        'bg-white border-b dark:bg-gray-800 dark:border-gray-700',
        hoverable && 'hover:bg-gray-50 dark:hover:bg-gray-600',
        selected && 'bg-gray-50 dark:bg-gray-600'
    ]">
        <slot></slot>
    </tr>
</template>

<script setup>
defineProps({
    hoverable: {
        type: Boolean,
        default: true
    },
    selected: {
        type: Boolean,
        default: false
    }
});
</script>
