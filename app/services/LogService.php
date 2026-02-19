<?php



namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class LogService
{
    protected static function channel(): string
    {
        return config('app.env') === 'testing' ? config('logging.default') : 'single';
    }

    public static function info(string $message, array $context = []): void
    {
        try {
            Log::channel(self::channel())->info($message, self::enrichContext($context));
        } catch (\Throwable) {
        }
    }

    public static function error(string $message, array $context = []): void
    {
        try {
            Log::channel(self::channel())->error($message, self::enrichContext($context));
        } catch (\Throwable) {
        }
    }

    public static function warning(string $message, array $context = []): void
    {
        try {
            Log::channel(self::channel())->warning($message, self::enrichContext($context));
        } catch (\Throwable) {
        }
    }

    public static function debug(string $message, array $context = []): void
    {
        try {
            Log::channel(self::channel())->debug($message, self::enrichContext($context));
        } catch (\Throwable) {
        }
    }

    public static function exception(Throwable $e, ?string $message = null, array $context = []): void
    {
        try {
            $context = array_merge($context, [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            Log::channel(self::channel())->error($message ?? $e->getMessage(), self::enrichContext($context));
        } catch (\Throwable) {
        }
    }

    public static function request(string $method, string $action, array $context = []): void
    {
        self::info("[{$method}] {$action}", $context);
    }

    protected static function enrichContext(array $context): array
    {
        $enriched = [
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            if (request()) {
                $enriched['url'] = request()->fullUrl();
                $enriched['method'] = request()->method();
                $enriched['ip'] = request()->ip();
                $enriched['user_agent'] = request()->userAgent();
            }

            if (auth()->check()) {
                $enriched['user_id'] = auth()->id();
                $enriched['user_email'] = auth()->user()?->email;
            }
        } catch (\Throwable) {
        }

        return array_merge($enriched, $context);
    }
}
