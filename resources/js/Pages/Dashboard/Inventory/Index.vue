<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Управление запасами</h1>
            <div class="sm:flex">
                <div class="items-center hidden mb-3 sm:flex sm:divide-x sm:divide-gray-100 sm:mb-0 dark:divide-gray-700">
                    <form class="lg:pr-3" action="#" method="GET">
                        <label for="inventory-search" class="sr-only">Поиск</label>
                        <div class="relative mt-1 lg:w-64 xl:w-96">
                            <input type="text" name="search" id="inventory-search"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                   placeholder="Поиск по названию или артикулу" v-model="searchQuery">
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <div class="flex flex-col">
            <div class="overflow-x-auto sm:oveflow-visible">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-visible shadow">
                        <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    Название
                                </th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    Тип
                                </th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    Количество
                                </th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    Ед. изм.
                                </th>
                                <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    Средняя цена
                                </th>
                                <th scope="col" class="p-4"></th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            <tr v-for="item in filteredInventory" :key="item.id" class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="p-4 text-sm font-normal text-gray-500 whitespace-nowrap dark:text-gray-400">
                                    <div class="text-base font-semibold text-gray-900 dark:text-white">
                                        {{ item.name || 'Нет названия' }}
                                    </div>
                                    <div v-if="item.item?.sku || item.item?.article" class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                        {{ item.item?.sku || item.item?.article }}
                                    </div>
                                </td>
                                <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ getItemTypeName(item.item_type) }}
                                </td>
                                <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ item.quantity }}
                                </td>
                                <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ item.unit || 'Нет единицы измерения' }}
                                </td>
                                <td class="p-4 text-base font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ item.average_price }}
                                </td>
                                <td class="p-4 space-x-2 whitespace-nowrap">
                                    <ContextMenu
                                        @item-click="handleContextMenuItemClick"
                                        :id="`menu-${item.id}`"
                                        :items="menuItems"
                                        :context-data="item"
                                    ></ContextMenu>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
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
    inventory: Object,
    materials: Array,
    products: Array,
    units: Array,
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
        case 'delete':
            // Здесь можно добавить логику удаления, если это необходимо
            console.log('Delete action for item:', inventoryItem);
            break;
    }
};



const filteredInventory = computed(() => {
    return props.inventory.data.filter(item =>
        (item.item?.name || '').toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (item.item?.sku || '').toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (item.item?.article || '').toLowerCase().includes(searchQuery.value.toLowerCase())
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

const unitOptions = computed(() => {
    return props.units.map(u => ({ label: u.name, value: u.id }));
});

const openModal = (mode, item) => {
    modalMode.value = mode;
    form.reset();
    if (item) {
        form.item_type = item.item_type;
        form.item_id = item.id;
        form.unit_id = item.unit_id;
    }
    if (mode === 'addStock') {
        form.received_date = new Date().toISOString().substr(0, 10); // Устанавливаем текущую дату
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
                // Здесь можно добавить обновление данных или уведомление об успехе
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
</script>
