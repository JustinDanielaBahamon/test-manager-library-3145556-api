<?php

namespace Tests\Unit\Domain;

use App\Domain\DomainError;
use App\Domain\Isbn;
use PHPUnit\Framework\TestCase;

/** T-03 — CA-03.2: the ISBN has 13 digits. */
final class IsbnTest extends TestCase
{
    public function test_accepts_thirteen_digits(): void
    {
        $this->assertSame('9780307474728', Isbn::of(' 9780307474728 ')->value);
    }

    public function test_rejects_less_than_thirteen_digits(): void
    {
        $this->expectException(DomainError::class);
        Isbn::of('978030747472');
    }

    public function test_rejects_non_digits(): void
    {
        $this->expectException(DomainError::class);
        Isbn::of('978030747472X');
    }
}
