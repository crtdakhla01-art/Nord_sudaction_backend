<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpportunityRequest;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;

class PublicOpportunityController extends Controller
{
    public function index(): JsonResponse
    {
        $opportunities = Opportunity::query()
            ->with('type:id,name')
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

        $opportunity->load('type:id,name');

        return response()->json($opportunity);
    }

    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] = 'pending';

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('opportunities', 'public');
        }

        $opportunity = Opportunity::query()->create($data);
        $opportunity->load('type:id,name');

        return response()->json([
            'message' => 'Opportunity submitted successfully and is pending review.',
            'data' => $opportunity,
        ], 201);
    }
}
