<?php

namespace App\Application\Services;

use App\Application\Ports\IdGenerator;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\UserRepository;
use App\Domain\User;

/** HU-02 */
final class CreateLibrarianService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly IdGenerator $ids,
    ) {
    }

    public function execute(string $username, string $password, string $role): User
    {
        $user = User::newLibrarian($this->ids->newId(), $username, $this->hasher->hash($password), $role);
        $this->users->add($user);

        return $user;
    }
}
