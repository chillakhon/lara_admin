<template>
  <span :class="classes">
    {{ formattedValue }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  value: Number,
  unit: String,
  showSign: {
    type: Boolean,
    default: false
  }
})

const classes = computed(() => {
  const baseClasses = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium dark:bg-opacity-80'
  return props.value > 0
    ? `${baseClasses} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300`
    : `${baseClasses} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300`
})

const formattedValue = computed(() => {
  const sign = props.showSign && props.value > 0 ? '+' : ''
  return `${sign}${props.value} ${props.unit}`
})
</script> 