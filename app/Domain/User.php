<?php

namespace App\Domain;

/** Internal operator (admin or librarian). Password is never stored in clear (Article IX). */
final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_LIBRARIAN = 'librarian';

    public function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly string $passwordHash,
        public readonly string $role,
    ) {
    }

    /** CA-02.2: stored lower-case and trimmed. */
    public static function normalizeUsername(string $username): string
    {
        return mb_strtolower(trim($username));
    }

    /** CA-02.1 / CA-02.3: only "librarian" can be created through the API. */
    public static function newLibrarian(string $id, string $username, string $passwordHash, string $requestedRole): self
    {
        if ($requestedRole !== self::ROLE_LIBRARIAN) {
            throw DomainError::validation('role', 'solo se admite "librarian"', 'Solo se pueden crear usuarios con rol bibliotecario.');
        }

        return self::create($id, $username, $passwordHash, self::ROLE_LIBRARIAN);
    }

    /** D-10: the initial admin is created by the boot process, never through the API. */
    public static function newAdmin(string $id, string $username, string $passwordHash): self
    {
        return self::create($id, $username, $passwordHash, self::ROLE_ADMIN);
    }

    private static function create(string $id, string $username, string $passwordHash, string $role): self
    {
        $username = self::normalizeUsername($username);
        if ($username === '' || mb_strlen($username) > 120) {
            throw DomainError::validation('username', 'obligatorio, máximo 120 caracteres');
        }

        return new self($id, $username, $passwordHash, $role);
    }
}
