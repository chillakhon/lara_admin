<script setup>
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import SelectDropdown from '@/Components/SelectDropdown.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ref } from 'vue';

const props = defineProps({
    product: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        required: true
    },
    units: {
        type: Array,
        required: true
    }
});

const form = useForm({
    name: props.product.name,
    description: props.product.description,
    type: props.product.type,
    default_unit_id: props.product.default_unit_id,
    is_active: props.product.is_active,
    has_variants: props.product.has_variants,
    allow_preorder: props.product.allow_preorder,
    after_purchase_processing_time: props.product.after_purchase_processing_time,
    categories: props.product.categories.map(c => c.id),
});

const typeOptions = [
    { value: 'simple', label: 'Простой товар' },
    { value: 'manufactured', label: 'Производимый товар' },
    { value: 'composite', label: 'Составной товар' }
];

const submit = () => {
    form.put(route('dashboard.products.update', props.product.id), {
        preserveScroll: true,
    });
};

const isEditing = ref(false);

const toggleEdit = () => {
    isEditing.value = !isEditing.value;
    if (!isEditing.value) {
        // Reset form to original values if canceling edit
        form.name = props.product.name;
        form.description = props.product.description;
        form.type = props.product.type;
        form.default_unit_id = props.product.default_unit_id;
        form.is_active = props.product.is_active;
        form.has_variants = props.product.has_variants;
        form.allow_preorder = props.product.allow_preorder;
        form.after_purchase_processing_time = props.product.after_purchase_processing_time;
        form.categories = props.product.categories.map(c => c.id);
    }
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Основная информация
                </h2>
                <button
                    @click="toggleEdit"
                    class="px-4 py-2 text-sm font-medium rounded-lg"
                    :class="isEditing ? 'text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600' : 'text-blue-700 bg-blue-100 hover:bg-blue-200 dark:text-blue-300 dark:bg-blue-900 dark:hover:bg-blue-800'"
                >
                    {{ isEditing ? 'Отменить' : 'Редактировать' }}
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Product Name -->
                <div class="grid gap-2">
                    <InputLabel for="name" value="Название товара" />
                    <div class="relative">
                        <TextInput
                            id="name"
                            type="text"
                            v-model="form.name"
                            :disabled="!isEditing"
                            class="block w-full"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                </div>

                <!-- Product Description -->
                <div class="grid gap-2">
                    <InputLabel for="description" value="Описание" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        :disabled="!isEditing"
                        rows="4"
                        class="block w-full rounded-lg border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Product Type -->
                    <div class="grid gap-2">
                        <InputLabel for="type" value="Тип товара" />
                        <SelectDropdown
                            id="type"
                            v-model="form.type"
                            :disabled="!isEditing"
                            :options="typeOptions"
                            class="block w-full"
                            required
                        />
                        <InputError :message="form.errors.type" class="mt-2" />
                    </div>

                    <!-- Default Unit -->
                    <div class="grid gap-2">
                        <InputLabel for="default_unit_id" value="Единица измерения" />
                        <SelectDropdown
                            id="default_unit_id"
                            v-model="form.default_unit_id"
                            :disabled="!isEditing"
                            :options="units.map(unit => ({
                                value: unit.id,
                                label: unit.name
                            }))"
                            class="block w-full"
                        />
                        <InputError :message="form.errors.default_unit_id" class="mt-2" />
                    </div>
                </div>

                <!-- Categories -->
                <div class="grid gap-2">
                    <InputLabel for="categories" value="Категории" />
                    <div class="relative">
                        <select
                            id="categories"
                            v-model="form.categories"
                            :disabled="!isEditing"
                            multiple
                            class="block w-full rounded-lg border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.categories" class="mt-2" />
                    </div>
                </div>

                <!-- Product Settings -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Настройки товара</h3>

                    <div class="space-y-4">
                        <!-- Is Active -->
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Активность</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Товар будет виден в каталоге</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.is_active"
                                    :disabled="!isEditing"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Has Variants -->
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Варианты товара</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Товар имеет различные варианты</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.has_variants"
                                    :disabled="!isEditing"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Allow Preorder -->
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Предзаказ</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Разрешить предзаказ товара</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.allow_preorder"
                                    :disabled="!isEditing"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Processing Time -->
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Время обработки (дни)</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Время обработки заказа после покупки</span>
                            </div>
                            <TextInput
                                type="number"
                                v-model="form.after_purchase_processing_time"
                                :disabled="!isEditing"
                                class="w-24 text-right"
                                min="0"
                            />
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end" v-if="isEditing">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                    >
                        Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
