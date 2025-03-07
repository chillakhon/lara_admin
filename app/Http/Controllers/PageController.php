<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Field;
use App\Models\FieldValue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class .)PageController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Content/Pages/Index', [
            'pages' => Page::latest()->paginate(10),
            'fields' => Field::with('children')->whereNull('parent_id')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean',
            'fields' => 'array'
        ]);

        DB::transaction(function () use ($validated) {
            $page = Page::create([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'meta_title' => $validated['meta_title'],
                'meta_description' => $validated['meta_description'],
                'is_active' => $validated['is_active']
            ]);

            $this->saveFieldValues($page, $validated['fields'] ?? []);
        });

        return redirect()->back();
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,' . $page->id,
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean',
            'fields' => 'array'
        ]);

        DB::transaction(function () use ($validated, $page) {
            $page->update([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'meta_title' => $validated['meta_title'],
                'meta_description' => $validated['meta_description'],
                'is_active' => $validated['is_active']
            ]);

            $this->saveFieldValues($page, $validated['fields'] ?? []);
        });

        return redirect()->back();
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return redirect()->back();
    }

    private function saveFieldValues(Page $page, array $values, ?FieldValue $parent = null)
    {
        foreach ($values as $key => $value) {
            $field = Field::where('key', $key)->first();

            if (!$field) continue;

            $fieldValue = new FieldValue([
                'page_id' => $page->id,
                'field_id' => $field->id,
                'value' => $value,
                'parent_id' => $parent?->id
            ]);

            $fieldValue->save();

            // Рекурсивно сохраняем значения для repeater
            if ($field->type === 'repeater' && is_array($value)) {
                foreach ($value as $index => $item) {
                    $this->saveFieldValues($page, $item, $fieldValue);
                }
            }
        }
    }
}
