<template>
  <div class="p-4 bg-white dark:bg-gray-800 sm:flex sm:items-center sm:justify-between">
    <div class="relative w-full sm:w-96">
      <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
        <MagnifyingGlassIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" />
      </div>
      <input
        type="text"
        v-model="search"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
        placeholder="Поиск по номеру..."
      >
    </div>
    
    <div class="flex items-center gap-4 mt-4 sm:mt-0">
      <select
        v-model="filters.status"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
      >
        <option value="">Все статусы</option>
        <option value="draft">Черновик</option>
        <option value="in_progress">В процессе</option>
        <option value="completed">Завершена</option>
        <option value="cancelled">Отменена</option>
      </select>

      <button
        @click="resetFilters"
        class="flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
      >
        <ArrowPathIcon class="w-4 h-4 mr-2" />
        Сбросить
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { MagnifyingGlassIcon, ArrowPathIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
  filters: Object
})

const search = ref(props.filters.search || '')
const filters = ref({
  status: props.filters.status || ''
})

watch([search, filters], ([newSearch, newFilters]) => {
  router.get(
    route('inventory-audits.index'),
    { search: newSearch, ...newFilters },
    { preserveState: true, preserveScroll: true }
  )
}, { deep: true })

const resetFilters = () => {
  search.value = ''
  filters.value = {
    status: ''
  }
}
</script> 