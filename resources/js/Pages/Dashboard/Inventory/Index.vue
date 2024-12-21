<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import SelectDropdown from "@/Components/SelectDropdown.vue";
import ContextMenu from "@/Components/ContextMenu.vue";

const props = defineProps({
    materialsInventory: {
        type: Array,
        required: true,
        default: () => []
    },
    productsInventory: {
        type: Array,
        required: true,
        default: () => []
    },
    materials: {
        type: Array,
        required: true,
        default: () => []
    },
    products: {
        type: Array,
        required: true,
        default: () => []
    },
    units: {
        type: Array,
        required: true,
        default: () => []
    }
});

const breadCrumbs = [
    {
        name: 'Управление запасами',
        link: route('dashboard.inventory.index')
    }
];

const searchQuery = ref('');
const showModal = ref(false);
const modalMode = ref('');

const form = useForm({
    item_type: '',
    item_id: '',
    quantity: '',
    price_per_unit: '',
    unit_id: '',
    received_date: '',
    description: '',
});

const menuItems = [
    {text: 'Добавить запас', action: 'add-stock'},
    {text: 'Списать', action: 'write-off'},
    {text: 'Удалить', action: 'delete', isDangerous: true}
];
const getItemTypeName = (itemType) => {
    switch(itemType) {
        case 'material':
            return 'Материал';
        case 'product':
            return 'Продукт';
        default:
            return 'Неизвестный тип';
    }
};

const handleContextMenuItemClick = (item, inventoryItem) => {
    switch (item.action) {
        case 'add-stock':
            openModal('addStock', inventoryItem);
            break;
        case 'write-off':
            openModal('writeOff', inventoryItem);
            break;
    }
};

// Добавим состояние для отслеживания развернутых строк
const expandedRows = ref(new Set());

// Функция для проверки, развернута ли строка
const isExpanded = (productId) => {
    return expandedRows.value.has(productId);
};

// Обновленная функция переключения
const toggleProductDetails = (productId) => {
    const details = document.querySelector(`#product-details-${productId}`);
    if (!details) return;

    if (details.classList.contains('hidden')) {
        details.classList.remove('hidden');
        expandedRows.value.add(productId);
    } else {
        details.classList.add('hidden');
        expandedRows.value.delete(productId);
    }
};

// Добавим обработчик клика для всей строки
const handleRowClick = (product) => {
    if (product.item?.has_variants && product.item?.variants?.length) {
        toggleProductDetails(product.id);
    }
};

