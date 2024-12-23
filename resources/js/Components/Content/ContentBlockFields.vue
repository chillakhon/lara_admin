<template>
    <div class="space-y-4">
        <div v-for="field in block.field_group.fields" :key="field.id">
            <InputLabel :for="field.key" :value="field.name" />
            
            <!-- Text Input -->
            <template v-if="field.field_type.type === 'text'">
                <TextInput
                    :id="field.key"
                    v-model="fieldValues[field.key]"
                    type="text"
                    :required="field.required"
                />
            </template>

            <!-- Textarea -->
            <template v-else-if="field.field_type.type === 'textarea'">
                <textarea
                    :id="field.key"
                    v-model="fieldValues[field.key]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    :required="field.required"
                    rows="4"
                ></textarea>
            </template>

            <!-- WYSIWYG Editor -->
            <template v-else-if="field.field_type.type === 'wysiwyg'">
                <Editor
                    v-model="fieldValues[field.key]"
                    :api-key="tinymceApiKey"
                    :init="{
                        height: 300,
                        menubar: false,
                        plugins: [
                            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                            'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
                        ],
                        toolbar: 'undo redo | blocks | ' +
                            'bold italic forecolor | alignleft aligncenter ' +
                            'alignright alignjustify | bullist numlist outdent indent | ' +
                            'removeformat | help'
                    }"
                />
            </template>

            <!-- Image Upload -->
            <template v-else-if="field.field_type.type === 'image'">
                <ImageUpload
                    :id="field.key"
                    v-model="fieldValues[field.key]"
                    :required="field.required"
                />
            </template>

            <!-- Gallery Upload -->
            <template v-else-if="field.field_type.type === 'gallery'">
                <GalleryUpload
                    :id="field.key"
                    v-model="fieldValues[field.key]"
                    :required="field.required"
                />
            </template>

            <!-- Select -->
            <template v-else-if="field.field_type.type === 'select'">
                <SelectDropdown
                    :id="field.key"
                    v-model="fieldValues[field.key]"
                    :options="getSelectOptions(field)"
                    :required="field.required"
                />
            </template>

            <!-- Checkbox -->
            <template v-else-if="field.field_type.type === 'checkbox'">
                <div class="flex items-center">
                    <Checkbox
                        :id="field.key"
                        v-model:checked="fieldValues[field.key]"
                        :required="field.required"
                    />
                    <label :for="field.key" class="ml-2">{{ field.name }}</label>
                </div>
            </template>

            <InputError :message="errors[field.key]" />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import SelectDropdown from '@/Components/SelectDropdown.vue';
import Editor from '@tinymce/tinymce-vue';
import ImageUpload from '@/Components/Content/ImageUpload.vue';
import GalleryUpload from '@/Components/Content/GalleryUpload.vue';

const props = defineProps({
    block: {
        type: Object,
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

const fieldValues = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const tinymceApiKey = 'rtcuy3rxu49l801jzdbnuksxfjceukc5cjoq4dv4uzcpr33a'; // Замените на ваш API ключ TinyMCE

const getSelectOptions = (field) => {
    if (field.settings && field.settings.options) {
        return field.settings.options.map(option => ({
            value: option.value,
            label: option.label
        }));
    }
    return [];
};

// Инициализация значений полей по умолчанию
watch(() => props.block, (newBlock) => {
    const defaultValues = {};
    newBlock.field_group.fields.forEach(field => {
        if (!(field.key in fieldValues.value)) {
            defaultValues[field.key] = field.field_type.type === 'checkbox' ? false : '';
        }
    });
    if (Object.keys(defaultValues).length > 0) {
        fieldValues.value = { ...fieldValues.value, ...defaultValues };
    }
}, { immediate: true });
</script> 