<?php

namespace App\Http\Controllers;

use App\Models\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;

class TaskStatusController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        // Временно для отладки
        Log::info('Current user:', [
            'id' => auth()->id(),
            'type' => auth()->user()->type
        ]);

        $this->authorize('manage-tasks');
        
        return Inertia::render('Dashboard/Tasks/Statuses/Index', [
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

        return redirect()->back()->with('success', 'Status created successfully');
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

        return redirect()->back()->with('success', 'Status updated successfully');
    }

    public function destroy(TaskStatus $status)
    {
        $this->authorize('manage-tasks');
        
        if ($status->tasks()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete status with associated tasks');
        }

        $status->delete();
        return redirect()->back()->with('success', 'Status deleted successfully');
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

        return response()->json(['message' => 'Order updated successfully']);
    }
} 