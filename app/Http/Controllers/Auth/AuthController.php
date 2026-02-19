<?php



namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\UserRegisteredMail;
use App\Models\User;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            LogService::request('POST', 'AuthController@login');

            if (!$token = auth('api')->attempt($request->validated())) {
                LogService::warning('Login failed: invalid credentials', ['email' => $request->get('email')]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            LogService::info('Login successful', ['user_id' => auth('api')->id()]);
            return $this->respondWithToken($token);
        } catch (Throwable $e) {
            LogService::exception($e, 'AuthController@login failed');
            throw $e;
        }
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            LogService::request('POST', 'AuthController@register');

            $user = User::create($request->validated());
            $token = auth('api')->login($user);

            LogService::info('Registration successful', ['user_id' => $user->id, 'email' => $user->email]);

            try {
                app(NotificationService::class)->send(new UserRegisteredMail($user));
                $user->notify(new \App\Notifications\UserRegisteredNotification());
            } catch (Throwable $notifException) {
                LogService::exception($notifException, 'Registration notifications failed (user created)');
            }

            return $this->respondWithToken($token);
        } catch (Throwable $e) {
            LogService::exception($e, 'AuthController@register failed');

            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'Registration failed. Please try again or contact support.',
                'exception' => config('app.debug') ? get_class($e) : null,
            ], 500);
        }
    }

    public function me(): JsonResponse
    {
        try {
            LogService::request('GET', 'AuthController@me');
            return response()->json(auth('api')->user());
        } catch (Throwable $e) {
            LogService::exception($e, 'AuthController@me failed');
            throw $e;
        }
    }

    public function logout(): JsonResponse
    {
        try {
            LogService::request('POST', 'AuthController@logout');
            auth('api')->logout();
            LogService::info('Logout successful');
            return response()->json(['message' => 'Successfully logged out']);
        } catch (Throwable $e) {
            LogService::exception($e, 'AuthController@logout failed');
            throw $e;
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            LogService::request('POST', 'AuthController@refresh');
            return $this->respondWithToken(auth('api')->refresh());
        } catch (Throwable $e) {
            LogService::exception($e, 'AuthController@refresh failed');
            throw $e;
        }
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        $ttl = (int) auth('api')->factory()->getTTL();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $ttl * 60,
        ]);
    }
}
