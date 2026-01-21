<?php

namespace App\Http\Controllers\Api\Review;

use App\Http\Controllers\Controller;
use App\Models\Review\Review;
use App\Services\Review\ReviewLikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewLikeController extends Controller
{
    protected ReviewLikeService $likeService;

    public function __construct(ReviewLikeService $likeService)
    {
        $this->likeService = $likeService;
    }

    /**
     * Поставить лайк отзыву
     */
    public function like(Request $request, Review $review): JsonResponse
    {
        $client = $request->user();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Необходима авторизация',
            ], 401);
        }

        $result = $this->likeService->likeReview($review, $client->id);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Убрать лайк с отзыва
     */
    public function unlike(Request $request, Review $review): JsonResponse
    {
        $client = $request->user();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Необходима авторизация',
            ], 401);
        }

        $result = $this->likeService->unlikeReview($review, $client->id);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }
}
