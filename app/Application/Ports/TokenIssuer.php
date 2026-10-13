<?php

namespace App\Application\Ports;

interface TokenIssuer
{
    public const TTL_SECONDS = 28800; // 8 hours (CA-01.1)

    /** Signed token with "sub" and "role" only: no personal data inside. */
    public function issue(string $userId, string $role): string;

    /** @return array{sub: string, role: string}|null null when invalid or expired */
    public function verify(string $token): ?array;
}
