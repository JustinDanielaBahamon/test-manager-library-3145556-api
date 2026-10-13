<?php

namespace App\Adapters\Persistence;

use App\Application\Ports\UserRepository;
use App\Domain\DomainError;
use App\Domain\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class DbUserRepository implements UserRepository
{
    public function findByUsername(string $normalizedUsername): ?User
    {
        $row = DB::table('library.user')->where('username', $normalizedUsername)->first();

        return $row === null ? null : new User($row->id, $row->username, $row->password_hash, $row->role);
    }

    public function add(User $user): void
    {
        try {
            DB::table('library.user')->insert([
                'id' => $user->id,
                'username' => $user->username,
                'password_hash' => $user->passwordHash,
                'role' => $user->role,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new DomainError('USERNAME_TAKEN', 'El nombre de usuario ya existe.');
        }
    }
}
