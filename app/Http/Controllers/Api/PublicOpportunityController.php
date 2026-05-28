<?php

namespace App\Http\Controllers\Api;

use App\Events\OpportunitySubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpportunityRequest;
use App\Models\Opportunity;
use App\Models\TypeOpportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
            return response()->json([
                'success' => false,
                'error_key' => 'api.error_not_found',
            ], 404);
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
            'calls' => 'Appels à projets',
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

        event(new OpportunitySubmitted($opportunity->id));

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => $opportunity,
        ], 201);
    }
}
