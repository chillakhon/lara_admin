<?php

namespace App\Http\Controllers;

use App\Models\ContentBlock;
use App\Models\FieldGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContentBlockController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Content/Blocks/Index', [
            'blocks' => ContentBlock::with('fieldGroup')->paginate(10),
            'fieldGroups' => FieldGroup::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:unique:content_blocks,key',
            'field_group_id' => 'required|exists:field_groups,id',
            'description' => 'nullable|string'
        ]);

        ContentBlock::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, ContentBlock $block)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255|unique:content_blocks,key,' . $block->id,
            'field_group_id' => 'required|exists:field_groups,id',
            'description' => 'nullable|string'
        ]);

        $block->update($validated);

        return redirect()->back();
    }

    public function destroy(ContentBlock $block)
    {
        $block->delete();
        return redirect()->back();
    }
}
