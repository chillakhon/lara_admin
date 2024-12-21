<template>
    <div class="space-y-4">
        <div v-for="(field, index) in fields" :key="index" class="border p-4 rounded-lg">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Поле {{ index + 1 }}</h3>
                <button @click="removeField(index)" class="text-red-600">
                    Удалить
                </button>
            </div>
            
            <!-- Форма поля -->
            <div class="grid grid-cols-2 gap-4">
                <TextInput v-model="field.name" label="Название поля" />
                <SelectDropdown v-model="field.type" :options="fieldTypes" label="Тип поля" />
                <!-- Дополнительные настройки поля -->
            </div>
        </div>
        
        <PrimaryButton @click="addField" type="button">
            Добавить поле
        </PrimaryButton>
    </div>
</template> 