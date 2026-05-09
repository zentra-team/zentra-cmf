<?php

namespace App\Http\Middleware;

use App\Events\ApiRequestAuthorizing;
use App\Events\ApiRequestServed;
use App\Models\ApiToken;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public const REQUEST_TOKEN_ATTR = 'api_token';

    public function handle(Request $request, \Closure $next): Response
    {
        if (Setting::getValue('api_enabled', '0') !== '1') {
            return $this->error(503, 'api_disabled', 'JSON API is disabled on this site.');
        }

        $plain = trim((string) $request->header('X-API-Key', ''));

        if ($plain === '') {
            return $this->error(401, 'missing_token', 'Missing X-API-Key header.');
        }

        $token = ApiToken::findByPlainToken($plain);

        if ($token === null) {
            return $this->error(401, 'invalid_token', 'Token is invalid or revoked.');
        }

        if (!$token->is_active) {
            return $this->error(401, 'token_inactive', 'Token is deactivated.');
        }

        if ($token->isExpired()) {
            return $this->error(401, 'token_expired', 'Token has expired.');
        }

        if ($token->rate_limit_per_minute > 0) {
            $key = 'api_token:' . $token->id;

            if (RateLimiter::tooManyAttempts($key, $token->rate_limit_per_minute)) {
                $retryAfter = RateLimiter::availableIn($key);

                return $this->error(429, 'rate_limited', 'Too many requests.', [
                    'retry_after' => $retryAfter,
                ])->header('Retry-After', (string) $retryAfter);
            }

            RateLimiter::hit($key, 60);
        }

        $event = new ApiRequestAuthorizing($token, $request);
        event($event);

        if ($event->denyResponse !== null) {
            return $event->denyResponse;
        }

        $request->attributes->set(self::REQUEST_TOKEN_ATTR, $token);

        $response = $next($request);

        event(new ApiRequestServed($token, $request, $response));

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $token = $request->attributes->get(self::REQUEST_TOKEN_ATTR);

        if (!$token instanceof ApiToken) {
            return;
        }

        try {
            DB::table('api_tokens')
                ->where('id', $token->id)
                ->update([
                    'hits'         => DB::raw('hits + 1'),
                    'last_used_at' => now(),
                    'last_used_ip' => substr((string) $request->ip(), 0, 45),
                ]);
        } catch (\Throwable) {
        }
    }

    private function error(int $status, string $code, string $message, array $extra = []): Response
    {
        $body = ['error' => array_merge([
            'code'    => $code,
            'message' => $message,
        ], $extra)];

        return response()->json($body, $status);
    }
}
