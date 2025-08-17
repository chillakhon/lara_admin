<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index()
    {
        $filters = [
            'search' => request('search', ''),
            'status' => request('status', ''),
            'priority' => request('priority', ''),
            'assignee' => request('assignee', ''),
            'label' => request('label', ''),
            'dueDate' => request('dueDate', '')
        ];

        // Параметры пагинации
        $perPage = request('per_page', 15);
        $page = request('page', 1);

        // Базовый запрос
        $query = Task::with([
            'status',
            'priority',
            'creator.profile',
            'assignee.profile',
            'labels',
            'comments.user.profile'
        ])->latest();

        // Фильтры
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status_id', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority_id', $filters['priority']);
        }

        if (!empty($filters['assignee'])) {
            $query->where('assignee_id', $filters['assignee']);
        }

        if (!empty($filters['label'])) {
            $query->whereHas('labels', function ($q) use ($filters) {
                $q->where('task_labels.id', $filters['label']);
            });
        }

        if (!empty($filters['dueDate'])) {
            $query->whereDate('due_date', $filters['dueDate']);
        }

        // Пагинация
        $tasks = $query->paginate($perPage, ['*'], 'page', $page);

        // Список пользователей
        $users = User::with('profile')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => data_get($user, 'profile.full_name'), // безопасно
                ];
            });

        return response()->json([
            'tasks' => $tasks->getCollection()->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'status_id' => $task->status_id,
                    'priority' => $task->priority,
                    'priority_id' => $task->priority_id,
                    'started_at' => $task->started_at,
                    'completed_at' => $task->completed_at,
                    'spent_time' => $task->spent_time,
                    'creator' => $task->creator ? [
                        'id' => $task->creator->id,
                        'name' => data_get($task, 'creator.profile.full_name'),
                        'email' => $task->creator->email,
                        'profile' => $task->creator->profile,
                    ] : null,
                    'creator_id' => $task->creator_id,
                    'assignee' => $task->assignee ? [
                        'id' => $task->assignee->id,
                        'name' => data_get($task, 'assignee.profile.full_name'),
                        'email' => $task->assignee->email,
                    ] : null,
                    'assignee_id' => $task->assignee_id,
                    'labels' => $task->labels,
                    'due_date' => $task->due_date,
                    'estimated_time' => $task->estimated_time,
                    'created_at' => $task->created_at,
                    'comments' => $task->comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'content' => $comment->content,
                            'user' => [
                                'id' => $comment->user?->id,
                                'name' => data_get($comment, 'user.profile.full_name'),
                                'email' => $comment->user?->email,
                            ],
                            'created_at' => $comment->created_at,
                        ];
                    }),
                ];
            }),

            'statuses' => TaskStatus::orderBy('order')->get(),
            'priorities' => TaskPriority::orderBy('level')->get(),
            'labels' => TaskLabel::get(),
            'users' => $users,
            'filters' => $filters,
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'from' => $tasks->firstItem(),
                'to' => $tasks->lastItem(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status_id' => 'required|exists:task_statuses,id',
            'priority_id' => 'required|exists:task_priorities,id',
            'creator_id' => 'nullable|exists:users,id',
            'assignee_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:tasks,id',
            'due_date' => 'nullable|date',
            'started_at' => 'nullable|date',
            'estimated_time' => 'nullable|integer|min:0',
            'labels' => 'nullable|array',
            'labels.*' => 'exists:task_labels,id'
        ]);

        $task = Task::create([
            ...$validated,
            'creator_id' => Auth::id(),
        ]);

        if (!empty($validated['labels'])) {
            $task->labels()->attach($validated['labels']);
        }

        return response()->json([
            'message' => 'Задача создана успешно',
            'task' => $task], 201);
    }


    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status_id' => ['sometimes', 'required', 'exists:task_statuses,id'],
            'priority_id' => ['sometimes', 'required', 'exists:task_priorities,id'],
            'creator_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'assignee_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'parent_id' => ['sometimes', 'nullable', 'exists:tasks,id'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'started_at' => ['sometimes', 'nullable', 'date'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
            'estimated_time' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'spent_time' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['exists:task_labels,id'],
        ]);

        // Обновляем основные поля
        $task->fill($validated);
        $task->save();

        // Синхронизируем метки, если поле пришло (даже пустой массив — значит снять все метки)
        if (array_key_exists('labels', $validated)) {
            $task->labels()->sync($validated['labels'] ?? []);
        }

        // Подгружаем связи, чтобы вернуть полный объект (по необходимости подкорректируй список)
        $task->load(['status', 'priority', 'creator.profile', 'assignee.profile', 'labels', 'comments.user.profile']);

        return response()->json([
            'message' => 'Задача обновлена успешно',
            'task' => $task,
        ], 200);
    }


    public function complete(Task $task)
    {
        // Находим или создаём статус "Завершено"
        $status = TaskStatus::firstOrCreate(
            ['name' => 'Завершено'],
            [
                'slug'     => Str::slug('Завершено'), // обязательное поле
                'order'    => 99,
                'color'    => '#22c55e',             // можно дефолтный цвет
                'is_default' => false,
            ]
        );


        $task->status_id = $status->id;
        $task->completed_at = now();
        $task->save();

        $task->load([
            'status',
            'priority',
            'creator.profile',
            'assignee.profile',
            'labels',
            'comments.user.profile'
        ]);

        return response()->json([
            'message' => 'Задача завершена',
            'task' => $task,
        ], 200);
    }


    /**
     * Remove the specified task.
     */
    public function destroy(Task $task)
    {
        // Отвязываем метки (опционально — если используешь pivot и хочешь чистить)
        if ($task->relationLoaded('labels') || method_exists($task, 'labels')) {
            $task->labels()->detach();
        }

        // Удаляем задачу (если у тебя soft deletes — это пометит удаление)
        $task->delete();

        return response()->json([
            'message' => 'Задача удалена успешно',
        ], 200);
    }

}
