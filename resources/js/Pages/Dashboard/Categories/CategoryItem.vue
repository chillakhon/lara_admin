<script setup>
import { computed } from 'vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    category: {
        type: Object,
        required: true
    },
    search: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['edit', 'delete']);

const isVisible = computed(() => {
    if (!props.search) return true;
    return props.category.name.toLowerCase().includes(props.search.toLowerCase()) ||
        props.category.slug.toLowerCase().includes(props.search.toLowerCase());
});

const paddingLeft = computed(() => `${props.category.depth * 20 + 12}px`);
</script>

<template>
    <template v-if="isVisible">
        <tr class="border-b dark:border-gray-700">
            <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white" :style="{ paddingLeft }">
                {{ category.name }}
            </th>
            <td class="px-4 py-3">{{ category.slug }}</td>
            <td class="px-4 py-3 flex items-center justify-end gap-4">
                <PrimaryButton @click="$emit('edit', category)"  type="default" size="xs">
                    Редактировать
                </PrimaryButton>
                <PrimaryButton @click="$emit('delete', category)"  type="red" size="xs">
                    Удалить
                </PrimaryButton>
            </td>
        </tr>
        <CategoryItem
            v-for="child in category.children"
            :key="child.id"
            :category="child"
            :search="search"
            @edit="$emit('edit', $event)"
            @delete="$emit('delete', $event)"
        />
    </template>
</template>
