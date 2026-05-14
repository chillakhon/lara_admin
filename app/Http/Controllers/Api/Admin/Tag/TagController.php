<?php

namespace App\Http\Controllers\Api\Admin\Tag;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\Tag\TagResource;
use App\Models\Tag\Tag;
use App\Services\Tag\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    protected TagService $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    /**
     * Получить список всех тегов
     */
    public function index(): AnonymousResourceCollection
    {
        $tags = $this->tagService->getAllTags();
        return TagResource::collection($tags);
    }

    /**
     * Создать новый тег
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = $this->tagService->createTag($request->validated());

        return response()->json([
            'message' => 'Тег успешно создан',
            'data' => new TagResource($tag),
        ], 201);
    }

    /**
     * Получить конкретный тег
     */
    public function show(Tag $tag): TagResource
    {
        return new TagResource($tag);
    }

    /**
     * Обновить тег
     */
    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $updatedTag = $this->tagService->updateTag($tag, $request->validated());

        return response()->json([
            'message' => 'Тег успешно обновлён',
            'data' => new TagResource($updatedTag),
        ]);
    }

    /**
     * Удалить тег
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $this->tagService->deleteTag($tag);

        return response()->json([
            'message' => 'Тег успешно удалён',
        ]);
    }

    /**
     * Получить статистику по тегам
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->tagService->getTagsStatistics();

        return response()->json([
            'data' => TagResource::collection($statistics),
        ]);
    }
}
