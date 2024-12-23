<script setup>
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';

const props = defineProps({
    levels: Array,
});

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    address: '',
    password: '',
    level_id: '',
    bonus_balance: 0,
});

const breadCrumbs = [
    { name: 'Клиенты', link: route('dashboard.clients.index') },
    { name: 'Создание клиента' }
];

const submit = () => {
    form.post(route('dashboard.clients.store'));
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs" />
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                Создание клиента
            </h1>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Основная информация -->
                            <div class="space-y-6">
                                <div>
                                    <InputLabel for="first_name" value="Имя" />
                                    <TextInput
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        required
                                        class="mt-1 block w-full"
                                        :error="form.errors.first_name"
                                    />
                                </div>

                                <div>
                                    <InputLabel for="last_name" value="Фамилия" />
                                    <TextInput
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        required
                                        class="mt-1 block w-full"
                                        :error="form.errors.last_name"
                                    />
                                </div>

                                <div>
                                    <InputLabel for="email" value="Email" />
                                    <TextInput
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        required
                                        class="mt-1 block w-full"
                                        :error="form.errors.email"
                                    />
                                </div>

                                <div>
                                    <InputLabel for="password" value="Пароль" />
                                    <TextInput
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        required
                                        class="mt-1 block w-full"
                                        :error="form.errors.password"
                                    />
                                </div>
                            </div>

                            <!-- Дополнительная информация -->
                            <div class="space-y-6">
                                <div>
                                    <InputLabel for="phone" value="Телефон" />
                                    <TextInput
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        class="mt-1 block w-full"
                                        :error="form.errors.phone"
                                    />
                                </div>

                                <div>
                                    <InputLabel for="address" value="Адрес" />
                                    <textarea
                                        id="address"
                                        v-model="form.address"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        rows="3"
                                    ></textarea>
                                </div>

                                <div>
                                    <InputLabel for="level_id" value="Уровень клиента" />
                                    <select
                                        id="level_id"
                                        v-model="form.level_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    >
                                        <option value="">Выберите уровень</option>
                                        <option v-for="level in levels" :key="level.id" :value="level.id">
                                            {{ level.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <InputLabel for="bonus_balance" value="Бонусный баланс" />
                                    <TextInput
                                        id="bonus_balance"
                                        v-model="form.bonus_balance"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="mt-1 block w-full"
                                        :error="form.errors.bonus_balance"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <PrimaryButton
                                type="button"
                                @click="$inertia.visit(route('dashboard.clients.index'))"
                                class="bg-gray-500"
                            >
                                Отмена
                            </PrimaryButton>
                            <PrimaryButton
                                type="submit"
                                :disabled="form.processing"
                                :loading="form.processing"
                            >
                                Создать клиента
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template> 