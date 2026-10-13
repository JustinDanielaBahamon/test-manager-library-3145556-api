<?php

namespace Tests\Unit\Domain;

use App\Domain\DateRange;
use App\Domain\DomainError;
use PHPUnit\Framework\TestCase;

/** T-03 — CA-10.2: end before start is rejected; "to" is inclusive. */
final class DateRangeTest extends TestCase
{
    public function test_end_before_start_is_invalid(): void
    {
        try {
            DateRange::of('2026-10-31', '2026-10-01');
            $this->fail('Expected INVALID_DATE_RANGE');
        } catch (DomainError $e) {
            $this->assertSame('INVALID_DATE_RANGE', $e->errorCode);
        }
    }

    public function test_to_is_inclusive(): void
    {
        $range = DateRange::of('2026-10-01', '2026-10-31');
        $this->assertSame('2026-11-01', $range->endExclusive()->format('Y-m-d'));
    }
}
