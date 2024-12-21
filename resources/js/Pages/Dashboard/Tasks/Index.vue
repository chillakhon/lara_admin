<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BreadCrumbs from '@/Components/BreadCrumbs.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Badge from '@/Components/Badge.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectDropdown from '@/Components/SelectDropdown.vue';
import StatusesManager from '@/Components/Tasks/StatusesManager.vue';
import PrioritiesManager from '@/Components/Tasks/PrioritiesManager.vue';
import LabelsManager from '@/Components/Tasks/LabelsManager.vue';
import { useForm } from '@inertiajs/vue3';
import TaskCard from '@/Components/Tasks/TaskCard.vue';
import TaskViewModal from '@/Components/Tasks/TaskViewModal.vue';

const props = defineProps({
    tasks: {
        type: Array,
        required: true
    },
    statuses: {
        type: Array,
        required: true
    },
    priorities: {
        type: Array,
        required: true
    },
    labels: {
        type: Array,
        required: true
    },
    users: {
        type: Array,
        required: true
    },
    filters: {
        type: Object,
        required: true
    }
});

const breadCrumbs = [
    { name: 'Задачи', link: route('dashboard.tasks.index') }
];

const showFiltersModal = ref(false);
const view = ref('board'); // 'board' или 'list'

const filters = ref({
    search: props.filters.search || '',
    status: props.filters.status || '',
    priority: props.filters.priority || '',
    assignee: props.filters.assignee || '',
    label: props.filters.label || '',
    dueDate: props.filters.dueDate || ''
});

// Группировка задач по статусам для канбан-доски
const groupedTasks = computed(() => {
    return props.statuses.reduce((acc, status) => {
        acc[status.id] = props.tasks.filter(task => task.status_id === status.id);
        return acc;
    }, {});
});

const getPriorityBadgeType = (priority) => {
    const types = {
        high: 'red',
        medium: 'yellow',
        low: 'blue'
    };
    return types[priority.slug] || 'gray';
};

const getStatusColor = (status) => {
    return status.color || '#6B7280';
};

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString('ru-RU') : '';
};

const handleDragStart = (e, taskId) => {
    e.dataTransfer.setData('text/plain', taskId);
};

const handleDrop = async (e, statusId) => {
    const taskId = e.dataTransfer.getData('text/plain');
    
    try {
        await router.put(route('dashboard.tasks.update', taskId), {
            status_id: statusId
        }, {
            preserveScroll: true,
            onBefore: () => {
                // Можно добавить индикацию загрузки
            },
            onSuccess: () => {
                // Можно добавить уведомление об успехе
            },
            onError: () => {
                // Обработка ошибки
            }
        });
    } catch (error) {
        console.error('Error updating task status:', error);
        // Можно добавить уведомление об ошибке
    }
};

const showSettings = ref(false);
const activeSettingsTab = ref('statuses'); // 'statuses', 'priorities', 'labels'

const toggleSettings = () => {
    showSettings.value = !showSettings.value;
};

const showTaskModal = ref(false);
const showViewModal = ref(false);
const editingTask = ref(null);
const viewingTask = ref(null);

const taskForm = useForm({
    title: '',
    description: '',
    status_id: props.statuses.find(s => s.is_default)?.id || '',
    priority_id: props.priorities[0]?.id || '',
    assignee_id: '',
    due_date: '',
    estimated_time: '',
    labels: []
});

const openCreateModal = (statusId) => {
    editingTask.value = null;
    taskForm.reset();
    taskForm.status_id = statusId;
    taskForm.priority_id = props.priorities[0]?.id;
    showTaskModal.value = true;
};

const openEditModal = (task) => {
    editingTask.value = task;
    taskForm.title = task.title;
    taskForm.description = task.description;
    taskForm.status_id = task.status_id;
    taskForm.priority_id = task.priority_id;
    taskForm.assignee_id = task.assignee_id;
    taskForm.due_date = task.due_date;
    taskForm.estimated_time = task.estimated_time;
    taskForm.labels = task.labels.map(l => l.id);
    showTaskModal.value = true;
};

const submitTaskForm = () => {
    if (editingTask.value) {
        taskForm.put(route('dashboard.tasks.update', editingTask.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showTaskModal.value = false;
                editingTask.value = null;
            }
        });
    } else {
        taskForm.post(route('dashboard.tasks.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showTaskModal.value = false;
            }
        });
    }
};

