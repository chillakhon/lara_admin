<template>
    <div class="space-y-4">
        <!-- Preview Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="(image, index) in images" :key="index" class="relative">
                <img 
                    :src="getPreviewUrl(image)" 
                    class="h-32 w-full object-cover rounded-lg"
                />
                <button 
                    @click="removeImage(index)"
                    class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
                >
                    <XMarkIcon class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Upload Input -->
        <div class="flex items-center justify-center w-full">
            <label 
                :for="id"
                class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600"
            >
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <ArrowUpTrayIcon class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-semibold">Нажмите для загрузки</span>
                    </p>
                </div>
                <input 
                    :id="id" 
                    type="file" 
                    class="hidden" 
                    accept="image/*"
                    multiple
                    @change="handleFileChange"
                    :required="required && images.length === 0"
                />
            </label>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { XMarkIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    id: {
        type: String,
        required: true
    },
    modelValue: {
        type: Array,
        default: () => []
    },
    required: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const images = ref([]);

watch(() => props.modelValue, (newValue) => {
    images.value = newValue || [];
}, { immediate: true });

const getPreviewUrl = (image) => {
    if (typeof image === 'string') {
        return image;
    } else if (image instanceof File) {
        return URL.createObjectURL(image);
    }
    return '';
};

const handleFileChange = (event) => {
    const files = Array.from(event.target.files);
    const validFiles = files.filter(file => {
        if (file.size > 2 * 1024 * 1024) { // 2MB
            alert(`Файл ${file.name} слишком большой. Максимальный размер 2MB`);
            return false;
        }
        return true;
    });
    
    emit('update:modelValue', [...images.value, ...validFiles]);
    event.target.value = '';
};

const removeImage = (index) => {
    const newImages = [...images.value];
    newImages.splice(index, 1);
    emit('update:modelValue', newImages);
};
</script> 