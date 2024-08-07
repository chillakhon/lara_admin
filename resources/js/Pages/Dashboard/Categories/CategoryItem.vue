<script setup>
defineProps({
    category: {
        type: Object,
        required: true
    }
})

defineEmits(['edit', 'delete'])
</script>
<template>
    <div class="category-item" :style="{ marginLeft: `${category.depth * 20}px` }">
        <div class="flex items-center justify-between py-2">
            <span class="text-gray-800">{{ category.name }}</span>
            <div>
                <button @click="$emit('edit', category)" class="text-indigo-600 hover:text-indigo-800 mr-2 focus:outline-none">
                    Edit
                </button>
                <button @click="$emit('delete', category)" class="text-red-600 hover:text-red-800 focus:outline-none">
                    Delete
                </button>
            </div>
        </div>
        <div v-if="category.children && category.children.length">
            <CategoryItem
                v-for="child in category.children"
                :key="child.id"
                :category="child"
                @edit="$emit('edit', $event)"
                @delete="$emit('delete', $event)"
            />
        </div>
    </div>
</template>

