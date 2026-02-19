<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class LinkedInAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        try {
            Log::info('[GET] LinkedInAuthController@redirect');
            $this->ensureLinkedInConfig();
            return Socialite::driver('linkedin')->stateless()->redirect();
        } catch (Throwable $e) {
            Log::error('LinkedInAuthController@redirect failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function callback(Request $request): JsonResponse|RedirectResponse
    {
        try {
            Log::info('[GET] LinkedInAuthController@callback');

            if ($request->has('error')) {
                Log::warning('LinkedIn OAuth error', [
                    'error' => $request->get('error'),
                    'error_description' => $request->get('error_description'),
                ]);
                return response()->json([
                    'error' => $request->get('error'),
                    'error_description' => $request->get('error_description', 'OAuth error'),
                ], 400);
            }

            if (!$request->has('code')) {
                Log::warning('LinkedIn OAuth: missing code parameter');
                return response()->json([
                    'error' => 'invalid_request',
                    'error_description' => 'Missing authorization code. Ensure you are redirected from LinkedIn and the redirect URI matches exactly in LinkedIn Developer Portal.',
                ], 400);
            }

            $linkedInUser = Socialite::driver('linkedin')->stateless()->user();

            $user = User::updateOrCreate(
                ['linkedin_id' => $linkedInUser->getId()],
                [
                    'name' => $linkedInUser->getName(),
                    'email' => $linkedInUser->getEmail(),
                    'avatar' => $linkedInUser->getAvatar(),
                    'email_verified_at' => now(),
                    'password' => bcrypt(str()->random(32)),
                ]
            );

            $token = auth('api')->login($user);

            Log::info('LinkedIn OAuth login successful', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => $user,
            ]);
        } catch (Throwable $e) {
            Log::error('LinkedInAuthController@callback failed', ['exception' => $e]);
            throw $e;
        }
    }

    protected function ensureLinkedInConfig(): void
    {
        $config = config('services.linkedin');
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            throw new \InvalidArgumentException(
                'LinkedIn OAuth is not configured. Set LINKEDIN_CLIENT_ID and LINKEDIN_CLIENT_SECRET in .env, then run: php artisan config:clear'
            );
        }
    }
}
