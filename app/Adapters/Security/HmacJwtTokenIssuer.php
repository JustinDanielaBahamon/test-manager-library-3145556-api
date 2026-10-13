<?php

namespace App\Adapters\Security;

use App\Application\Ports\Clock;
use App\Application\Ports\TokenIssuer;

/** D-11: JWT HS256, 8 hours, key from JWT_SECRET. */
final class HmacJwtTokenIssuer implements TokenIssuer
{
    public function __construct(
        private readonly string $secret,
        private readonly Clock $clock,
    ) {
        if (strlen($secret) < 32) {
            throw new \RuntimeException('JWT_SECRET must be set and have at least 32 characters.');
        }
    }

    public function issue(string $userId, string $role): string
    {
        $now = $this->clock->now()->getTimestamp();
        $header = self::encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = self::encode(['sub' => $userId, 'role' => $role, 'iat' => $now, 'exp' => $now + self::TTL_SECONDS]);
        $signature = self::b64(hash_hmac('sha256', "$header.$payload", $this->secret, true));

        return "$header.$payload.$signature";
    }

    public function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$header, $payload, $signature] = $parts;
        $expected = self::b64(hash_hmac('sha256', "$header.$payload", $this->secret, true));
        if (! hash_equals($expected, $signature)) {
            return null;
        }
        $head = json_decode(self::unb64($header), true);
        $claims = json_decode(self::unb64($payload), true);
        if (! is_array($head) || ($head['alg'] ?? null) !== 'HS256' || ! is_array($claims)) {
            return null;
        }
        if (! isset($claims['sub'], $claims['role'], $claims['exp']) || $claims['exp'] <= $this->clock->now()->getTimestamp()) {
            return null;
        }

        return ['sub' => (string) $claims['sub'], 'role' => (string) $claims['role']];
    }

    private static function encode(array $data): string
    {
        return self::b64(json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    private static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function unb64(string $text): string
    {
        return (string) base64_decode(strtr($text, '-_', '+/'));
    }
}
