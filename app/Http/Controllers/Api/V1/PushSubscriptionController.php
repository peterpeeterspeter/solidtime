<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\PushSubscription\PushSubscriptionStoreRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PushSubscriptionController extends Controller
{
    /**
     * Store a new push subscription
     */
    public function store(PushSubscriptionStoreRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        // Check if subscription already exists
        $existing = DB::table('push_subscriptions')
            ->where('user_id', $user->id)
            ->where('endpoint', $validated['endpoint'])
            ->first();

        if ($existing) {
            // Update existing subscription
            DB::table('push_subscriptions')
                ->where('id', $existing->id)
                ->update([
                    'p256dh_key' => $validated['keys']['p256dh'],
                    'auth_key' => $validated['keys']['auth'],
                    'updated_at' => now(),
                ]);

            return response()->json([
                'message' => 'Push subscription updated successfully',
            ]);
        }

        // Create new subscription
        DB::table('push_subscriptions')->insert([
            'user_id' => $user->id,
            'endpoint' => $validated['endpoint'],
            'p256dh_key' => $validated['keys']['p256dh'],
            'auth_key' => $validated['keys']['auth'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Push subscription created successfully',
        ], 201);
    }

    /**
     * Delete a push subscription
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = Auth::user();

        DB::table('push_subscriptions')
            ->where('user_id', $user->id)
            ->delete();

        return response()->json([
            'message' => 'Push subscriptions deleted successfully',
        ]);
    }

    /**
     * Get VAPID public key
     */
    public function vapidPublicKey(): JsonResponse
    {
        $publicKey = config('services.vapid.public_key', '');

        if (empty($publicKey)) {
            return response()->json([
                'error' => 'VAPID public key not configured',
            ], 500);
        }

        return response()->json([
            'publicKey' => $publicKey,
        ]);
    }
}
