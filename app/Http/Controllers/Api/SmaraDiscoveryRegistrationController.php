<?php

namespace App\Http\Controllers\Api;

use App\Events\SmaraDiscoveryRegistrationSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSmaraDiscoveryRegistrationRequest;
use App\Http\Resources\SmaraDiscoveryRegistrationResource;
use App\Models\SmaraDiscoveryRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmaraDiscoveryRegistrationController extends Controller
{
    public function store(StoreSmaraDiscoveryRegistrationRequest $request): JsonResponse
    {
        $sendTraceId = trim((string) $request->header('X-Send-Trace-Id', ''));
        if ($sendTraceId === '') {
            $sendTraceId = (string) Str::uuid();
        }

        Log::info('Smara discovery registration flow started', [
            'send_trace_id' => $sendTraceId,
            'ip' => $request->ip(),
        ]);

        $registration = SmaraDiscoveryRegistration::query()->create($request->validated());

        Log::info('Smara discovery registration created', [
            'send_trace_id' => $sendTraceId,
            'registration_id' => $registration->id,
        ]);

        event(new SmaraDiscoveryRegistrationSubmitted($registration->id, $sendTraceId));

        Log::info('Smara discovery registration event dispatched', [
            'send_trace_id' => $sendTraceId,
            'registration_id' => $registration->id,
            'event' => SmaraDiscoveryRegistrationSubmitted::class,
        ]);

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => new SmaraDiscoveryRegistrationResource($registration),
        ], 201);
    }
}
