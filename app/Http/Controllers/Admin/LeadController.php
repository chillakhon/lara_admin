<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::with(['type', 'client', 'history'])
            ->latest();

        // Фильтры
        if ($request->has('type')) {
            $query->where('lead_type_id', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('client', function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhereJsonContains('data->name', $search)
                ->orWhereJsonContains('data->phone', $search)
                ->orWhereJsonContains('data->email', $search);
            });
        }

        return Inertia::render('Dashboard/Leads/Index', [
            'leads' => $query->paginate(15),
            'leadTypes' => LeadType::where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'comment' => 'nullable|string',
        ]);

        $lead->update([
            'status' => $validated['status']
        ]);

        $lead->history()->create([
            'user_id' => $request->user()->id,
            'status' => $validated['status'],
            'comment' => $validated['comment'],
        ]);

        return redirect()->back();
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();
        return redirect()->back()->with('success', 'Заявка успешно удалена');
    }

    public function createClient(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'lead_id' => 'required|exists:leads,id'
        ]);

        DB::beginTransaction();
        try {
            // Создаем пользователя только с необходимыми данными
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make(Str::random(12)),
                'type' => User::TYPE_CLIENT
            ]);

            // Создаем клиента с полным именем
            $client = Client::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
            ]);

            // Привязываем заявку к клиенту
            $lead = Lead::findOrFail($validated['lead_id']);
            $lead->update(['client_id' => $client->id]);

            // Добавляем запись в историю
            $lead->history()->create([
                'user_id' => $request->user()->id,
                'status' => $lead->status,
                'comment' => 'Создан клиент и привязан к заявке'
            ]);

            // TODO: Отправить email с данными для входа

            DB::commit();

            return redirect()->back()->with('success', 'Клиент успешно создан и привязан к заявке');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 