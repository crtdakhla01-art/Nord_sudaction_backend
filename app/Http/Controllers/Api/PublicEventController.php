<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

class PublicEventController extends Controller
{
    public function index(): JsonResponse
    {
        $events = Event::query()
            ->with('gallery')
            ->latest('date')
            ->get();

        return response()->json($events);
    }

    public function show(Event $event): JsonResponse
    {
        $event->load('gallery');

        return response()->json($event);
    }
}
