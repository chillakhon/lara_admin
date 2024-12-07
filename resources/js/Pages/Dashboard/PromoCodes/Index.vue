<script setup>
    import { ref, computed } from 'vue';
    import { useForm } from '@inertiajs/vue3';
    import DashboardLayout from '@/Layouts/DashboardLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import BreadCrumbs from '@/Components/BreadCrumbs.vue';
    import ContextMenu from '@/Components/ContextMenu.vue';
    import SelectInput from '@/Components/SelectInput.vue';

    const props = defineProps({
        promoCodes: Array,
    });

    const breadCrumbs = [
        { name: 'Панель управления', link: route('dashboard') },
        { name: 'Промокоды', link: route('dashboard.promo-codes.index') },
    ];

    const form = useForm({
        code: '',
        discount_amount: '',
        discount_type: 'percentage',
        starts_at: '',
        expires_at: '',
        max_uses: '',
        is_active: true,
    });

    const showModal = ref(false);
    const modalMode = ref('create');
    const editingPromoCode = ref(null);
    const showUsageModal = ref(false);
    const currentPromoCodeUsage = ref(null);

    const modalTitle = computed(() => {
        switch (modalMode.value) {
            case 'create':
                return 'Создать промокод';
            case 'edit':
                return 'Редактировать промокод';
            case 'delete':
                return 'Удалить промокод';
            default:
                return '';
        }
    });

    const openCreateModal = () => {
        modalMode.value = 'create';
        editingPromoCode.value = null;
        form.reset();
        showModal.value = true;
    };

    const openEditModal = (promoCode) => {
        modalMode.value = 'edit';
        editingPromoCode.value = promoCode;
        form.code = promoCode.code;
        form.discount_amount = promoCode.discount_amount;
        form.discount_type = promoCode.discount_type;
        form.starts_at = promoCode.starts_at;
        form.expires_at = promoCode.expires_at;
        form.max_uses = promoCode.max_uses;
        form.is_active = promoCode.is_active;
        showModal.value = true;
    };

    const openDeleteModal = (promoCode) => {
        modalMode.value = 'delete';
        editingPromoCode.value = promoCode;
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
        form.reset();
        editingPromoCode.value = null;
    };

    const submitForm = () => {
        if (modalMode.value === 'delete') {
            form.delete(route('dashboard.promo-codes.destroy', editingPromoCode.value.id), {
                preserveScroll: true,
                onSuccess: () => closeModal(),
            });
        } else if (modalMode.value === 'edit') {
            form.put(route('dashboard.promo-codes.update', editingPromoCode.value.id), {
                preserveScroll: true,
                onSuccess: () => closeModal(),
            });
        } else {
            form.post(route('dashboard.promo-codes.store'), {
                preserveScroll: true,
                onSuccess: () => closeModal(),
            });
        }
    };

    const openUsageModal = async (promoCode) => {
        showUsageModal.value = true;
        currentPromoCodeUsage.value = null;

        try {
            const response = await axios.get(route('dashboard.promo-codes.usage', promoCode.id));
            currentPromoCodeUsage.value = {
                ...response.data,
                max_uses: promoCode.max_uses,
            };
        } catch (error) {
            console.error('Error fetching promo code usage:', error);
        }
    };

    const closeUsageModal = () => {
        showUsageModal.value = false;
        currentPromoCodeUsage.value = null;
    };

    const discountTypes = [
        { value: 'percentage', label: 'Процент' },
        { value: 'fixed', label: 'Фиксированная сумма' },
    ];

    // Функция форматирования даты
    const formatDate = (dateString) => {
        if (!dateString) return '—';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    };

    // Функция для получения элементов контекстного меню
    const getContextMenuItems = (promoCode) => [
        {
            text: 'Редактировать',
            action: () => openEditModal(promoCode),
        },
        {
            text: 'Просмотр использований',
            action: () => openUsageModal(promoCode),
        },
        {
            text: 'Удалить',
            action: () => openDeleteModal(promoCode),
            isDangerous: true,
        },
    ];

    // Обработчик действий контекстного меню
    const handleContextMenuAction = (item, promoCode) => {
        if (typeof item.action === 'function') {
            item.action(promoCode);
        }
    };
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs" />
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                    Промокоды
                </h1>
                <PrimaryButton @click="openCreateModal" class="flex items-center gap-2">
                    <PlusIcon class="w-5 h-5" />
                    Добавить промокод
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                        >
                                            Код
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                        >
                                            Скидка
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                        >
                                            Период действия
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                        >
                                            Макс. использований
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                        >
                                            Статус
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                        >
                                            Действия
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-200 dark:divide-gray-700 dark:bg-gray-800"
                                >
                                    <tr
                                        v-for="promoCode in promoCodes"
                                        :key="promoCode.id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200"
                                        >
                                            {{ promoCode.code }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200"
                                        >
                                            {{ promoCode.discount_amount
                                            }}{{
                                                promoCode.discount_type === 'percentage' ? '%' : '₽'
                                            }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200"
                                        >
                                            {{ formatDate(promoCode.starts_at) }} -
                                            {{ formatDate(promoCode.expires_at) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200"
                                        >
                                            {{ promoCode.max_uses }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200"
                                        >
                                            <span
                                                :class="[
                                                    'px-2 py-1 rounded-full text-xs font-medium',
                                                    promoCode.is_active
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                ]"
                                            >
                                                {{ promoCode.is_active ? 'Активен' : 'Неактивен' }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                        >
                                            <ContextMenu
                                                :items="getContextMenuItems(promoCode)"
                                                :id="`promo-code-${promoCode.id}`"
                                                :context-data="promoCode"
                                                @item-click="handleContextMenuAction"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <Modal :show="showModal" @close="closeModal">
            <template #title>
                {{ modalTitle }}
            </template>

            <template #content>
                <div v-if="modalMode !== 'delete'" class="space-y-4">
                    <div>
                        <InputLabel for="code" value="Код" />
                        <TextInput
                            id="code"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.code"
                        />
                        <InputError :message="form.errors.code" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="discount_amount" value="Размер скидки" />
                        <TextInput
                            id="discount_amount"
                            type="number"
                            step="0.01"
                            class="mt-1 block w-full"
                            v-model="form.discount_amount"
                        />
                        <InputError :message="form.errors.discount_amount" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="discount_type" value="Тип скидки" />
                        <SelectInput
                            id="discount_type"
                            v-model="form.discount_type"
                            :options="discountTypes"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.discount_type" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="starts_at" value="Дата начала" />
                        <TextInput
                            id="starts_at"
                            type="datetime-local"
                            class="mt-1 block w-full"
                            v-model="form.starts_at"
                        />
                        <InputError :message="form.errors.starts_at" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="expires_at" value="Дата окончания" />
                        <TextInput
                            id="expires_at"
                            type="datetime-local"
                            class="mt-1 block w-full"
                            v-model="form.expires_at"
                        />
                        <InputError :message="form.errors.expires_at" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="max_uses" value="Максимальное количество использований" />
                        <TextInput
                            id="max_uses"
                            type="number"
                            class="mt-1 block w-full"
                            v-model="form.max_uses"
                        />
                        <InputError :message="form.errors.max_uses" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <input
                            id="is_active"
                            type="checkbox"
                            class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                            v-model="form.is_active"
                        />
                        <InputLabel for="is_active" value="Активен" class="ml-2" />
                    </div>
                </div>
                <div v-else>
                    <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                        Вы уверены, что хотите удалить этот промокод? Это действие нельзя отменить.
                    </p>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <PrimaryButton
                        @click="closeModal"
                        type="button"
                        class="bg-gray-500 hover:bg-gray-600 focus:bg-gray-600"
                    >
                        Отмена
                    </PrimaryButton>
                    <PrimaryButton
                        @click="submitForm"
                        :disabled="form.processing"
                        :class="[
                            { 'opacity-25': form.processing },
                            modalMode === 'delete'
                                ? 'bg-red-600 hover:bg-red-700 focus:bg-red-700'
                                : '',
                        ]"
                    >
                        {{
                            modalMode === 'delete'
                                ? 'Удалить'
                                : modalMode === 'create'
                                ? 'Создать'
                                : 'Сохранить'
                        }}
                    </PrimaryButton>
                </div>
            </template>
        </Modal>

        <!-- Usage Modal -->
        <Modal :show="showUsageModal" @close="closeUsageModal">
            <template #title> История использования промокода </template>

            <template #content>
                <div v-if="currentPromoCodeUsage" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-semibold">Промокод:</span>
                                {{ currentPromoCodeUsage.code }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <span class="font-semibold">Всего использований:</span>
                                {{ currentPromoCodeUsage.total_uses }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-semibold">Лимит использований:</span>
                                {{ currentPromoCodeUsage.max_uses || 'Без ограничений' }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <span class="font-semibold">Осталось использований:</span>
                                {{
                                    currentPromoCodeUsage.max_uses
                                        ? currentPromoCodeUsage.max_uses -
                                          currentPromoCodeUsage.total_uses
                                        : '∞'
                                }}
                            </p>
                        </div>
                    </div>

                    <div v-if="currentPromoCodeUsage.usages.length > 0">
                        <div class="overflow-x-auto relative">
                            <table
                                class="w-full text-sm text-left text-gray-500 dark:text-gray-400"
                            >
                                <thead
                                    class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
                                >
                                    <tr>
                                        <th scope="col" class="py-3 px-4">Дата</th>
                                        <th scope="col" class="py-3 px-4">Номер заказа</th>
                                        <th scope="col" class="py-3 px-4">Клиент</th>
                                        <th scope="col" class="py-3 px-4">Сумма скидки</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="usage in currentPromoCodeUsage.usages"
                                        :key="usage.id"
                                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700"
                                    >
                                        <td class="py-3 px-4">
                                            {{ formatDate(usage.created_at) }}
                                        </td>
                                        <td class="py-3 px-4">
                                            {{ usage.order.order_number }}
                                        </td>
                                        <td class="py-3 px-4">
                                            {{ usage.client.name }}
                                        </td>
                                        <td class="py-3 px-4">{{ usage.discount_amount }} ₽</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-else>
                        <p class="text-base text-gray-500 dark:text-gray-400 text-center py-4">
                            Промокод еще не использовался
                        </p>
                    </div>
                </div>
                <div v-else class="text-center py-4">
                    <svg
                        class="animate-spin h-8 w-8 text-gray-500 mx-auto"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end">
                    <PrimaryButton
                        @click="closeUsageModal"
                        class="bg-gray-500 hover:bg-gray-600 focus:bg-gray-600"
                    >
                        Закрыть
                    </PrimaryButton>
                </div>
            </template>
        </Modal>
    </DashboardLayout>
</template>
