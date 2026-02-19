<?php



namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionAccess
{
    public function handle(Request $request, Closure $next, ?string $tier = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $activeSubscription = $user->userSubscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->with('subscription')
            ->latest()
            ->first();

        if (!$activeSubscription) {
            return response()->json(['message' => 'Active subscription required'], 403);
        }

        if ($tier && $activeSubscription->subscription->slug !== $tier) {
            return response()->json(['message' => 'Insufficient subscription tier'], 403);
        }

        $request->attributes->set('active_subscription', $activeSubscription);

        return $next($request);
    }
}
