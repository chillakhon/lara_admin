<script setup>
import { ref, watch, computed } from 'vue';
import { Combobox, ComboboxInput, ComboboxButton, ComboboxOptions, ComboboxOption } from '@headlessui/vue';
import { ChevronUpDownIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    options: {
        type: Array,
        required: true
    },
    labelKey: {
        type: String,
        default: 'label'
    },
    valueKey: {
        type: String,
        default: 'value'
    },
    placeholder: {
        type: String,
        default: 'Выберите значения'
    }
});

const emit = defineEmits(['update:modelValue']);

const selectedItems = ref(props.modelValue);
const query = ref('');

watch(selectedItems, (newValue) => {
    emit('update:modelValue', newValue);
});

const filteredOptions = computed(() => {
    return props.options.filter((option) => {
        const label = option[props.labelKey].toLowerCase();
        return label.includes(query.value.toLowerCase());
    });
});

const getSelectedLabels = computed(() => {
    return selectedItems.value.map(value => {
        const option = props.options.find(opt => opt[props.valueKey] === value);
        return option ? option[props.labelKey] : '';
    }).join(', ');
});
</script>

<template>
    <Combobox v-model="selectedItems" multiple>
        <div class="relative">
            <div class="relative w-full cursor-default overflow-hidden rounded-lg bg-white text-left border border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                <ComboboxInput
                    class="w-full border-none py-2 pl-3 pr-10 text-sm leading-5 text-gray-900 dark:text-white bg-transparent focus:ring-0"
                    :displayValue="() => getSelectedLabels"
                    @change="query = $event.target.value"
                    :placeholder="placeholder"
                />
                <ComboboxButton class="absolute inset-y-0 right-0 flex items-center pr-2">
                    <ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                </ComboboxButton>
            </div>
            <TransitionRoot
                leave="transition ease-in duration-100"
                leaveFrom="opacity-100"
                leaveTo="opacity-0"
                @after-leave="query = ''"
            >
                <ComboboxOptions class="absolute mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700">
                    <div v-if="filteredOptions.length === 0" class="relative cursor-default select-none py-2 px-4 text-gray-700 dark:text-gray-300">
                        Ничего не найдено.
                    </div>
                    <ComboboxOption
                        v-for="option in filteredOptions"
                        :key="option[valueKey]"
                        :value="option[valueKey]"
                        v-slot="{ selected, active }"
                    >
                        <div :class="[
                            'relative cursor-default select-none py-2 pl-10 pr-4',
                            active ? 'bg-primary-600 text-white' : 'text-gray-900 dark:text-white'
                        ]">
                            <span :class="[
                                'block truncate',
                                selected ? 'font-medium' : 'font-normal'
                            ]">
                                {{ option[labelKey] }}
                            </span>
                            <span v-if="selected" :class="[
                                'absolute inset-y-0 left-0 flex items-center pl-3',
                                active ? 'text-white' : 'text-primary-600'
                            ]">
                                <CheckIcon class="h-5 w-5" aria-hidden="true" />
                            </span>
                        </div>
                    </ComboboxOption>
                </ComboboxOptions>
            </TransitionRoot>
        </div>
    </Combobox>
</template> 