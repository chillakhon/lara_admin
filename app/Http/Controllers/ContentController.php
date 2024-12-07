<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ContentController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Content/Index', [
            'stats' => [
                'pages' => [
                    'count' => \App\Models\Page::count(),
                    'route' => route('dashboard.content.pages.index')
                ],
                'blocks' => [
                    'count' => \App\Models\ContentBlock::count(),
                    'route' => route('dashboard.content.blocks.index')
                ],
                'fieldGroups' => [
                    'count' => \App\Models\FieldGroup::count(),
                    'route' => route('dashboard.content.field-groups.index')
                ],
                'fieldTypes' => [
                    'count' => \App\Models\FieldType::count(),
                    'route' => route('dashboard.content.field-types.index')
                ]
            ]
        ]);
    }
}
