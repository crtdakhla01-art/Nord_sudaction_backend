<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;

class ImageProcessingService
{
    protected ImageManager $manager;
    protected int $maxWidth;
    protected int $quality;

    public function __construct(int $maxWidth = 1200, int $quality = 80)
    {
        $this->manager  = ImageManager::gd();
        $this->maxWidth = $maxWidth;
        $this->quality  = $quality;
    }

    /**
     * Process an image file on disk: resize, convert to WebP, save in place.
     * Returns the new file path, or null on failure.
     */
    public function processFile(string $sourcePath, ?string $outputPath = null): ?string
    {
        if (! File::exists($sourcePath)) {
            return null;
        }

        $basename   = pathinfo($sourcePath, PATHINFO_FILENAME);
        $outputPath = $outputPath ?? dirname($sourcePath) . DIRECTORY_SEPARATOR . $basename . '.webp';

        try {
            $originalSize = File::size($sourcePath);

            $image = $this->manager->read($sourcePath);

            if ($image->width() > $this->maxWidth) {
                $image->scaleDown(width: $this->maxWidth);
            }

            File::ensureDirectoryExists(dirname($outputPath));

            $image->toWebp($this->quality)->save($outputPath);

            $newSize   = File::size($outputPath);
            $reduction = $originalSize > 0
                ? round((($originalSize - $newSize) / $originalSize) * 100, 1)
                : 0;

            $origKB = round($originalSize / 1024, 1);
            $newKB  = round($newSize / 1024, 1);

            Log::info("ImageProcessing | {$basename} | {$origKB}KB -> {$newKB}KB | -{$reduction}%");

            return $outputPath;
        } catch (\Throwable $e) {
            Log::warning("ImageProcessing | FAILED | {$basename} | {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Process an UploadedFile: convert to WebP, save to the gallery storage folder.
     * Returns ['disk_path' => ..., 'filename' => ...] or null on failure.
     */
    public function processUpload(UploadedFile $file, string $storageDiskPath = 'gallary'): ?array
    {
        $webpName   = 'img_' . time() . '.webp';
        $outputDir  = storage_path('app/public/' . $storageDiskPath);
        $outputPath = $outputDir . DIRECTORY_SEPARATOR . $webpName;

        // Ensure unique name if a same-second upload exists
        $counter = 1;
        while (File::exists($outputPath)) {
            $webpName   = 'img_' . time() . '_' . $counter . '.webp';
            $outputPath = $outputDir . DIRECTORY_SEPARATOR . $webpName;
            $counter++;
        }

        File::ensureDirectoryExists($outputDir);

        $result = $this->processFile($file->getRealPath(), $outputPath);

        if (! $result) {
            return null;
        }

        return [
            'filename'  => $webpName,
            'disk_path' => $storageDiskPath . '/' . $webpName,
        ];
    }
}
