<template>
  <span :class="classes">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: String
})

const statusConfig = {
  draft: {
    label: 'Черновик',
    classes: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  },
  in_progress: {
    label: 'В процессе',
    classes: 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300'
  },
  completed: {
    label: 'Завершена',
    classes: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
  },
  cancelled: {
    label: 'Отменена',
    classes: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
  }
}

const config = computed(() => statusConfig[props.status] || statusConfig.draft)

const label = computed(() => config.value.label)
const classes = computed(() => 
  `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.value.classes}`
)
</script> 