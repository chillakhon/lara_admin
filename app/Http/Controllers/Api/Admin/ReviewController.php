<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    /**
     * Получить список отзывов
     *
     * @OA\Get(
     *     path="/api/reviews",
     *     summary="Получить отзывы",
     *     description="Возвращает список отзывов с пагинацией. Возможность фильтрации по product_id.",
     *     tags={"Reviews"},
     *     @OA\Parameter(
     *         name="product_id",
     *         in="query",
     *         description="ID продукта, для которого получаем отзывы",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example=123
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список отзывов с пагинацией",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="text", type="string", example="Отличный продукт!"),
     *                     @OA\Property(property="rating", type="integer", example=5),
     *                     @OA\Property(property="client", type="object",
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name", type="string", example="Иван Иванов")
     *                     ),
     *                     @OA\Property(property="attributes", type="array",
     *                         @OA\Items(type="object",
     *                             @OA\Property(property="name", type="string", example="Цвет"),
     *                             @OA\Property(property="value", type="string", example="Красный")
     *                         )
     *                     ),
     *                     @OA\Property(property="responses", type="array",
     *                         @OA\Items(type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="text", type="string", example="Спасибо за ваш отзыв!")
     *                         )
     *                     ),
     *                     @OA\Property(property="images", type="array",
     *                         @OA\Items(type="object",
     *                             @OA\Property(property="url", type="string", example="https://example.com/image.jpg")
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="total", type="integer", example=45),
     *             @OA\Property(property="last_page", type="integer", example=3),
     *             @OA\Property(property="next_page_url", type="string", example="https://site.com/api/reviews?page=2"),
     *             @OA\Property(property="prev_page_url", type="string", example="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка запроса",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Bad Request"),
     *             @OA\Property(property="message", type="string", example="Неверный запрос")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Internal Server Error"),
     *             @OA\Property(property="message", type="string", example="Ошибка при получении отзывов")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $reviews = Review::query()
            ->with(['client', 'attributes', 'responses', 'images'])
            ->published()
            ->verified()
            ->when($request->product_id, function ($query, $productId) {
                $query->where('reviewable_type', Product::class)
                    ->where('reviewable_id', $productId);
            })
            ->latest()
            ->paginate();

        // Возвращаем JSON-ответ напрямую
        return response()->json([
            'data' => $reviews->items(),
            'current_page' => $reviews->currentPage(),
            'per_page' => $reviews->perPage(),
            'total' => $reviews->total(),
            'last_page' => $reviews->lastPage(),
            'next_page_url' => $reviews->nextPageUrl(),
            'prev_page_url' => $reviews->previousPageUrl(),
        ]);
    }


    /**
     * Создать новый отзыв
     *
     * @OA\Post(
     *     path="/api/reviews",
     *     summary="Создать новый отзыв",
     *     description="Создает новый отзыв для продукта или другого объекта. Требуется обязательная авторизация.",
     *     tags={"Reviews"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Данные для создания отзыва",
     *         @OA\JsonContent(
     *             required={"reviewable_id", "reviewable_type", "content", "rating"},
     *             @OA\Property(property="reviewable_id", type="integer", example=1, description="ID объекта для отзыва (например, продукта)"),
     *             @OA\Property(property="reviewable_type", type="string", example="Product", description="Тип объекта для отзыва (например, Product)"),
     *             @OA\Property(property="content", type="string", example="Отличный продукт!", description="Текст отзыва"),
     *             @OA\Property(property="rating", type="integer", example=5, description="Оценка от 1 до 5"),
     *             @OA\Property(property="attributes", type="array", description="Дополнительные атрибуты отзыва",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Цвет", description="Название атрибута"),
     *                     @OA\Property(property="rating", type="integer", example=4, description="Оценка атрибута")
     *                 )
     *             ),
     *             @OA\Property(property="images", type="array", description="Изображения отзыва",
     *                 @OA\Items(
     *                     type="string",
     *                     format="binary",
     *                     description="Изображение отзыва"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Отзыв успешно создан",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="content", type="string", example="Отличный продукт!"),
     *                 @OA\Property(property="rating", type="integer", example=5),
     *                 @OA\Property(property="client", type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="name", type="string", example="Иван Иванов")
     *                 ),
     *                 @OA\Property(property="attributes", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="name", type="string", example="Цвет"),
     *                         @OA\Property(property="rating", type="integer", example="4")
     *                     )
     *                 ),
     *                 @OA\Property(property="images", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="path", type="string", example="/storage/reviews/image.jpg"),
     *                         @OA\Property(property="url", type="string", example="https://example.com/storage/reviews/image.jpg")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации данных",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Bad Request"),
     *             @OA\Property(property="message", type="string", example="Некорректные данные запроса")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Неавторизованный доступ",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *             @OA\Property(property="message", type="string", example="Необходима авторизация")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Internal Server Error"),
     *             @OA\Property(property="message", type="string", example="Не удалось создать отзыв")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reviewable_id' => 'required|integer',
            'reviewable_type' => 'required|string',
            'content' => 'required|string|min:10',
            'rating' => 'required|integer|between:1,5',
            'attributes' => 'array',
            'attributes.*.name' => 'required|string',
            'attributes.*.rating' => 'required|integer|between:1,5',
            'images' => 'array',
            'images.*' => 'image|max:5120', // 5MB max
        ]);

        $review = Review::create([
           'client_id' => auth()->user()->client->id,
            'reviewable_id' => $validated['reviewable_id'],
            'reviewable_type' => $validated['reviewable_type'],
            'content' => $validated['content'],
            'rating' => $validated['rating'],
        ]);

        if (!empty($validated['attributes'])) {
            foreach ($validated['attributes'] as $attribute) {
                $review->attributes()->create($attribute);
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $review->images()->create([
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                ]);
            }
        }

        // Возвращаем новый отзыв в формате JSON
        return response()->json([
            'data' => [
                'id' => $review->id,
                'content' => $review->content,
                'rating' => $review->rating,
                'client' => $review->client ? [ // Проверяем, есть ли клиент
                    'id' => $review->client->id,
                    'name' => $review->client->name,
                ] : null, // Если клиента нет, возвращаем null
                'attributes' => $review->attributes->map(function ($attribute) {
                    return [
                        'name' => $attribute->name,
                        'rating' => $attribute->rating,
                    ];
                }),
                'images' => $review->images->map(function ($image) {
                    return [
                        'path' => $image->path,
                        'url' => $image->url,
                    ];
                }),
            ]
        ], 201);
    }

    /**
     * Получить отзывы для конкретного продукта
     *
     * @OA\Get(
     *     path="/api/reviews/product/{product}",
     *     summary="Получить отзывы для конкретного продукта",
     *     description="Возвращает отзывы для конкретного продукта. Поддерживает фильтрацию по продукту.",
     *     tags={"Reviews"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID продукта, для которого нужно получить отзывы",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список отзывов для продукта",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="content", type="string", example="Отличный продукт!"),
     *                 @OA\Property(property="rating", type="integer", example=5),
     *                 @OA\Property(property="client", type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="name", type="string", example="Иван Иванов")
     *                 ),
     *                 @OA\Property(property="attributes", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="name", type="string", example="Цвет"),
     *                         @OA\Property(property="rating", type="integer", example="4")
     *                     )
     *                 ),
     *                 @OA\Property(property="responses", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="text", type="string", example="Спасибо за ваш отзыв!")
     *                     )
     *                 ),
     *                 @OA\Property(property="images", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="path", type="string", example="/storage/reviews/image.jpg"),
     *                         @OA\Property(property="url", type="string", example="https://example.com/storage/reviews/image.jpg")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка запроса",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Bad Request"),
     *             @OA\Property(property="message", type="string", example="Неверный запрос")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Internal Server Error"),
     *             @OA\Property(property="message", type="string", example="Ошибка при получении отзывов")
     *         )
     *     )
     * )
     */
    public function productReviews(Product $product)
    {
        $reviews = Review::where('reviewable_id', $product->id)
            ->where('reviewable_type', Product::class)
            ->published()
            ->verified()
            ->get();

        return response()->json($reviews);
    }


}
