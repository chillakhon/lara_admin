<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('adminUser')->where('type', '!=', 'client')->paginate(10);

        return Inertia::render('Dashboard/Users/Index', ['users' => $users]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,manager',
            'permissions' => 'nullable|array',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'type' => $validated['role'],
        ]);

        $user->adminUser()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'role' => $validated['role'],
            'permissions' => $validated['permissions'] ?? null,
        ]);

        return redirect()->back()->with('success', 'User created successfully');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,manager',
            'permissions' => 'nullable|array',
        ]);

        $user->update([
            'email' => $validated['email'],
            'type' => $validated['role'],
        ]);

        $user->adminUser->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'role' => $validated['role'],
            'permissions' => $validated['permissions'] ?? null,
        ]);

        return redirect()->back()->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $user->adminUser()->delete();
        $user->delete();
        return redirect()->back()->with('success', 'User deleted successfully');
    }
}
