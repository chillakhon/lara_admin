<template>
    <div class="space-y-4">
        <template v-for="field in fields" :key="field.id">
            <!-- Repeater Field -->
            <div v-if="field.type === 'repeater'" class="space-y-4">
                <div class="flex justify-between items-center">
                    <InputLabel :value="field.name" />
                    <PrimaryButton type="button" @click="addRepeaterItem(field)">
                        <PlusIcon class="w-4 h-4 mr-2" />
                        Добавить блок
                    </PrimaryButton>
                </div>

                <draggable 
                    v-model="modelValue[field.key]" 
                    class="space-y-4"
                    item-key="id"
                    handle=".drag-handle"
                    @end="updateOrder"
                >
                    <template #item="{ element, index }">
                        <div class="border rounded-lg p-4 bg-white dark:bg-gray-800">
                            <div class="flex justify-between items-center mb-4">
                                <div class="drag-handle cursor-move">
                                    <Bars3Icon class="w-5 h-5 text-gray-400" />
                                </div>
                                <button @click="removeRepeaterItem(field.key, index)" 
                                        class="text-red-600 hover:text-red-700">
                                    <XMarkIcon class="w-5 h-5" />
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <FieldRenderer
                                    :fields="field.children"
                                    v-model="element"
                                    :errors="errors"
                                />
                            </div>
                        </div>
                    </template>
                </draggable>
            </div>

            <!-- Text Field -->
            <div v-else-if="field.type === 'text'">
                <InputLabel :for="field.key" :value="field.name" />
                <TextInput
                    :id="field.key"
                    v-model="modelValue[field.key]"
                    type="text"
                    :required="field.required"
                />
                <InputError :message="errors[field.key]" />
            </div>

            <!-- Textarea Field -->
            <div v-else-if="field.type === 'textarea'">
                <InputLabel :for="field.key" :value="field.name" />
                <textarea
                    :id="field.key"
                    v-model="modelValue[field.key]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    :required="field.required"
                    rows="4"
                ></textarea>
                <InputError :message="errors[field.key]" />
            </div>

            <!-- WYSIWYG Editor -->
            <div v-else-if="field.type === 'wysiwyg'">
                <InputLabel :for="field.key" :value="field.name" />
                <Editor
                    v-model="modelValue[field.key]"
                    :api-key="tinymceApiKey"
                    :init="{
                        height: field.settings?.height || 300,
                        menubar: false,
                        plugins: [
                            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                            'insertdatetime', 'media', 'table', 'help', 'wordcount'
                        ],
                        toolbar: field.settings?.toolbar === 'full' 
                            ? 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help'
                            : 'undo redo | bold italic | bullist numlist | link',
                    }"
                />
                <InputError :message="errors[field.key]" />
            </div>

            <!-- Image Upload -->
            <div v-else-if="field.type === 'image'">
                <InputLabel :for="field.key" :value="field.name" />
                <ImageUpload
                    :id="field.key"
                    v-model="modelValue[field.key]"
                    :required="field.required"
                />
                <InputError :message="errors[field.key]" />
            </div>

            <!-- Gallery Upload -->
            <div v-else-if="field.type === 'gallery'">
                <InputLabel :for="field.key" :value="field.name" />
                <GalleryUpload
                    :id="field.key"
                    v-model="modelValue[field.key]"
                    :required="field.required"
                    :settings="field.settings"
                />
                <InputError :message="errors[field.key]" />
            </div>

            <!-- Select Field -->
            <div v-else-if="field.type === 'select'">
                <InputLabel :for="field.key" :value="field.name" />
                <SelectDropdown
                    :id="field.key"
                    v-model="modelValue[field.key]"
                    :options="field.settings?.options || []"
                    :required="field.required"
                />
                <InputError :message="errors[field.key]" />
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import SelectDropdown from '@/Components/SelectDropdown.vue';
import Editor from '@tinymce/tinymce-vue';
import ImageUpload from '@/Components/Content/ImageUpload.vue';
import GalleryUpload from '@/Components/Content/GalleryUpload.vue';
import { PlusIcon, XMarkIcon, Bars3Icon } from '@heroicons/vue/24/outline';
import draggable from 'vuedraggable';

const props = defineProps({
    fields: {
        type: Array,
        required: true
    },
    modelValue: {
        type: Object,
        default: () => ({})
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

const tinymceApiKey = 'your-api-key';

const addRepeaterItem = (field) => {
    const values = props.modelValue[field.key] || [];
    const newItem = {};
    
    // Инициализируем значения по умолчанию для дочерних полей
    field.children.forEach(childField => {
        newItem[childField.key] = childField.type === 'repeater' ? [] : '';
    });

    emit('update:modelValue', {
        ...props.modelValue,
        [field.key]: [...values, newItem]
    });
};

const removeRepeaterItem = (key, index) => {
    const values = [...props.modelValue[key]];
    values.splice(index, 1);
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: values
    });
};

const updateOrder = () => {
    emit('update:modelValue', { ...props.modelValue });
};
</script>

<style>
.drag-handle {
    cursor: move;
    cursor: -webkit-grabbing;
}
</style> 