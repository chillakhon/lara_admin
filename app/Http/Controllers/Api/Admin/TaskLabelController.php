<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaskLabel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskLabelController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        $this->authorize('manage-tasks');

        return response()->json([
            'labels' => TaskLabel::withCount('tasks')->get()
        ]);
    }

    public function store(Request $request)
    {
//        dd('dd');
        // $this->authorize('manage-tasks');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:task_labels,name',
            'color' => 'required|string|max:7'
        ]);

        $taskLabel = TaskLabel::create([
            ...$validated,
            'slug' => Str::slug($validated['name'])
        ]);
//        dd($taskLabel);

        return response()->json([
            'message' => 'Label created successfully',
            'taskLabel' => $taskLabel], 201);
    }

    public function update(Request $request, TaskLabel $label)
    {
        //$this->authorize('manage-tasks');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:task_labels,name,' . $label->id,
            'color' => 'required|string|max:7'
        ]);

        $label->update([
            ...$validated,
            'slug' => Str::slug($validated['name'])
        ]);

        return response()->json([
            'message' => 'Label updated successfully',
            'taskLabel' => $label], 201);
    }

    public function destroy(TaskLabel $label)
    {
        $this->authorize('manage-tasks');

        $label->tasks()->detach(); // Удаляем связи с задачами
        $label->delete();

        return response()->json([
            'message' => 'Label deleted successfully'], 201);
    }
}
