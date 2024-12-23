<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskPriority;
use App\Models\TaskLabel;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $tasks = Task::with([
            'status',
            'priority',
            'creator.profile',
            'assignee.profile',
            'labels',
            'comments.user.profile'
        ])->get();

        // Получаем пользователей, исключая клиентов
        $users = User::whereHas('roles', function($query) {
                $query->where('slug', '!=', 'client');
            })
            ->with('profile')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->profile->full_name,
                    'display_name' => $user->profile->full_name ?: $user->email
                ];
            });

        return Inertia::render('Dashboard/Tasks/Index', [
            'tasks' => $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'creator' => $task->creator ? [
                        'id' => $task->creator->id,
                        'name' => $task->creator->profile->full_name,
                        'email' => $task->creator->email,
                    ] : null,
                    'assignee' => $task->assignee ? [
                        'id' => $task->assignee->id,
                        'name' => $task->assignee->profile->full_name,
                        'email' => $task->assignee->email,
                    ] : null,
                    'labels' => $task->labels,
                    'due_date' => $task->due_date,
                    'estimated_time' => $task->estimated_time,
                    'created_at' => $task->created_at,
                    'comments' => $task->comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'content' => $comment->content,
                            'user' => [
                                'id' => $comment->user->id,
                                'name' => $comment->user->profile->full_name,
                                'email' => $comment->user->email,
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
            'filters' => $filters
        ]);
    }

    public function create()
    {
        return Inertia::render('Dashboard/Tasks/Create', [
            'statuses' => TaskStatus::all(),
            'priorities' => TaskPriority::all(),
            'labels' => TaskLabel::all(),
            'users' => User::where('type', '!=', 'client')->get(),
            'parentTasks' => Task::whereNull('parent_id')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status_id' => 'required|exists:task_statuses,id',
            'priority_id' => 'required|exists:task_priorities,id',
            'assignee_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:tasks,id',
            'due_date' => 'nullable|date',
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

        return redirect()->back()
            ->with('success', 'Задача создана успешно');
    }

    public function show(Task $task)
    {
        $task->load([
            'status', 
            'priority', 
            'creator.profile', 
            'assignee.profile', 
            'labels',
            'parent',
            'subtasks',
            'comments.user.profile',
            'attachments',
            'history.user.profile'
        ]);

        // Получаем пользователей для формы назначения
        $users = User::whereHas('roles', function($query) {
                $query->where('slug', '!=', 'client');
            })
            ->with('profile')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->profile->full_name,
                    'display_name' => $user->profile->full_name ?: $user->email
                ];
            });

        return Inertia::render('Dashboard/Tasks/Show', [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'creator' => $task->creator ? [
                    'id' => $task->creator->id,
                    'name' => $task->creator->profile->full_name,
                    'email' => $task->creator->email,
                ] : null,
                'assignee' => $task->assignee ? [
                    'id' => $task->assignee->id,
                    'name' => $task->assignee->profile->full_name,
                    'email' => $task->assignee->email,
                ] : null,
                'labels' => $task->labels,
                'due_date' => $task->due_date,
                'estimated_time' => $task->estimated_time,
                'created_at' => $task->created_at,
                'comments' => $task->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'content' => $comment->content,
                        'user' => [
                            'id' => $comment->user->id,
                            'name' => $comment->user->profile->full_name,
                            'email' => $comment->user->email,
                        ],
                        'created_at' => $comment->created_at,
                    ];
                }),
            ],
            'statuses' => TaskStatus::all(),
            'priorities' => TaskPriority::all(),
            'labels' => TaskLabel::all(),
            'users' => $users
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status_id' => 'required|exists:task_statuses,id',
            'priority_id' => 'required|exists:task_priorities,id',
            'assignee_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:tasks,id',
            'due_date' => 'nullable|date',
            'estimated_time' => 'nullable|integer|min:0',
            'labels' => 'nullable|array',
            'labels.*' => 'exists:task_labels,id'
        ]);

        // Проверяем права на изменение статуса
        if ($request->has('status_id') && $task->status_id !== $validated['status_id']) {
            if ($task->assignee_id !== Auth::id()) {
                return response()->json([
                    'message' => 'У вас нет прав на изменение статуса задачи'
                ], 403);
            }
        }

        // Сохраняем старые значения для истории
        $oldValues = $task->only(array_keys($validated));
        
        $task->update($validated);

        // Обновляем метки
        if (isset($validated['labels'])) {
            $task->labels()->sync($validated['labels']);
        }

        // Записываем изменения в историю
        foreach ($validated as $field => $newValue) {
            if ($field !== 'labels' && $oldValues[$field] !== $newValue) {
                $task->history()->create([
                    'user_id' => Auth::id(),
                    'field' => $field,
                    'old_value' => $oldValues[$field],
                    'new_value' => $newValue
                ]);
            }
        }

        return response()->json([
            'message' => 'Задача обновлена',
            'task' => $task->fresh([
                'status', 
                'priority', 
                'assignee', 
                'labels', 
                'comments.user'
            ])
        ]);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->route('dashboard.tasks.index')
            ->with('success', 'Task deleted successfully');
    }
} 