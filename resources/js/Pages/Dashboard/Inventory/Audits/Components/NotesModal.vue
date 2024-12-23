<template>
  <Modal :show="show" @close="$emit('close')">
    <template #title>
      Добавить примечание
    </template>
    
    <template #content>
      <div class="mt-4">
        <textarea
          v-model="notes"
          rows="3"
          class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
          placeholder="Введите примечание..."
        ></textarea>
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
          @click="save"
          class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800"
        >
          Сохранить
        </button>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref, watch } from 'vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  show: Boolean,
  initialNotes: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['close', 'save'])

const notes = ref(props.initialNotes)

watch(() => props.initialNotes, (newValue) => {
  notes.value = newValue
})

const save = () => {
  emit('save', notes.value)
}
</script> 