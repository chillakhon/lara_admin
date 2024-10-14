<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Материалы</h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
                <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-visible">
                    <div
                        class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                        <div class="w-full md:w-1/2">
                            <form class="flex items-center">
                                <label for="simple-search" class="sr-only">Search</label>
                                <div class="relative w-full">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                             fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd"
                                                  d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <input type="text" id="simple-search"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                           placeholder="Search" required="">
                                </div>
                            </form>
                        </div>
                        <div
                            class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0">
                            <PrimaryButton @click="openModal('create')">
                                <template #icon-left>
                                    <svg class="h-5 w-5 mr-2" fill="currentColor" viewbox="0 0 20 20"
                                         xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path clip-rule="evenodd" fill-rule="evenodd"
                                              d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"/>
                                    </svg>
                                </template>
                                Добавить материал
                            </PrimaryButton>
                            <div class="flex items-center space-x-3 w-full md:w-auto">
                                <!-- Здесь можно добавить дополнительные кнопки действий, если нужно -->
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto sm:overflow-x-visible overflow-y-visible">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead
                                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3">Название</th>
                                <th scope="col" class="px-4 py-3">Ед. изм.</th>
                                <th scope="col" class="px-4 py-3">Текущий остаток</th>
                                <th scope="col" class="px-4 py-3">Средняя стоимость</th>
                                <th scope="col" class="px-4 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="material in materials.data" :key="material.id"
                                class="border-b dark:border-gray-700">
                                <th scope="row"
                                    class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ material.title }}
                                </th>
                                <td class="px-4 py-3">{{ material.unit.name }}</td>
                                <td class="px-4 py-3">
                                    {{ material.inventory_balance ? material.inventory_balance.total_quantity : 0 }}
                                    {{ material.unit.abbreviation }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ material.inventory_balance ? material.inventory_balance.average_price : 0 }} руб.
                                </td>
                                <td class="px-4 py-3 flex items-center justify-end">
                                    <ContextMenu
                                        @item-click="handleContextMenuItemClick"
                                        :id="`menu-${material.id}`"
                                        :items="menuItems"
                                        :context-data="material"
                                    ></ContextMenu>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <nav
                        class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4"
                        aria-label="Table navigation">
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            Показано
                            <span class="font-semibold text-gray-900 dark:text-white">1-10</span>
                            из
                            <span class="font-semibold text-gray-900 dark:text-white">1000</span>
                        </span>
                        <Pagination :links="materials.links"/>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Modal for Create/Edit Material -->
        <Modal :show="showModal" @close="closeModal">
            <template #title>
                {{ modalMode === 'create' ? 'Создать материал' : 'Редактировать материал' }}
            </template>
            <template #content>
                <form>
                    <div class="mt-6">
                        <TextInput
                            label="Название"
                            id="title"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.title"
                            :error="form.errors.title"
                            required
                            autofocus
                        />
                    </div>

                    <div class="mt-6">
                        <InputLabel for="unit_id" value=""/>
                        <SelectDropdown
                            label="Единица измерения"
                            id="unit_id"
                            class="mt-1 block w-full"
                            v-model="form.unit_id"
                            :options="unitOptions"
                            :error="form.errors.unit_id"
                            required
                        />
                    </div>

                </form>
            </template>
            <template #footer>
                <PrimaryButton type="red" class="mr-3" @click="closeModal">Отмена</PrimaryButton>
                <PrimaryButton @click.prevent="submitForm" :disabled="form.processing">Сохранить</PrimaryButton>
            </template>
        </Modal>

        <Modal :show="showInventoryModal" @close="closeInventoryModal">
            <template #title>
                {{ inventoryModalMode === 'add' ? 'Добавить запас' : 'Списать запас' }}
            </template>
            <template #content>
                <form >
                    <div class="mt-6">
                        <TextInput
                            label="Количество"
                            id="quantity"
                            type="number"
                            step="0.01"
                            class="mt-1 block w-full"
                            v-model="inventoryForm.quantity"
                            :error="inventoryForm.errors.quantity"
                            required
                        />
                    </div>

                    <div v-if="inventoryModalMode === 'add'" class="mt-6">
                        <TextInput
                            label="Цена за единицу"
                            id="price_per_unit"
                            type="number"
                            step="0.01"
                            class="mt-1 block w-full"
                            v-model="inventoryForm.price_per_unit"
                            :error="inventoryForm.errors.price_per_unit"
                            required
                        />
                    </div>

                    <div v-if="inventoryModalMode === 'add'" class="mt-6">
                        <TextInput
                            label="Дата поступления"
                            id="received_date"
                            type="date"
                            class="mt-1 block w-full"
                            v-model="inventoryForm.received_date"
                            :error="inventoryForm.errors.received_date"
                            required
                        />
                    </div>

                    <div class="mt-6">
                        <InputLabel for="description" value="Описание"/>
                        <textarea
                            id="description"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                            v-model="inventoryForm.description"
                        ></textarea>
                        <InputError :message="inventoryForm.errors.description" class="mt-2"/>
                    </div>

                </form>
            </template>
            <template #footer>
                <PrimaryButton type="red" class="mr-3" @click="closeInventoryModal">Отмена</PrimaryButton>
                <PrimaryButton @click.prevent="submitInventoryForm" :disabled="inventoryForm.processing">
                    {{ inventoryModalMode === 'add' ? 'Добавить' : 'Списать' }}
                </PrimaryButton>
            </template>
        </Modal>
        <!-- Модальное окно подтверждения удаления -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal">
            <template #title>
                Подтверждение удаления
            </template>
            <template #content>
                <p>Вы уверены, что хотите удалить материал "{{ materialToDelete?.title }}"?</p>
                <p class="text-sm text-gray-500 mt-2">Это действие необратимо.</p>
            </template>
            <template #footer>
                <PrimaryButton type="red" @click="closeDeleteModal" class="mr-2">Отмена</PrimaryButton>
                <PrimaryButton @click="confirmDelete" :class="{'opacity-25': deleteForm.processing}" :disabled="deleteForm.processing">
                    Удалить
                </PrimaryButton>
            </template>
        </Modal>
    </DashboardLayout>
