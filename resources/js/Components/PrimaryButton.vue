<script setup>
import { computed } from 'vue';

const props = defineProps({
    text: {
        type: String,
        default: ''
    },
    type: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'alternative', 'dark', 'light', 'green', 'red', 'yellow', 'purple', 'gradient'].includes(value)
    },
    size: {
        type: String,
        default: 'base',
        validator: (value) => ['xs', 'sm', 'base', 'lg', 'xl'].includes(value)
    },
    color: {
        type: String,
        default: 'blue'
    },
    rounded: {
        type: String,
        default: 'medium',
        validator: (value) => ['none', 'medium', 'full'].includes(value)
    },
    disabled: Boolean,
    loading: Boolean,
    pill: Boolean,
    outline: Boolean,
    iconOnly: Boolean // New prop for icon-only buttons
});

const buttonClasses = computed(() => {
    const baseClasses = 'font-medium focus:outline-none focus:ring-4 focus:ring-opacity-50 flex items-center';
    const sizeClasses = {
        xs: props.iconOnly ? 'p-1.5' : 'px-3 py-2 text-xs',
        sm: props.iconOnly ? 'p-2' : 'px-3 py-2 text-sm',
        base: props.iconOnly ? 'p-2.5' : 'px-5 py-2.5 text-sm',
        lg: props.iconOnly ? 'p-3' : 'px-5 py-3 text-base',
        xl: props.iconOnly ? 'p-3.5' : 'px-6 py-3.5 text-base'
    };
    const roundedClasses = {
        none: '',
        medium: 'rounded-lg',
        full: 'rounded-full'
    };
    const typeClasses = {
        default: `text-white bg-${props.color}-700 hover:bg-${props.color}-800 focus:ring-${props.color}-300 dark:bg-${props.color}-600 dark:hover:bg-${props.color}-700 dark:focus:ring-${props.color}-800`,
        alternative: `text-gray-900 focus:ring-4 focus:ring-gray-100 bg-white border border-gray-200 hover:bg-gray-100 hover:text-${props.color}-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700`,
        dark: 'text-white bg-gray-800 hover:bg-gray-900 focus:ring-4 focus:ring-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700',
        light: 'text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700',
        green: `focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800`,
        red: `focus:outline-none text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900`,
        yellow: `focus:outline-none text-white bg-yellow-400 hover:bg-yellow-500 focus:ring-4 focus:ring-yellow-300 dark:focus:ring-yellow-900`,
        purple: `focus:outline-none text-white bg-purple-700 hover:bg-purple-800 focus:ring-4 focus:ring-purple-300 dark:bg-purple-600 dark:hover:bg-purple-700 dark:focus:ring-purple-900`,
        gradient: `text-white bg-gradient-to-r from-${props.color}-500 via-${props.color}-600 to-${props.color}-700 hover:bg-gradient-to-br focus:ring-4 focus:ring-${props.color}-300 dark:focus:ring-${props.color}-800 shadow-lg shadow-${props.color}-500/50 dark:shadow-lg dark:shadow-${props.color}-800/80`
    };

    let classes = `${baseClasses} ${sizeClasses[props.size]} ${roundedClasses[props.rounded]} ${typeClasses[props.type]}`;

    if (props.outline) {
        classes = `text-${props.color}-700 hover:text-white border border-${props.color}-700 hover:bg-${props.color}-800 focus:ring-4 focus:outline-none focus:ring-${props.color}-300 dark:border-${props.color}-500 dark:text-${props.color}-500 dark:hover:text-white dark:hover:bg-${props.color}-600 dark:focus:ring-${props.color}-800`;
    }

    if (props.disabled) {
        classes += ' opacity-50 cursor-not-allowed';
    }

    if (props.iconOnly) {
        classes += ' inline-flex items-center justify-center';
    }

    return classes;
});
</script>

<template>
    <button
        :class="buttonClasses"
        :disabled="disabled || loading"
    >
    <span v-if="loading" class="inline-flex items-center">
      <svg aria-hidden="true" role="status" class="inline w-4 h-4 me-3 text-white animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="#E5E7EB"/>
        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentColor"/>
      </svg>
      <span v-if="!iconOnly">Подождите...</span>
    </span>
        <template v-else>
            <slot name="icon-left"></slot>
            <span v-if="!iconOnly">
        <slot>{{ text }}</slot>
      </span>
            <slot v-else></slot>
            <slot name="icon-right"></slot>
        </template>
    </button>
</template>
