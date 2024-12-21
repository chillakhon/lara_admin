<template>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-6">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
        Всего позиций
      </dt>
      <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
        {{ stats.total }}
      </dd>
    </div>
    
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
        Проверено
      </dt>
      <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
        {{ stats.counted }}
      </dd>
    </div>
    
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
        С расхождениями
      </dt>
      <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
        {{ stats.withDifference }}
      </dd>
    </div>
  </div>
</template>

<script setup>
defineProps({
  stats: {
    type: Object,
    required: true
  }
})
</script> 