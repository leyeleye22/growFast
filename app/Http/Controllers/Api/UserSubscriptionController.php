<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeRequest;
use App\Mail\UserSubscribedMail;
use App\Mail\UserSubscriptionCancelledMail;
use App\Models\Subscription;
use App\Models\UserSubscription;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserSubscriptionController extends Controller
{
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        try {
            Log::info('[POST] UserSubscriptionController@subscribe');
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
            Log::info('User subscribed', ['user_subscription_id' => $userSub->id]);

            app(NotificationService::class)->send(new UserSubscribedMail($userSub->load(['user', 'subscription'])));
            $request->user()->notify(new \App\Notifications\UserSubscribedNotification($userSub->load('subscription')));
            return response()->json($userSub->load('subscription'), 201);
        } catch (Throwable $e) {
            Log::error('UserSubscriptionController@subscribe failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function cancel(): JsonResponse
    {
        try {
            Log::info('[POST] UserSubscriptionController@cancel');
            $active = request()->user()->activeSubscription;
            if (!$active) {
                return response()->json(['message' => 'No active subscription'], 404);
            }
            $active->update(['status' => SubscriptionStatus::Cancelled, 'auto_renew' => false]);
            Log::info('Subscription cancelled', ['user_subscription_id' => $active->id]);

            app(NotificationService::class)->send(new UserSubscriptionCancelledMail($active->load(['user', 'subscription'])));
            return response()->json($active->load('subscription'));
        } catch (Throwable $e) {
            Log::error('UserSubscriptionController@cancel failed', ['exception' => $e]);
            throw $e;
        }
    }
}
