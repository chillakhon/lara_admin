<template>
    <DashboardLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <BreadCrumbs :breadcrumbs="breadCrumbs"/>
                    <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Рецепты</h1>
                </div>
            </div>
        </template>

        <div class="py-4">
            <div class=" mx-auto sm:px-6 lg:px-8">
                <!-- Панель фильтров и действий -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4 flex-1">
                            <TextInput
                                v-model="search"
                                type="search"
                                placeholder="Поиск по названию..."
                                class="w-72"
                            />
                            <select v-model="filters.status"
                                    class="rounded-md border-gray-300 dark:border-gray-700 w-48">
                                <option value="">Все статусы</option>
                                <option value="active">Активные</option>
                                <option value="inactive">Неактивные</option>
                            </select>
                        </div>
                        <PrimaryButton @click="openCreateModal">
                            <template #icon-left>
                                <svg class="w-5 h-5 mr-2 text-white" aria-hidden="true"
                                     xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                     viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="2" d="M5 12h14m-7 7V5"/>
                                </svg>
                            </template>
                            Создать рецепт
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Таблица рецептов -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeader>Название</TableHeader>
                                <TableHeader>Продукты</TableHeader>
                                <TableHeader>Выход</TableHeader>
                                <TableHeader>Статус</TableHeader>
                                <TableHeader>Действия</TableHeader>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            <TableRow v-for="recipe in filteredRecipes" :key="recipe.id">
                                <TableCell>{{ recipe.name }}</TableCell>
                                <TableCell>
                                    <div v-for="product in recipe.products" :key="product.id" class="text-sm">
                                        {{ product.name }}
                                        <span v-if="product.pivot.product_variant_id" class="text-gray-500">
                                            ({{
                                                product.variants.find(v => v.id === product.pivot.product_variant_id)?.name
                                            }})
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {{ recipe.output_quantity }} {{ recipe.output_unit.abbreviation }}
                                </TableCell>
                                <TableCell>
                                    <Badge :type="recipe.is_active ? 'green' : 'red'">
                                        {{ recipe.is_active ? 'Активный' : 'Неактивный' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <primary-button type="secondary" icon-only @click="openEditModal(recipe)">
                                            <template #icon-left>
                                                <svg class="w-5 h-5 text-gray-700 dark:text-white"
                                                     viewBox="0 0 24 24">
                                                    <path fill="currentColor"
                                                          d="M20.71 7.04c.39-.39.39-1.04 0-1.41l-2.34-2.34c-.37-.39-1.02-.39-1.41 0l-1.84 1.83l3.75 3.75M3 17.25V21h3.75L17.81 9.93l-3.75-3.75z"/>
                                                </svg>
                                            </template>
                                        </primary-button>
                                        <primary-button type="secondary" icon-only @click="openViewModal(recipe)">
                                            <template #icon-left>
                                                <svg class="w-5 h-5 text-gray-700 dark:text-white" aria-hidden="true"
                                                     xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                     fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd"
                                                          d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                                                          clip-rule="evenodd"/>
                                                </svg>
                                            </template>
                                        </primary-button>
                                        <primary-button type="secondary" icon-only @click="openDeleteModal(recipe)">
                                            <template #icon-left>
                                                <svg class="w-5 h-5 text-red-600" aria-hidden="true"
                                                     xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                     fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd"
                                                          d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                                                          clip-rule="evenodd"/>
                                                </svg>
                                            </template>
                                        </primary-button>
                                        <primary-button type="secondary" icon-only v-if="recipe.is_active"
                                                        @click="openProductionModal(recipe)">
                                            <template #icon-left>
                                                <svg class="w-5 h-5 text-gray-700 dark:text-white" aria-hidden="true"
                                                     xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                     fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd"
                                                          d="M8.6 5.2A1 1 0 0 0 7 6v12a1 1 0 0 0 1.6.8l8-6a1 1 0 0 0 0-1.6l-8-6Z"
                                                          clip-rule="evenodd"/>
                                                </svg>
                                            </template>
                                        </primary-button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>

        <!-- Модальное окно создания/редактирования -->
        <Modal :show="showEditModal" @close="closeEditModal" :max-width="'2xl'">
            <template #title>
                {{ form.id ? 'Редактировать рецепт' : 'Создать рецепт' }}
            </template>
            <template #content>
                <form @submit.prevent="submitForm" class="space-y-6">
                    <!-- Основная информация -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <h3 class="text-lg font-medium mb-4">Основная информация</h3>
                        <div class="space-y-4">
                            <div>
                                <TextInput
                                    label="Название рецепта"
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :error="form.errors.name"
                                    required
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="output_quantity" value="Выход продукции"/>
                                    <TextInput
                                        id="output_quantity"
                                        v-model="form.output_quantity"
                                        type="number"
                                        step="0.001"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.output_quantity"/>
                                </div>
                                <div>
                                    <InputLabel for="output_unit_id" value="Единица измерения выхода"/>
                                    <select
                                        id="output_unit_id"
                                        v-model="form.output_unit_id"
                                        class="mt-1 block w-full rounded-md"
                                        required
                                    >
                                        <option value="">Выберите единицу измерения</option>
                                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                            {{ unit.name }} ({{ unit.abbreviation }})
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.output_unit_id"/>
                                </div>
                            </div>

                            <div>
                                <InputLabel for="production_time" value="Время производства (минут)"/>
                                <TextInput
                                    id="production_time"
                                    v-model="form.production_time"
                                    type="number"
                                    min="1"
                                    class="mt-1 block w-full"
                                />
                                <p class="text-sm text-gray-500 mt-1">
                                    Укажите примерное время производства в минутах
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Привязка к продуктам -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium">Применение рецепта</h3>
                            <PrimaryButton type="button" @click="addProductLink" size="sm">
                                <template #icon-left>
                                    <svg class="w-4 h-4 text-white" aria-hidden="true"
                                         xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                         viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                              stroke-width="2" d="M5 12h14m-7 7V5"/>
                                    </svg>
                                </template>
                                Добавить продукт
                            </PrimaryButton>
                        </div>

                        <div class="space-y-4">
                            <div v-for="(product, index) in form.products" :key="index"
                                 class="relative p-4 border rounded-lg bg-white dark:bg-gray-700">
                                <div class="absolute right-2 top-2" v-if="index > 0">
                                    <button type="button" @click="removeProduct(index)"
                                            class="text-red-500 hover:text-red-700">
                                        <svg class="w-4 h-4 text-red-700" aria-hidden="true"
                                             xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                             viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                  stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="grid grid-cols-12 gap-4">
                                    <!-- Выбор продукта -->
                                    <div class="col-span-5">
                                        <InputLabel value="Продукт"/>
                                        <select v-model="product.product_id"
                                                class="mt-1 block w-full rounded-md"
                                                @change="updateProductVariants(product)">
                                            <option value="">Выберите продукт</option>
                                            <option v-for="p in props.products" :key="p.id" :value="p.id">
                                                {{ p.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Выбор варианта -->
                                    <div v-if="hasVariants(product.product_id)" class="col-span-5">
                                        <InputLabel value="Вариант продукта"/>
                                        <div class="relative">
                                            <select v-model="product.variant_id"
                                                    class="mt-1 block w-full rounded-md"
                                                    :disabled="!hasVariants(product.product_id)">
                                                <option value="">{{ variantSelectLabel(product.product_id) }}</option>
                                                <option v-for="variant in getProductVariants(product.product_id)"
                                                        :key="variant.id"
                                                        :value="variant.id"
                                                        :class="{
                                                            'font-semibold bg-blue-50': isSelectedVariant(product, variant.id)
                                                        }">
                                                    {{ variant.name }}
                                                    {{ isSelectedVariant(product, variant.id) ? '(Текущий)' : '' }}
                                                </option>
                                            </select>
                                            <div
                                                v-if="!getProductVariants(product.product_id).length"
                                                class="absolute inset-y-0 right-0 flex items-center pr-3"
                                            >
                                                <span class="text-gray-400">
                                                    Загрузка вариантов...
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Флаг "По умолчанию" -->
                                    <div class="col-span-2 flex items-end">
                                        <Switch v-model="product.is_default"
                                                class="mt-1"
                                                :disabled="isOnlyProduct(index)"
                                                label="По умолчанию">
                                        </Switch>
                                    </div>
                                </div>

                                <!-- Информация о продукте -->
                                <div v-if="product.product_id" class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p><strong>Тип:</strong> {{ getProductType(product.product_id) }}</p>
                                            <p><strong>Единица измерения:</strong> {{
                                                    getProductUnit(product.product_id)
                                                }}</p>
                                        </div>
                                        <div v-if="product.variant_id">
                                            <p><strong>SKU варианта:</strong> {{ getVariantSku(product.variant_id) }}
                                            </p>
                                            <p><strong>Цена варианта:</strong>
                                                {{ formatPrice(getVariantPrice(product.variant_id)) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Компоненты рецепта -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium">Компоненты рецепта</h3>
                            <PrimaryButton type="button" @click="addItem" size="sm">
                                <template #icon-left>
                                    <svg class="w-4 h-4 text-white" aria-hidden="true"
                                         xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                         viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                              stroke-width="2" d="M5 12h14m-7 7V5"/>
                                    </svg>
                                </template>
                                Добавить компонент
                            </PrimaryButton>
                        </div>

                        <div class="space-y-4">
                            <div v-for="(item, index) in form.items" :key="index"
                                 class="relative p-4 border rounded-lg bg-white dark:bg-gray-700">
                                <div class="absolute right-2 top-2" v-if="index > 0">
                                    <button type="button" @click="removeItem(index)"
                                            class="text-red-500 hover:text-red-700">
                                        <svg class="w-5 h-5 text-red-700" aria-hidden="true"
                                             xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                             viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                  stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
                                        </svg>

                                    </button>
                                </div>

                                <div class="grid grid-cols-12 gap-4">
                                    <!-- Тип компонента -->
                                    <div class="col-span-3">
                                        <InputLabel value="Тип компонента"/>
                                        <select v-model="item.component_type"
                                                class="mt-1 block w-full rounded-md"
                                                @change="updateComponentDetails(item)">
                                            <option value="">Выберите тип</option>
                                            <option value="Material">Материал</option>
                                            <option value="Product">Продукт</option>
                                        </select>
                                    </div>

                                    <!-- Выбор компонента -->
                                    <div class="col-span-5">
                                        <InputLabel value="Компонент"/>
                                        <select v-model="item.component_id"
                                                class="mt-1 block w-full rounded-md"
                                                @change="updateComponentDetails(item)">
                                            <option value="">Выберите компонент</option>
                                            <option v-for="component in getComponents(item.component_type)"
                                                    :key="component.id"
                                                    :value="component.id">
                                                {{ component.name || component.title }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Количество -->
                                    <div class="col-span-4">
                                        <InputLabel value="Количество"/>
                                        <div class="flex items-center gap-2">
                                            <TextInput
                                                v-model="item.quantity"
                                                type="number"
                                                step="0.001"
                                                class="mt-1 block w-full"
                                            />
                                            <div class="mt-1 px-3 py-2 bg-gray-100 dark:bg-gray-600 rounded-md">
                                                {{ getComponentUnit(item) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Информация о компоненте -->
                                <div v-if="item.component_id" class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <p><strong>В наличии:</strong> {{ formatNumber(getComponentStock(item)) }}
                                                {{ getComponentUnit(item) }}</p>
                                        </div>
                                        <div>
                                            <p><strong>Средняя цена:</strong>
                                                {{ formatPrice(getComponentAveragePrice(item)) }}</p>
                                        </div>
                                        <div v-if="item.quantity">
                                            <p><strong>Требуется:</strong>
                                                <span :class="{
                                            'text-green-600': hasEnoughStock(item),
                                            'text-red-600': !hasEnoughStock(item)
                                        }">
                                            {{ formatNumber(item.quantity) }} {{ getComponentUnit(item) }}
                                        </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- В модальном окне редактирования рецепта, после секции с компонентами -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium">Дополнительные расходы</h3>
                            <PrimaryButton @click="addCostRate" size="sm">
                                <template #icon-left>
                                    <PlusIcon class="w-4 h-4" />
                                </template>
                                Добавить расход
                            </PrimaryButton>
                        </div>

                        <div class="space-y-4">
                            <div v-for="(rate, index) in form.cost_rates"
                                 :key="index"
                                 class="relative p-4 border rounded-lg bg-white dark:bg-gray-700">
                                <!-- Кнопка удаления -->
                                <button v-if="form.cost_rates.length > 1"
                                        @click="removeCostRate(index)"
                                        type="button"
                                        class="absolute right-2 top-2 text-red-500 hover:text-red-700">
                                    <XMarkIcon class="w-4 h-4" />
                                </button>

                                <div class="grid grid-cols-3 gap-4">
                                    <!-- Категория затрат -->
                                    <div>
                                        <InputLabel value="Категория затрат" />
                                        <select v-model="rate.cost_category_id"
                                                class="mt-1 block w-full rounded-md">
                                            <option value="">Выберите категорию</option>
                                            <optgroup v-for="(categories, groupName) in groupedCostCategories"
                                                      :key="groupName"
                                                      :label="groupName">
                                                <option v-for="category in categories"
                                                        :key="category.id"
                                                        :value="category.id">
                                                    {{ category.name }}
                                                </option>
                                            </optgroup>
                                        </select>
                                        <InputError :message="form.errors[`cost_rates.${index}.cost_category_id`]" />
                                    </div>

                                    <!-- Ставка за единицу -->
                                    <div>
                                        <InputLabel value="Ставка за единицу продукции" />
                                        <TextInput
                                            v-model="rate.rate_per_unit"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="mt-1 block w-full"
                                        />
                                        <p class="text-sm text-gray-500 mt-1">
                                            Стоимость на единицу произведенной продукции
                                        </p>
                                        <InputError :message="form.errors[`cost_rates.${index}.rate_per_unit`]" />
                                    </div>

                                    <!-- Фиксированная ставка -->
                                    <div>
                                        <InputLabel value="Фиксированная ставка" />
                                        <TextInput
                                            v-model="rate.fixed_rate"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="mt-1 block w-full"
                                        />
                                        <p class="text-sm text-gray-500 mt-1">
                                            Фиксированная стоимость на партию
                                        </p>
                                        <InputError :message="form.errors[`cost_rates.${index}.fixed_rate`]" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Инструкции -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <h3 class="text-lg font-medium mb-4">Инструкции по производству</h3>
                        <textarea
                            v-model="form.instructions"
                            class="mt-1 block w-full rounded-md"
                            rows="4"
                            placeholder="Опишите процесс производства..."
                        />
                    </div>
                </form>
            </template>

            <template #footer>
                <div class="flex justify-between gap-4">
                    <Switch v-model="form.is_active">
                        {{ form.is_active ? 'Активный рецепт' : 'Неактивный рецепт' }}
                    </Switch>

                    <div class="flex gap-2">
                        <PrimaryButton type="button" variant="secondary" @click="closeEditModal">
                            Отмена
                        </PrimaryButton>
                        <PrimaryButton
                            type="submit"
                            @click="submitForm"
                            :disabled="form.processing"
                        >
                            {{ form.id ? 'Сохранить' : 'Создать' }}
                        </PrimaryButton>
                    </div>
                </div>
            </template>
        </Modal>

        <!-- Модальное окно просмотра рецепта -->
        <Modal :show="showViewModal" @close="closeViewModal">
            <template #title>
                <div class="flex items-center justify-between">
                    <span>{{ selectedRecipe?.name }}</span>
                    <span :class="{
                'px-2 py-1 text-sm rounded-full': true,
                'bg-green-100 text-green-800': selectedRecipe?.is_active,
                'bg-gray-100 text-gray-800': !selectedRecipe?.is_active
            }">
                {{ selectedRecipe?.is_active ? 'Активный' : 'Неактивный' }}
            </span>
                </div>
            </template>

            <template #content>
                <div class="space-y-6">
                    <!-- Информация о продуктах -->
                    <div>
                        <h3 class="text-lg font-medium">Продукты</h3>
                        <div class="mt-2 space-y-2">
                            <div v-for="product in selectedRecipe?.products" :key="product.id"
                                 class="flex items-center space-x-2">
                                <span>{{ product.name }}</span>
                                <template v-if="product.pivot?.product_variant_id">
                                    <span class="text-gray-400">/</span>
                                    <span class="text-gray-600">
                                {{ getProductVariantName(product, product.pivot.product_variant_id) }}
                            </span>
                                </template>
                                <span v-if="product.pivot?.is_default"
                                      class="px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full">
                            По умолчанию
                        </span>
                            </div>
                        </div>
                    </div>

                    <!-- Компоненты -->
                    <div>
                        <h3 class="text-lg font-medium">Компоненты</h3>
                        <div class="mt-2">
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeader>Наименование</TableHeader>
                                        <TableHeader>Тип</TableHeader>
                                        <TableHeader align="right">Количество</TableHeader>
                                        <TableHeader align="right">В наличии</TableHeader>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    <TableRow v-for="item in selectedRecipe?.items" :key="item.id">
                                        <TableCell>{{ item.component.name || item.component.title }}</TableCell>
                                        <TableCell>{{
                                                item.component_type === 'material' ? 'Материал' : 'Продукт'
                                            }}
                                        </TableCell>
                                        <TableCell align="right">
                                            {{ item.quantity }} {{ item.unit.abbreviation }}
                                        </TableCell>
                                        <TableCell align="right">
                                    <span :class="{
                                        'px-2 py-1 rounded-full text-sm': true,
                                        'bg-green-100 text-green-800': hasEnoughStock(item),
                                        'bg-red-100 text-red-800': !hasEnoughStock(item)
                                    }">
                                        {{ getComponentStock(item) }} {{ item.unit.abbreviation }}
                                    </span>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>

                    <!-- Инструкции -->
                    <div v-if="selectedRecipe?.instructions">
                        <h3 class="text-lg font-medium">Инструкции</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            {{ selectedRecipe.instructions }}
                        </p>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end space-x-2">
                    <PrimaryButton @click="closeViewModal">
                        Закрыть
                    </PrimaryButton>
                </div>
            </template>
        </Modal>

        <!-- Модальное окно подтверждения удаления -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal">
            <template #title>
                Удаление рецепта
            </template>

            <template #content>
                <p>Вы действительно хотите удалить рецепт "{{ selectedRecipe?.name }}"?</p>
                <p class="mt-2 text-sm text-gray-500">
                    Это действие нельзя будет отменить
                </p>
            </template>

            <template #footer>
                <div class="flex justify-end space-x-2">
                    <PrimaryButton @click="closeDeleteModal">
                        Отмена
                    </PrimaryButton>
                    <PrimaryButton
                        @click="deleteRecipe"
                        :class="'!bg-red-600 hover:!bg-red-700'"
                        :disabled="deleteForm.processing"
                    >
                        Удалить
                    </PrimaryButton>
                </div>
            </template>
        </Modal>

        <!-- Модальное окно создания производственной партии -->
        <Modal :show="showProductionModal" @close="closeProductionModal">
            <template #title>
                Создание производственной партии
                <p class="text-sm text-gray-500 mt-1">
                    {{ selectedRecipe?.name }}
                </p>
            </template>

            <template #content>
                <div class="space-y-6">
                    <!-- Количество -->
                    <div class="space-y-2">
                        <InputLabel for="quantity" value="Количество"/>
                        <div class="flex items-center gap-2">
                            <TextInput
                                id="quantity"
                                v-model="productionForm.quantity"
                                type="number"
                                step="0.001"
                                min="0.001"
                                :max="maxAvailableQuantity"
                                required
                                class="flex-1"
                            />
                            <span class="text-gray-500">
                            {{ selectedRecipe?.output_unit?.abbreviation }}
                        </span>
                        </div>
                        <p class="text-sm text-gray-500">
                            Максимально доступно: {{ formatNumber(maxAvailableQuantity) }}
                            {{ selectedRecipe?.output_unit?.abbreviation }}
                        </p>
                    </div>

                    <!-- Компоненты -->
                    <div v-if="componentsAvailability" class="space-y-2">
                        <h4 class="font-medium text-gray-900 dark:text-white">
                            Требуемые компоненты
                        </h4>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="space-y-3">
                                <div v-for="component in componentsAvailability"
                                     :key="component.name"
                                     class="flex justify-between items-center">
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ component.name }}
                                </span>
                                    <div class="flex items-center gap-2">
                                        <Badge
                                            :type="component.available >= component.required ? 'green' : 'red'"
                                        >
                                            {{ formatNumber(component.required) }} /
                                            {{ formatNumber(component.available) }}
                                            {{ component.unit }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Планируемая дата -->
                    <div class="space-y-2">
                        <InputLabel for="planned_start_date" value="Планируемая дата начала"/>
                        <TextInput
                            id="planned_start_date"
                            v-model="productionForm.planned_start_date"
                            type="datetime-local"
                            required
                            :min="new Date().toISOString().slice(0, 16)"
                        />
                    </div>

                    <!-- Стратегия расчета стоимости -->
                    <div class="space-y-2">
                        <InputLabel for="cost_strategy" value="Стратегия расчета стоимости"/>
                        <select
                            id="cost_strategy"
                            v-model="productionForm.cost_strategy"
                            class="w-full rounded-md border-gray-300 dark:border-gray-700"
                            required
                        >
                            <option value="">Выберите стратегию</option>
                            <option value="average">По средней стоимости</option>
                            <option value="fifo">FIFO (первым пришел, первым ушел)</option>
                            <option value="lifo">LIFO (последним пришел, первым ушел)</option>
                            <option value="newest">По последней закупке</option>
                        </select>
                    </div>

                    <!-- Результаты расчета -->
                    <div v-if="costEstimation"
                         class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3">
                        <h4 class="font-medium text-gray-900 dark:text-white">
                            Расчет стоимости
                        </h4>

                        <!-- Основные показатели -->
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between">
                                <span>Материалы:</span>
                                <span>{{ formatPrice(costEstimation.materials) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Оплата труда:</span>
                                <span>{{ formatPrice(costEstimation.labor) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Накладные расходы:</span>
                                <span>{{ formatPrice(costEstimation.overhead) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Управленческие расходы:</span>
                                <span>{{ formatPrice(costEstimation.management) }}</span>
                            </div>
                        </div>

                        <!-- Итоговые суммы -->
                        <div class="border-t dark:border-gray-700 pt-4">
                            <div class="flex justify-between font-medium">
                                <span>Общая стоимость:</span>
                                <span>{{ formatPrice(costEstimation.total) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-500 mt-2">
                                <span>Стоимость единицы:</span>
                                <span>{{ formatPrice(costEstimation.per_unit) }}</span>
                            </div>
                        </div>

                        <!-- Детализация -->
                        <Disclosure as="div" class="mt-4">
                            <template #title="">
                                <span>Детализация расходов</span>
                            </template>
                            <div class="space-y-2">
                                <!-- Материалы -->
                                <div v-for="detail in costEstimation.materials_details"
                                     :key="'material-' + detail.name"
                                     class="flex justify-between">
                                    <span>{{ detail.name }}</span>
                                    <span>{{ formatPrice(detail.total_cost) }}</span>
                                </div>
                                <!-- Другие затраты -->
                                <div v-for="detail in costEstimation.details"
                                     :key="detail.name"
                                     class="flex justify-between">
                                    <span>{{ detail.name }}</span>
                                    <span>{{ formatPrice(detail.amount) }}</span>
                                </div>
                            </div>
                        </Disclosure>
                    </div>

                    <!-- Примечания -->
                    <div class="space-y-2">
                        <InputLabel for="notes" value="Примечания"/>
                        <textarea
                            id="notes"
                            v-model="productionForm.notes"
                            rows="3"
                            class="w-full rounded-md border-gray-300 dark:border-gray-700"
                            placeholder="Дополнительная информация о производственной партии..."
                        />
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-between w-full">
                    <PrimaryButton
                        type="red"
                        variant="secondary"
                        @click="closeProductionModal"
                    >
                        Отмена
                    </PrimaryButton>

                    <div class="flex gap-2">
                        <PrimaryButton
                            type="default"
                            @click="calculateCost"
                            :disabled="!canCalculateCost"
                            variant="info"
                        >
                            <template #icon-left>
                                <svg class="w-5 h-5 text-white mr-2" width="32" height="32" viewBox="0 0 24 24">
                                    <path fill="currentColor"
                                          d="M8 18h1.5v-2h2v-1.5h-2v-2H8v2H6V16h2zm5-.75h5v-1.5h-5zm0-2.5h5v-1.5h-5zM6.25 9.2h5V7.7h-5zM5 21q-.825 0-1.412-.587T3 19V5q0-.825.588-1.412T5 3h14q.825 0 1.413.588T21 5v14q0 .825-.587 1.413T19 21zm9.1-10.05l1.4-1.4l1.4 1.4l1.05-1.05l-1.4-1.45l1.4-1.4L16.9 6l-1.4 1.4L14.1 6l-1.05 1.05l1.4 1.4l-1.4 1.45z"/>
                                </svg>
                            </template>
                            Рассчитать
                        </PrimaryButton>

                        <PrimaryButton
                            type="default"
                            @click="startProduction"
                            :disabled="!canStartProduction"
                        >
                            <template #icon-left>
                                <svg class="w-5 h-5 mr-2 text-white" width="32" height="32" viewBox="0 0 20 20">
                                    <path fill="currentColor"
                                          d="M5 3.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5zm6.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm.5 3V4h2v2zM2 12a4 4 0 0 1 4-4h8a4 4 0 0 1 0 8H6a4 4 0 0 1-4-4m5 0a1 1 0 1 0-2 0a1 1 0 0 0 2 0m4 0a1 1 0 1 0-2 0a1 1 0 0 0 2 0m3 1a1 1 0 1 0 0-2a1 1 0 0 0 0 2"/>
                                </svg>
                            </template>
                            Создать партию
                        </PrimaryButton>
                    </div>
                </div>
            </template>
        </Modal>

        <!-- Добавляем модальное окно для настройки ставок затрат -->
        <Modal :show="showCostRatesModal" @close="closeCostRatesModal">
            <template #title>
                Настройка производственных затрат
            </template>

            <template #content>
                <div class="space-y-4">
                    <div v-for="(rate, index) in form.cost_rates"
                         :key="index"
                         class="relative border rounded-lg p-4">
                        <!-- Кнопка удаления -->
                        <button @click="removeCostRate(index)"
                                class="absolute top-2 right-2 text-red-500"
                                type="button">
                            <XMarkIcon class="w-5 h-5"/>
                        </button>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <InputLabel value="Категория затрат"/>
                                <select v-model="rate.cost_category_id"
                                        class="mt-1 block w-full rounded-md">
                                    <option value="">Выберите категорию</option>
                                    <optgroup v-for="(categories, groupName) in groupedCostCategories"
                                              :key="groupName"
                                              :label="groupName">
                                        <option v-for="category in categories"
                                                :key="category.id"
                                                :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </optgroup>
                                </select>
                            </div>

                            <div>
                                <InputLabel value="Ставка за единицу"/>
                                <TextInput
                                    v-model="rate.rate_per_unit"
                                    type="number"
                                    step="0.01"
                                    class="mt-1 block w-full"
                                />
                                <p class="text-sm text-gray-500 mt-1">
                                    За каждую единицу продукции
                                </p>
                            </div>

                            <div>
                                <InputLabel value="Фиксированная ставка"/>
                                <TextInput
                                    v-model="rate.fixed_rate"
                                    type="number"
                                    step="0.01"
                                    class="mt-1 block w-full"
                                />
                                <p class="text-sm text-gray-500 mt-1">
                                    За всю партию
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <PrimaryButton
                            type="button"
                            @click="addCostRate"
                            class="mt-4"
                        >
                            <PlusIcon class="w-5 h-5 mr-2"/>
                            Добавить ставку
                        </PrimaryButton>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <PrimaryButton
                        type="button"
                        variant="secondary"
                        @click="closeCostRatesModal"
                    >
                        Отмена
                    </PrimaryButton>
                    <PrimaryButton
                        type="button"
                        @click="saveCostRates"
                    >
                        Сохранить
                    </PrimaryButton>
                </div>
            </template>
        </Modal>
    </DashboardLayout>
</template>

<script setup>
import {ref, computed, watch, onMounted} from 'vue';
import {useForm} from '@inertiajs/vue3';
import {
    Table,
    TableHead,
    TableBody,
    TableRow,
    TableHeader,
    TableCell
} from '@/Components/Table';
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import Switch from "@/Components/Switch.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import BreadCrumbs from "@/Components/BreadCrumbs.vue";
import Badge from "@/Components/Badge.vue";
import Disclosure from "@/Components/Disclosure/Disclosure.vue";
// Определение пропсов
const props = defineProps({
    recipes: {
        type: Array,
        required: true,
        default: () => []
    },
    products: {
        type: Array,
        required: true,
        default: () => []
    },
    materials: {
        type: Array,
        required: true,
        default: () => []
    },
    units: {
        type: Array,
        required: true,
        default: () => []
    },
    costCategories: {
        type: Array,
        required: true,
        default: () => []
    }
});
const breadCrumbs = [
    {
        name: 'Рецепты',
        link: route('dashboard.recipes.index')
    }
]
const COMPONENT_TYPES = {
    MATERIAL: 'Material',
    PRODUCT: 'Product'
};

const search = ref('');
const filters = ref({
    status: '',
    productType: ''
});

const showEditModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const showProductionModal = ref(false);
const costEstimation = ref(null);
const selectedRecipe = ref(null);
const costCategories = ref([]);
const showCostRatesModal = ref(false);

// Форма редактирования
const form = useForm({
    id: null,
    name: '',
    description: '',
    output_quantity: 1,
    output_unit_id: '',
    instructions: '',
    production_time: null,
    is_active: true,
    products: [
        {product_id: '', variant_id: null, is_default: true}
    ],
    items: [
        {component_type: COMPONENT_TYPES.MATERIAL, component_id: '', quantity: 1, unit_id: ''}
    ],
    cost_rates: [
        {
            cost_category_id: '',
            rate_per_unit: 0,
            fixed_rate: 0
        }
    ]
});

const deleteForm = useForm({});
const productionForm = useForm({
    recipe_id: '',
    quantity: 0,
    planned_start_date: '',
    cost_strategy: 'average',
    notes: ''
});

const getProductVariantName = (product, variantId) => {
    const variant = product.variants?.find(v => v.id === variantId);
    return variant?.name || '';
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedRecipe.value = null;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedRecipe.value = null;
};

const closeProductionModal = () => {
    showProductionModal.value = false;
    selectedRecipe.value = null;
    productionForm.reset();
    costEstimation.value = null;
};
const maxAvailableQuantity = computed(() => {
    if (!selectedRecipe.value) return 0;

    return selectedRecipe.value.items.reduce((min, item) => {
        const stock = getComponentStock(item);
        const maxQuantity = (stock / item.quantity) * selectedRecipe.value.output_quantity;
        return Math.min(min, maxQuantity);
    }, Infinity);
});

const canCalculateCost = computed(() => {
    return productionForm.quantity > 0 &&
        productionForm.quantity <= maxAvailableQuantity.value &&
        productionForm.cost_strategy &&
        selectedRecipe.value;
});

const canStartProduction = computed(() => {
    return productionForm.quantity > 0 &&
        productionForm.quantity <= maxAvailableQuantity.value &&
        productionForm.planned_start_date &&
        productionForm.cost_strategy &&
        costEstimation.value &&
        selectedRecipe.value &&
        !hasInsufficientComponents.value;
});

const hasInsufficientComponents = computed(() => {
    if (!componentsAvailability.value) return false;
    return Object.values(componentsAvailability.value).some(
        component => component.available < component.required
    );
});
const componentsAvailability = computed(() => {
    if (!selectedRecipe.value || !productionForm.quantity) return null;

    return selectedRecipe.value.items.map(item => ({
        name: item.component.name || item.component.title,
        required: calculateRequiredQuantity(item, productionForm.quantity),
        available: item.component.inventory_balance?.total_quantity || 0,
        unit: item.unit.abbreviation
    }));
});

const calculateRequiredQuantity = (item, productionQuantity) => {
    const baseQuantity = (item.quantity / selectedRecipe.value.output_quantity) * productionQuantity;
    const wasteQuantity = baseQuantity * (item.waste_percentage / 100 || 0);
    return baseQuantity + wasteQuantity;
};


const startProduction = () => {
    if (!canStartProduction.value) return;

    productionForm.post(route('dashboard.production.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeProductionModal();
            // Добавляем уведомление об успехе
            // Предполагается, что у вас есть компонент для уведомлений
        },
        onError: (errors) => {
            console.error('Production creation errors:', errors);
            // Добавляем уведомление об ошибке
        }
    });
};

const deleteRecipe = () => {
    deleteForm.delete(route('dashboard.recipes.destroy', selectedRecipe.value.id), {
        onSuccess: () => closeDeleteModal()
    });
};


const openCreateModal = () => {
    resetForm();
    showEditModal.value = true;
};

const openViewModal = (recipe) => {
    selectedRecipe.value = recipe;
    showViewModal.value = true;
};


const closeEditModal = () => {
    showEditModal.value = false;
    resetForm();
};

// Вспомогательные методы

const getComponentTypes = () => [
    {value: COMPONENT_TYPES.MATERIAL, label: 'Материал'},
    {value: COMPONENT_TYPES.PRODUCT, label: 'Продукт'}
];
const getProduct = (productId) => {
    if (!props.products) return null;
    return props.products.find(p => p.id === productId) || null;
};

const getComponents = (type) => {
    return type === COMPONENT_TYPES.MATERIAL ? props.materials : props.products;
};

const addProduct = () => {
    form.products.push({product_id: '', variant_id: null, is_default: false});
};

// Фильтрация рецептов
const filteredRecipes = computed(() => {
    return props.recipes.filter(recipe => {
        const matchesSearch = search.value === '' ||
            recipe.name.toLowerCase().includes(search.value.toLowerCase());

        const matchesStatus = filters.value.status === '' ||
            (filters.value.status === 'active' && recipe.is_active) ||
            (filters.value.status === 'inactive' && !recipe.is_active);

        return matchesSearch && matchesStatus;
    });
});

const openDeleteModal = (recipe) => {
    selectedRecipe.value = recipe;
    showDeleteModal.value = true;
};

const openProductionModal = (recipe) => {
    selectedRecipe.value = recipe;
    productionForm.recipe_id = recipe.id;
    productionForm.planned_quantity = recipe.output_quantity;
    showProductionModal.value = true;
};
const getComponentUnit = (item) => {
    if (!item.component_type || !item.component_id) return '';

    const component = getComponents(item.component_type)
        .find(c => c.id === item.component_id);

    return component?.unit?.abbreviation || '';
};

const updateComponentUnit = (item) => {
    if (!item.component_type || !item.component_id) return;

    const component = getComponents(item.component_type)
        .find(c => c.id === item.component_id);

    if (component?.unit) {
        item.unit_id = component.unit.id;
    }
};

const openEditModal = (recipe) => {
    console.log('Opening edit modal with recipe:', recipe);
    selectedRecipe.value = recipe;
    if (recipe) {
        form.id = recipe.id;
        form.name = recipe.name;
        form.description = recipe.description || '';
        form.output_quantity = recipe.output_quantity;
        form.output_unit_id = recipe.output_unit_id;
        form.instructions = recipe.instructions || '';
        form.production_time = recipe.production_time;
        form.is_active = recipe.is_active;

        // Преобразуем связи с продуктами
        form.products = recipe.products.map(p => ({
            product_id: parseInt(p.id),
            variant_id: recipe.selected_variants.find(v => v.product_id === p.id)?.id || null,
            is_default: Boolean(recipe.selected_variants.find(v => v.product_id === p.id)?.pivot.is_default)
        }));

        // Преобразуем компоненты
        form.items = recipe.items.map(item => ({
            component_type: item.component_type,
            component_id: parseInt(item.component_id),
            quantity: parseFloat(item.quantity),
            unit_id: parseInt(item.unit_id)
        }));

        // Добавляем ставки затрат
        form.cost_rates = recipe.cost_rates?.length
            ? recipe.cost_rates.map(rate => ({
                cost_category_id: rate.cost_category_id,
                rate_per_unit: parseFloat(rate.rate_per_unit || 0),
                fixed_rate: parseFloat(rate.fixed_rate || 0)
            }))
            : [{
                cost_category_id: '',
                rate_per_unit: 0,
                fixed_rate: 0
            }];
    } else {
        form.reset();
        form.cost_rates = [{
            cost_category_id: '',
            rate_per_unit: 0,
            fixed_rate: 0
        }];
    }
    showEditModal.value = true;
};
const groupedCostCategories = computed(() => {
    const groups = {};
    props.costCategories.forEach(category => {
        if (!groups[category.type_name]) {
            groups[category.type_name] = [];
        }
        groups[category.type_name].push(category);
    });
    return groups;
});
const formatNumber = (value) => {
    return new Intl.NumberFormat('ru-RU', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3
    }).format(value);
};
// Обновляем метод calculateCost
const calculateCost = async () => {
    if (!canCalculateCost.value) return;

    try {
        const response = await axios.post(route('dashboard.recipes.estimate-cost'), {
            recipe_id: selectedRecipe.value.id,
            quantity: parseFloat(productionForm.quantity),
            strategy: productionForm.cost_strategy
        });

        costEstimation.value = {
            materials: response.data.materials_cost,
            materials_details: response.data.materials_details,
            labor: response.data.labor_cost,
            overhead: response.data.overhead_cost,
            management: response.data.management_cost,
            total: response.data.total_cost,
            per_unit: response.data.cost_per_unit,
            details: response.data.cost_details
        };
    } catch (error) {
        console.error('Error calculating cost:', error);
        // Здесь можно добавить отображение ошибки пользователю
    }
};

const variantSelectLabel = (productId) => {
    if (!productId) return 'Выберите сначала продукт';
    const product = props.products.find(p => p.id === parseInt(productId));
    if (!product) return 'Выберите сначала продукт';
    if (!product.has_variants) return 'Продукт без вариантов';
    if (!product.variants?.length) return 'Нет доступных вариантов';
    return 'Выберите вариант';
};


const isOnlyProduct = (index) => {
    return form.products.length === 1 && index === 0;
};

const getProductType = (productId) => {
    const product = props.products.find(p => p.id === productId);
    const types = {
        'simple': 'Простой',
        'manufactured': 'Производимый',
        'composite': 'Составной'
    };
    return types[product?.type] || '';
};

const getProductUnit = (productId) => {
    const product = props.products.find(p => p.id === productId);
    return product?.default_unit?.abbreviation || '';
};

const getVariantSku = (variantId) => {
    const variant = findVariantById(variantId);
    return variant?.sku || '';
};

const getVariantPrice = (variantId) => {
    const variant = findVariantById(variantId);
    return variant?.price || 0;
};

const findVariantById = (variantId) => {
    for (const product of props.products) {
        const variant = product.variants?.find(v => v.id === variantId);
        if (variant) return variant;
    }
    return null;
};
const getComponentStock = (item) => {
    if (!item.component_type || !item.component_id) return 0;
    const component = getComponents(item.component_type)
        .find(c => c.id === item.component_id);
    return component?.inventory_balance?.total_quantity || 0;
};

const getComponentAveragePrice = (item) => {
    if (!item.component_type || !item.component_id) return 0;
    const component = getComponents(item.component_type)
        .find(c => c.id === item.component_id);
    return component?.inventory_balance?.average_price || 0;
};


const formatPrice = (value) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
    }).format(value);
};
const updateProductVariants = (product) => {
    console.log('Updating variants for product:', product);
    // Сбрасываем вариант только если меняется продукт
    if (product.variant_id && !hasVariants(product.product_id)) {
        product.variant_id = null;
    }
    product.is_default = isOnlyProduct(form.products.indexOf(product));

    // Проверяем, существует ли выбранный вариант в новом продукте
    if (product.variant_id) {
        const variants = getProductVariants(product.product_id);
        if (!variants.find(v => v.id === product.variant_id)) {
            product.variant_id = null;
        }
    }
};
const hasVariants = (productId) => {
    if (!productId) return false;
    const product = props.products.find(p => p.id === parseInt(productId));
    console.log('Checking variants for product:', product);
    return product?.has_variants && Array.isArray(product?.variants) && product.variants.length > 0;
};

const getProductVariants = (productId) => {
    if (!productId) return [];
    const product = props.products.find(p => p.id === parseInt(productId));
    console.log('Getting variants for product:', productId, product?.variants);
    return product?.variants || [];
};
const updateComponentDetails = (item) => {
    // Сбрасываем компонент при смене типа
    if (!item.component_type) {
        item.component_id = null;
        return;
    }

    // Если компонент выбран, обновляем его unit_id
    if (item.component_id) {
        const component = getComponents(item.component_type)
            .find(c => c.id === item.component_id);
        if (component?.unit) {
            item.unit_id = component.unit.id;
        }
    }
};

const hasEnoughStock = (item) => {
    if (!item.quantity) return true;
    return getComponentStock(item) >= item.quantity;
};


const validateForm = () => {
    if (!form.products.length) {
        alert('Добавьте хотя бы один продукт');
        return false;
    }

    if (!form.items.length) {
        alert('Добавьте хотя бы один компонент');
        return false;
    }

    // Проверяем, что хотя бы один продукт помечен как "по умолчанию"
    if (!form.products.some(p => p.is_default)) {
        alert('Необходимо указать продукт по умолчанию');
        return false;
    }

    return true;
};

const submitForm = () => {
    if (!validateForm()) return;

    // Убираем пустые значения перед отправкой
    const products = form.products.filter(p =>
        p.product_id && p.variant_id
    );

    // Проверяем наличие продукта по умолчанию
    if (!products.some(p => p.is_default)) {
        products[0].is_default = true;
    }

    const formData = {
        ...form,
        products
    };

    if (form.id) {
        form.put(route('dashboard.recipes.update', form.id), {
            onSuccess: () => {
                closeEditModal();
            }
        });
    } else {
        form.post(route('dashboard.recipes.store'), {
            onSuccess: () => {
                closeEditModal();
            }
        });
    }
};

// Вспомогательные методы для работы с формой
const addProductLink = () => {
    form.products.push({
        product_id: '',
        variant_id: null,
        is_default: false
    });
};

const removeProduct = (index) => {
    form.products.splice(index, 1);

    // Если удалили последний продукт, добавляем пустой
    if (form.products.length === 0) {
        addProduct();
    }

    // Если удалили продукт по умолчанию и есть другие продукты,
    // делаем первый продукт по умолчанию
    if (!form.products.some(p => p.is_default) && form.products.length > 0) {
        form.products[0].is_default = true;
    }
};

const addItem = () => {
    form.items.push({
        component_type: '',
        component_id: '',
        quantity: null,
        unit_id: ''
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Метод очистки формы
const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.products = [{
        product_id: '',
        variant_id: null,
        is_default: true
    }];
    form.items = [{
        component_type: '',
        component_id: '',
        quantity: null,
        unit_id: ''
    }];
};

const isSelectedVariant = (product, variantId) => {
    if (!selectedRecipe.value || !product.product_id) return false;

    const selectedVariant = selectedRecipe.value.selected_variants.find(
        v => v.product_id === product.product_id && v.id === variantId
    );

    return Boolean(selectedVariant);
};

const getVariantOptionText = (variant, product) => {
    let text = variant.name;
    if (isSelectedVariant(product, variant.id)) {
        text += ' (Выбран)';
    }
    return text;
};

const addCostRate = () => {
    form.cost_rates.push({
        cost_category_id: '',
        rate_per_unit: 0,
        fixed_rate: 0
    });
};

const removeCostRate = (index) => {
    if (form.cost_rates.length > 1) {
        form.cost_rates.splice(index, 1);
    }
}

onMounted(async () => {
    try {
        const response = await axios.get(route('dashboard.cost-categories.index'));
        costCategories.value = response.data.data; // Обратите внимание на .data из-за API Resource
    } catch (error) {
        console.error('Error loading cost categories:', error);
        // Можно добавить уведомление об ошибке
    }
})

watch(() => form.products, (newProducts) => {
    console.log('Products changed:', newProducts);
}, {deep: true});

// Добавим отслеживание для конкретного продукта
const watchProduct = (product, index) => {
    console.log(`Product ${index} current state:`, {
        product_id: product.product_id,
        variant_id: product.variant_id,
        is_default: product.is_default,
        available_variants: product.product_id ? getProductVariants(product.product_id) : []
    });
};
</script>
