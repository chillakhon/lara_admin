<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Leads/Types/Index', [
            'leadTypes' => LeadType::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:lead_types',
            'description' => 'nullable|string',
            'required_fields' => 'required|array',
            'is_active' => 'boolean'
        ]);

        LeadType::create($validated);

        return redirect()->route('dashboard.lead-types.index')
            ->with('success', 'Тип лида успешно создан');
    }

    public function update(Request $request, LeadType $leadType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:lead_types,code,' . $leadType->id,
            'description' => 'nullable|string',
            'required_fields' => 'required|array',
            'is_active' => 'boolean'
        ]);

        $leadType->update($validated);

        return redirect()->route('dashboard.lead-types.index')
            ->with('success', 'Тип лида успешно обновлен');
    }

    public function destroy(LeadType $leadType)
    {
        $leadType->delete();

        return redirect()->route('dashboard.lead-types.index')
            ->with('success', 'Тип лида успешно удален');
    }
} 