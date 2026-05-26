<?php

namespace App\Http\Controllers\Api;

use App\Events\InscriptionSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInscriptionRequest;
use App\Models\Inscription;
use Illuminate\Http\JsonResponse;

class InscriptionController extends Controller
{
    public function store(StoreInscriptionRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $paymentProofPath = $request->file('payment_proof')?->store('inscriptions/payment-proofs', 'public');
        $cinCopyPath = $request->file('cin_copy')?->store('inscriptions/cin-copies', 'public');

        unset($payload['payment_proof'], $payload['cin_copy']);
        $payload['payment_proof_path'] = $paymentProofPath;
        $payload['cin_copy_path'] = $cinCopyPath;
        $payload['participation_fee'] = 1500;
        $payload['is_paid'] = false;
        $payload['paid_at'] = null;

        $inscription = Inscription::query()->create($payload);

        event(new InscriptionSubmitted($inscription->id));

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => $inscription,
        ], 201);
    }
}
