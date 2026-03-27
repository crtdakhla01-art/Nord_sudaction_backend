<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\JsonResponse;

class PublicAdvertisementController extends Controller
{
    public function index(): JsonResponse
    {
        $today = now()->toDateString();

        $activeAdvertisements = Advertisement::query()
            ->whereDate('begin_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('begin_date')
            ->get();

        if ($activeAdvertisements->isNotEmpty()) {
            return response()->json($activeAdvertisements);
        }

        $fallbackAdvertisements = Advertisement::query()
            ->latest('created_at')
            ->limit(5)
            ->get();

        return response()->json($fallbackAdvertisements);
    }
}
