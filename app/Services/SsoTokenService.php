<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use JsonException;

class SsoTokenService
{
    public function decode(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            abort(403, 'Token SSO tidak ditemukan.');
        }

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            abort(403, 'Format token tidak valid.');
        }

        [$encodedPayload, $signature] = $parts;

        $sharedSecret = (string) config('services.sso.shared_secret');
        if ($sharedSecret === '') {
            abort(500, 'SSO belum dikonfigurasi (shared secret kosong).');
        }

        $expectedSignature = hash_hmac('sha256', $encodedPayload, $sharedSecret);
        if (! hash_equals($expectedSignature, $signature)) {
            abort(403, 'Signature token tidak valid.');
        }

        $payloadJson = $this->base64UrlDecode($encodedPayload);

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            abort(403, 'Payload token tidak valid.');
        }

        if (! is_array($decoded)) {
            abort(403, 'Payload token tidak valid.');
        }

        $payload = $decoded;

        if (Arr::get($payload, 'iss') !== config('services.sso.issuer')) {
            abort(403, 'Issuer token tidak valid.');
        }

        if (Arr::get($payload, 'aud') !== config('services.sso.audience')) {
            abort(403, 'Audience token tidak valid.');
        }

        $exp = Arr::get($payload, 'exp');
        if (! is_int($exp) && ! (is_string($exp) && ctype_digit($exp))) {
            abort(403, 'Expiry token tidak valid.');
        }

        $expTs = (int) $exp;
        if ($expTs <= 0) {
            abort(403, 'Expiry token tidak valid.');
        }

        $expiresAt = Carbon::createFromTimestamp($expTs);
        if ($expiresAt->isPast()) {
            abort(403, 'Token sudah expired.');
        }

        $this->ensureNotReplayed($token, $expiresAt);

        return $payload;
    }

    private function ensureNotReplayed(string $token, Carbon $expiresAt): void
    {
        $tokenHash = hash('sha256', $token);
        $cacheKey = 'sso:token_replay:' . $tokenHash;

        $ttlSeconds = max(1, now()->diffInSeconds($expiresAt, false));
        if (! Cache::add($cacheKey, true, $ttlSeconds)) {
            abort(403, 'Token replay terdeteksi.');
        }
    }

    private function base64UrlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $value .= str_repeat('=', (4 - strlen($value) % 4) % 4);

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            abort(403, 'Payload token tidak valid.');
        }

        return $decoded;
    }
}
