<?php

namespace App\Http\Controllers;

use App\Models\FieldGroup;
use App\Models\FieldType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FieldGroupController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Content/FieldGroups/Index', [
            'fieldGroups' => FieldGroup::with('fields.fieldType')->paginate(10),
            'fieldTypes' => FieldType::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fields' => 'required|array',
            'fields.*.field_type_id' => 'required|exists:field_types,id',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.key' => 'required|string|max:255',
            'fields.*.required' => 'boolean'
        ]);

        $fieldGroup = FieldGroup::create([
            'name' => $validated['name']
        ]);

        foreach ($validated['fields'] as $field) {
            $fieldGroup->fields()->create($field);
        }

        return redirect()->back();
    }

    public function update(Request $request, FieldGroup $fieldGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fields' => 'required|array',
            'fields.*.field_type_id' => 'required|exists:field_types,id',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.key' => 'required|string|max:255',
            'fields.*.required' => 'boolean'
        ]);

        $fieldGroup->update([
            'name' => $validated['name']
        ]);

        // Удаляем существующие поля и создаем новые
        $fieldGroup->fields()->delete();
        foreach ($validated['fields'] as $field) {
            $fieldGroup->fields()->create($field);
        }

        return redirect()->back();
    }

    public function destroy(FieldGroup $fieldGroup)
    {
        $fieldGroup->delete();
        return redirect()->back();
    }
}
