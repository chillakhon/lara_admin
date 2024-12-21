<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    label: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: ''
    },
    size: {
        type: String,
        default: 'default',
        validator: (value) => ['small', 'default', 'large'].includes(value)
    },
    color: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'success', 'danger', 'warning'].includes(value)
    }
});

const emit = defineEmits(['update:modelValue']);

const toggle = () => {
    if (!props.disabled) {
        emit('update:modelValue', !props.modelValue);
    }
};

const switchClasses = computed(() => {
    const baseClasses = [
        'relative inline-flex items-center rounded-full transition-colors duration-200 ease-in-out cursor-pointer',
        props.disabled ? 'opacity-50 cursor-not-allowed' : '',
    ];

    // Размеры
    const sizeClasses = {
        small: 'w-9 h-5',
        default: 'w-11 h-6',
        large: 'w-14 h-7'
    };

    // Цвета
    const colorClasses = {
        primary: props.modelValue ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-600',
        success: props.modelValue ? 'bg-green-600' : 'bg-gray-200 dark:bg-gray-600',
        danger: props.modelValue ? 'bg-red-600' : 'bg-gray-200 dark:bg-gray-600',
        warning: props.modelValue ? 'bg-yellow-600' : 'bg-gray-200 dark:bg-gray-600'
    };

    return [...baseClasses, sizeClasses[props.size], colorClasses[props.color]].join(' ');
});

const buttonClasses = computed(() => {
    const baseClasses = [
        'inline-block transform rounded-full bg-white transition duration-200 ease-in-out',
        props.modelValue ? 'translate-x-full' : 'translate-x-0'
    ];

    const sizeClasses = {
        small: 'h-4 w-4',
        default: 'h-5 w-5',
        large: 'h-6 w-6'
    };

    return [...baseClasses, sizeClasses[props.size]].join(' ');
});

const labelClasses = computed(() => {
    return [
        'ml-3 text-sm font-medium',
        props.error ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-gray-300',
        props.disabled ? 'opacity-50' : ''
    ].join(' ');
});
</script>

<template>
    <div>
        <label class="inline-flex items-center cursor-pointer">
            <div
                :class="switchClasses"
                @click="toggle"
                role="switch"
                :aria-checked="modelValue"
                :aria-label="label"
            >
                <span
                    :class="buttonClasses"
                    aria-hidden="true"
                ></span>
            </div>
            <span v-if="label" :class="labelClasses">
                {{ label }}
            </span>
        </label>
        <p 
            v-if="error" 
            class="mt-2 text-sm text-red-600 dark:text-red-500"
        >
            {{ error }}
        </p>
    </div>
</template> 