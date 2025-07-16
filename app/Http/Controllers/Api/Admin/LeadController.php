<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadType;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

            $validator = Validator::make($request->data, $this->getValidationRules($leadType));

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $normalizedData = $this->normalizeData($request->data);

            $client = $this->findOrInitializeClient($normalizedData);

            $lead = Lead::create([
                'lead_type_id' => $leadType->id,
                'client_id' => $client?->id,
                'status' => Lead::STATUS_NEW,
                'data' => $normalizedData,
                'source' => $request->header('Referer'),
                'utm_source' => $request->utm_source,
                'utm_medium' => $request->utm_medium,
                'utm_campaign' => $request->utm_campaign,
                'utm_content' => $request->utm_content,
                'utm_term' => $request->utm_term,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $this->createLeadHistory($lead, $client);

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

    private function getValidationRules(LeadType $leadType): array
    {
        $rules = [];

        foreach ($leadType->required_fields as $field) {
            switch ($field) {
                case 'phone':
                    $rules['phone'] = 'required|string|regex:/^[0-9\+\-\(\)\s]{10,}$/';
                    break;
                case 'email':
                    $rules['email'] = 'required|email';
                    break;
                case 'name':
                    $rules['name'] = 'required|string|min:2|max:100';
                    break;
                case 'message':
                    $rules['message'] = 'required|string|max:1000';
                    break;
                default:
                    $rules[$field] = 'required';
            }
        }

        return $rules;
    }

    private function normalizeData(array $data): array
    {
        $normalized = [];

        if (isset($data['phone'])) {
            $normalized['phone'] = preg_replace('/[^0-9+]/', '', $data['phone']);
        }

        if (isset($data['email'])) {
            $normalized['email'] = strtolower(trim($data['email']));
        }

        if (isset($data['name'])) {
            $normalized['name'] = ucwords(trim($data['name']));
        }

        foreach ($data as $key => $value) {
            if (!isset($normalized[$key])) {
                $normalized[$key] = is_string($value) ? trim($value) : $value;
            }
        }

        return $normalized;
    }

    private function findOrInitializeClient(array $data)
    {
        if (!empty($data['phone'])) {
            $client = Client::where('phone', $data['phone'])->whereNull('deleted_at')->first();
            if ($client) return $client;
        }

        if (!empty($data['email'])) {
            $client = Client::whereHas('user', function($q) use ($data) {
                $q->where('email', $data['email']);
            })->whereNull('deleted_at')->first();
            if ($client) return $client;
        }

        return null;
    }

    private function createLeadHistory(Lead $lead, ?Client $client)
    {
        $lead->history()->create([
            'status' => Lead::STATUS_NEW,
            'comment' => $client
                ? 'Заявка создана и привязана к существующему клиенту'
                : 'Заявка создана'
        ]);
    }
}
