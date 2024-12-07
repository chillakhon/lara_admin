<?php

namespace App\Http\Controllers;

use App\Models\ClientLevel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientLevelController extends Controller
{
    public function index()
    {
        $levels = ClientLevel::withCount('clients')
            ->get()
            ->map(function ($level) {
                return array_merge($level->toArray(), [
                    'total_spent' => $level->clients->sum('total_spent'),
                    'average_spent' => $level->clients_count > 0
                        ? $level->clients->average('total_spent')
                        : 0,
                ]);
            });

        return Inertia::render('Dashboard/Levels/Index', [
            'levels' => $levels,
            'statistics' => [
                'total_clients' => $levels->sum('clients_count'),
                'total_spent' => $levels->sum('total_spent'),
                'average_spent' => $levels->sum('clients_count') > 0
                    ? $levels->sum('total_spent') / $levels->sum('clients_count')
                    : 0,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'threshold' => 'required|numeric|min:0',
            'calculation_type' => 'required|in:order_count,order_sum',
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ]);

        ClientLevel::create($validated);

        return redirect()->back()->with('success', 'Уровень клиента успешно создан');
    }

    public function update(Request $request, ClientLevel $clientLevel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'threshold' => 'required|numeric|min:0',
            'calculation_type' => 'required|in:order_count,order_sum',
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $clientLevel->update($validated);

        return redirect()->back()->with('success', 'Уровень клиента успешно обновлен');
    }

    public function destroy(ClientLevel $clientLevel)
    {
        $clientLevel->delete();

        return redirect()->back()->with('success', 'Уровень клиента успешно удален');
    }
}
