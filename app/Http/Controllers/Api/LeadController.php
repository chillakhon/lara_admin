<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadType;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|exists:lead_types,code',
            'data' => 'required|array'
        ]);

        try {
            DB::beginTransaction();

            $leadType = LeadType::where('code', $request->type)->firstOrFail();
            
            // Проверяем обязательные поля
            if ($leadType->required_fields) {
                foreach ($leadType->required_fields as $field) {
                    if (!isset($request->data[$field])) {
                        throw new \Exception("Field {$field} is required");
                    }
                }
            }

            // Ищем существующего клиента по телефону или email
            $client = null;
            $phone = $request->data['phone'] ?? null;
            $email = $request->data['email'] ?? null;

            if ($phone) {
                $client = Client::where('phone', $phone)->first();
            }

            if (!$client && $email) {
                $client = Client::whereHas('user', function($q) use ($email) {
                    $q->where('email', $email);
                })->first();
            }

            // Создаем заявку
            $lead = Lead::create([
                'lead_type_id' => $leadType->id,
                'client_id' => $client?->id, // Если клиент найден, привязываем его
                'status' => Lead::STATUS_NEW,
                'data' => $request->data,
                'source' => $request->header('Referer'),
                'utm_source' => $request->utm_source,
                'utm_medium' => $request->utm_medium,
                'utm_campaign' => $request->utm_campaign,
                'utm_content' => $request->utm_content,
                'utm_term' => $request->utm_term,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Записываем историю
            $lead->history()->create([
                'status' => Lead::STATUS_NEW,
                'comment' => $client 
                    ? 'Заявка создана и привязана к существующему клиенту' 
                    : 'Заявка создана'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lead created successfully',
                'lead' => $lead->load('client'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
} 