<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\OpportunityAccepted;
use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    public function accept(Request $request, Opportunity $opportunity): JsonResponse
    {
        $sendTraceId = trim((string) $request->header('X-Send-Trace-Id', ''));
        if ($sendTraceId === '') {
            $sendTraceId = (string) Str::uuid();
        }

        $wasAccepted = $opportunity->status === 'accepted';
        $opportunity->update(['status' => 'accepted']);

        Log::info('Opportunity accept action processed', [
            'send_trace_id' => $sendTraceId,
            'opportunity_id' => $opportunity->id,
            'was_accepted' => $wasAccepted,
        ]);

        if (! $wasAccepted) {
            event(new OpportunityAccepted($opportunity->id, $sendTraceId));

            Log::info('Opportunity accepted event dispatched', [
                'send_trace_id' => $sendTraceId,
                'opportunity_id' => $opportunity->id,
                'event' => OpportunityAccepted::class,
            ]);
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
