<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import CategoryTable from "./CategoryTable.vue";
import Modal from "@/Components/Modal.vue";
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import SelectDropdown from "@/Components/SelectDropdown.vue";

const props = defineProps({
    categories: Array
})

const breadCrumbs = [
    {
        name: 'Категории',
        link: route('dashboard.categories.index')
    }
]

const showModal = ref(false)
const modalMode = ref('create')
const selectedCategory = ref(null)
const form = useForm({
    name: '',
    parent_id: null
})

const flattenedCategories = computed(() => {
    const flatten = (categories, depth = 0) => {
        return categories.reduce((acc, category) => {
            const flatCategory = {
                ...category,
                depth,
                label: '- '.repeat(depth) + category.name
            };
            acc.push(flatCategory);
            if (category.children && category.children.length) {
                acc.push(...flatten(category.children, depth + 1));
            }
            return acc;
        }, []);
    };
    return flatten(props.categories);
});

const openModal = (mode, category = null) => {
    modalMode.value = mode
    selectedCategory.value = category
    if (mode === 'edit' && category) {
        form.name = category.name
        form.parent_id = category.parent_id
    } else {
        form.reset()
    }
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    form.reset()
    selectedCategory.value = null
}

const submitForm = () => {
    if (modalMode.value === 'create') {
        form.post(route('dashboard.categories.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    } else if (modalMode.value === 'edit') {
        form.put(route('dashboard.categories.update', selectedCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    }
}

const deleteCategory = () => {
    if (selectedCategory.value) {
        form.delete(route('dashboard.categories.destroy', selectedCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    }
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white mb-4 sm:mb-0">Категории</h1>
            <div class="sm:flex sm:justify-end">

                <div class="flex items-center ml-auto space-x-2 sm:space-x-3">
                    <PrimaryButton type="default" @click="openModal('create')"
                                   class="w-full sm:w-auto flex justify-center">
                        <template #icon-left>
                            <svg class="w-5 h-5  -ml-1" fill="currentColor" viewBox="0 0 20 20"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                      d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                      clip-rule="evenodd"></path>
                            </svg>
                        </template>
                        <span > Создать категорию</span>
                    </PrimaryButton>
                </div>
            </div>
        </template>
        <template #default>
            <CategoryTable
                :categories="categories"
                @edit="openModal('edit', $event)"
                @delete="openModal('delete', $event)"
            />

            <Modal :show="showModal" @close="closeModal">
                <template #title v-if="modalMode === 'create'">
                    Создать категорию
                </template>
                <template #title v-if="modalMode === 'edit'">
                    Редактировать категорию
                </template>
                <template #title v-if="modalMode === 'delete'">
                    Удалить категорию
                </template>

                <template #content v-if="modalMode !== 'delete'">
                    <form @submit.prevent="submitForm" v-if="modalMode !== 'delete'">
                        <div class="mt-4">
                            <TextInput label="Название" v-model="form.name"></TextInput>
                        </div>
                        <div class="mt-4">
                            <SelectDropdown
                                class="w-full"
                                v-model="form.parent_id"
                                :options="flattenedCategories"
                                label="Родительская
                                категория"
                                placeholder="Без родителя"
                                value-key="id"
                                label-key="name"
                                children-key="children"
                                null-label="Без категории"
                            ></SelectDropdown>
                        </div>
                    </form>
                </template>
                <template v-else #content>
                    <p class="mt-4">Вы уверены, что хотите удалить эту категорию?</p>
                </template>
                <template #footer v-if="modalMode !== 'delete'">
                    <PrimaryButton @click="submitForm">{{ modalMode === 'create' ? 'Создать' : 'Обновить' }}</PrimaryButton>
                    <PrimaryButton type="red" @click="closeModal">Отмена</PrimaryButton>
                </template>
                <template #footer v-else>
                    <PrimaryButton @click="deleteCategory" class="mr-3">Удалить</PrimaryButton>
                    <PrimaryButton type="red" @click="closeModal">Отмена</PrimaryButton>
                </template>
            </Modal>

        </template>
    </DashboardLayout>
</template>
