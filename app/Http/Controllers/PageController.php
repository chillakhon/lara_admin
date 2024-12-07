<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::query()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Dashboard/Content/Pages/Index', [
            'pages' => $pages
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        Page::create($validated);

        return redirect()->back()->with('success', 'Страница успешно создана');
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug,' . $page->id,
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $page->update($validated);

        return redirect()->back()->with('success', 'Страница успешно обновлена');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->back()->with('success', 'Страница успешно удалена');
    }
}
