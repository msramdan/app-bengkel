<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

trait RespondsToModal
{
    protected function modalSuccess(string $message, mixed $data = null): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $data,
            ]);
        }

        return back()->with('success', $message);
    }

    protected function modalError(string $message, int $status = 422): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->with('error', $message);
    }
}
