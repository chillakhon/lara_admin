<?php

namespace App\Http\Controllers;

use App\Facades\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function yookassa(Request $request)
    {
        try {
            $payment = Payment::provider('yookassa')
                ->processPayment($request->all());

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Yookassa webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            return response()->json(['success' => false], 400);
        }
    }

    public function yandexPay(Request $request)
    {
        try {
            $payment = Payment::provider('yandexpay')
                ->processPayment($request->all());

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('YandexPay webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            return response()->json(['success' => false], 400);
        }
    }

    public function cloudPayment(Request $request)
    {
        try {
            $payment = Payment::provider('cloudpayment')
                ->processPayment($request->all());

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('CloudPayment webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            return response()->json(['success' => false], 400);
        }
    }

    public function robokassa(Request $request)
    {
        try {
            $payment = Payment::provider('robokassa')
                ->processPayment($request->all());

            return 'OK' . $request->input('InvId');
        } catch (\Exception $e) {
            Log::error('Robokassa webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            return 'ERROR';
        }
    }
} 