<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'is_paid' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $validated = $validator->validated();

        $isPaid = (bool) $validated['is_paid'];

        $inscription->update([
            'is_paid' => $isPaid,
            'paid_at' => $isPaid ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => $inscription->fresh(),
        ]);
    }

    private function validationErrorResponse(\Illuminate\Contracts\Validation\Validator $validator): JsonResponse
    {
        $firstError = strtolower((string) $validator->errors()->first());

        return response()->json([
            'success' => false,
            'error_key' => $this->convertToErrorKey($firstError),
            'errors' => $validator->errors(),
        ], 422);
    }

    private function convertToErrorKey(string $message): string
    {
        $msg = strtolower($message);

        if (str_contains($msg, 'required')) {
            if (str_contains($msg, 'is_paid')) return 'api.error_field_required';
            return 'api.error_field_required';
        }

        if (str_contains($msg, 'boolean')) {
            return 'api.error_field_required';
        }

        return 'api.error_validation_failed';
    }
}
