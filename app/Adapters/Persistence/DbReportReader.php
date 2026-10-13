<?php

namespace App\Adapters\Persistence;

use App\Application\Ports\ReportReader;
use App\Domain\DateRange;
use Illuminate\Support\Facades\DB;

/** ADR-004: groups by the FROZEN book_id + book_title + category_name. No members, no librarians. */
final class DbReportReader implements ReportReader
{
    public function mostBorrowed(DateRange $range): array
    {
        $rows = DB::select(
            'SELECT i.book_id, i.book_title, i.category_name, COUNT(*) AS times_borrowed
             FROM library.loan_item i
             JOIN library.loan l ON l.id = i.loan_id
             WHERE l.loaned_at >= ? AND l.loaned_at < ?
             GROUP BY i.book_id, i.book_title, i.category_name
             ORDER BY times_borrowed DESC, i.book_title ASC',
            [Time::toDb($range->from), Time::toDb($range->endExclusive())]
        );

        return array_map(fn ($r) => [
            'book_id' => $r->book_id,
            'book_title' => $r->book_title,
            'category_name' => $r->category_name,
            'times_borrowed' => (int) $r->times_borrowed,
        ], $rows);
    }
}
