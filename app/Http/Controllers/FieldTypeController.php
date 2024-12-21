<?php

namespace App\Http\Controllers;

use App\Models\FieldType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FieldTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Content/FieldTypes/Index', [
            'fieldTypes' => FieldType::paginate(10)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'settings' => 'nullable|array'
        ]);

        FieldType::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, FieldType $fieldType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'settings' => 'nullable|array'
        ]);

        $fieldType->update($validated);

        return redirect()->back();
    }

    public function destroy(FieldType $fieldType)
    {
        $fieldType->delete();
        return redirect()->back();
    }
}
