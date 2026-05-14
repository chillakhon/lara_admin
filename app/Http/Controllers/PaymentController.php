<?php

namespace App\Http\Controllers;

use App\Facades\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function checkout(Order $order, Request $request)
    {
        // Проверяем, можно ли оплатить заказ
        if (!$order->canBePaid()) {
            return redirect()->back()->with('error', 'Заказ не может быть оплачен');
        }

        try {
            $provider = $request->get('provider', config('payment.default'));
            $result = Payment::initializePayment($order, $provider);

            // Для разных провайдеров может быть разная логика
            if (isset($result['confirmation_url'])) {
                return redirect($result['confirmation_url']);
            }

            if (isset($result['payment_token'])) {
                return Inertia::render('Payment/YandexPay', [
                    'paymentToken' => $result['payment_token'],
                    'orderId' => $order->id
                ]);
            }

            return Inertia::render('Payment/Form', [
                'paymentData' => $result,
                'order' => $order
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function success(Request $request)
    {
        return Inertia::render('Payment/Success', [
            'orderId' => $request->get('order')
        ]);
    }

    public function cancel(Request $request)
    {
        return Inertia::render('Payment/Cancel', [
            'orderId' => $request->get('order')
        ]);
    }
} 