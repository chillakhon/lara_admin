<?php

namespace App\Services\Review;

use App\Models\Review\Review;
use App\Models\Review\ReviewLike;
use Illuminate\Support\Facades\DB;

class ReviewLikeService
{
    /**
     * Поставить лайк отзыву
     *
     * @param Review $review
     * @param int $clientId
     * @return array
     */
    public function likeReview(Review $review, int $clientId): array
    {
        try {
            DB::beginTransaction();

            // Проверяем, не лайкнул ли уже клиент этот отзыв
            $existingLike = ReviewLike::where('review_id', $review->id)
                ->where('client_id', $clientId)
                ->first();

            if ($existingLike) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Вы уже поставили лайк этому отзыву',
                ];
            }

            // Создаём лайк
            ReviewLike::create([
                'review_id' => $review->id,
                'client_id' => $clientId,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Лайк успешно добавлен',
                'likes_count' => $review->likesCount(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Ошибка при добавлении лайка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Убрать лайк с отзыва
     *
     * @param Review $review
     * @param int $clientId
     * @return array
     */
    public function unlikeReview(Review $review, int $clientId): array
    {
        try {
            DB::beginTransaction();

            $like = ReviewLike::where('review_id', $review->id)
                ->where('client_id', $clientId)
                ->first();

            if (!$like) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Вы не ставили лайк этому отзыву',
                ];
            }

            $like->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Лайк успешно убран',
                'likes_count' => $review->likesCount(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Ошибка при удалении лайка: ' . $e->getMessage(),
            ];
        }
    }
}
