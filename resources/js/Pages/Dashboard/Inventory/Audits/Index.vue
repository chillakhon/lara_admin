<template>
  <DashboardLayout>
    <template #header>
      <BreadCrumbs :breadcrumbs="breadCrumbs"/>
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Инвентаризации
        </h1>
        <Link
          :href="route('inventory-audits.create')"
          class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Создать инвентаризацию
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
        <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Номер
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Статус
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Создал
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Дата создания
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Действия
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="audit in audits.data" :key="audit.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  {{ audit.number }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <StatusBadge :status="audit.status" />
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  {{ audit.creator?.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  {{ formatDate(audit.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <Link
                    :href="route('inventory-audits.show', audit.id)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    Просмотр
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <Pagination class="mt-6" :links="audits.links" />
    </div>
  </DashboardLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import StatusBadge from './Components/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'
import { formatDate } from '@/utils/index'

defineProps({
  audits: Object
})

const breadCrumbs = [
  { name: 'Управление запасами', link: route('dashboard.inventory.index') },
  { name: 'Инвентаризации', link: route('inventory-audits.index') }
];
</script> 