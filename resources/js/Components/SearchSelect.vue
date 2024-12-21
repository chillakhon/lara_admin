<script setup>
    import { ref, watch, computed } from 'vue';
    import {
        Combobox,
        ComboboxInput,
        ComboboxButton,
        ComboboxOptions,
        ComboboxOption,
    } from '@headlessui/vue';

    const props = defineProps({
        modelValue: {
            type: [String, Number, Object],
            default: null,
        },
        type: {
            type: String,
            required: true, // 'products', 'categories', 'orders', etc.
        },
        placeholder: {
            type: String,
            default: 'Поиск...',
        },
        showVariants: {
            type: Boolean,
            default: false,
        },
    });

    const emit = defineEmits(['update:modelValue', 'change']);

    const query = ref('');
    const isLoading = ref(false);
    const results = ref([]);
    const selected = ref(props.modelValue);

    // Добавляем функцию форматирования цены
    const formatPrice = (price) => {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(price);
    };

    // Наблюдаем за изменением поискового запроса
    watch(
        query,
        async (newQuery) => {
            if (newQuery.length < 2) {
                results.value = [];
                return;
            }

            isLoading.value = true;
            try {
                console.log('Sending search request:', {
                    query: newQuery,
                    type: props.type,
                });

                const response = await axios.get(route('api.search'), {
                    params: {
                        query: newQuery,
                        type: props.type,
                    },
                });

                console.log('Search response:', response.data);
                results.value = response.data;
            } catch (error) {
                console.error('Search error:', error);
                results.value = [];
            } finally {
                isLoading.value = false;
            }
        },
        { debounce: 300 }
    );

    // Обработка выбора значения
    const handleSelect = (value) => {
        console.log('Selected value:', value);
        selected.value = value;
        emit('update:modelValue', value);
        emit('change', value);
    };

    // Обновляем форматирование отображаемого значения
    const formatDisplay = (item) => {
        if (!item) return '';

        if (typeof item === 'object') {
            if (props.type === 'products') {
                let display = item.name;
                if (item.variant) {
                    display += ` (${item.variant.name})`;
                }
                return display;
            }
            if (props.type === 'clients') {
                return item.name;
            }
            return item.name;
        }
        return item;
    };

    // Обновляем форматирование результата поиска
    const formatSearchResult = (result) => {
        if (props.type === 'products' && props.showVariants && result.has_variants) {
            return result.variants.map((variant) => ({
                ...result,
                variant,
                displayValue: `${result.name} - ${variant.name}`,
                price: variant.price,
            }));
        }
        return [result];
    };

    // Обработка результатов поиска
    const processSearchResults = computed(() => {
        return results.value.flatMap(formatSearchResult);
    });
</script>

<template>
    <Combobox v-model="selected" @update:modelValue="handleSelect">
        <div class="relative">
            <div class="relative w-full">
                <ComboboxInput
                    class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm leading-5 text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    :displayValue="(item) => formatDisplay(item)"
                    @change="query = $event.target.value"
                    :placeholder="placeholder"
                />
                <ComboboxButton class="absolute inset-y-0 right-0 flex items-center pr-2">
                    <svg
                        :class="['h-5 w-5 text-gray-400', isLoading ? 'animate-spin' : '']"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                    >
                        <path
                            fill="none"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="m15 15l6 6m-11-4a7 7 0 1 1 0-14a7 7 0 0 1 0 14"
                        />
                    </svg>
                </ComboboxButton>
            </div>

            <ComboboxOptions
                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm dark:bg-gray-700 dark:ring-gray-600"
            >
                <div v-if="isLoading" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                    Поиск...
                </div>

                <div
                    v-else-if="processSearchResults.length === 0 && query.length >= 2"
                    class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400"
                >
                    Ничего не найдено
                </div>

                <ComboboxOption
                    v-for="result in processSearchResults"
                    :key="result.variant ? `${result.id}-${result.variant.id}` : result.id"
                    :value="result"
                    v-slot="{ selected, active }"
                >
                    <div
                        :class="[
                            'relative cursor-pointer select-none py-2 pl-10 pr-4',
                            active ? 'bg-primary-600 text-white' : 'text-gray-900 dark:text-white',
                        ]"
                    >
                        <!-- Отображение для клиентов -->
                        <template v-if="type === 'clients'">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center"
                                    >
                                        <span
                                            class="text-sm font-medium text-primary-600 dark:text-primary-400"
                                        >
                                            {{
                                                result.name?.trim()
                                                    .split(' ')
                                                    .filter(Boolean)
                                                    .map((n) => n[0])
                                                    .join('')
                                                    || '?'
                                            }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="font-medium">{{ result.name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ result.email }}
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Отображение для других типов -->
                        <template v-else>
                            <span
                                :class="[
                                    'block truncate',
                                    selected ? 'font-medium' : 'font-normal',
                                ]"
                            >
                                {{ result.variant ? result.displayValue : result.name }}
                            </span>

                            <span
                                v-if="result.variant"
                                :class="[
                                    'text-sm',
                                    active
                                        ? 'text-primary-200'
                                        : 'text-gray-500 dark:text-gray-400',
                                ]"
                            >
                                {{ formatPrice(result.variant.price) }}
                            </span>
                        </template>

                        <span
                            v-if="selected"
                            :class="[
                                'absolute inset-y-0 left-0 flex items-center pl-3',
                                active ? 'text-white' : 'text-primary-600',
                            ]"
                        >
                            <CheckIcon class="h-5 w-5" />
                        </span>
                    </div>
                </ComboboxOption>
            </ComboboxOptions>
        </div>
    </Combobox>
</template>
