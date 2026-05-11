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
        $payload['participation_fee'] = 1500;
        $payload['is_paid'] = false;
        $payload['paid_at'] = null;

        $inscription = Inscription::query()->create($payload);

        event(new InscriptionSubmitted($inscription->id));

        return response()->json([
            'message' => 'Inscription enregistrée avec succès.',
            'data' => $inscription,
        ], 201);
    }
}
