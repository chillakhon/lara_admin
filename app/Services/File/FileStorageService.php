<?php

namespace App\Services\File;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileStorageService
{
    /**
     * Сохранить массив файлов и вернуть данные для БД
     *
     * @param array $files Массив UploadedFile
     * @return array Массив с данными для сохранения
     */
    public function storeAttachments(array $files): array
    {
        $attachmentsData = [];

        foreach ($files as $file) {
            // Генерируем уникальное имя файла (hash)
            $fileName = md5(time() . uniqid()) . '.' . $file->extension();

            // Путь: chat-attachments/2024/12/
            $directory = 'chat-attachments/' . now()->format('Y/m');

            // Полный путь: chat-attachments/2024/12/a3c4f5e6d7b8a9c0.jpg
            $filePath = $directory . '/' . $fileName;

            // Сохраняем файл
            Storage::disk('public')->putFileAs($directory, $file, $fileName);

            // Формируем данные для БД
            $attachmentsData[] = [
                'type' => $this->getAttachmentType($file),
                'url' => url('storage/' . $filePath), // Полный URL для фронтенда
                'file_path' => $filePath,             // Относительный путь для хранения
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),

                // Дополнительно для адаптеров (не сохраняется в БД)
                'full_path' => storage_path('app/public/' . $filePath)
            ];
        }

        return $attachmentsData;
    }

    /**
     * Удалить файл по пути
     *
     * @param string $filePath
     * @return bool
     */
    public function delete(string $filePath): bool
    {
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->delete($filePath);
        }

        return false;
    }

    /**
     * Определить тип файла по расширению
     *
     * @param UploadedFile $file
     * @return string
     */
    private function getAttachmentType(UploadedFile $file): string
    {
        $extension = strtolower($file->extension());

        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return 'image';
        }

        if (in_array($extension, ['mp3', 'wav', 'ogg', 'm4a'])) {
            return 'audio';
        }

        return 'file';
    }
}
