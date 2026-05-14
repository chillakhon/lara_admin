<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\OtoBanner\AttachManagerRequest;
use App\Http\Requests\StoreContactRequest;
use App\Http\Resources\OtoBanner\OtoBannerSubmissionResource;
use App\Models\Client;
use App\Models\ContactRequest;
use App\Models\UserProfile;
use App\Notifications\NewContactRequestNotification;
use App\Services\ContactRequest\ContactRequestService;
use App\Services\TelegramNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ContactRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactRequest::query()
            ->with('client.profile', 'manager');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%")
                    ->orWhere('phone', 'like', "%$s%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $list = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' =>  $list->items(),
            'meta' => PaginationHelper::format($list)
        ]);
    }


    public function store(StoreContactRequest $request, ContactRequestService $service)
    {
        $data = $request->validated();
        $data['ip'] = $request->ip();
        $data['user_agent'] = $request->userAgent();

        $contactRequest = ContactRequest::create($data);

        $service->handleNewContactRequest($contactRequest);


        if (!empty($data['client_id'])) {
            $this->sendNotificationsToTelegram($contactRequest, $data['client_id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Заявка успешно создана',
            'data' => $contactRequest->only('id', 'name', 'email', 'phone', 'message', 'client_id')
        ], 201);
    }


    private function sendNotificationsToTelegram(ContactRequest $contactRequest, $clientId)
    {
        $profile = UserProfile::where('client_id', $clientId)->first();

        if ($profile && $profile->phone) {
            $telegramService = new TelegramNotificationService();
            $telegramService->sendContactRequestNotificationToClient($contactRequest, $profile);
        }
    }


    public function show(ContactRequest $contact_request)
    {
        return response()->json($contact_request);
    }

    public function update(Request $request, ContactRequest $contact_request)
    {

        $contact_request->update($request->only(['status']));

        if ($request->status === 'viewed') {
            $contact_request->update(['read_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Статус заявки обновлён',
            'data' => $contact_request->only(['id', 'status', 'read_at'])
        ], 200);
    }

    public function destroy(ContactRequest $contact_request)
    {
        $contact_request->delete();
        return response()->json(null, 204);
    }

    public function count()
    {
        $count = ContactRequest::where('status', 'new')->count();
        return response()->json(['new' => $count]);
    }


    public function attachManager(Request $request, ContactRequest $contact_request): JsonResponse
    {
        // Валидация
        $request->validate([
            'manager_id' => 'nullable|exists:users,id'
        ]);

        $manager_id = $request->input('manager_id', null);

        if ($manager_id) {
            $contact_request->update(['manager_id' => $manager_id]);
        } else {
            $contact_request->update(['manager_id' => null]);
        }

        // Загружаем связь manager, если она существует
        $contact_request->load(['manager.profile', 'client.profile']);

        return response()->json([
            'success' => true,
            'message' => $manager_id
                ? 'Менеджер успешно прикреплён к заявке'
                : 'Менеджер откреплён от заявки',
            'data' => $contact_request,
        ]);
    }


}
