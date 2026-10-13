<?php

namespace App\Application\Services;

use App\Application\Ports\PasswordHasher;
use App\Application\Ports\TokenIssuer;
use App\Application\Ports\UserRepository;
use App\Domain\DomainError;
use App\Domain\User;

/** HU-01 */
final class LoginService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly TokenIssuer $tokens,
    ) {
    }

    /** @return array{access_token: string, token_type: string, expires_in: int, role: string} */
    public function execute(string $username, string $password): array
    {
        $user = $this->users->findByUsername(User::normalizeUsername($username));

        // CA-01.2: same message whether the username or the password failed.
        if ($user === null || ! $this->hasher->verify($password, $user->passwordHash)) {
            throw new DomainError('INVALID_CREDENTIALS', 'Usuario o contraseña incorrectos.');
        }

        return [
            'access_token' => $this->tokens->issue($user->id, $user->role),
            'token_type' => 'Bearer',
            'expires_in' => TokenIssuer::TTL_SECONDS,
            'role' => $user->role,
        ];
    }
}
