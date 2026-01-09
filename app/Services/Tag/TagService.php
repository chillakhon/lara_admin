<?php

namespace App\Services\Tag;

use App\Models\Client;
use App\Models\Tag\Tag;
use Illuminate\Database\Eloquent\Collection;

class TagService
{
    /**
     * Получить все теги
     */
    public function getAllTags(): Collection
    {
        return Tag::orderBy('name')->get();
    }

    /**
     * Создать новый тег
     */
    public function createTag(array $data): Tag
    {
        return Tag::create([
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
        ]);
    }

    /**
     * Обновить тег
     */
    public function updateTag(Tag $tag, array $data): Tag
    {
        $tag->update($data);
        return $tag->fresh();
    }

    /**
     * Удалить тег
     */
    public function deleteTag(Tag $tag): bool
    {
        return $tag->delete();
    }

    /**
     * Прикрепить теги к клиенту (заменить все существующие)
     */
    public function syncTagsToClient(Client $client, array $tagIds): void
    {
        $client->tags()->sync($tagIds);
    }

    /**
     * Добавить один тег к клиенту (не удаляя существующие)
     */
    public function attachTagToClient(Client $client, int $tagId): void
    {
        $client->tags()->syncWithoutDetaching([$tagId]);
    }

    /**
     * Удалить тег у клиента
     */
    public function detachTagFromClient(Client $client, int $tagId): void
    {
        $client->tags()->detach($tagId);
    }

    /**
     * Получить все теги клиента
     */
    public function getClientTags(Client $client): Collection
    {
        return $client->tags;
    }

    /**
     * Получить статистику использования тегов
     */
    public function getTagsStatistics(): Collection
    {
        return Tag::withCount('clients')
            ->orderByDesc('clients_count')
            ->get();
    }
}
