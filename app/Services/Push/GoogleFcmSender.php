<?php

namespace App\Services\Push;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging (HTTP v1) with a service account. Set FIREBASE_PROJECT_ID and
 * FIREBASE_CREDENTIALS (the service-account JSON, as-is or base64-encoded).
 */
class GoogleFcmSender implements FcmSender
{
    public function enabled(): bool
    {
        return filled(config('services.fcm.project_id')) && $this->credentials() !== null;
    }

    public function send(string $token, string $title, string $body, array $data, string $channel): bool
    {
        if (! $this->enabled()) {
            return true; // nothing configured: skip quietly, keep the token
        }

        $response = Http::withToken($this->accessToken())
            ->timeout(5)
            ->post('https://fcm.googleapis.com/v1/projects/'.config('services.fcm.project_id').'/messages:send', [
                'message' => [
                    self::targetField($token) => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => $data,
                    'android' => [
                        'priority' => 'high',
                        'notification' => ['channel_id' => $channel],
                    ],
                ],
            ]);

        if ($response->successful()) {
            return true;
        }

        // The phone uninstalled the app or the token expired: forget it.
        $status = $response->json('error.details.0.errorCode') ?? $response->json('error.status');
        if ($response->status() === 404 || in_array($status, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
            return false;
        }

        Log::warning('Push notification failed', ['status' => $response->status(), 'error' => $response->json('error.message')]);

        return true;
    }

    /**
     * Where FCM sends: an FCM registration token ("…:APA91b…", from the app since 1.0.13), or a
     * Firebase Installation ID (22 characters, sent by older app versions).
     */
    public static function targetField(string $token): string
    {
        return str_contains($token, ':') ? 'token' : 'fid';
    }

    private function accessToken(): string
    {
        // Google's tokens last an hour; reuse one for 50 minutes.
        return Cache::remember('fcm.access_token', now()->addMinutes(50), function () {
            $client = new GoogleClient;
            $client->setAuthConfig($this->credentials());
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            return $client->fetchAccessTokenWithAssertion()['access_token'];
        });
    }

    /** @return array<string, mixed>|null */
    private function credentials(): ?array
    {
        $raw = trim((string) config('services.fcm.credentials'));
        if ($raw === '') {
            return null;
        }
        $json = str_starts_with($raw, '{') ? $raw : base64_decode($raw, true);
        $decoded = is_string($json) ? json_decode($json, true) : null;

        return is_array($decoded) ? $decoded : null;
    }
}
