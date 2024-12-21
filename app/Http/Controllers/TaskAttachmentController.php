<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TaskAttachmentController extends Controller
{
    use AuthorizesRequests;
    
    public function store(Request $request, Task $task)
    {
        $request->validate([
            'file' => 'required|file|max:10240' // максимум 10MB
        ]);

        $file = $request->file('file');
        $path = $file->store('task-attachments');

        $attachment = $task->attachments()->create([
            'user_id' => Auth::id(),
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize()
        ]);

        return redirect()->back()->with('success', 'File uploaded successfully');
    }

    public function destroy(Task $task, TaskAttachment $attachment)
    {
        $this->authorize('delete', $attachment);

        Storage::delete($attachment->path);
        $attachment->delete();

        return redirect()->back()->with('success', 'File deleted successfully');
    }

    public function download(Task $task, TaskAttachment $attachment)
    {
        return Storage::download(
            $attachment->path, 
            $attachment->filename
        );
    }
} 