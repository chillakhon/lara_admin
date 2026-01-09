<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class CatalogService
{
    /**
     * Получить категории для меню каталога
     */
    public function getCatalogMenuCategories()
    {
        return Category::where('show_in_catalog_menu', true)
            ->orderBy('menu_order', 'asc')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'slug']);
    }

    /**
     * Получить категории для баннеров главной страницы
     */
    public function getHomeBanners()
    {
        $banners = Category::where('show_as_home_banner', true)
            ->orderBy('menu_order', 'asc')
            ->get(['id', 'name', 'slug', 'banner_image', 'description']);

        // Добавляем полный URL для баннера
        $banners->transform(function ($banner) {
            if ($banner->banner_image) {
                $banner->banner_url = url('storage/' . $banner->banner_image);
            } else {
                $banner->banner_url = null;
            }
            return $banner;
        });

        return $banners;
    }

    /**
     * Получить товары каталога с фильтрами
     *
     * @param array $filters - массив фильтров
     * @return Builder
     */
    public function getProductsQuery(array $filters = []): Builder
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with([
                'images' => function ($q) {
                    $q->orderBy('order', 'asc');
                },
                'colors:id,name,code',
                'defaultUnit',
                'variants',
            ]);


        // Фильтр по категории (ID или SLUG)
        if (!empty($filters['category_id']) || !empty($filters['category_slug'])) {

            // Получаем категорию
            $category = null;
            if (!empty($filters['category_id'])) {
                $category = Category::find($filters['category_id']);
            } elseif (!empty($filters['category_slug'])) {
                $category = Category::where('slug', $filters['category_slug'])->first();
            }

            if ($category) {
                // Если у категории включен флаг is_new_product
                if ($category->is_new_product) {
                    // Показываем ВСЕ товары с меткой "новинка"
                    $query->where('is_new', true);
                } else {
                    // Обычная логика - товары привязанные к категории
                    $query->whereHas('categories', function ($q) use ($category) {
                        $q->where('categories.id', $category->id);
                    });
                }
            }
        }

        // Фильтр по впитываемости
        if (!empty($filters['absorbency_level'])) {
            $query->where('absorbency_level', $filters['absorbency_level']);
        }

        // Фильтр по посадке
        if (!empty($filters['fit_type'])) {
            $query->where('fit_type', $filters['fit_type']);
        }

        // Фильтр "Новинки"
        if (!empty($filters['is_new'])) {
            $query->where('is_new', true);
        }

        // Фильтр по цвету (существующий)
        if (!empty($filters['color_id'])) {
            $query->whereHas('variants', function ($q) use ($filters) {
                $q->whereNull('deleted_at');
                $q->where('color_id', $filters['color_id']);
            });
        }

        // Фильтр по цене (существующий)
        if (!empty($filters['price_after'])) {
            $query->where('price', '>=', $filters['price_after']);
        }
        if (!empty($filters['price_before'])) {
            $query->where('price', '<=', $filters['price_before']);
        }

        // Фильтр "В наличии" (существующий)
        if (!empty($filters['in_stock'])) {
            $query->where('stock_quantity', '>', 0);
        }

        // Поиск (существующий)
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'display_order';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query;
    }


    public function getCategoryBySlug(string $slug): ?Category
    {
        return Category::whereSlug($slug)
            ->first();
    }


}
