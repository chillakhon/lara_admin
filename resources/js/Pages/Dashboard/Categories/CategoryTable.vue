<script setup>
import { ref } from 'vue';
import CategoryItem from './CategoryItem.vue';

const props = defineProps({
    categories: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['edit', 'delete', 'add']);

const tableHeaders = ['Название', 'Слаг', 'Действия'];

const search = ref('');
</script>

<template>
    <section class="bg-gray-50 dark:bg-gray-900 p-4 mx-auto">
        <div class=" ">
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th v-for="header in tableHeaders" :key="header" scope="col" class="px-4 py-3">{{ header }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <CategoryItem
                            v-for="category in categories"
                            :key="category.id"
                            :category="category"
                            :search="search"
                            @edit="$emit('edit', $event)"
                            @delete="$emit('delete', $event)"
                        />
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</template>
