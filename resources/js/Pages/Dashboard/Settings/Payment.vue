<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                Настройки платежных систем
            </h1>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <!-- ЮKassa -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium">ЮKassa</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center">
                                <Checkbox v-model:checked="form.yookassa_enabled" />
                                <span class="ml-2">Включить ЮKassa</span>
                            </div>
                            <TextInput
                                v-model="form.yookassa_shop_id"
                                label="ID магазина"
                                :disabled="!form.yookassa_enabled"
                            />
                            <TextInput
                                v-model="form.yookassa_secret_key"
                                label="Секретный ключ"
                                type="password"
                                :disabled="!form.yookassa_enabled"
                            />
                            <div class="flex items-center">
                                <Checkbox v-model:checked="form.yookassa_test_mode" :disabled="!form.yookassa_enabled" />
                                <span class="ml-2">Тестовый режим</span>
                            </div>
                        </div>
                    </div>

                    <!-- YandexPay -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium">YandexPay</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center">
                                <Checkbox v-model:checked="form.yandexpay_enabled" />
                                <span class="ml-2">Включить YandexPay</span>
                            </div>
                            <TextInput
                                v-model="form.yandexpay_merchant_id"
                                label="ID мерчанта"
                                :disabled="!form.yandexpay_enabled"
                            />
                            <TextInput
                                v-model="form.yandexpay_api_key"
                                label="API ключ"
                                type="password"
                                :disabled="!form.yandexpay_enabled"
                            />
                        </div>
                    </div>

                    <!-- Robokassa -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium">Robokassa</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center">
                                <Checkbox v-model:checked="form.robokassa_enabled" />
                                <span class="ml-2">Включить Robokassa</span>
                            </div>
                            <TextInput
                                v-model="form.robokassa_login"
                                label="Логин магазина"
                                :disabled="!form.robokassa_enabled"
                            />
                            <TextInput
                                v-model="form.robokassa_password1"
                                label="Пароль #1"
                                type="password"
                                :disabled="!form.robokassa_enabled"
                            />
                            <TextInput
                                v-model="form.robokassa_password2"
                                label="Пароль #2"
                                type="password"
                                :disabled="!form.robokassa_enabled"
                            />
                            <div class="flex items-center">
                                <Checkbox v-model:checked="form.robokassa_test_mode" :disabled="!form.robokassa_enabled" />
                                <span class="ml-2">Тестовый режим</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <PrimaryButton type="submit" :disabled="form.processing">
                            Сохранить настройки
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    settings: Object
});

const breadCrumbs = [
    { name: 'Настройки', link: route('dashboard.settings.general') },
    { name: 'Платежные системы', link: route('dashboard.settings.payment') }
];

const form = useForm({
    yookassa_enabled: props.settings.yookassa_enabled ?? false,
    yookassa_shop_id: props.settings.yookassa_shop_id ?? '',
    yookassa_secret_key: props.settings.yookassa_secret_key ?? '',
    yookassa_test_mode: props.settings.yookassa_test_mode ?? false,

    yandexpay_enabled: props.settings.yandexpay_enabled ?? false,
    yandexpay_merchant_id: props.settings.yandexpay_merchant_id ?? '',
    yandexpay_api_key: props.settings.yandexpay_api_key ?? '',

    robokassa_enabled: props.settings.robokassa_enabled ?? false,
    robokassa_login: props.settings.robokassa_login ?? '',
    robokassa_password1: props.settings.robokassa_password1 ?? '',
    robokassa_password2: props.settings.robokassa_password2 ?? '',
    robokassa_test_mode: props.settings.robokassa_test_mode ?? false,
});

const submit = () => {
    form.post(route('dashboard.settings.payment.update'));
};
</script> 