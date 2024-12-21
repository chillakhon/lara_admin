<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskPriority;
use App\Models\TaskLabel;
use App\Models\User;
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
            'creator',
            'assignee.adminUser',
            'labels',
            'comments.user'
        ])->get();

        return Inertia::render('Dashboard/Tasks/Index', [
            'tasks' => $tasks->map(function ($task) {
                if ($task->assignee) {
                    $task->assignee = [
                        'id' => $task->assignee->id,
                        'email' => $task->assignee->email,
                        'name' => $task->assignee->adminUser ? 
                            $task->assignee->adminUser->first_name . ' ' . $task->assignee->adminUser->last_name : null,
                        'display_name' => $task->assignee->adminUser ? 
                            $task->assignee->adminUser->first_name . ' ' . $task->assignee->adminUser->last_name : 
                            $task->assignee->email
                    ];
                }
                return $task;
            }),
            'statuses' => TaskStatus::orderBy('order')->get(),
            'priorities' => TaskPriority::orderBy('level')->get(),
            'labels' => TaskLabel::get(),
            'users' => User::where('type', '!=', 'client')
                ->with('adminUser')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'email' => $user->email,
                        'type' => $user->type,
                        'name' => $user->adminUser ? 
                            $user->adminUser->first_name . ' ' . $user->adminUser->last_name : null,
                        'display_name' => $user->adminUser ? 
                            $user->adminUser->first_name . ' ' . $user->adminUser->last_name : 
                            $user->email
                    ];
                }),
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
            'creator', 
            'assignee', 
            'labels',
            'parent',
            'subtasks',
            'comments.user',
            'attachments',
            'history.user'
        ]);

        return Inertia::render('Dashboard/Tasks/Show', [
            'task' => $task,
            'statuses' => TaskStatus::all(),
            'priorities' => TaskPriority::all(),
            'labels' => TaskLabel::all(),
            'users' => User::where('type', '!=', 'client')->get()
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