<?php

namespace App\Application\Ports;

use App\Domain\User;

interface UserRepository
{
    public function findByUsername(string $normalizedUsername): ?User;

    /** @throws \App\Domain\DomainError USERNAME_TAKEN when the username already exists */
    public function add(User $user): void;
}
