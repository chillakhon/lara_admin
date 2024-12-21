<template>
    <div class="space-y-2">
        <!-- Preview -->
        <div v-if="preview" class="relative w-48 h-48">
            <img :src="preview" class="w-full h-full object-cover rounded-lg" />
            <button 
                @click="removeImage" 
                class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
            >
                <XMarkIcon class="w-4 h-4" />
            </button>
        </div>

        <!-- Upload Input -->
        <div v-if="!preview" class="flex items-center justify-center w-full">
            <label 
                :for="id"
                class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600"
            >
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <ArrowUpTrayIcon class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" />
                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-semibold">Нажмите для загрузки</span> или перетащите файл
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        PNG, JPG или GIF (макс. 2MB)
                    </p>
                </div>
                <input 
                    :id="id" 
                    type="file" 
                    class="hidden" 
                    accept="image/*"
                    @change="handleFileChange"
                    :required="required && !modelValue"
                />
            </label>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { XMarkIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    id: {
        type: String,
        required: true
    },
    modelValue: {
        type: [String, File],
        default: null
    },
    required: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const preview = ref(null);

// Обновляем превью при изменении modelValue
watch(() => props.modelValue, (newValue) => {
    if (typeof newValue === 'string') {
        preview.value = newValue;
    } else if (newValue instanceof File) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.value = e.target.result;
        };
        reader.readAsDataURL(newValue);
    } else {
        preview.value = null;
    }
}, { immediate: true });

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) { // 2MB
            alert('Файл слишком большой. Максимальный размер 2MB');
            event.target.value = '';
            return;
        }
        emit('update:modelValue', file);
    }
};

const removeImage = () => {
    emit('update:modelValue', null);
    preview.value = null;
};
</script> 