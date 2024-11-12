<template>
    <th scope="col" :class="[
        'px-6 py-3',
        align === 'left' && 'text-left',
        align === 'center' && 'text-center',
        align === 'right' && 'text-right'
    ]">
        <slot></slot>
    </th>
</template>

<script setup>
defineProps({
    align: {
        type: String,
        default: 'left',
        validator: (value) => ['left', 'center', 'right'].includes(value)
    }
});
</script>
