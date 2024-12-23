<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectDropdown from '@/Components/SelectDropdown.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Badge from '@/Components/Badge.vue';
import { Editor } from '@tinymce/tinymce-vue';

const props = defineProps({
    task: {
        type: Object,
        default: null
    },
    statuses: Array,
    priorities: Array,
    labels: Array,
    users: Array,
    parentTasks: Array
});

const isEditing = computed(() => !!props.task);

const breadCrumbs = computed(() => [
    { name: 'Задачи', link: route('dashboard.tasks.index') },
    { name: isEditing.value ? 'Редактирование' : 'Создание', link: '#' }
]);

const form = useForm({
    title: props.task?.title || '',
    description: props.task?.description || '',
    status_id: props.task?.status_id || props.statuses.find(s => s.is_default)?.id,
    priority_id: props.task?.priority_id || props.priorities[0]?.id,
    assignee_id: props.task?.assignee_id || '',
    parent_id: props.task?.parent_id || '',
    due_date: props.task?.due_date || '',
    estimated_time: props.task?.estimated_time || '',
    labels: props.task?.labels?.map(l => l.id) || []
});

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('dashboard.tasks.update', props.task.id), {
            preserveScroll: true
        });
    } else {
        form.post(route('dashboard.tasks.store'), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ isEditing ? 'Редактирование задачи' : 'Создание задачи' }}
                </h1>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <form @submit.prevent="submitForm" class="p-6 space-y-6">
                        <!-- Основная информация -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-6">
                                <TextInput
                                    v-model="form.title"
                                    label="Название задачи"
                                    :error="form.errors.title"
                                    required
                                />

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Описание
                                    </label>
                                    <Editor
                                        v-model="form.description"
                                        :init="{
                                            height: 300,
                                            menubar: false,
                                            plugins: [
                                                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                                                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                                                'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
                                            ],
                                            toolbar: 'undo redo | blocks | ' +
                                                'bold italic forecolor | alignleft aligncenter ' +
                                                'alignright alignjustify | bullist numlist outdent indent | ' +
                                                'removeformat | help'
                                        }"
                                    />
                                    <p v-if="form.errors.description" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.description }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <SelectDropdown
                                    v-model="form.status_id"
                                    :options="statuses"
                                    label="Статус"
                                    :error="form.errors.status_id"
                                    required
                                >
                                    <template #option="{ option: status }">
                                        <div class="flex items-center gap-2">
                                            <span 
                                                class="w-3 h-3 rounded-full"
                                                :style="{ backgroundColor: status.color }"
                                            ></span>
                                            {{ status.name }}
                                        </div>
                                    </template>
                                </SelectDropdown>

                                <SelectDropdown
                                    v-model="form.priority_id"
                                    :options="priorities"
                                    label="Приоритет"
                                    :error="form.errors.priority_id"
                                    required
                                >
                                    <template #option="{ option: priority }">
                                        <div class="flex items-center gap-2">
                                            <span 
                                                class="w-3 h-3 rounded-full"
                                                :style="{ backgroundColor: priority.color }"
                                            ></span>
                                            {{ priority.name }}
                                        </div>
                                    </template>
                                </SelectDropdown>

                                <SelectDropdown
                                    v-model="form.assignee_id"
                                    :options="users"
                                    label="Исполнитель"
                                    :error="form.errors.assignee_id"
                                    placeholder="Выберите исполнителя"
                                >
                                    <template #option="{ option: user }">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center">
                                                {{ user.name[0] }}
                                            </div>
                                            {{ user.name }}
                                        </div>
                                    </template>
                                </SelectDropdown>

                                <SelectDropdown
                                    v-if="parentTasks.length"
                                    v-model="form.parent_id"
                                    :options="parentTasks"
                                    label="Родительская задача"
                                    :error="form.errors.parent_id"
                                    placeholder="Выберите родительскую задачу"
                                />

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Срок выполнения
                                    </label>
                                    <input
                                        type="datetime-local"
                                        v-model="form.due_date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <p v-if="form.errors.due_date" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.due_date }}
                                    </p>
                                </div>

                                <TextInput
                                    v-model="form.estimated_time"
                                    type="number"
                                    label="Оценка вре��ени (в минутах)"
                                    :error="form.errors.estimated_time"
                                />

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Метки
                                    </label>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <button
                                            v-for="label in labels"
                                            :key="label.id"
                                            type="button"
                                            @click="toggleLabel(label.id)"
                                            :class="[
                                                'px-3 py-1 rounded-full text-sm font-medium transition-colors',
                                                form.labels.includes(label.id)
                                                    ? 'text-white'
                                                    : 'text-gray-700 bg-gray-100 hover:bg-gray-200'
                                            ]"
                                            :style="form.labels.includes(label.id) ? { backgroundColor: label.color } : {}"
                                        >
                                            {{ label.name }}
                                        </button>
                                    </div>
                                    <p v-if="form.errors.labels" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.labels }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <PrimaryButton
                                type="button"
                                @click="$inertia.visit(route('dashboard.tasks.index'))"
                                variant="alternative"
                            >
                                Отмена
                            </PrimaryButton>
                            <PrimaryButton
                                type="submit"
                                :loading="form.processing"
                            >
                                {{ isEditing ? 'Сохранить' : 'Создать' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template> 