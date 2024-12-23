<template>
  <Modal :show="show" @close="$emit('close')">
    <template #title>
      {{ title }}
    </template>
    
    <template #content>
      <div class="mt-2">
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ message }}
        </p>
      </div>
    </template>
    
    <template #footer>
      <div class="flex justify-end gap-4">
        <button
          @click="$emit('close')"
          class="flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
        >
          Отмена
        </button>
        <button
          @click="$emit('confirm')"
          class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white rounded-lg bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 focus:outline-none dark:focus:ring-red-800"
        >
          Подтвердить
        </button>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import Modal from '@/Components/Modal.vue'

defineProps({
  show: Boolean,
  title: String,
  message: String
})

defineEmits(['close', 'confirm'])
</script> 