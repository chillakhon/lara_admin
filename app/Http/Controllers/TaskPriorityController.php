<?php

namespace App\Http\Controllers;

use App\Models\TaskPriority;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;

class TaskPriorityController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        Log::info('Current user:', [
            'id' => auth()->id(),
            'type' => auth()->user()->type
        ]);

        $this->authorize('manage-tasks');
        
        return Inertia::render('Dashboard/Tasks/Priorities/Index', [
            'priorities' => TaskPriority::orderBy('level')->get()
        ]);
    }

    public function store(Request $request)
    {
        // $this->authorize('manage-tasks');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:task_priorities,name',
            'color' => 'required|string|max:7',
            'level' => 'required|integer|min:0'
        ]);

        TaskPriority::create([
            ...$validated,
            'slug' => Str::slug($validated['name'])
        ]);

        return redirect()->back()->with('success', 'Priority created successfully');
    }

    public function update(Request $request, TaskPriority $priority)
    {
        //$this->authorize('manage-tasks');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:task_priorities,name,' . $priority->id,
            'color' => 'required|string|max:7',
            'level' => 'required|integer|min:0'
        ]);

        $priority->update([
            ...$validated,
            'slug' => Str::slug($validated['name'])
        ]);

        return redirect()->back()->with('success', 'Priority updated successfully');
    }

    public function destroy(TaskPriority $priority)
    {
        $this->authorize('manage-tasks');
        
        if ($priority->tasks()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete priority with associated tasks');
        }

        $priority->delete();
        return redirect()->back()->with('success', 'Priority deleted successfully');
    }
} 