<?php

namespace App\Adapters\Security;

use App\Application\Ports\PasswordHasher;

/** D-09: Argon2id. Falls back to bcrypt only if the PHP build has no Argon2 support. */
final class Argon2idPasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;

        return password_hash($plain, $algo);
    }

    public function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
