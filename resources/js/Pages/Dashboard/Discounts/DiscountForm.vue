<script setup>
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputError from '@/Components/InputError.vue';
import SearchSelect from '@/Components/SearchSelect.vue';

const props = defineProps({
    discount: Object,
    products: Object,
    productVariants: Object,
    categories: Object
});

const emit = defineEmits(['saved', 'cancelled']);

const form = useForm({
    name: props.discount?.name || '',
    type: props.discount?.type || 'percentage',
    value: props.discount?.value || '',
    is_active: props.discount?.is_active ?? true,
    starts_at: props.discount?.starts_at || '',
    ends_at: props.discount?.ends_at || '',
    priority: props.discount?.priority || 0,
    conditions: props.discount?.conditions || {},
    discount_type: props.discount?.discount_type || 'specific',
    categories: props.discount?.categories?.map(c => c.id) || [],
    products: props.discount?.products?.map(p => p.id) || [],
    product_variants: props.discount?.product_variants?.map(v => v.id) || []
});

const discountTypes = [
    { value: 'percentage', label: 'Процент' },
    { value: 'fixed', label: 'Фиксированная сумма' },
    { value: 'special_price', label: 'Специальная цена' }
];

const discountTargetTypes = [
    { value: 'specific', label: 'Конкретные товары' },
    { value: 'category', label: 'Категории товаров' },
    { value: 'all', label: 'Все товары' }
];

const handleCategorySelect = (categories) => {
    if (Array.isArray(categories)) {
        form.categories = categories.map(cat => cat.id);
    } else if (categories?.id) {
        form.categories = [categories.id];
    } else {
        form.categories = [];
    }
};

const handleProductSelect = (products) => {
    if (Array.isArray(products)) {
        form.products = products.map(prod => prod.id);
        form.product_variants = [];
    } else if (products?.id) {
        form.products = [products.id];
        form.product_variants = [];
    } else {
        form.products = [];
        form.product_variants = [];
    }
};

const handleVariantSelect = (variants) => {
    if (Array.isArray(variants)) {
        form.product_variants = variants.map(variant => variant.id);
    } else if (variants?.id) {
        form.product_variants = [variants.id];
    } else {
        form.product_variants = [];
    }
};

const submit = () => {
    if (props.discount) {
        form.put(route('dashboard.discounts.update', props.discount.id), {
            onSuccess: () => emit('saved')
        });
    } else {
        form.post(route('dashboard.discounts.store'), {
            onSuccess: () => emit('saved')
        });
    }
};

defineExpose({ submit });
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <!-- Основная информация -->
        <div class="space-y-4">
            <div>
                <InputLabel for="name" value="Название скидки" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="type" value="Тип скидки" />
                    <SelectInput
                        id="type"
                        v-model="form.type"
                        :options="discountTypes"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.type" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="value" value="Значение" />
                    <TextInput
                        id="value"
                        v-model="form.value"
                        type="number"
                        step="0.01"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError :message="form.errors.value" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Период действия -->
        <div class="space-y-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Период действия</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="starts_at" value="Дата начала" />
                    <TextInput
                        id="starts_at"
                        v-model="form.starts_at"
                        type="datetime-local"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.starts_at" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="ends_at" value="Дата окончания" />
                    <TextInput
                        id="ends_at"
                        v-model="form.ends_at"
                        type="datetime-local"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.ends_at" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Применение скидки -->
        <div class="space-y-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Применение скидки</h3>
            
            <div>
                <InputLabel for="discount_type" value="Тип применения" />
                <SelectInput
                    id="discount_type"
                    v-model="form.discount_type"
                    :options="discountTargetTypes"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.discount_type" class="mt-2" />
            </div>

            <template v-if="form.discount_type === 'specific'">
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Товары" />
                        <SearchSelect
                            v-model="form.selectedProducts"
                            type="products"
                            placeholder="Поиск товаров..."
                            class="mt-1"
                            @change="handleProductSelect"
                        />
                        <InputError :message="form.errors.products" class="mt-2" />
                    </div>

                    <div v-if="form.products.length > 0">
                        <InputLabel value="Варианты товаров" />
                        <SearchSelect
                            v-model="form.selectedVariants"
                            type="variants"
                            :product-ids="form.products"
                            placeholder="Поиск вариантов..."
                            class="mt-1"
                            @change="handleVariantSelect"
                        />
                        <InputError :message="form.errors.product_variants" class="mt-2" />
                    </div>
                </div>
            </template>

            <template v-if="form.discount_type === 'category'">
                <div>
                    <InputLabel value="Категории" />
                    <SearchSelect
                        v-model="form.selectedCategories"
                        type="categories"
                        placeholder="Поиск категорий..."
                        class="mt-1"
                        @change="handleCategorySelect"
                    />
                    <InputError :message="form.errors.categories" class="mt-2" />
                </div>
            </template>
        </div>

        <!-- Дополнительные настройки -->
        <div class="space-y-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Дополнительные настройки</h3>
            
            <div>
                <InputLabel for="priority" value="Приоритет" />
                <TextInput
                    id="priority"
                    v-model="form.priority"
                    type="number"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.priority" class="mt-2" />
            </div>

            <div class="flex items-center">
                <input
                    id="is_active"
                    v-model="form.is_active"
                    type="checkbox"
                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                >
                <InputLabel for="is_active" value="Активна" class="ml-2" />
            </div>
        </div>
    </form>
</template>