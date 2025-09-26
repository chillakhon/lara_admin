<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewAttribute;
use App\Models\Role;
use App\Traits\HelperTrait;
use App\Traits\ReviewTrait;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\isInstanceOf;


class ReviewController extends Controller
{
    use ReviewTrait, HelperTrait;


    // in this whole controller the image field is not using.
    // you will not get that back in response

    public function index(Request $request)
    {

        // it's not in middleware that is why better to send parameter
        $admin_role = $request->boolean('admin', false);


        if (!$admin_role && !$request->get('product_id')) {
            return response()->json([
                'success' => false,
                'message' => "Пожалуйста, укажите ID товара"
            ]);
        }

        $reviewableMorphMap = $this->reviewable_morph_review_map($request);

        $reviews = Review::query()
            ->with([
                // 'client',
                'attributes',
                'responses' => function ($query) use ($admin_role) {
                    $query->with('user')->orderBy('created_at', 'asc');
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
            $review->reviewable_type = $this->get_type_by_model($review->reviewable_type);
            return $review;
        });

        // Возвращаем JSON-ответ напрямую
        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews->items()),
            'current_page' => $reviews->currentPage(),
            'per_page' => $reviews->perPage(),
            'total' => $reviews->total(),
            'last_page' => $reviews->lastPage(),
            'next_page_url' => $reviews->nextPageUrl(),
            'prev_page_url' => $reviews->previousPageUrl(),
        ]);
    }


    public function getMainPageReviews(Request $request)
    {
        // Загружаем последние 10 отзывов с нужными связями
        $reviews = Review::with([
            'attributes',
            'responses' => function ($query) {
                $query->with('user')
                    ->orderBy('created_at', 'asc')
                    ->whereNull('deleted_at');
            },
            'reviewable',
            'images',
            'client.profile',
        ])
            ->orderByDesc('created_at') // ⬅️ показываем самые новые
            ->take(10) // ⬅️ максимум 10
            ->get();

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews),
        ]);
    }



    public function attributes(Request $request)
    {
        return response()->json([
            'success' => true,
            'attributes' => ReviewAttribute::get(),
        ]);
    }

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
            // 'images' => 'array',
            // 'images.*' => 'image|max:5120', // 5MB max
        ]);

        $get_type_by_model = $this->get_model_by_type($request->get('reviewable_type'));

        $review = Review::create([
            'client_id' => $request->user()->id,
            'reviewable_id' => $validated['reviewable_id'],
            'reviewable_type' => $get_type_by_model,
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

    public function productReviews(Product $product)
    {
        $reviews = Review::where('reviewable_id', $product->id)
            ->where('reviewable_type', Product::class)
            ->published()
            ->verified()
            ->get();

        return response()->json($reviews);
    }

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

    public function destroy(Request $request, Review $review)
    {
        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully',
        ], 204);
    }


    public function respond(Request $request, Review $review)
    {
        $validated = $request->validate([
            'content' => 'required|string|min:1',
            'is_published' => 'nullable|boolean',
            'review_response_id' => 'nullable|exists:review_responses,id',
        ]);

        DB::beginTransaction();

        try {
            $authenticatedUser = $request->user();

            $user = null;
            $client = null;

            if ($authenticatedUser instanceof \App\Models\User) {
                $user = $authenticatedUser;
            } else if ($authenticatedUser instanceof \App\Models\Client) {
                $client = $authenticatedUser;
            }

            if (is_null($client) && is_null($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не аутентифицирован или не распознан.',
                ], 401);
            }

            $alreadyExists = $review->responses()->where('content', $validated['content']);

            if ($client) {
                $alreadyExists->where('client_id', $client->id);
            } else if ($user) {
                $alreadyExists->where('user_id', $user->id);
            }

            $alreadyExists = $alreadyExists->exists();

            if ($alreadyExists) {
                return response()->json([
                    'message' => 'Такой ответ уже был добавлен ранее.',
                    'duplicate' => true,
                ], 409); // HTTP 409 Conflict
            }

            $response = $review->responses()->create([
                'user_id' => $user?->id,
                'client_id' => $client?->id,
                'content' => $validated['content'],
                'review_response_id' => $validated['review_response_id'],
                'is_published' => $validated['is_published'] ?? true,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Ответ добавлен',
                'data' => $response,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                "line" => $e->getLine(),
                "stack_trace" => $e->getTraceAsString()
            ]);
        }
    }
}
