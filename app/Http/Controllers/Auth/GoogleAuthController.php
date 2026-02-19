<?php



namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        try {
            LogService::request('GET', 'GoogleAuthController@redirect');
            return Socialite::driver('google')->stateless()->redirect();
        } catch (Throwable $e) {
            LogService::exception($e, 'GoogleAuthController@redirect failed');
            throw $e;
        }
    }

    public function callback(Request $request): JsonResponse|RedirectResponse
    {
        try {
            LogService::request('GET', 'GoogleAuthController@callback');

            if ($request->has('error')) {
                LogService::warning('Google OAuth error', [
                    'error' => $request->get('error'),
                    'error_description' => $request->get('error_description'),
                ]);
                return response()->json([
                    'error' => $request->get('error'),
                    'error_description' => $request->get('error_description', 'OAuth error'),
                ], 400);
            }

            if (!$request->has('code')) {
                LogService::warning('Google OAuth: missing code parameter');
                return response()->json([
                    'error' => 'invalid_request',
                    'error_description' => 'Missing authorization code. Ensure you are redirected from Google and the redirect URI matches exactly in Google Cloud Console.',
                ], 400);
            }

            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::updateOrCreate(
                ['google_id' => $googleUser->getId()],
                [
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                    'password' => bcrypt(str()->random(32)),
                ]
            );

            $token = auth('api')->login($user);

            LogService::info('Google OAuth login successful', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => $user,
            ]);
        } catch (Throwable $e) {
            LogService::exception($e, 'GoogleAuthController@callback failed');
            throw $e;
        }
    }
}
