<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SmaraDiscoveryRegistrationResource;
use App\Models\SmaraDiscoveryRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSmaraDiscoveryRegistrationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $interestLevel = $request->query('interest_level');
        $notifyFirstDate = $request->query('notify_first_date');
        $hasVisited = $request->query('has_visited_es_smara');
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $registrations = SmaraDiscoveryRegistration::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%')
                        ->orWhere('departure_city', 'like', '%'.$search.'%');
                });
            })
            ->when(
                is_string($interestLevel) && in_array($interestLevel, SmaraDiscoveryRegistration::INTEREST_LEVELS, true),
                fn ($query) => $query->where('interest_level', $interestLevel)
            )
            ->when(
                $notifyFirstDate !== null && $notifyFirstDate !== '',
                fn ($query) => $query->where('notify_first_date', filter_var($notifyFirstDate, FILTER_VALIDATE_BOOLEAN))
            )
            ->when(
                $hasVisited !== null && $hasVisited !== '',
                fn ($query) => $query->where('has_visited_es_smara', filter_var($hasVisited, FILTER_VALIDATE_BOOLEAN))
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return SmaraDiscoveryRegistrationResource::collection($registrations)->response();
    }

    public function show(SmaraDiscoveryRegistration $smaraDiscoveryRegistration): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new SmaraDiscoveryRegistrationResource($smaraDiscoveryRegistration),
        ]);
    }

    public function destroy(SmaraDiscoveryRegistration $smaraDiscoveryRegistration): JsonResponse
    {
        $smaraDiscoveryRegistration->delete();

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
        ]);
    }
}
