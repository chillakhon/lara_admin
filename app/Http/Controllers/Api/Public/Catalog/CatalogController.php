<?php

namespace App\Http\Controllers\Api\Public\Catalog;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Public\ProductPublicResource;
use App\Models\Product;
use App\Services\Catalog\CatalogService;
use App\Traits\ProductsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CatalogController extends Controller
{
    use ProductsTrait;

    protected CatalogService $catalogService;

    public function __construct(CatalogService $catalogService)
    {
        $this->catalogService = $catalogService;
    }

    /**
     * Получить категории для меню каталога
     * GET /api/public/catalog/menu-categories
     */
    public function menuCategories()
    {
        $categories = $this->catalogService->getCatalogMenuCategories();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Получить баннеры для главной страницы
     * GET /api/public/catalog/home-banners
     */
    public function homeBanners()
    {
        $banners = $this->catalogService->getHomeBanners();

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    /**
     * Получить товары каталога с фильтрами
     * GET /api/public/catalog/products
     */
    public function products(Request $request)
    {
        // Собираем фильтры из запроса
        $filters = [
            'category_id' => $request->get('category_id'),
            'category_slug' => $request->get('category_slug'),
            'absorbency_level' => $request->get('absorbency_level'),
            'fit_type' => $request->get('fit_type'),
            'is_new' => $request->boolean('is_new'),
            'color_id' => $request->get('color_id'),
            'price_after' => $request->get('price_after'),
            'price_before' => $request->get('price_before'),
            'in_stock' => $request->boolean('in_stock'),
            'search' => $request->get('search'),
            'sort_by' => $request->get('sort_by', 'display_order'),
            'sort_order' => $request->get('sort_order', 'asc'),
        ];

        // Получаем query builder из сервиса
        $query = $this->catalogService->getProductsQuery($filters);

        // Пагинация
        $perPage = $request->get('per_page', 9);
        $products = $query->paginate($perPage);

        // Применяем скидки (используем существующий trait)
        $this->applyDiscountsToCollection($products->getCollection());

        $category = null;
        if (!empty($filters['category_slug'])) {
            $category = $this->catalogService->getCategoryBySlug($filters['category_slug']);
        }

        return [
            'success' => true,
            'data' => ProductPublicResource::collection($products->items()),
            'meta' => array_merge(
                PaginationHelper::formatShopFron($products),
                [
                    'category' => $category ? [
                        'id' => $category->id,
                        'name' => $category->name
                    ] : null,
                ]
            )
        ];
    }


    public function getProduct(Product $product): ProductPublicResource
    {

        $this->applyDiscountToProduct($product);

        $product->load('variants');
        if ($product->relationLoaded('variants')) {
            foreach ($product->variants as $variant) {
                $this->applyDiscountToProduct($variant);
            }
        }


        return ProductPublicResource::make($product);
    }

}
