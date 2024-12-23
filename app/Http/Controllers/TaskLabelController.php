<?php

namespace App\Http\Controllers;

use App\Models\TaskLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;

class TaskLabelController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        Log::info('Current user:', [
            'id' => auth()->id(),
            'type' => auth()->user()->type
        ]);

        $this->authorize('manage-tasks');
        
        return Inertia::render('Dashboard/Tasks/Labels/Index', [
            'labels' => TaskLabel::withCount('tasks')->get()
        ]);
    }

    public function store(Request $request)
    {
        // $this->authorize('manage-tasks');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:task_labels,name',
            'color' => 'required|string|max:7'
        ]);

        TaskLabel::create([
            ...$validated,
            'slug' => Str::slug($validated['name'])
        ]);

        return redirect()->back()->with('success', 'Label created successfully');
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

        return redirect()->back()->with('success', 'Label updated successfully');
    }

    public function destroy(TaskLabel $label)
    {
        $this->authorize('manage-tasks');
        
        $label->tasks()->detach(); // Удаляем связи с задачами
        $label->delete();
        
        return redirect()->back()->with('success', 'Label deleted successfully');
    }
} 