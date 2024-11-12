<template>
    <div class="w-full">
        <slot name="disclosure">
            <button
                type="button"
                class="flex items-center justify-between w-full p-5 font-medium text-left text-gray-900 border border-gray-200 rounded-t-xl hover:bg-gray-100 focus:ring-4 focus:ring-gray-200"
                @click="toggle"
                :aria-expanded="isOpen"
                :aria-controls="id"
            >
                <slot name="title">{{ title }}</slot>
                <svg
                    :class="{ 'rotate-180': isOpen }"
                    class="w-6 h-6 transform transition-transform duration-200"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    ></path>
                </svg>
            </button>
        </slot>

        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="transform scale-y-95 opacity-0"
            enter-to-class="transform scale-y-100 opacity-100"
            leave-active-class="transition duration-200 ease-out"
            leave-from-class="transform scale-y-100 opacity-100"
            leave-to-class="transform scale-y-95 opacity-0"
        >
            <div
                v-show="isOpen"
                :id="id"
                class="p-5 border border-t-0 border-gray-200 rounded-b-xl"
            >
                <slot></slot>
            </div>
        </transition>
    </div>
</template>

<script setup>
import {ref, computed, watch} from 'vue'


const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    modelValue: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])

const id = computed(() => `disclosure-id`)
const isOpen = ref(props.modelValue)

const toggle = () => {
    isOpen.value = !isOpen.value
    emit('update:modelValue', isOpen.value)
}

// Следим за изменениями modelValue извне
watch(() => props.modelValue, (newValue) => {
    isOpen.value = newValue
})
</script>
