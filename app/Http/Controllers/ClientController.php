<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::all();
        return Inertia::render('Dashboard/Clients/Index', ['clients' => $clients]);
    }


    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'system_id' => 'nullable|string|unique:clients,system_id,' . $client->id,
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $client->update($validated);

        return redirect()->back()->with('success', 'Client updated successfully');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('dashboard.clients.index')->with('success', 'Client deleted successfully');
    }
}
