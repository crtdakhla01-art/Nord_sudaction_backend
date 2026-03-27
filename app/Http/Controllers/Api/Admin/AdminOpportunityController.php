<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;

class AdminOpportunityController extends Controller
{
    public function index(): JsonResponse
    {
        $opportunities = Opportunity::query()
            ->with('type:id,name')
            ->latest()
            ->get();

        return response()->json($opportunities);
    }

    public function show(Opportunity $opportunity): JsonResponse
    {
        $opportunity->load('type:id,name');

        return response()->json($opportunity);
    }

    public function accept(Opportunity $opportunity): JsonResponse
    {
        $opportunity->update(['status' => 'accepted']);

        return response()->json([
            'message' => 'Opportunity accepted.',
            'data' => $opportunity->fresh()->load('type:id,name'),
        ]);
    }

    public function reject(Opportunity $opportunity): JsonResponse
    {
        $opportunity->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Opportunity rejected.',
            'data' => $opportunity->fresh()->load('type:id,name'),
        ]);
    }
}
