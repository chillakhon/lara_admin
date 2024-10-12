<script setup>
import {onMounted, ref, computed} from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'text'
    },
    label: String,
    placeholder: String,
    required: Boolean,
    autofocus: Boolean,
    disabled: Boolean,
    id: String,
    name: String,
    autocomplete: String,
    error: String,
});

const model = defineModel({
    type: [String, Number],
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (props.autofocus) {
        input.value.focus();
    }
});

const inputClasses = computed(() => {
    return `bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:placeholder-gray-400 dark:text-white
            ${props.error
        ? 'border-red-500 focus:ring-red-500 focus:border-red-500 dark:border-red-600 dark:focus:ring-red-500 dark:focus:border-red-500'
        : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:border-gray-600 dark:focus:ring-blue-500 dark:focus:border-blue-500'}
            ${props.disabled ? 'cursor-not-allowed' : ''}`;
});

const labelClasses = computed(() => {
    return `block mb-2 text-sm font-medium ${props.error ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-white'}`;
});

defineExpose({focus: () => input.value.focus()});
</script>

<template>
    <div>
        <label v-if="label" :for="id" :class="labelClasses">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <input
            :class="inputClasses"
            v-model="model"
            ref="input"
            :type="type"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :id="id"
            :name="name"
            :autocomplete="autocomplete"
        />
        <p v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ error }}</p>
    </div>
</template>
