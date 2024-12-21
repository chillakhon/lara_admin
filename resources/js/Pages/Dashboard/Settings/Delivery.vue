<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                Настройки служб доставки
            </h1>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <!-- СДЭК -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium">СДЭК</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center">
                                <Checkbox v-model:checked="form.cdek_enabled" />
                                <span class="ml-2">Включить СДЭК</span>
                            </div>
                            <TextInput
                                v-model="form.cdek_account"
                                label="Аккаунт"
                                :disabled="!form.cdek_enabled"
                            />
                            <TextInput
                                v-model="form.cdek_password"
                                label="Пароль"
                                type="password"
                                :disabled="!form.cdek_enabled"
                            />
                            <div class="flex items-center">
                                <Checkbox v-model:checked="form.cdek_test_mode" :disabled="!form.cdek_enabled" />
                                <span class="ml-2">Тестовый режим</span>
                            </div>
                            <TextInput
                                v-model="form.cdek_sender_city_id"
                                label="Город отправителя (ID)"
                                :disabled="!form.cdek_enabled"
                            />
                        </div>
                    </div>

                    <!-- Почта России -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium">Почта России</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center">
                                <Checkbox v-model:checked="form.russian_post_enabled" />
                                <span class="ml-2">Включить Почту России</span>
                            </div>
                            <TextInput
                                v-model="form.russian_post_token"
                                label="API токен"
                                type="password"
                                :disabled="!form.russian_post_enabled"
                            />
                            <TextInput
                                v-model="form.russian_post_login"
                                label="Логин"
                                :disabled="!form.russian_post_enabled"
                            />
                            <TextInput
                                v-model="form.russian_post_password"
                                label="Пароль"
                                type="password"
                                :disabled="!form.russian_post_enabled"
                            />
                            <div class="flex items-center">
                                <Checkbox v-model:checked="form.russian_post_test_mode" :disabled="!form.russian_post_enabled" />
                                <span class="ml-2">Тестовый режим</span>
                            </div>
                        </div>
                    </div>

                    <!-- Boxberry -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium">Boxberry</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center">
                                <Checkbox v-model:checked="form.boxberry_enabled" />
                                <span class="ml-2">Включить Boxberry</span>
                            </div>
                            <TextInput
                                v-model="form.boxberry_token"
                                label="API токен"
                                type="password"
                                :disabled="!form.boxberry_enabled"
                            />
                            <TextInput
                                v-model="form.boxberry_sender_city_id"
                                label="Город отправителя (ID)"
                                :disabled="!form.boxberry_enabled"
                            />
                            <div class="flex items-center">
                                <Checkbox v-model:checked="form.boxberry_test_mode" :disabled="!form.boxberry_enabled" />
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
    { name: 'Службы доставки', link: route('dashboard.settings.delivery') }
];

const form = useForm({
    cdek_enabled: props.settings.cdek_enabled ?? false,
    cdek_account: props.settings.cdek_account ?? '',
    cdek_password: props.settings.cdek_password ?? '',
    cdek_test_mode: props.settings.cdek_test_mode ?? false,
    cdek_sender_city_id: props.settings.cdek_sender_city_id ?? '',

    russian_post_enabled: props.settings.russian_post_enabled ?? false,
    russian_post_token: props.settings.russian_post_token ?? '',
    russian_post_login: props.settings.russian_post_login ?? '',
    russian_post_password: props.settings.russian_post_password ?? '',
    russian_post_test_mode: props.settings.russian_post_test_mode ?? false,

    boxberry_enabled: props.settings.boxberry_enabled ?? false,
    boxberry_token: props.settings.boxberry_token ?? '',
    boxberry_sender_city_id: props.settings.boxberry_sender_city_id ?? '',
    boxberry_test_mode: props.settings.boxberry_test_mode ?? false,
});

const submit = () => {
    form.post(route('dashboard.settings.delivery.update'));
};
</script> 