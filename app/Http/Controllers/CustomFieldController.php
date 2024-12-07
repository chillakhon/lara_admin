<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    public function index(Request $request)
    {
        $customFields = CustomField::paginate(10);
        return inertia('Dashboard/Content/CustomFields', [
            'customFields' => $customFields
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'field_name' => 'required|string|max:255',
            'field_value' => 'required|string',
        ]);

        CustomField::create($request->all());

        return redirect()->route('dashboard.content.custom-fields');
    }

    public function update(Request $request, CustomField $customField)
    {
        $request->validate([
            'field_name' => 'required|string|max:255',
            'field_value' => 'required|string',
        ]);

        $customField->update($request->all());

        return redirect()->route('dashboard.content.custom-fields');
    }

    public function destroy(CustomField $customField)
    {
        $customField->delete();

        return redirect()->route('dashboard.content.custom-fields');
    }
}
