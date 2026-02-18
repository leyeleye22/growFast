<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeRequest;
use App\Models\Subscription;
use App\Models\UserSubscription;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Throwable;

class UserSubscriptionController extends Controller
{
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        try {
            LogService::request('POST', 'UserSubscriptionController@subscribe');
            $subscription = Subscription::findOrFail($request->validated('subscription_id'));
            if (!$subscription->is_active) {
                return response()->json(['message' => 'Subscription plan is not available'], 422);
            }

            $userSub = UserSubscription::create([
                'user_id' => $request->user()->id,
                'subscription_id' => $subscription->id,
                'started_at' => now(),
                'expires_at' => now()->addMonths(str_starts_with((string) $subscription->billing_cycle, 'year') ? 12 : 1),
                'status' => SubscriptionStatus::Active,
                'auto_renew' => true,
            ]);
            LogService::info('User subscribed', ['user_subscription_id' => $userSub->id]);
            return response()->json($userSub->load('subscription'), 201);
        } catch (Throwable $e) {
            LogService::exception($e, 'UserSubscriptionController@subscribe failed');
            throw $e;
        }
    }

    public function cancel(): JsonResponse
    {
        try {
            LogService::request('POST', 'UserSubscriptionController@cancel');
            $active = request()->user()->activeSubscription;
            if (!$active) {
                return response()->json(['message' => 'No active subscription'], 404);
            }
            $active->update(['status' => SubscriptionStatus::Cancelled, 'auto_renew' => false]);
            LogService::info('Subscription cancelled', ['user_subscription_id' => $active->id]);
            return response()->json($active->load('subscription'));
        } catch (Throwable $e) {
            LogService::exception($e, 'UserSubscriptionController@cancel failed');
            throw $e;
        }
    }
}
