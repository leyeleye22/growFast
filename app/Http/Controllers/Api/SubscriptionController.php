<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscriptionController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            Log::info('[GET] SubscriptionController@index');
            $subscriptions = Subscription::where('is_active', true)->get();
            return response()->json($subscriptions);
        } catch (Throwable $e) {
            Log::error('SubscriptionController@index failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function my(): JsonResponse
    {
        try {
            Log::info('[GET] SubscriptionController@my');
            $active = request()->user()->activeSubscription;
            return response()->json($active ? $active->load('subscription') : null);
        } catch (Throwable $e) {
            Log::error('SubscriptionController@my failed', ['exception' => $e]);
            throw $e;
        }
    }
}