const filteredMaterialsInventory = computed(() => {
    return props.materialsInventory.data.filter(item =>
        (item.item?.name || '').toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const filteredProductsInventory = computed(() => {
    return props.productsInventory.data.filter(item =>
        (item.item?.name || '').toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const itemOptions = computed(() => {
    if (form.item_type === 'material') {
        return props.materials.map(m => ({ label: m.name, value: m.id }));
    } else if (form.item_type === 'product') {
        return props.products.map(p => ({ label: p.name, value: p.id }));
    }
    return [];
});

const unitOptions = computed(() => props.units || []);

const openModal = (mode, item) => {
    modalMode.value = mode;
    form.reset();
    
    if (item.item_type === 'variant') {
        form.item_type = 'variant';
        form.item_id = item.id;
        form.unit_id = item.unit_id;
    } else {
        form.item_type = item.item_type;
        form.item_id = item.id;
        form.unit_id = item.unit_id;
    }
    
    if (mode === 'addStock') {
        form.received_date = new Date().toISOString().substr(0, 10);
    }
    
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submitForm = () => {
    if (modalMode.value === 'addStock') {
        form.post(route('dashboard.inventory.add'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                // Здесь можно добавить обновление даннх или уведомление об успехе
            }
        });
    } else if (modalMode.value === 'writeOff') {
        form.post(route('dashboard.inventory.remove'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                // Здесь можно добавить обновление данных или уведомление об успехе
            }
        });
    }
};

// Добавляем новую переменную для активной вкладки
const activeTab = ref('materials');
</script>

<template>
    <DashboardLayout>
        <template #header>
            <div class="mb-4">
                <BreadCrumbs :breadcrumbs="breadCrumbs" />
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                    Управление запасами
                </h1>
            </div>
        </template>

        <div class="p-4">
            <!-- Tabs -->
            <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
                    <li class="mr-2">
                        <button
                            @click="activeTab = 'materials'"
                            class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg group"
                            :class="[
                                activeTab === 'materials'
                                    ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500'
                                    : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'
                            ]"
                        >
                            <span>Материалы производства</span>
                        </button>
                    </li>
                    <li class="mr-2">
                        <button
                            @click="activeTab = 'products'"
                            class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg group"
                            :class="[
                                activeTab === 'products'
                                    ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500'
                                    : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'
                            ]"
                        >
                            <span>Товары</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Поиск и фильтры -->
            <div class="flex flex-col md:flex-row gap-4 mb-4">
                <div class="flex-1">
                    <TextInput
                        type="search"
                        placeholder="Поиск по названию..."
                        v-model="searchQuery"
                    />
                </div>
            </div>

            <!-- Таблица материалов -->
            <div v-show="activeTab === 'materials'" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">Название</th>
                                <th scope="col" class="px-6 py-3">Количество</th>
                                <th scope="col" class="px-6 py-3">Ед. изм.</th>
                                <th scope="col" class="px-6 py-3">Средняя цена</th>
                                <th scope="col" class="px-6 py-3">
                                    <span class="sr-only">Действия</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in filteredMaterialsInventory" :key="item.id" 
                                class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ item.item?.name }}
                                </td>
                                <td class="px-6 py-4">{{ item.total_quantity }}</td>
                                <td class="px-6 py-4">{{ item.unit.name }}</td>
                                <td class="px-6 py-4">{{ item.average_price }}</td>
                                <td class="px-6 py-4 text-right">
                                    <ContextMenu
                                        :items="menuItems"
                                        :context-data="item"
                                        @item-click="handleContextMenuItemClick"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Таблица товаров -->
            <div v-show="activeTab === 'products'" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3">
                                    <span class="sr-only">Expand/Collapse Row</span>
                                </th>
                                <th scope="col" class="px-4 py-3">Название</th>
                                <th scope="col" class="px-4 py-3">Количество</th>
                                <th scope="col" class="px-4 py-3">Ед. изм.</th>
                                <th scope="col" class="px-4 py-3">Средняя цена</th>
                                <th scope="col" class="px-4 py-3">
                                    <span class="sr-only">Действия</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody data-accordion="table-column">
                            <template v-for="product in filteredProductsInventory" :key="product.id">
                                <!-- Основная строка продукта -->
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                    :id="'product-row-' + product.id"
                                    :data-accordion-target="'#product-details-' + product.id"
                                    aria-expanded="false"
                                    @click="product.item?.has_variants && toggleProductDetails(product.id)"
                                    :class="{ 'cursor-pointer': product.item?.has_variants }">
                                    <td class="p-3 w-4">
                                        <svg v-if="product.item?.has_variants && product.item?.variants?.length > 0" 
                                            class="w-6 h-6 shrink-0" 
                                            fill="currentColor" 
                                            viewBox="0 0 20 20"
                                        >
                                            <path fill-rule="evenodd" 
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" 
                                                clip-rule="evenodd" 
                                            />
                                        </svg>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ product.item?.name }}
                                    </td>
                                    <td class="px-4 py-3" v-if="!product.item?.has_variants">
                                        {{ product.total_quantity }}
                                    </td>
                                    <td class="px-4 py-3" v-else>
                                        <!-- Общее количество всех вариантов -->
                                        {{ product.item?.variants?.reduce((sum, variant) => sum + (variant.inventory_balance?.quantity || 0), 0) || 0 }}
                                    </td>
                                    <td class="px-4 py-3">{{ product.unit?.name }}</td>
                                    <td class="px-4 py-3">{{ product.average_price }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <ContextMenu
                                            v-if="!product.item?.has_variants"
                                            :items="menuItems"
                                            :context-data="product"
                                            @item-click="handleContextMenuItemClick"
                                        />
                                    </td>
                                </tr>

                                <!-- Детальная информация о вариантах -->
                                <tr v-if="product.item?.has_variants && product.item?.variants?.length > 0"
                                    :id="'product-details-' + product.id"
                                    class="hidden"
                                    :aria-labelledby="'product-row-' + product.id">
                                    <td colspan="6" class="p-0 border-b dark:border-gray-700">
                                        <div class="p-4">
                                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                                    <tr>
                                                        <th scope="col" class="px-4 py-3">Вариант</th>
                                                        <th scope="col" class="px-4 py-3">SKU</th>
                                                        <th scope="col" class="px-4 py-3">Количество</th>
                                                        <th scope="col" class="px-4 py-3">Ед. изм.</th>
                                                        <th scope="col" class="px-4 py-3">Средняя цена</th>
                                                        <th scope="col" class="px-4 py-3">
                                                            <span class="sr-only">Действия</span>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="variant in product.item.variants" :key="variant.id"
                                                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                                            {{ variant.name }}
                                                        </td>
                                                        <td class="px-4 py-3">{{ variant.sku }}</td>
                                                        <td class="px-4 py-3">{{ variant.inventory_balance?.total_quantity || 0 }}</td>
                                                        <td class="px-4 py-3">{{ variant.inventory_balance?.unit?.name }}</td>
                                                        <td class="px-4 py-3">{{ variant.inventory_balance?.average_price || 0 }}</td>
                                                        <td class="px-4 py-3 text-right">
                                                            <ContextMenu
                                                                :items="menuItems"
                                                                :context-data="{
                                                                    ...variant,
                                                                    item_type: 'variant',
                                                                    parent_product: product
                                                                }"
                                                                @item-click="handleContextMenuItemClick"
                                                            />
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal :show="showModal" @close="closeModal">
            <template #title>
                {{ modalMode === 'addStock' ? 'Добавить запас' : 'Списать запас' }}
            </template>

            <template #content>
                <form>
                    <div class="grid gap-4 mb-4 sm:grid-cols-2">
                        <div>
                            <TextInput
                                label="Количество"
                                id="quantity"
                                type="number"
                                v-model="form.quantity"
                                required
                            />
                        </div>
                        <div v-if="modalMode === 'addStock'">
                            <TextInput
                                label="Цена за единицу"
                                id="price_per_unit"
                                type="number"
                                step="0.01"
                                v-model="form.price_per_unit"
                                required
                            />
                        </div>
                        <div v-if="modalMode === 'addStock'">
                            <SelectDropdown
                                label="Единица измерения"
                                v-model="form.unit_id"
                                :options="unitOptions"
                            />
                        </div>
                        <div v-if="modalMode === 'addStock'">
                            <TextInput
                                label="Дата получения"
                                id="received_date"
                                type="date"
                                v-model="form.received_date"
                                required
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="description" value="Описание"/>
                            <textarea
                                id="description"
                                rows="4"
                                class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                placeholder="Добавьте любые дополнительные детали о транзакции здесь"
                                v-model="form.description"
                            ></textarea>
                        </div>
                    </div>
                </form>
            </template>

            <template #footer>
                <PrimaryButton @click.prevent="submitForm" class="mr-3">
                    {{ modalMode === 'addStock' ? 'Добавить' : 'Списать' }}
                </PrimaryButton>
                <PrimaryButton type="red" @click="closeModal">Отмена</PrimaryButton>
            </template>
        </Modal>
    </DashboardLayout>
</template>


