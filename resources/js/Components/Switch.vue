<template>
    <div class="flex items-center ">
        <button
            type="button"
            @click="toggle"
            :class="[
                'relative items-center inline-flex shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                switchSizeClasses,
                modelValue ? enabledClasses : disabledClasses,
                disabled ? 'opacity-50 cursor-not-allowed' : '',
                customClass
            ]"
            :disabled="disabled"
            v-bind="$attrs"
        >
            <span
                class="sr-only"
                v-if="label"
            >
                {{ label }}
            </span>
            <span
                aria-hidden="true"
                :class="[
                    'pointer-events-none inline-block transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    toggleSizeClasses,
                    modelValue ? toggleOnClasses : toggleOffClasses,
                ]"
            />
        </button>
        <span
            v-if="label"
            :class="[
                'ml-3 text-gray-900',
                labelSizeClasses,
                disabled ? 'opacity-50' : ''
            ]"
        >
            {{ label }}
        </span>
    </div>
</template>

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
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    color: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'success', 'danger', 'warning'].includes(value)
    },
    disabled: {
        type: Boolean,
        default: false
    },
    customClass: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const toggle = () => {
    if (!props.disabled) {
        emit('update:modelValue', !props.modelValue);
        emit('change', !props.modelValue);
    }
};

// Цветовые классы для разных состояний
const enabledClasses = computed(() => {
    const colors = {
        primary: 'bg-indigo-600',
        success: 'bg-green-600',
        danger: 'bg-red-600',
        warning: 'bg-yellow-600'
    };
    return colors[props.color];
});

const disabledClasses = computed(() => 'bg-gray-200');

// Классы для разных размеров переключателя
const switchSizeClasses = computed(() => {
    const sizes = {
        sm: 'h-5 w-9',
        md: 'h-6 w-11',
        lg: 'h-7 w-14'
    };
    return sizes[props.size];
});

// Классы для размеров переключающегося элемента
const toggleSizeClasses = computed(() => {
    const sizes = {
        sm: 'h-3 w-3',
        md: 'h-4 w-4',
        lg: 'h-5 w-5'
    };
    return sizes[props.size];
});

// Классы для позиционирования переключающегося элемента
const toggleOnClasses = computed(() => {
    const positions = {
        sm: 'translate-x-4',
        md: 'translate-x-6',
        lg: 'translate-x-7'
    };
    return positions[props.size];
});

const toggleOffClasses = computed(() => 'translate-x-0');

// Классы для размера текста метки
const labelSizeClasses = computed(() => {
    const sizes = {
        sm: 'text-sm',
        md: 'text-base',
        lg: 'text-lg'
    };
    return sizes[props.size];
});
</script>
