<?php

namespace App\Application\Ports;

use App\Domain\DateRange;

/** ADR-004: one aggregate query over frozen values; the report is never persisted. */
interface ReportReader
{
    /** @return list<array{book_id: string, book_title: string, category_name: string, times_borrowed: int}> */
    public function mostBorrowed(DateRange $range): array;
}
