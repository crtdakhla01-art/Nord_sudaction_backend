<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpportunityRequest;
use App\Models\Opportunity;
use App\Models\TypeOpportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PublicOpportunityController extends Controller
{
    public function index(): JsonResponse
    {
        $opportunities = Opportunity::query()
            ->with(['type:id,name', 'images:id,opportunity_id,path,sort_order'])
            ->where('status', 'accepted')
            ->latest()
            ->get();

        return response()->json($opportunities);
    }

    public function show(Opportunity $opportunity): JsonResponse
    {
        if ($opportunity->status !== 'accepted') {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $opportunity->load(['type:id,name', 'images:id,opportunity_id,path,sort_order']);

        return response()->json($opportunity);
    }

    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        $data = $request->validated();

        $typeName = match ($data['type_key']) {
            'investment' => 'Projets d\'investissement',
            'commerce' => 'Fonds de commerce (tourisme, restauration...)',
            'partnership' => 'Partenariats',
            'calls' => 'Appels a projets',
            default => 'Autre',
        };

        $type = TypeOpportunity::query()->firstOrCreate([
            'name' => $typeName,
        ]);

        $data['type_id'] = $type->id;
        unset($data['type_key']);

        $data['status'] = 'pending';

        $storedImagePaths = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $uploadedImage) {
                $storedImagePaths[] = $uploadedImage->store('opportunities', 'public');
            }
        }

        // Backward compatibility for clients still sending a single "image" field.
        if (empty($storedImagePaths) && $request->hasFile('image')) {
            $storedImagePaths[] = $request->file('image')->store('opportunities', 'public');
        }

        $opportunity = DB::transaction(function () use ($data, $storedImagePaths) {
            $opportunity = Opportunity::query()->create($data);

            if (! empty($storedImagePaths)) {
                $opportunity->images()->createMany(
                    collect($storedImagePaths)
                        ->values()
                        ->map(fn (string $path, int $index) => [
                            'path' => $path,
                            'sort_order' => $index,
                        ])
                        ->all()
                );
            }

            return $opportunity;
        });

        $opportunity->load(['type:id,name', 'images:id,opportunity_id,path,sort_order']);

        $recipient = config('mail.contact_recipient', 'contact@nordsudaction.org');

        try {
            $body = "Nouvelle opportunité soumise\n\n"
                . "Titre: {$opportunity->titre}\n"
                . "Nom: {$opportunity->first_name} {$opportunity->last_name}\n"
                . "Email: {$opportunity->email}\n"
                . "Téléphone: {$opportunity->phone}\n"
                . "Ville: {$opportunity->ville}\n"
                . "Type: " . ($opportunity->type?->name ?? '—') . "\n"
                . "Budget: " . ($opportunity->budget ? number_format($opportunity->budget, 2, ',', ' ') . ' MAD' : '—') . "\n\n"
                . "Description:\n{$opportunity->description}";

            Mail::raw($body, function ($message) use ($opportunity, $recipient) {
                $message->to($recipient)
                    ->replyTo($opportunity->email, $opportunity->first_name . ' ' . $opportunity->last_name)
                    ->subject("Nouvelle opportunité soumise : {$opportunity->titre}");

                foreach ($opportunity->images as $image) {
                    $fullPath = Storage::disk('public')->path($image->path);
                    if (file_exists($fullPath)) {
                        $message->attach($fullPath);
                    }
                }
            });
        } catch (\Throwable $exception) {
            Log::warning('Opportunity notification mail failed', [
                'opportunity_id' => $opportunity->id,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Opportunity submitted successfully and is pending review.',
            'data' => $opportunity,
        ], 201);
    }
}
