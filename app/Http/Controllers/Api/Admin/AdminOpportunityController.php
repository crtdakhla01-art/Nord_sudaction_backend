<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\OpportunityAccepted;
use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;

class AdminOpportunityController extends Controller
{
    public function index(): JsonResponse
    {
        $opportunities = Opportunity::query()
            ->with(['type:id,name', 'images:id,opportunity_id,path,sort_order'])
            ->latest()
            ->get();

        return response()->json($opportunities);
    }

    public function show(Opportunity $opportunity): JsonResponse
    {
        $opportunity->load(['type:id,name', 'images:id,opportunity_id,path,sort_order']);

        return response()->json($opportunity);
    }

    public function accept(Opportunity $opportunity): JsonResponse
    {
        $wasAccepted = $opportunity->status === 'accepted';
        $opportunity->update(['status' => 'accepted']);

        if (! $wasAccepted) {
            event(new OpportunityAccepted($opportunity->id));
        }

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => $opportunity->fresh()->load(['type:id,name', 'images:id,opportunity_id,path,sort_order']),
        ]);
    }

    public function reject(Opportunity $opportunity): JsonResponse
    {
        $opportunity->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => $opportunity->fresh()->load(['type:id,name', 'images:id,opportunity_id,path,sort_order']),
        ]);
    }
}
