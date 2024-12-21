<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string'
        ]);

        $comment = $task->comments()->create([
            'content' => $validated['content'],
            'user_id' => Auth::id()
        ]);

        // Загружаем связанные данные для комментария
        $comment->load(['user:id,name,email']);

        return response()->json([
            'message' => 'Комментарий добавлен',
            'comment' => $comment
        ]);
    }

    public function update(Request $request, Task $task, TaskComment $comment)
    {
        $this->authorize('update', $comment);

        $validated = $request->validate([
            'content' => 'required|string'
        ]);

        $comment->update($validated);

        return redirect()->back()->with('success', 'Comment updated successfully');
    }

    public function destroy(Task $task, TaskComment $comment)
    {
        $this->authorize('delete', $comment);
        
        $comment->delete();
        return redirect()->back()->with('success', 'Comment deleted successfully');
    }
} 