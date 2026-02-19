<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('[GET] NotificationController@index');

            $user = $request->user();
            $perPage = min((int) $request->get('per_page', 15), 50);

            $notifications = $user->notifications()
                ->paginate($perPage);

            return response()->json($notifications);
        } catch (Throwable $e) {
            Log::error('NotificationController@index failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $count = $request->user()->unreadNotifications()->count();

            return response()->json(['count' => $count]);
        } catch (Throwable $e) {
            Log::error('NotificationController@unreadCount failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        try {
            Log::info('[PATCH] NotificationController@markAsRead', ['id' => $id]);

            $notification = $request->user()->notifications()->findOrFail($id);
            $notification->markAsRead();

            return response()->json(['message' => 'Notification marquée comme lue', 'notification' => $notification->fresh()]);
        } catch (Throwable $e) {
            Log::error('NotificationController@markAsRead failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            Log::info('[POST] NotificationController@markAllAsRead');

            $request->user()->unreadNotifications->markAsRead();

            return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues']);
        } catch (Throwable $e) {
            Log::error('NotificationController@markAllAsRead failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            Log::info('[DELETE] NotificationController@destroy', ['id' => $id]);

            $notification = $request->user()->notifications()->findOrFail($id);
            $notification->delete();

            return response()->json(['message' => 'Notification supprimée']);
        } catch (Throwable $e) {
            Log::error('NotificationController@destroy failed', ['exception' => $e]);
            throw $e;
        }
    }
}
