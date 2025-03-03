<template>
    <div class="border-t dark:border-gray-700 p-4">
        <div class="flex items-start gap-2">
            <textarea
                v-model="localValue"
                rows="3"
                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                placeholder="Введите сообщение..."
                @keydown.enter.prevent="handleEnter"
            ></textarea>
            
            <div class="flex flex-col gap-2">
                <input
                    type="file"
                    ref="fileInput"
                    class="hidden"
                    multiple
                    @change="handleFiles"
                />
                
                <PrimaryButton
                    type="button"
                    @click="$refs.fileInput.click()"
                    :disabled="loading"
                    size="sm"
                    iconOnly
                >
                    <PaperClipIcon class="w-5 h-5" />
                </PrimaryButton>

                <PrimaryButton
                    @click="$emit('send')"
                    :disabled="!localValue.trim() || loading"
                    :loading="loading"
                    size="sm"
                    iconOnly
                >
                    <PaperAirplaneIcon class="w-5 h-5" />
                </PrimaryButton>
            </div>
        </div>

        <!-- Превью файлов -->
        <div v-if="files.length" class="mt-2 flex flex-wrap gap-2">
            <div 
                v-for="(file, index) in files" 
                :key="index"
                class="relative bg-gray-100 dark:bg-gray-700 rounded p-2"
            >
                <button
                    @click="removeFile(index)"
                    class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-1"
                >
                    <XMarkIcon class="w-4 h-4" />
                </button>
                <span class="text-sm">{{ file.name }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { PaperClipIcon, PaperAirplaneIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue', 'send']);

const localValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const files = ref([]);
const fileInput = ref(null);

const handleEnter = (e) => {
    if (!e.shiftKey) {
        emit('send');
    }
};

const handleFiles = (e) => {
    files.value.push(...Array.from(e.target.files));
    fileInput.value.value = '';
};

const removeFile = (index) => {
    files.value.splice(index, 1);
};
</script> 