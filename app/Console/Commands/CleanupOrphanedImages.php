<?php

namespace App\Console\Commands;

use App\Models\Image;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedImages extends Command
{
    protected $signature = 'app:cleanup-orphaned-images {--dry-run : Run in dry-run mode without deleting}';

    protected $description = 'Clean up orphaned images not associated with any product or variant';

    public function handle()
    {
        $this->info('Starting cleanup of orphaned images...');

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        $orphanedImages = Image::whereDoesntHave('products', function ($query) {
            $query->whereNotNull('imagables.product_variant_id');
        })->get();

        $count = 0;
        foreach ($orphanedImages as $image) {
            $this->info("Processing image ID: {$image->id}, Path: {$image->path}");

            if (!$dryRun) {
                // Удаляем файл изображения
                if (Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                    $this->info("Deleted file: {$image->path}");
                } else {
                    $this->warn("File not found: {$image->path}");
                }

                // Удаляем связи в промежуточной таблице
                DB::table('imagables')->where('image_id', $image->id)->delete();
                $this->info("Deleted imagable relations for image ID: {$image->id}");

                // Удаляем запись из базы данных
                $image->delete();
                $this->info("Deleted image record from database, ID: {$image->id}");
            }

            $count++;
        }

        $actionText = $dryRun ? "Found" : "Removed";
        $this->info("Cleanup completed. {$actionText} {$count} orphaned images.");
    }
}
