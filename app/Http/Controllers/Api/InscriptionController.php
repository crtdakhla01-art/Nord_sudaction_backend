<?php

namespace App\Http\Controllers\Api;

use App\Events\InscriptionSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInscriptionRequest;
use App\Models\Inscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InscriptionController extends Controller
{
    public function store(StoreInscriptionRequest $request): JsonResponse
    {
        $sendTraceId = trim((string) $request->header('X-Send-Trace-Id', ''));
        if ($sendTraceId === '') {
            $sendTraceId = (string) Str::uuid();
        }

        Log::info('Inscription flow started', [
            'send_trace_id' => $sendTraceId,
            'ip' => $request->ip(),
        ]);

        $payload = $request->validated();
        $paymentProofPath = $request->file('payment_proof')?->store('inscriptions/payment-proofs', 'public');
        $cinCopyPath = $request->file('cin_copy')?->store('inscriptions/cin-copies', 'public');

        unset($payload['payment_proof'], $payload['cin_copy']);
        $payload['payment_proof_path'] = $paymentProofPath;
        $payload['cin_copy_path'] = $cinCopyPath;
        $payload['participation_fee'] = 1800;
        $payload['is_paid'] = false;
        $payload['paid_at'] = null;

        $inscription = Inscription::query()->create($payload);

        Log::info('Inscription created', [
            'send_trace_id' => $sendTraceId,
            'inscription_id' => $inscription->id,
        ]);

        event(new InscriptionSubmitted($inscription->id, $sendTraceId));

        Log::info('Inscription event dispatched', [
            'send_trace_id' => $sendTraceId,
            'inscription_id' => $inscription->id,
            'event' => InscriptionSubmitted::class,
        ]);

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => $inscription,
        ], 201);
    }
}
