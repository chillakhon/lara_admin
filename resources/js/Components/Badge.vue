<template>
    <a v-if="href" :href="href">
    <span :class="classes">
      <slot name="icon" v-if="$slots.icon" class="w-3 h-3" />
      <slot />
    </span>
    </a>
    <span v-else :class="classes">
    <slot name="icon" v-if="$slots.icon" class="w-3 h-3" />
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: {
        type: String,
        default: 'default',
        validator: (value) => {
            return ['default', 'dark', 'red', 'green', 'yellow', 'indigo', 'purple', 'pink'].includes(value)
        }
    },
    size: {
        type: String,
        default: 'default',
        validator: (value) => {
            return ['default', 'sm', 'lg'].includes(value)
        }
    },
    rounded: {
        type: Boolean,
        default: false
    },
    icon: {
        type: Boolean,
        default: false
    },
    href: {
        type: String,
        default: ''
    }
})

const baseClasses = 'flex items-center font-medium'

const typeClasses = computed(() => {
    const classes = {
        default: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        dark: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        red: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        pink: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300'
    }
    return classes[props.type]
})

const sizeClasses = computed(() => {
    const classes = {
        sm: 'text-xs px-2 py-0.5',
        default: 'text-sm px-2.5 py-0.5',
        lg: 'text-base px-3 py-1'
    }
    return classes[props.size]
})

const roundedClass = computed(() => {
    return props.rounded ? 'rounded-full' : 'rounded'
})

const iconClass = computed(() => {
    return props.icon ? 'gap-1' : ''
})

const classes = computed(() => {
    return `${baseClasses} ${typeClasses.value} ${sizeClasses.value} ${roundedClass.value} ${iconClass.value}`
})
</script>
