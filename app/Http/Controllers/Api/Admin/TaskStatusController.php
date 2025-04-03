<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaskStatus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskStatusController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        $this->authorize('manage-tasks');

        return response()->json([
            'statuses' => TaskStatus::orderBy('order')->get()
        ]);
    }

    public function store(Request $request)
    {
        // $this->authorize('manage-tasks');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:task_statuses,name',
            'color' => 'required|string|max:7',
            'order' => 'required|integer|min:0',
            'is_default' => 'boolean'
        ]);

        $status = TaskStatus::create([
            ...$validated,
            'slug' => Str::slug($validated['name'])
        ]);

        if ($status->is_default) {
            TaskStatus::where('id', '!=', $status->id)
                ->update(['is_default' => false]);
        }

        return response()->json([
            'message' => 'Status created successfully',
            'taskStatus' => $status], 201);
    }

    public function update(Request $request, TaskStatus $status)
    {
        $this->authorize('manage-tasks');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:task_statuses,name,' . $status->id,
            'color' => 'required|string|max:7',
            'order' => 'required|integer|min:0',
            'is_default' => 'boolean'
        ]);

        $status->update([
            ...$validated,
            'slug' => Str::slug($validated['name'])
        ]);

        if ($status->is_default) {
            TaskStatus::where('id', '!=', $status->id)
                ->update(['is_default' => false]);
        }

        return response()->json([
            'message' => 'Status updated successfully',
            'taskStatus' => $status], 201);
    }

    public function destroy(TaskStatus $status)
    {
        $this->authorize('manage-tasks');

        if ($status->tasks()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete status with associated tasks');
        }

        $status->delete();

        return response()->json([
            'message' => 'Status deleted successfully'], 201);
    }

    public function reorder(Request $request)
    {
        $this->authorize('manage-tasks');

        $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'required|integer|exists:task_statuses,id'
        ]);

        foreach ($request->orders as $index => $id) {
            TaskStatus::where('id', $id)->update(['order' => $index]);
        }

        return response()->json([
            'message' => 'Order updated successfully'], 201);
    }
}
