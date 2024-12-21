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
            'fields' => 'array',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.key' => 'required|string|max:255',
            'fields.*.field_type_id' => 'required|exists:field_types,id',
            'fields.*.required' => 'boolean',
            'fields.*.settings' => 'nullable|array'
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
            'fields' => 'array',
            'fields.*.id' => 'nullable|exists:fields,id',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.key' => 'required|string|max:255',
            'fields.*.field_type_id' => 'required|exists:field_types,id',
            'fields.*.required' => 'boolean',
            'fields.*.settings' => 'nullable|array'
        ]);

        $fieldGroup->update([
            'name' => $validated['name']
        ]);

        // Обновляем или создаем поля
        foreach ($validated['fields'] as $fieldData) {
            if (isset($fieldData['id'])) {
                $field = $fieldGroup->fields()->find($fieldData['id']);
                $field->update($fieldData);
            } else {
                $fieldGroup->fields()->create($fieldData);
            }
        }

        // Удаляем поля, которых нет в запросе
        $existingIds = collect($validated['fields'])->pluck('id')->filter();
        $fieldGroup->fields()->whereNotIn('id', $existingIds)->delete();

        return redirect()->back();
    }

    public function destroy(FieldGroup $fieldGroup)
    {
        $fieldGroup->delete();
        return redirect()->back();
    }
}
