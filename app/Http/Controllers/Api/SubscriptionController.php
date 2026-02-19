<?php



namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Throwable;

class SubscriptionController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            LogService::request('GET', 'SubscriptionController@index');
            $subscriptions = Subscription::where('is_active', true)->get();
            return response()->json($subscriptions);
        } catch (Throwable $e) {
            LogService::exception($e, 'SubscriptionController@index failed');
            throw $e;
        }
    }

    public function my(): JsonResponse
    {
        try {
            LogService::request('GET', 'SubscriptionController@my');
            $active = request()->user()->activeSubscription;
            return response()->json($active ? $active->load('subscription') : null);
        } catch (Throwable $e) {
            LogService::exception($e, 'SubscriptionController@my failed');
            throw $e;
        }
    }
}
