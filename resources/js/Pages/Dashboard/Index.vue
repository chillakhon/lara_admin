<script setup>
import { Head } from '@inertiajs/vue3';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {FwbButton} from "flowbite-vue";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
</script>

<template>
    <Head title="Dashboard" />

    <DashboardLayout>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">Место для статистик</div>
                    <primary-button
                    >
                        <template #icon-left >
                            <svg class="w-3.5 h-3.5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 21">
                                <path d="M15 12a1 1 0 0 0 .962-.726l2-7A1 1 0 0 0 17 3H3.77L3.175.745A1 1 0 0 0 2.208 0H1a1 1 0 0 0 0 2h.438l.6 2.255v.019l2 7 .746 2.986A3 3 0 1 0 9 17a2.966 2.966 0 0 0-.184-1h2.368c-.118.32-.18.659-.184 1a3 3 0 1 0 3-3H6.78l-.5-2H15Z"/>
                            </svg>
                        </template>
                        Пример кнопки</primary-button>

                    <fwb-button color="default">Default</fwb-button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
