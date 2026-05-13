<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CounterController extends Controller
{
    /**
     * Get visitor counter from external API with caching and failover.
     * Cached for 5 minutes to prevent abuse and excessive outbound requests.
     */
    public function up(): JsonResponse
    {
        try {
            // Cache for 5 minutes to prevent abuse and reduce outbound traffic.
            $cached = Cache::get('visitor_counter_up', null);

            if ($cached !== null) {
                return response()->json($cached);
            }

            $response = Http::timeout(5)
                ->get('https://api.counterapi.dev/v2/conseil-tourisme-dakhlas-team-3599/nordsudaction_visitore/up');

            if ($response->successful()) {
                $data = $response->json();

                // Cache the result for 5 minutes.
                Cache::put('visitor_counter_up', $data, now()->addMinutes(5));

                return response()->json($data);
            }

            Log::warning('Counter API returned non-success status', [
                'status' => $response->status(),
            ]);

            return response()->json([
                'success' => false,
                'error_key' => 'api.error_server_error',
                'data' => ['code' => 502],
            ], 502);
        } catch (\Throwable $exception) {
            Log::error('Counter API request failed', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error_key' => 'api.error_server_error',
                'data' => ['code' => 502],
            ], 502);
        }
    }
}
