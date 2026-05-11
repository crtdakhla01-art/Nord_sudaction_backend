<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInscriptionController extends Controller
{
    public function index(): JsonResponse
    {
        $inscriptions = Inscription::query()
            ->latest()
            ->get();

        return response()->json($inscriptions);
    }

    public function updatePaymentStatus(Request $request, Inscription $inscription): JsonResponse
    {
        $validated = $request->validate([
            'is_paid' => ['required', 'boolean'],
        ]);

        $isPaid = (bool) $validated['is_paid'];

        $inscription->update([
            'is_paid' => $isPaid,
            'paid_at' => $isPaid ? now() : null,
        ]);

        return response()->json([
            'message' => 'Statut de paiement mis à jour.',
            'data' => $inscription->fresh(),
        ]);
    }
}
