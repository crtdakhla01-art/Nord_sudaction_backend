<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Event::query()->with('gallery')->latest('date')->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $data = $request->validated();
        $galleryItems = $data['gallery'] ?? [];
        $storedPaths = [];

        unset($data['gallery']);

        try {
            $event = DB::transaction(function () use ($data, $galleryItems, &$storedPaths) {
                $createdEvent = Event::query()->create($data);
                $galleryPayload = [];

                foreach ($galleryItems as $index => $item) {
                    $galleryEntry = [
                        'image' => $this->storeGalleryUpload(
                            $item['image'] ?? null,
                            'image',
                            'event_images',
                            $storedPaths,
                            $index,
                            'image'
                        ),
                        'vedio' => $this->storeGalleryUpload(
                            $item['vedio'] ?? null,
                            'video',
                            'event_videos',
                            $storedPaths,
                            $index,
                            'vedio'
                        ),
                        'link' => $item['link'] ?? null,
                    ];

                    if (!empty($galleryEntry['image']) || !empty($galleryEntry['vedio']) || !empty($galleryEntry['link'])) {
                        $galleryPayload[] = $galleryEntry;
                    }
                }

                if (!empty($galleryPayload)) {
                    $createdEvent->gallery()->createMany($galleryPayload);
                }

                return $createdEvent;
            });
        } catch (Throwable $exception) {
            if (!empty($storedPaths)) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }

        return response()->json($event->load('gallery'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event): JsonResponse
    {
        return response()->json($event->load('gallery'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $event->loadMissing('gallery');

        $data = $request->validated();
        $hasGalleryField = array_key_exists('gallery', $data);
        $gallery = $this->normalizeGalleryPayload($data['gallery'] ?? []);
        $obsoletePaths = $hasGalleryField
            ? $this->extractGalleryPaths($event->gallery->all())->diff($this->extractGalleryPaths($gallery))->values()->all()
            : [];
        unset($data['gallery']);

        DB::transaction(function () use ($event, $data, $hasGalleryField, $gallery) {
            $event->update($data);

            if ($hasGalleryField) {
                $event->gallery()->delete();

                if (!empty($gallery)) {
                    $event->gallery()->createMany($gallery);
                }
            }
        });

        if (!empty($obsoletePaths)) {
            Storage::disk('public')->delete($obsoletePaths);
        }

        return response()->json($event->fresh()->load('gallery'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event): JsonResponse
    {
        $event->loadMissing('gallery');
        $pathsToDelete = $this->extractGalleryPaths($event->gallery->all())->values()->all();

        $event->delete();

        if (!empty($pathsToDelete)) {
            Storage::disk('public')->delete($pathsToDelete);
        }

        return response()->json([
            'message' => 'Event deleted successfully.',
        ]);
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array<string, string|null>>
     */
    private function normalizeGalleryPayload(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                return [
                    'image' => $this->resolveStoredMediaPath(
                        $item['image'] ?? null,
                        $item['existing_image'] ?? null,
                        'event_images'
                    ),
                    'vedio' => $this->resolveStoredMediaPath(
                        $item['vedio'] ?? null,
                        $item['existing_vedio'] ?? null,
                        'event_videos'
                    ),
                    'link' => $item['link'] ?? null,
                ];
            })
            ->filter(function ($item) {
                return !empty($item['image']) || !empty($item['vedio']) || !empty($item['link']);
            })
            ->values()
            ->all();
    }

    private function resolveStoredMediaPath(mixed $file, mixed $existingPath, string $directory): ?string
    {
        if ($file instanceof UploadedFile) {
            return $file->store($directory, 'public');
        }

        if (is_string($existingPath) && $existingPath !== '') {
            return $existingPath;
        }

        return null;
    }

    /**
     * @param  array<int, string>  $storedPaths
     */
    private function storeGalleryUpload(
        mixed $file,
        string $expectedType,
        string $directory,
        array &$storedPaths,
        int $index,
        string $field
    ): ?string {
        if (!$file instanceof UploadedFile) {
            return null;
        }

        $this->assertGalleryUploadType($file, $expectedType, $index, $field);

        $path = $file->store($directory, 'public');
        $storedPaths[] = $path;

        return $path;
    }

    private function assertGalleryUploadType(UploadedFile $file, string $expectedType, int $index, string $field): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = strtolower($file->getMimeType() ?? '');

        $isValid = match ($expectedType) {
            'image' => str_starts_with($mimeType, 'image/')
                || in_array($extension, ['jpg', 'jpeg', 'jfif', 'png', 'webp', 'gif', 'bmp', 'svg', 'tiff', 'tif', 'ico', 'avif', 'heic', 'heif'], true),
            'video' => str_starts_with($mimeType, 'video/') && in_array($extension, ['mp4', 'webm', 'ogg', 'mov'], true),
            default => false,
        };

        if ($isValid) {
            return;
        }

        throw ValidationException::withMessages([
            "gallery.$index.$field" => ['The uploaded file does not match the expected gallery media type.'],
        ]);
    }

    /**
     * @param  array<int, mixed>  $galleryItems
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function extractGalleryPaths(array $galleryItems)
    {
        return collect($galleryItems)
            ->flatMap(function ($item) {
                if ($item instanceof \App\Models\EventGalerie) {
                    return array_filter([$item->image, $item->vedio]);
                }

                return array_filter([$item['image'] ?? null, $item['vedio'] ?? null]);
            })
            ->filter()
            ->unique()
            ->values();
    }
}
