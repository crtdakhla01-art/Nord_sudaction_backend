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

        // --- Pass 1: move ALL files to a temp subfolder to avoid rename collisions ---
        $tmpDir = $galleryDirectory . DIRECTORY_SEPARATOR . '_seed_tmp';
        File::ensureDirectoryExists($tmpDir);

        foreach ($imageFiles as $file) {
            File::move($file->getPathname(), $tmpDir . DIRECTORY_SEPARATOR . $file->getFilename());
        }

        // Re-collect from the temp folder (same sort order)
        $tmpFiles = collect(File::files($tmpDir))
            ->sortBy(fn ($f) => $f->getFilename())
            ->values();

        // --- Pass 2: process each file and write the final img_N.webp into gallary/ ---
        $i = 1;

        foreach ($tmpFiles as $file) {
            $filename  = $file->getFilename();
            $extension = strtolower($file->getExtension());
            $newName   = "img_{$i}.webp";
            $newPath   = $galleryDirectory . DIRECTORY_SEPARATOR . $newName;

            if ($extension === 'webp' && $file->getSize() < 512_000) {
                // Already optimised WebP — just move to final name
                File::move($file->getPathname(), $newPath);
            } else {
                // Convert to optimised WebP with clean name
                $result = $processor->processFile($file->getPathname(), $newPath);

                if (! $result) {
                    Log::warning("GalleryImageSeeder | FAILED | {$filename}");
                    continue;
                }

                File::delete($file->getPathname());
            }

            $diskPath = 'gallary/' . $newName;

            GalleryImage::query()->updateOrCreate(
                ['disk_path' => $diskPath],
                ['filename' => $newName],
            );

            $i++;
        }

        // Remove the temp folder
        File::deleteDirectory($tmpDir);

        // Clean up the /optimized subfolder if it exists (no longer needed)
        $optimizedDir = $galleryDirectory . DIRECTORY_SEPARATOR . 'optimized';
        if (File::isDirectory($optimizedDir)) {
            File::deleteDirectory($optimizedDir);
        }

        Log::info('GalleryImageSeeder | Renamed ' . ($i - 1) . ' images to img_n.webp');
    }
}
