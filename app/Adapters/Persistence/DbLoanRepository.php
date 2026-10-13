<?php

namespace App\Adapters\Persistence;

use App\Application\Ports\LoanRepository;
use App\Domain\DomainError;
use App\Domain\Loan;
use Illuminate\Support\Facades\DB;

final class DbLoanRepository implements LoanRepository
{
    public function unreturnedCountForMember(string $memberId): int
    {
        return DB::table('library.loan_item as i')->join('library.loan as l', 'l.id', '=', 'i.loan_id')
            ->where('l.member_id', $memberId)->whereNull('i.returned_at')->count();
    }

    public function memberHasOverdue(string $memberId, \DateTimeImmutable $now): bool
    {
        return DB::table('library.loan_item as i')->join('library.loan as l', 'l.id', '=', 'i.loan_id')
            ->where('l.member_id', $memberId)->whereNull('i.returned_at')
            ->where('i.due_at', '<', Time::toDb($now))->exists();
    }

    public function saveNew(Loan $loan): void
    {
        DB::transaction(function () use ($loan) {
            foreach ($loan->items as $i => $item) {
                // Last-copy protection without a version column (spec-gaps.md G-1, ADR-002, RNF-2):
                // the conditional update is atomic; zero rows means no copy left and the whole transaction rolls back.
                $affected = DB::update(
                    'UPDATE library.book SET available_copies = available_copies - 1
                     WHERE id = ? AND deleted_at IS NULL AND available_copies > 0',
                    [$item->bookId]
                );
                if ($affected === 0) {
                    throw new DomainError(
                        'BOOK_NOT_AVAILABLE',
                        "No quedan ejemplares disponibles de «{$item->bookTitle}».",
                        [['field' => "items[$i].book_id", 'issue' => 'sin ejemplares']]
                    );
                }
            }
            DB::table('library.loan')->insert([
                'id' => $loan->id,
                'member_id' => $loan->memberId,
                'loaned_by_user_id' => $loan->loanedByUserId,
                'loaned_at' => Time::toDb($loan->loanedAt),
            ]);
            foreach ($loan->items as $item) {
                DB::table('library.loan_item')->insert([
                    'id' => $item->id,
                    'loan_id' => $loan->id,
                    'book_id' => $item->bookId,
                    'book_title' => $item->bookTitle,
                    'isbn' => $item->isbn,
                    'category_name' => $item->categoryName,
                    'due_at' => Time::toDb($item->dueAt),
                    'returned_at' => null,
                ]);
            }
        });
    }

    public function returnItem(string $loanId, string $itemId, \DateTimeImmutable $at): void
    {
        DB::transaction(function () use ($loanId, $itemId, $at) {
            $item = DB::table('library.loan_item')->where('id', $itemId)->where('loan_id', $loanId)->lockForUpdate()->first();
            if ($item === null) {
                throw DomainError::notFound('La línea de préstamo');
            }
            if ($item->returned_at !== null) {
                throw new DomainError('ITEM_ALREADY_RETURNED', 'Esta línea ya fue devuelta.');
            }
            DB::table('library.loan_item')->where('id', $itemId)->update(['returned_at' => Time::toDb($at)]);
            // ck_book_available_range guarantees available never exceeds total (CA-07.3).
            DB::update('UPDATE library.book SET available_copies = available_copies + 1 WHERE id = ?', [$item->book_id]);
        });
    }

    public function view(string $loanId, \DateTimeImmutable $now): ?array
    {
        $loan = DB::table('library.loan')->where('id', $loanId)->first();

        return $loan ? $this->toView($loan, $now) : null;
    }

    public function itemView(string $itemId): ?array
    {
        $i = DB::table('library.loan_item')->where('id', $itemId)->first();

        return $i ? self::itemToView($i) : null;
    }

    public function search(?\DateTimeImmutable $from, ?\DateTimeImmutable $toExclusive, ?string $status, ?string $memberId, \DateTimeImmutable $now, int $page, int $size): array
    {
        $q = DB::table('library.loan as l');
        if ($from) {
            $q->where('l.loaned_at', '>=', Time::toDb($from));
        }
        if ($toExclusive) {
            $q->where('l.loaned_at', '<', Time::toDb($toExclusive));
        }
        if ($memberId) {
            $q->where('l.member_id', $memberId);
        }
        $nowDb = Time::toDb($now);
        $pending = fn ($s) => $s->from('library.loan_item as i')->whereColumn('i.loan_id', 'l.id')->whereNull('i.returned_at');
        if ($status === 'closed') {
            $q->whereNotExists($pending);
        } elseif ($status === 'overdue') {
            $q->whereExists(fn ($s) => $pending($s)->where('i.due_at', '<', $nowDb));
        } elseif ($status === 'open') {
            $q->whereExists($pending)->whereNotExists(fn ($s) => $pending($s)->where('i.due_at', '<', $nowDb));
        }
        $total = (clone $q)->count();
        $rows = $q->orderByDesc('l.loaned_at')->offset(($page - 1) * $size)->limit($size)->get(['l.*']);

        return ['items' => $rows->map(fn ($l) => $this->toView($l, $now))->all(), 'total' => $total];
    }

    public function overdue(\DateTimeImmutable $now, int $page, int $size): array
    {
        $nowDb = Time::toDb($now);
        $q = DB::table('library.loan_item as i')
            ->join('library.loan as l', 'l.id', '=', 'i.loan_id')
            ->join('library.member as m', 'm.id', '=', 'l.member_id')
            ->whereNull('i.returned_at')
            ->where('i.due_at', '<', $nowDb);
        $total = (clone $q)->count();
        $rows = $q->orderBy('i.due_at')->offset(($page - 1) * $size)->limit($size)
            ->get(['i.loan_id', 'i.id as item_id', 'i.book_title', 'm.full_name as member_name', 'i.due_at']);

        $items = $rows->map(function ($r) use ($now) {
            $due = Time::fromDb($r->due_at);

            return [
                'loan_id' => $r->loan_id,
                'item_id' => $r->item_id,
                'book_title' => $r->book_title,
                'member_name' => $r->member_name,
                'due_at' => Time::iso($due),
                'days_late' => intdiv($now->getTimestamp() - $due->getTimestamp(), 86400),
            ];
        })->all();

        return ['items' => $items, 'total' => $total];
    }

    private function toView(object $loan, \DateTimeImmutable $now): array
    {
        $items = DB::table('library.loan_item')->where('loan_id', $loan->id)->orderBy('book_title')->get();
        $status = Loan::statusOf(
            $items->map(fn ($i) => ['due_at' => Time::fromDb($i->due_at), 'returned_at' => Time::fromDb($i->returned_at)])->all(),
            $now
        );

        return [
            'id' => $loan->id,
            'member_id' => $loan->member_id,
            'loaned_at' => Time::iso(Time::fromDb($loan->loaned_at)),
            'status' => $status,
            'items' => $items->map(fn ($i) => self::itemToView($i))->all(),
        ];
    }

    private static function itemToView(object $i): array
    {
        return [
            'id' => $i->id,
            'book_id' => $i->book_id,
            'book_title' => $i->book_title,
            'isbn' => $i->isbn,
            'category_name' => $i->category_name,
            'due_at' => Time::iso(Time::fromDb($i->due_at)),
            'returned_at' => Time::iso(Time::fromDb($i->returned_at)),
        ];
    }
}
