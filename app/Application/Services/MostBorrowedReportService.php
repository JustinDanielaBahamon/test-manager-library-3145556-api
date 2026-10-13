<?php

namespace App\Application\Services;

use App\Application\Ports\ReportReader;
use App\Domain\DateRange;

/** HU-10 */
final class MostBorrowedReportService
{
    public function __construct(private readonly ReportReader $reader)
    {
    }

    public function execute(string $from, string $to): array
    {
        $range = DateRange::of($from, $to);

        return ['from' => $from, 'to' => $to, 'rows' => $this->reader->mostBorrowed($range)];
    }
}
