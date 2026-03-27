<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-mail', function () {
    try {
        $to = env('TEST_MAIL_TO', 'test@example.com');

        Mail::raw('Test email working', function ($message) use ($to): void {
            $message->to($to)
                ->subject('Test Mail');
        });

        Log::info('Test mail sent successfully', ['to' => $to]);

        return response()->json([
            'message' => 'Mail sent',
            'to' => $to,
        ]);
    } catch (\Throwable $exception) {
        Log::error('Test mail failed', [
            'error' => $exception->getMessage(),
        ]);

        return response()->json([
            'message' => 'Mail failed',
            'error' => $exception->getMessage(),
        ], 500);
    }
});
