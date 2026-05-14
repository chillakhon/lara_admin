<?php

namespace App\Http\Controllers\Api\Admin\Tag;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\AttachTagsRequest;
use App\Http\Resources\Tag\TagResource;
use App\Models\Client;
use App\Services\Tag\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientTagController extends Controller
{
    protected TagService $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    /**
     * Получить все теги клиента
     */
    public function index(Client $client): AnonymousResourceCollection
    {
        $tags = $this->tagService->getClientTags($client);
        return TagResource::collection($tags);
    }

    /**
     * Заменить все теги клиента
     */
    public function sync(AttachTagsRequest $request, Client $client): JsonResponse
    {
        $this->tagService->syncTagsToClient(
            $client,
            $request->validated()['tag_ids']
        );

        return response()->json([
            'message' => 'Теги успешно обновлены',
            'data' => TagResource::collection($client->fresh()->tags),
        ]);
    }

    /**
     * Добавить тег к клиенту (не удаляя существующие)
     */
    public function attach(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'tag_id' => 'required|integer|exists:tags,id',
        ]);

        $this->tagService->attachTagToClient(
            $client,
            $validated['tag_id']
        );

        return response()->json([
            'message' => 'Тег успешно добавлен',
            'data' => TagResource::collection($client->fresh()->tags),
        ]);
    }

    /**
     * Удалить тег у клиента
     */
    public function detach(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'tag_id' => 'required|integer|exists:tags,id',
        ]);

        $this->tagService->detachTagFromClient(
            $client,
            $validated['tag_id']
        );

        return response()->json([
            'message' => 'Тег успешно удалён',
            'data' => TagResource::collection($client->fresh()->tags),
        ]);
    }
}
