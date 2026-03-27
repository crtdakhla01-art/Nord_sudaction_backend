<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TypeOpportunity;
use Illuminate\Http\JsonResponse;

class PublicTypeOpportunityController extends Controller
{
    public function index(): JsonResponse
    {
        $types = TypeOpportunity::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($types);
    }
}
