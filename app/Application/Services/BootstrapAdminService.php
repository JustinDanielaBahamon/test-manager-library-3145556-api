<?php

namespace App\Application\Services;

use App\Application\Ports\IdGenerator;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\UserRepository;
use App\Domain\User;

/** D-10 / T-17: creates the initial admin from environment credentials. Idempotent. */
final class BootstrapAdminService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly IdGenerator $ids,
    ) {
    }

    /** @return bool true when the admin was created, false when it already existed */
    public function execute(string $username, string $password): bool
    {
        if ($this->users->findByUsername(User::normalizeUsername($username)) !== null) {
            return false;
        }
        $this->users->add(User::newAdmin($this->ids->newId(), $username, $this->hasher->hash($password)));

        return true;
    }
}
