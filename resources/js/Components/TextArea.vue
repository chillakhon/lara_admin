<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    rows: {
        type: Number,
        default: 4,
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
    autogrow: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['update:modelValue']);

const updateValue = (event) => {
    emit('update:modelValue', event.target.value);
    if (props.autogrow) {
        event.target.style.height = 'auto';
        event.target.style.height = event.target.scrollHeight + 'px';
    }
};

const textareaClasses = computed(() => {
    return [
        'block p-2.5 w-full text-sm rounded-lg border',
        'focus:ring-4 focus:outline-none',
        props.error
            ? 'border-red-500 text-red-900 placeholder-red-700 focus:ring-red-500 focus:border-red-500 dark:border-red-500 dark:placeholder-red-500 dark:text-red-500 dark:focus:ring-red-500'
            : 'border-gray-300 text-gray-900 focus:ring-primary-500 focus:border-primary-500 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500',
        props.disabled ? 'bg-gray-100 cursor-not-allowed dark:bg-gray-700' : 'bg-white dark:bg-gray-800',
    ].join(' ');
});

const labelClasses = computed(() => {
    return [
        'block mb-2 text-sm font-medium',
        props.error
            ? 'text-red-700 dark:text-red-500'
            : 'text-gray-900 dark:text-white'
    ].join(' ');
});
</script>

<template>
    <div>
        <label 
            v-if="label" 
            :for="label" 
            :class="labelClasses"
        >
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <textarea
            :id="label"
            :value="modelValue"
            @input="updateValue"
            :rows="rows"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :class="textareaClasses"
        ></textarea>
        
        <p 
            v-if="error" 
            class="mt-2 text-sm text-red-600 dark:text-red-500"
        >
            {{ error }}
        </p>
    </div>
</template> 