</template>

<script setup>
import {ref, computed} from 'vue'
import {useForm} from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Modal from '@/Components/Modal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Pagination from "@/Components/Pagination.vue"
import SelectDropdown from "@/Components/SelectDropdown.vue"
import BreadCrumbs from "@/Components/BreadCrumbs.vue"
import ContextMenu from "@/Components/ContextMenu.vue";

const props = defineProps({
    materials: Object,
    units: Array,
})
const showDeleteModal = ref(false)
const materialToDelete = ref(null)
const deleteForm = useForm({})
const showModal = ref(false)
const modalMode = ref('')
const form = useForm({
    id: null,
    title: '',
    unit_id: '',
})

const menuItems = [
    {text: 'Редактировать', action: 'edit'},
    {text: 'Добавить запас', action: 'add-stock'},
    {text: 'Списать', action: 'write-off'},
    {text: 'Удалить', action: 'delete', isDangerous: true}
];

const breadCrumbs = [
    {
        name: 'Материалы',
        link: route('dashboard.materials.index')
    }
]

const showInventoryModal = ref(false)
const inventoryModalMode = ref('')
const inventoryForm = useForm({
    material_id: null,
    quantity: '',
    price_per_unit: '',
    received_date: '',
    description: '',
})

const unitOptions = computed(() => props.units.map(unit => ({
    label: unit.name,
    value: unit.id
})))

const handleContextMenuItemClick = (item, material) => {
    console.log(`Выбрано действие: ${item.action}`);
    switch (item.action) {
        case 'edit':
            openModal('edit', material)
            break
        case 'add-stock':
            openInventoryModal('add', material)
            break
        case 'write-off':
            openInventoryModal('remove', material)
            break
        case 'delete':
            openDeleteModal(material)
            break
    }
};

const openModal = (mode, material = null) => {
    modalMode.value = mode
    if (mode === 'edit') {
        form.id = material.id
        form.title = material.title
        form.unit_id = material.unit_id
    } else {
        form.reset()
    }
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    form.reset()
}

const submitForm = () => {
    if (modalMode.value === 'create') {
        form.post(route('dashboard.materials.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        })
    } else if (modalMode.value === 'edit') {
        form.put(route('dashboard.materials.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        })
    }
}

const openInventoryModal = (mode, material) => {
    inventoryModalMode.value = mode
    inventoryForm.material_id = material.id
    inventoryForm.reset('quantity', 'price_per_unit', 'received_date', 'description')
    showInventoryModal.value = true
}

const closeInventoryModal = () => {
    showInventoryModal.value = false
    inventoryForm.reset()
}

const submitInventoryForm = () => {
    const endpoint = inventoryModalMode.value === 'add'
        ? 'dashboard.inventory.add'
        : 'dashboard.inventory.remove'

    inventoryForm.post(route(endpoint), {
        preserveScroll: true,
        onSuccess: () => closeInventoryModal(),
    })
}

const openDeleteModal = (material) => {
    materialToDelete.value = material
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    materialToDelete.value = null
}

const confirmDelete = () => {
    deleteForm.delete(route('dashboard.materials.destroy', materialToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal()
        },
    })
}
</script>
