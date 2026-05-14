<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Resources\CategoryResource;

class CategoryController extends Controller
{
    public function index()
    {
        // Получаем только корневые категории с их потомками
        $categories = Category::with('children')
            ->whereIsRoot()
            ->defaultOrder()
            ->get();

        return CategoryResource::collection($categories);
    }
}
