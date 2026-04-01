<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;

class OptimizeGalleryImages extends Command
{
    protected $signature = 'gallery:optimize
                            {--max-width=1200 : Maximum width in pixels}
                            {--quality=80 : WebP quality (1-100)}
                            {--force : Re-optimize already processed images}';

    protected $description = 'Convert gallery images to optimized WebP thumbnails (resize + compress)';

    public function handle(): int
    {
        $maxWidth = (int) $this->option('max-width');
        $quality  = (int) $this->option('quality');
        $force    = (bool) $this->option('force');

        $sourceDir    = storage_path('app/public/gallary');
        $optimizedDir = storage_path('app/public/gallary/optimized');

        if (! File::isDirectory($sourceDir)) {
            $this->error("Source directory not found: {$sourceDir}");
            return self::FAILURE;
        }

        File::ensureDirectoryExists($optimizedDir);

        $manager = ImageManager::gd();

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $files = collect(File::files($sourceDir))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), $allowedExtensions));

        $total     = $files->count();
        $processed = 0;
        $skipped   = 0;
        $totalSaved = 0;

        $this->info("Found {$total} images to optimize (max-width: {$maxWidth}px, quality: {$quality}%)");
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($files as $file) {
            $basename    = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $outputName  = $basename . '.webp';
            $outputPath  = $optimizedDir . DIRECTORY_SEPARATOR . $outputName;

            if (! $force && File::exists($outputPath)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            try {
                $originalSize = $file->getSize();

                $image = $manager->read($file->getPathname());

                // Only downscale, never upscale
                if ($image->width() > $maxWidth) {
                    $image->scaleDown(width: $maxWidth);
                }

                $image->toWebp($quality)->save($outputPath);

                $optimizedSize = File::size($outputPath);
                $reduction     = $originalSize - $optimizedSize;
                $percent       = $originalSize > 0
                    ? round(($reduction / $originalSize) * 100, 1)
                    : 0;

                $totalSaved += $reduction;

                $origMB = round($originalSize / 1048576, 2);
                $optMB  = round($optimizedSize / 1048576, 2);

                Log::info("gallery:optimize | {$file->getFilename()} | {$origMB}MB -> {$optMB}MB | -{$percent}%");

                $processed++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("  Failed: {$file->getFilename()} — {$e->getMessage()}");
                Log::warning("gallery:optimize | FAILED | {$file->getFilename()} | {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $savedMB = round($totalSaved / 1048576, 1);
        $this->info("Done! Processed: {$processed} | Skipped: {$skipped} | Total saved: {$savedMB} MB");

        return self::SUCCESS;
    }
}