const applyFilters = () => {
    router.get(route('dashboard.tasks.index'), {
        search: filters.value.search,
        status: filters.value.status,
        priority: filters.value.priority,
        assignee: filters.value.assignee,
        label: filters.value.label,
        dueDate: filters.value.dueDate
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
    showFiltersModal.value = false;
};

const toggleLabel = (labelId) => {
    const index = taskForm.labels.indexOf(labelId);
    if (index === -1) {
        taskForm.labels.push(labelId);
    } else {
        taskForm.labels.splice(index, 1);
    }
};

const deleteTask = async (taskId) => {
    if (confirm('Вы уверены, что хотите удалить эту задачу?')) {
        try {
            await router.delete(route('dashboard.tasks.destroy', taskId), {
                preserveScroll: true,
                onSuccess: () => {
                    // Можно добавить уведомление об успехе
                }
            });
        } catch (error) {
            console.error('Error deleting task:', error);
            // Можно добавить уведомление об ошибке
        }
    }
};

const openViewModal = (task) => {
    viewingTask.value = task;
    showViewModal.value = true;
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <BreadCrumbs :breadcrumbs="breadCrumbs"/>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Задачи
                </h1>
                <div class="flex gap-2">
                    <!-- Переключатель вида -->
                    <div class="flex p-0.5 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <button 
                            @click="view = 'board'"
                            :class="[
                                'p-2 rounded-md transition-colors',
                                view === 'board' 
                                    ? 'bg-white dark:bg-gray-600 shadow-sm' 
                                    : 'hover:bg-gray-200 dark:hover:bg-gray-500'
                            ]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <!-- Иконка канбан-доски -->
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                        </button>
                        <button 
                            @click="view = 'list'"
                            :class="[
                                'p-2 rounded-md transition-colors',
                                view === 'list' 
                                    ? 'bg-white dark:bg-gray-600 shadow-sm' 
                                    : 'hover:bg-gray-200 dark:hover:bg-gray-500'
                            ]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <!-- Иконка списка -->
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>

                    <PrimaryButton @click="showFiltersModal = true" type="alternative">
                        <template #icon-left>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                        </template>
                        Фильтры
                    </PrimaryButton>

                    <PrimaryButton @click="openCreateModal">
                        <template #icon-left>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </template>
                        Создать задачу
                    </PrimaryButton>

                    <PrimaryButton @click="toggleSettings" type="alternative">
                        <template #icon-left>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </template>
                        Настройки
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="flex h-[calc(100vh-64px)]">
            <!-- Основной контент -->
            <div class="flex-1 overflow-auto">
                <!-- Канбан-доска -->
                <div v-if="view === 'board'" class="p-6">
                    <div class="flex flex-col mt-2">
                        <div class="overflow-x-auto">
                            <div class="inline-block min-w-full align-middle">
                                <div class="overflow-hidden shadow">
                                    <div class="flex justify-start items-start px-4 mb-6 space-x-4">
                                        <div v-for="status in statuses" :key="status.id" class="min-w-kanban">
                                            <div class="py-4 text-base font-semibold text-gray-900 dark:text-gray-300">
                                                {{ status.name }}
                                            </div>

                                            <div class="mb-4 space-y-4 min-w-kanban">
                                                <TaskCard
                                                    v-for="task in groupedTasks[status.id]"
                                                    :key="task.id"
                                                    :task="task"
                                                    @edit="openEditModal"
                                                    @delete="deleteTask"
                                                    @view="openViewModal"
                                                    draggable="true"
                                                    @dragstart="handleDragStart($event, task)"
                                                />
                                            </div>

                                            <button 
                                                type="button"
                                                @click="openCreateModal(status.id)"
                                                class="flex justify-center items-center py-2 w-full font-semibold text-gray-500 rounded-lg border-2 border-gray-200 border-dashed hover:bg-gray-100 hover:text-gray-900 hover:border-gray-300 dark:border-gray-800 dark:hover:border-gray-700 dark:hover:bg-gray-800 dark:hover:text-white"
                                            >
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/>
                                                </svg>
                                                Добавить карточку
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Список -->
                <div v-else class="p-6">
                    <!-- Таблица задач -->
                </div>
            </div>

            <!-- Боковая панель настроек -->
            <div 
                v-show="showSettings"
                class="w-80 border-l border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
            >
                <div class="p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium">Настройки</h3>
                        <button @click="toggleSettings" class="text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Табы настроек -->
                    <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
                        <nav class="flex -mb-px">
                            <button
                                v-for="tab in ['statuses', 'priorities', 'labels']"
                                :key="tab"
                                @click="activeSettingsTab = tab"
                                :class="[
                                    'px-4 py-2 text-sm font-medium',
                                    activeSettingsTab === tab
                                        ? 'border-b-2 border-indigo-500 text-indigo-600'
                                        : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
                            </button>
                        </nav>
                    </div>

                    <!-- Компоненты управления -->
                    <StatusesManager
                        v-if="activeSettingsTab === 'statuses'"
                        :statuses="statuses"
                        @update="updateStatuses"
                        @delete="deleteStatus"
                    />
                    <PrioritiesManager
                        v-else-if="activeSettingsTab === 'priorities'"
                        :priorities="priorities"
                        @update="updatePriorities"
                        @delete="deletePriority"
                    />
                    <LabelsManager
                        v-else
                        :labels="labels"
                        @update="updateLabels"
                        @delete="deleteLabel"
                    />
                </div>
            </div>
        </div>

        <!-- Модальное окно фильтров -->
        <Modal :show="showFiltersModal" @close="showFiltersModal = false">
            <template #title>Фильтры</template>
            <template #content>
                <div class="space-y-4">
                    <TextInput
                        v-model="filters.search"
                        label="Поиск"
                        placeholder="Поиск по названию или описанию..."
                    />

                    <SelectDropdown
                        v-model="filters.status"
                        :options="statuses"
                        label="Статус"
                        option-value="id"
                        option-label="name"
                        placeholder="Все статусы"
                    />

                    <SelectDropdown
                        v-model="filters.priority"
                        :options="priorities"
                        label="Приоритет"
                        option-value="id"
                        option-label="name"
                        placeholder="Все приоритеты"
                    />

                    <SelectDropdown
                        v-model="filters.assignee"
                        :options="users"
                        label="Исполнитель"
                        option-value="id"
                        option-label="name"
                        placeholder="Все исполнители"
                    />

                    <SelectDropdown
                        v-model="filters.label"
                        :options="labels"
                        label="Метка"
                        option-value="id"
                        option-label="name"
                        placeholder="Все метки"
                    />

                    <div>
                        <InputLabel value="Срок выполнения" />
                        <input
                            type="date"
                            v-model="filters.dueDate"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <PrimaryButton @click="showFiltersModal = false" type="alternative">
                        Отмена
                    </PrimaryButton>
                    <PrimaryButton @click="applyFilters">
                        Применить
                    </PrimaryButton>
                </div>
            </template>
        </Modal>

        <!-- Модальное окно создания/редактирования задачи -->
        <Modal :show="showTaskModal" @close="showTaskModal = false" max-width="2xl">
            <template #title>
                {{ editingTask ? 'Редактирование задачи' : 'Создание задачи' }}
            </template>

            <template #content>
                <form @submit.prevent="submitTaskForm" class="space-y-4">
                    <TextInput
                        v-model="taskForm.title"
                        label="Название задачи"
                        :error="taskForm.errors.title"
                        required
                    />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Описание
                        </label>
                        <textarea
                            v-model="taskForm.description"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <SelectDropdown
                            v-model="taskForm.status_id"
                            :options="statuses"
                            label="Статус"
                            option-value="id"
                            option-label="name"
                            :error="taskForm.errors.status_id"
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
                            v-model="taskForm.priority_id"
                            :options="priorities"
                            label="Приоритет"
                            option-value="id"
                            option-label="name"
                            :error="taskForm.errors.priority_id"
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
                            v-model="taskForm.assignee_id"
                            :options="users"
                            label="Исполнитель"
                            option-value="id"
                            option-label="display_name"
                            :error="taskForm.errors.assignee_id"
                            placeholder="Выберите исполнителя"
                        >
                            <template #option="{ option: user }">
                                <div class="flex flex-col">
                                    <span>{{ user.display_name }}</span>
                                    <span v-if="user.name && user.email" class="text-xs text-gray-500">
                                        {{ user.email }}
                                    </span>
                                </div>
                            </template>
                        </SelectDropdown>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Срок выполнения
                            </label>
                            <input
                                type="datetime-local"
                                v-model="taskForm.due_date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

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
                                    taskForm.labels.includes(label.id)
                                        ? 'text-white'
                                        : 'text-gray-700 bg-gray-100 hover:bg-gray-200'
                                ]"
                                :style="taskForm.labels.includes(label.id) ? { backgroundColor: label.color } : {}"
                            >
                                {{ label.name }}
                            </button>
                        </div>
                    </div>
                </form>
            </template>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <PrimaryButton @click="showTaskModal = false" type="alternative">
                        Отмена
                    </PrimaryButton>
                    <PrimaryButton @click="submitTaskForm" :loading="taskForm.processing">
                        {{ editingTask ? 'Сохранить' : 'Создать' }}
                    </PrimaryButton>
                </div>
            </template>
        </Modal>

        <!-- Добавляем модальное окно просмотра -->
        <TaskViewModal
            :show="showViewModal"
            :task="viewingTask"
            :current-user="$page.props.auth.user"
            :statuses="statuses"
            @close="showViewModal = false"
            @update="loadTasks"
        />
    </DashboardLayout>
</template> 