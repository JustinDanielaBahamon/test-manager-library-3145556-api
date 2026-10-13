<?php

namespace Tests\Unit\Domain;

use App\Domain\DomainError;
use App\Domain\User;
use PHPUnit\Framework\TestCase;

/** T-06 — CA-02.2 and CA-02.3. */
final class UserTest extends TestCase
{
    public function test_username_is_lower_case_and_trimmed(): void
    {
        $this->assertSame('ana', User::newLibrarian('u1', 'Ana ', 'hash', 'librarian')->username);
    }

    public function test_admin_role_cannot_be_requested_through_the_api(): void
    {
        $this->expectException(DomainError::class);
        User::newLibrarian('u1', 'ana', 'hash', 'admin');
    }
}
