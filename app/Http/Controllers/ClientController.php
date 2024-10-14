<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::with('user')->get();
        return Inertia::render('Dashboard/Clients/Index', ['clients' => $clients]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'type' => 'client',
        ]);

        $client = $user->client()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
        ]);

        return redirect()->back()->with('success', 'Client created successfully');
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $client->user_id,
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $client->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
        ]);

        $client->user->update([
            'email' => $validated['email'],
        ]);

        return redirect()->back()->with('success', 'Client updated successfully');
    }

    public function destroy(Client $client)
    {
        $user = $client->user;
        $client->delete();
        $user->delete();
        return redirect()->route('dashboard.clients.index')->with('success', 'Client deleted successfully');
    }
}
