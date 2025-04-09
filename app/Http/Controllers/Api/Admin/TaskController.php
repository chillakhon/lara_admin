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

class TaskController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tasks",
     *     summary="Получить список задач",
     *     description="Возвращает список задач с фильтрами, статусами, приоритетами и метками",
     *     operationId="getTasks",
     *     tags={"Tasks"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Поиск по названию/описанию",
     *         required=false,
     *         @OA\Schema(type="string", example="баг")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Фильтр по статусу",
     *         required=false,
     *         @OA\Schema(type="string", example="in-progress")
     *     ),
     *     @OA\Parameter(
     *         name="priority",
     *         in="query",
     *         description="Фильтр по приоритету",
     *         required=false,
     *         @OA\Schema(type="string", example="high")
     *     ),
     *     @OA\Parameter(
     *         name="assignee",
     *         in="query",
     *         description="Фильтр по исполнителю",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="label",
     *         in="query",
     *         description="Фильтр по метке",
     *         required=false,
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="dueDate",
     *         in="query",
     *         description="Фильтр по сроку выполнения",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="tasks",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Task")
     *             ),
     *             @OA\Property(
     *                 property="statuses",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/TaskStatus")
     *             ),
     *             @OA\Property(
     *                 property="priorities",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/TaskPriority")
     *             ),
     *             @OA\Property(
     *                 property="labels",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/TaskLabel")
     *             ),
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="user@example.com"),
     *                     @OA\Property(property="name", type="string", example="Иван Иванов"),
     *                     @OA\Property(property="display_name", type="string", example="Иван Иванов")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="filters",
     *                 type="object",
     *                 @OA\Property(property="search", type="string", example=""),
     *                 @OA\Property(property="status", type="string", example=""),
     *                 @OA\Property(property="priority", type="string", example=""),
     *                 @OA\Property(property="assignee", type="string", example=""),
     *                 @OA\Property(property="label", type="string", example=""),
     *                 @OA\Property(property="dueDate", type="string", example="")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Неавторизованный доступ",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Internal Server Error")
     *         )
     *     )
     * )
     */
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

        return response()->json([
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
            'filters' => $filters,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/tasks",
     *     summary="Создать новую задачу",
     *     description="Создает новую задачу с указанными параметрами",
     *     operationId="createTask",
     *     tags={"Tasks"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Данные для создания задачи",
     *         @OA\JsonContent(
     *             required={"title", "status_id", "priority_id"},
     *             @OA\Property(property="title", type="string", maxLength=255, example="Исправить критический баг"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Приложение падает при нажатии кнопки 'Сохранить'"),
     *             @OA\Property(property="status_id", type="integer", example=1, description="ID статуса задачи"),
     *             @OA\Property(property="priority_id", type="integer", example=1, description="ID приоритета задачи"),
     *             @OA\Property(property="creator_id", type="integer", nullable=true, example=1, description="ID создателя задачи (если не указан - текущий пользователь)"),
     *             @OA\Property(property="assignee_id", type="integer", nullable=true, example=2, description="ID исполнителя задачи"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=3, description="ID родительской задачи"),
     *             @OA\Property(property="due_date", type="string", format="date-time", nullable=true, example="2024-12-31T23:59:59Z"),
     *             @OA\Property(property="estimated_time", type="integer", nullable=true, example=120, description="Оценка времени в минутах"),
     *             @OA\Property(property="labels", type="array", nullable=true,
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Задача успешно создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Задача создана успешно"),
     *             @OA\Property(property="task", ref="#/components/schemas/Task")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Неавторизованный доступ",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибки валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="title", type="array",
     *                     @OA\Items(type="string", example="The title field is required")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Internal Server Error")
     *         )
     *     )
     * )
     */
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
}
