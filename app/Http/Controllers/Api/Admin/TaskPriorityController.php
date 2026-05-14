<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaskPriority;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskPriorityController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function index(Request $request)
    {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "User was not found",
            ]);
        }

//        if (!$user->hasAnyRole(['admin', 'super-admin', 'manager'])) {
//            return response()->json([
//                'success' => false,
//                'message' => "Sorry, you dont have specific permission to continue"
//            ]);
//        }
//


        $query = TaskPriority::query();
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }


        return response()->json([
            'priorities' => $query->orderBy('level')->get()
        ]);
    }

    public function store(Request $request)
    {
        // $this->authorize('manage-tasks');

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "User was not found",
            ]);
        }

        if (!$user->hasAnyRole(['admin', 'super-admin', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => "Sorry, you dont have specific permission to continue"
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:task_priorities,name',
            'color' => 'required|string|max:7',
            'level' => 'required|integer|min:0'
        ]);

        $priority = TaskPriority::create([
            ...$validated,
            'slug' => Str::slug($validated['name'])
        ]);

        return response()->json([
            'message' => 'Priority created successfully',
            'taskPriority' => $priority
        ], 201);
    }

    public function update(Request $request, TaskPriority $priority)
    {
        //$this->authorize('manage-tasks');
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "User was not found",
            ]);
        }

        if (!$user->hasAnyRole(['admin', 'super-admin', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => "Sorry, you dont have specific permission to continue"
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:task_priorities,name,' . $priority->id,
            'color' => 'required|string|max:7',
            'level' => 'required|integer|min:0'
        ]);

        $priority->update([
            ...$validated,
            'slug' => Str::slug($validated['name'])
        ]);

        return response()->json([
            'message' => 'Priority updated successfully',
            'taskPriority' => $priority
        ], 201);
    }

    public function destroy(Request $request, TaskPriority $priority)
    {
        // $this->authorize('manage-tasks');
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "User was not found",
            ]);
        }

        if (!$user->hasAnyRole(['admin', 'super-admin', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => "Sorry, you dont have specific permission to continue"
            ]);
        }

        if ($priority->tasks()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete priority with associated tasks');
        }

        $priority->delete();

        return response()->json([
            'message' => 'Priority deleted successfully'
        ], 201);
    }
}
