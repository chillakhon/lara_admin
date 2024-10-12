<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Select an option',
    },
    label: String,
    required: Boolean,
    disabled: Boolean,
    error: String,
    valueKey: {
        type: String,
        default: 'value'
    },
    labelKey: {
        type: String,
        default: 'label'
    },
    childrenKey: {
        type: String,
        default: 'children'
    },
    indentChar: {
        type: String,
        default: '-'
    },
    nullLabel: {
        type: String,
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const dropdownRef = ref(null);

const flattenOptions = (options, depth = 0) => {
    return options.reduce((acc, option) => {
        const flatOption = {
            ...option,
            [props.labelKey]: `${props.indentChar.repeat(depth)} ${option[props.labelKey]}`,
            __rawLabel: option[props.labelKey]
        };
        acc.push(flatOption);
        if (option[props.childrenKey] && option[props.childrenKey].length > 0) {
            acc.push(...flattenOptions(option[props.childrenKey], depth + 1));
        }
        return acc;
    }, []);
};

const flatOptions = computed(() => flattenOptions(props.options));

const selectedOption = computed(() => {
    return flatOptions.value.find(option => option[props.valueKey] === props.modelValue) || null;
});

const toggleDropdown = () => {
    if (!props.disabled) {
        isOpen.value = !isOpen.value;
    }
};

const selectOption = (option) => {
    emit('update:modelValue', option? option[props.valueKey] : null);
    isOpen.value = false;
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const buttonClasses = computed(() => {
    return `w-full flex-shrink-0 z-10 inline-flex items-center py-2.5 px-4 text-sm font-medium text-center text-gray-500 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700 dark:text-white dark:border-gray-600 ${props.disabled ? 'opacity-50 cursor-not-allowed' : ''}`;
});

const labelClasses = computed(() => {
    return `block mb-2 text-sm font-medium ${props.error ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-white'}`;
});
</script>

<template>
    <div class="" ref="dropdownRef">
        <label v-if="label" :for="label" :class="labelClasses">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <button
            :id="label"
            type="button"
            :class="buttonClasses"
            @click="toggleDropdown"
            :disabled="disabled"
        >
            <span v-if="selectedOption">
                {{ selectedOption.__rawLabel }}
            </span>
            <span v-else>{{ placeholder }}</span>
            <svg class="w-2.5 h-2.5 ml-auto" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 10 6">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="m1 1 4 4 4-4"/>
            </svg>
        </button>
        <div v-if="isOpen" class="w-full z-10 bg-white divide-y divide-gray-100 rounded-lg shadow  dark:bg-gray-700">
            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                <li v-if="nullLabel">
                    <button
                        type="button"
                        class="inline-flex w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                        @click="selectOption(null)"
                    >
                        <span class="inline-flex items-center">
                            {{ nullLabel }}
                        </span>
                    </button>
                </li>
                <li v-for="option in flatOptions" :key="option[valueKey]">
                    <button
                        type="button"
                        class="inline-flex w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                        @click="selectOption(option)"
                    >
                        <span class="inline-flex items-center">
                            {{ option[labelKey] }}
                        </span>
                    </button>
                </li>
            </ul>
        </div>
        <p v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ error }}</p>
    </div>
</template>
