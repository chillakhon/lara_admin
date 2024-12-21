<template>
  <DashboardLayout>
    <template #header>
      <BreadCrumbs :breadcrumbs="breadCrumbs"/>
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Создание инвентаризации
        </h1>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-4 lg:px-4">
        <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden p-6">
          <form @submit.prevent="submit">
            <div class="mb-6">
              <p class="text-gray-600 dark:text-gray-400 mb-4">
                При создании инвентаризации будет автоматически сформирован список всех товаров и материалов с текущими остатками.
              </p>
            </div>

            <div class="flex items-center justify-end gap-4">
              <Link
                :href="route('inventory-audits.index')"
                class="flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
              >
                <ArrowLeftIcon class="w-4 h-4 mr-2" />
                Назад
              </Link>
              <button
                type="submit"
                class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800"
                :disabled="form.processing"
              >
                <PlusIcon class="w-4 h-4 mr-2" />
                Создать инвентаризацию
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import BreadCrumbs from '@/Components/BreadCrumbs.vue'
import { PlusIcon, ArrowLeftIcon } from '@heroicons/vue/24/solid'

const form = useForm({})

const breadCrumbs = [
  { name: 'Управление запасами', link: route('dashboard.inventory.index') },
  { name: 'Инвентаризации', link: route('inventory-audits.index') },
  { name: 'Создание' }
]

const submit = () => {
  form.post(route('inventory-audits.store'))
}
</script> 