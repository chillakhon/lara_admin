<?php

namespace App\Http\Controllers\Api\Public\GiftCard;

use App\Http\Controllers\Controller;
use App\Http\Requests\GiftCard\ValidateGiftCardRequest;
use App\Services\GiftCard\GiftCardService;
use Illuminate\Http\JsonResponse;

class GiftCardPublicController extends Controller
{
    public function __construct(
        protected GiftCardService $giftCardService
    ) {}

    /**
     * Валидация подарочной карты (проверка баланса)
     */
    public function validate(ValidateGiftCardRequest $request): JsonResponse
    {
        $code = $request->input('code');

        $result = $this->giftCardService->validate($code);

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'balance' => $result['balance'],
                'code' => $result['gift_card']->code,
            ],
        ]);
    }

    /**
     * Проверить баланс карты (без авторизации)
     */
    public function checkBalance(ValidateGiftCardRequest $request): JsonResponse
    {
        return $this->validate($request);
    }
}
