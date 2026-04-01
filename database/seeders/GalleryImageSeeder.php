<?php

namespace Database\Seeders;

use App\Models\GalleryImage;
use App\Services\ImageProcessingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GalleryImageSeeder extends Seeder
{
    public function run(): void
    {
        $galleryDirectory = storage_path('app/public/gallary');

        if (! File::isDirectory($galleryDirectory)) {
            return;
        }

        $allowedImageExt = ['jpg', 'jpeg', 'png', 'webp'];

        // Collect image files, delete anything else (heic, txt, etc.)
        $allFiles = File::files($galleryDirectory);
        $imageFiles = [];

        foreach ($allFiles as $file) {
            if (in_array(strtolower($file->getExtension()), $allowedImageExt)) {
                $imageFiles[] = $file;
            } else {
                File::delete($file->getPathname());
            }
        }

        usort($imageFiles, fn ($a, $b) => strcmp($a->getFilename(), $b->getFilename()));

        if (empty($imageFiles)) {
            return;
        }

        $processor = new ImageProcessingService(1200, 80);
        $i = 1;

        foreach ($imageFiles as $file) {
            $filename  = $file->getFilename();
            $extension = strtolower($file->getExtension());
            $newName   = "img_{$i}.webp";
            $newPath   = $galleryDirectory . DIRECTORY_SEPARATOR . $newName;

            if ($extension === 'webp' && $file->getSize() < 512_000) {
                // Already optimised WebP — just rename to img_n.webp
                if ($file->getPathname() !== $newPath) {
                    // Use a temp name first to avoid collisions during renaming
                    $tmpPath = $galleryDirectory . DIRECTORY_SEPARATOR . "_tmp_{$i}.webp";
                    File::move($file->getPathname(), $tmpPath);
                    File::move($tmpPath, $newPath);
                }
            } else {
                // Convert to optimised WebP with clean name
                $result = $processor->processFile($file->getPathname(), $newPath);

                if (! $result) {
                    Log::warning("GalleryImageSeeder | FAILED | {$filename}");
                    continue;
                }

                // Delete original if it was a different file
                if ($file->getPathname() !== $newPath && File::exists($file->getPathname())) {
                    File::delete($file->getPathname());
                }
            }

            $diskPath = 'gallary/' . $newName;

            GalleryImage::query()->updateOrCreate(
                ['disk_path' => $diskPath],
                ['filename' => $newName],
            );

            $i++;
        }

        // Clean up the /optimized subfolder if it exists (no longer needed)
        $optimizedDir = $galleryDirectory . DIRECTORY_SEPARATOR . 'optimized';
        if (File::isDirectory($optimizedDir)) {
            File::deleteDirectory($optimizedDir);
        }

        Log::info('GalleryImageSeeder | Renamed ' . ($i - 1) . ' images to img_n.webp');
    }
}
