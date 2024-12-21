<template>
  <DashboardLayout>
    <template #header>
        <BreadCrumbs :breadcrumbs="breadCrumbs"/>
        <div v-if="audit" class="flex justify-between items-center">
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Инвентаризация {{ audit.number }}
          </h1>
          <div class="flex gap-4">
            <button
              v-if="audit.status === 'draft'"
              @click="startAudit"
              class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800"
            >
              <PlayIcon class="w-4 h-4 mr-2" />
              Начать инвентаризацию
            </button>
            
            <button
              v-if="audit.status === 'in_progress'"
              @click="completeAudit"
              class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white rounded-lg bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 focus:outline-none dark:focus:ring-green-800"
            >
              <CheckIcon class="w-4 h-4 mr-2" />
              Завершить инвентаризацию
            </button>
            
            <button
              v-if="['draft', 'in_progress'].includes(audit.status)"
              @click="cancelAudit"
              class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white rounded-lg bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 focus:outline-none dark:focus:ring-red-800"
            >
              <XMarkIcon class="w-4 h-4 mr-2" />
              Отменить
            </button>
          </div>
        </div>
      </template>
    <template>
      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
          <div v-if="!audit">
            <p>Загрузка данных...</p>
          </div>
          <div v-else-if="!audit.items?.length">
            <p>Нет позиций для инвентаризации</p>
          </div>
          <div v-else>
            <!-- Таблица позиций -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Наименование
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Ожидаемое кол-во
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Фактическое кол-во
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Расхождение
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Сумма расхождения
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Действия
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="item in audit.items" :key="item.id">
                    <td class="px-6 py-4">
                      {{ item.item_type === 'App\\Models\\Product' ? 'Товар' : 'Материал' }}:
                      {{ item.item?.name || item.item?.title }}
                    </td>
                    <td class="px-6 py-4">
                      {{ item.expected_quantity }} {{ item.unit.abbreviation }}
                    </td>
                    <td class="px-6 py-4">
                      <div v-if="audit.status === 'in_progress'">
                        <input
                          type="number"
                          v-model="item.actual_quantity"
                          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-24 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                          step="0.001"
                          min="0"
                          @change="updateQuantity(item)"
                        >
                      </div>
                      <span v-else>
                        {{ item.actual_quantity }} {{ item.unit.abbreviation }}
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <DifferenceBadge
                        v-if="item.difference"
                        :value="item.difference"
                        :unit="item.unit.abbreviation"
                      />
                    </td>
                    <td class="px-6 py-4">
                      <DifferenceBadge
                        v-if="item.difference_cost"
                        :value="item.difference_cost"
                        unit="₽"
                        :show-sign="true"
                      />
                    </td>
                    <td class="px-6 py-4">
                      <button
                        v-if="audit.status === 'in_progress'"
                        @click="showNotes(item)"
                        class="flex items-center text-primary-700 hover:text-primary-800 dark:text-primary-500 dark:hover:text-primary-400"
                      >
                        <PencilIcon class="w-4 h-4 mr-1" />
                        Добавить примечание
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Модальное окно для примечаний -->
      <Modal v-model="showNotesModal">
        <template #title>Добавить примечание</template>
        <template #content>
          <textarea
            v-model="notesForm.notes"
            class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
            rows="3"
            placeholder="Введите примечание..."
          ></textarea>
        </template>
        <template #footer>
          <button
            @click="saveNotes"
            class="btn-primary"
          >
            Сохранить
          </button>
        </template>
      </Modal>
    </template>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import BreadCrumbs from '@/Components/BreadCrumbs.vue'
import { PlayIcon, CheckIcon, XMarkIcon, PencilIcon } from '@heroicons/vue/24/solid'
import StatusBadge from './Components/StatusBadge.vue'
import DifferenceBadge from './Components/DifferenceBadge.vue'
import Modal from '@/Components/Modal.vue'
import { formatDate } from '@/utils/index'

const props = defineProps({
  audit: Object
})

const showNotesModal = ref(false)
const selectedItem = ref(null)
const notesForm = ref({
  notes: ''
})

const startAudit = () => {
  router.post(route('inventory-audits.start', props.audit.id))
}

const completeAudit = () => {
  router.post(route('inventory-audits.complete', props.audit.id))
}

const cancelAudit = () => {
  if (confirm('Вы уверены, что хотите отменить инвентаризацию?')) {
    router.post(route('inventory-audits.cancel', props.audit.id))
  }
}

const updateQuantity = (item) => {
  router.post(route('inventory-audits.update-quantity', item.id), {
    actual_quantity: item.actual_quantity
  })
}

const showNotes = (item) => {
  selectedItem.value = item
  notesForm.value.notes = item.notes || ''
  showNotesModal.value = true
}

const saveNotes = () => {
  router.post(route('inventory-audits.update-quantity', selectedItem.value.id), {
    actual_quantity: selectedItem.value.actual_quantity,
    notes: notesForm.value.notes
  }, {
    onSuccess: () => {
      showNotesModal.value = false
      selectedItem.value = null
    }
  })
}

const breadCrumbs = computed(() => [
  { name: 'Управление запасами', link: route('dashboard.inventory.index') },
  { name: 'Инвентаризации', link: route('inventory-audits.index') },
  ...(props.audit ? [{ name: props.audit.number }] : [])
])

onMounted(() => {
  console.log('Component mounted');
  console.log('Current route:', route().current());
  console.log('Route params:', route().params);
  console.log('Full audit data:', props.audit);
  
  if (props.audit?.items) {
    console.log('Items count:', props.audit.items.length);
  }
});
</script> 