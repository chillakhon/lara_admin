<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use App\Models\Role;
use App\Traits\ReviewTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;


class ReviewController extends Controller
{
    use ReviewTrait;
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
     *                     @OA\Property(property="id", type="integer", example=14),
     *                     @OA\Property(property="content", type="string", example="Отличный продукт!"),
     *                     @OA\Property(property="rating", type="integer", example=5),
     *                     @OA\Property(property="is_verified", type="boolean", example=true),
     *                     @OA\Property(property="is_published", type="boolean", example=true),
     *                     @OA\Property(property="published_at", type="string", format="date-time", example="09.04.2025 12:00", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="09.04.2025 11:22"),
     *                     @OA\Property(
     *                         property="status",
     *                         type="string",
     *                         enum={"new", "published"},
     *                         example="published",
     *                         description="Статус отзыва: 'new' (новый) или 'published' (опубликован)"
     *                     ),
     *                     @OA\Property(
     *                         property="client",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=11),
     *                         @OA\Property(property="name", type="string", example="Super Admin"),
     *                         @OA\Property(property="email", type="string", example="superadmin@example.com"),
     *                         @OA\Property(property="avatar", type="string", example=null, nullable=true)
     *                     ),
     *                     @OA\Property(
     *                         property="reviewable",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="type", type="string", example="Product"),
     *                         @OA\Property(property="name", type="string", example="Product Name")
     *                     ),
     *                     @OA\Property(
     *                         property="attributes",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=23),
     *                             @OA\Property(property="name", type="string", example="Качество"),
     *                             @OA\Property(property="rating", type="integer", example=5)
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="responses",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="content", type="string", example="Спасибо за отзыв!"),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example="09.04.2025 12:30")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="images",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="url", type="string", example="http://127.0.0.1:8000/storage/reviews/image1.jpg"),
     *                             @OA\Property(property="thumbnail", type="string", example="http://127.0.0.1:8000/storage/reviews/thumb1.jpg")
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="total", type="integer", example=45),
     *             @OA\Property(property="last_page", type="integer", example=3),
     *             @OA\Property(property="next_page_url", type="string", example="https://site.com/api/reviews?page=2", nullable=true),
     *             @OA\Property(property="prev_page_url", type="string", example=null, nullable=true)
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
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function index(Request $request)
    {
        $reviewableMorphMap = $this->reviewable_morph_review_map($request);
        $admin_role = $request->user()->hasAnyRole(Role::$admin_roles);

        $reviews = Review::query()
            ->with([
                // 'client',
                'attributes',
                'responses' => function ($query) use ($admin_role) {
                    if (!$admin_role) {
                        $query->whereNull('deleted_at');
                    }
                },
                'reviewable' => function ($morphTo) use ($reviewableMorphMap) {
                    $morphTo->morphWith($reviewableMorphMap);
                },
                'images',
            ]);

        $reviews = $this->filter_reviews($request, $reviews, $admin_role);

        $reviews = $reviews->latest()->paginate($request->get('per_page', 10));

        $reviews->getCollection()->transform(function ($review) {
            $review->client_name = optional($review->client?->user?->profile)->full_name;
            $review->client_email = optional($review->client?->user)->email;
            return $review;
        });

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
     * @OA\Post(
     *     path="/api/reviews",
     *     summary="Создать отзыв",
     *     description="Создает отзыв клиента на продукт или услугу",
     *     operationId="storeReview",
     *     tags={"Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reviewable_id", "reviewable_type", "content", "rating"},
     *             @OA\Property(property="reviewable_id", type="integer", example=123),
     *             @OA\Property(property="reviewable_type", type="string", example="App\\Models\\Product"),
     *             @OA\Property(property="content", type="string", example="Очень хороший товар!"),
     *             @OA\Property(property="rating", type="integer", example=5),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Качество"),
     *                     @OA\Property(property="rating", type="integer", example=4)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Успешно создано",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="content", type="string", example="Отличное качество!"),
     *                 @OA\Property(property="rating", type="integer", example=5),
     *                 @OA\Property(property="status", type="string", example="new"),
     *                 @OA\Property(property="client", type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="name", type="string", example="Иван")
     *                 ),
     *                 @OA\Property(
     *                     property="attributes",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="Упаковка"),
     *                         @OA\Property(property="rating", type="integer", example=4)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="path", type="string", example="reviews/image.jpg"),
     *                         @OA\Property(property="url", type="string", example="https://example.com/storage/reviews/image.jpg")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации"),
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
                'status' => $review->status,
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

    /**
     * @OA\Post(
     *     path="/api/reviews/{review}/publish",
     *     summary="Публикация отзыва",
     *     description="Помечает отзыв как опубликованный, проверенный и устанавливает дату публикации",
     *     operationId="publishReview",
     *     tags={"Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         required=true,
     *         description="ID отзыва",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Отзыв успешно опубликован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review published successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Review")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Отзыв не найден"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован"
     *     )
     * )
     */
    public function publish(Review $review, Request $request)
    {
        $review->update([
            'is_published' => true,
            'is_verified' => true,
            'published_at' => $review->published_at ?? now(),
            'status' => Review::STATUS_PUBLISHED,
        ]);

        return response()->json([
            'message' => 'Review published successfully',
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/reviews/{review}/unpublish",
     *     summary="Снять публикацию отзыва",
     *     description="Ставит is_published и is_verified в false, сбрасывает published_at, меняет статус на 'new'",
     *     operationId="unpublishReview",
     *     tags={"Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         required=true,
     *         description="ID отзыва",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Отзыв успешно снят с публикации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review unpublished successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Review")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Отзыв не найден"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован"
     *     )
     * )
     */

    public function unpublish(Request $request, Review $review)
    {
        $review->update([
            'is_published' => false,
            'is_verified' => false,
            'published_at' => null,
            'status' => Review::STATUS_NEW,
        ]);

        return response()->json([
            'message' => 'Review unpublished successfully',
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/reviews/{review}",
     *     summary="Удаление отзыва",
     *     description="Удаляет отзыв по ID (мягкое удаление, если используется SoftDeletes)",
     *     operationId="deleteReview",
     *     tags={"Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         required=true,
     *         description="ID отзыва",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Отзыв успешно удалён (без тела ответа)"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Отзыв не найден"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован"
     *     )
     * )
     */
    public function destroy(Request $request, Review $review)
    {
        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully',
        ], 204);
    }

}
