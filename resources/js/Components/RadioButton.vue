<template>
    <div class="flex items-center">
        <input
            type="radio"
            :value="value"
            :checked="modelValue === value"
            @change="$emit('update:modelValue', value)"
            class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
        >
        <label class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
            {{ label }}
        </label>
    </div>
</template>

<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        required: true
    },
    value: {
        type: [String, Number],
        required: true
    },
    label: {
        type: String,
        required: true
    }
});

defineEmits(['update:modelValue']);
</script> 