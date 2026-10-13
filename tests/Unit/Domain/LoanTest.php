<?php

namespace Tests\Unit\Domain;

use App\Domain\DomainError;
use App\Domain\Loan;
use PHPUnit\Framework\TestCase;

/** T-05 — HU-06 rules and computed status (CA-08.3), with a fixed clock instead of waiting 14 days. */
final class LoanTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-10-14T14:15:00Z');
    }

    private function line(string $id, string $bookId): array
    {
        return ['id' => $id, 'book_id' => $bookId, 'book_title' => 'Título', 'isbn' => '9780307474728', 'category_name' => 'Novela'];
    }

    private function errorCode(callable $fn): ?string
    {
        try {
            $fn();
        } catch (DomainError $e) {
            return $e->errorCode;
        }

        return null;
    }

    public function test_due_date_is_fourteen_days_after_the_loan(): void
    {
        $loan = Loan::open('l1', 'm1', 'u1', $this->now, [$this->line('i1', 'b1')], 0, false);
        $this->assertSame('2026-10-28T14:15:00+00:00', $loan->items[0]->dueAt->format(DATE_ATOM));
    }

    public function test_line_freezes_title_isbn_and_category(): void
    {
        $loan = Loan::open('l1', 'm1', 'u1', $this->now, [$this->line('i1', 'b1')], 0, false);
        $this->assertSame('Título', $loan->items[0]->bookTitle);
        $this->assertSame('Novela', $loan->items[0]->categoryName);
    }

    public function test_one_to_three_lines(): void
    {
        $four = [$this->line('i1', 'b1'), $this->line('i2', 'b2'), $this->line('i3', 'b3'), $this->line('i4', 'b4')];
        $this->assertSame('VALIDATION_ERROR', $this->errorCode(fn () => Loan::open('l1', 'm1', 'u1', $this->now, [], 0, false)));
        $this->assertSame('VALIDATION_ERROR', $this->errorCode(fn () => Loan::open('l1', 'm1', 'u1', $this->now, $four, 0, false)));
    }

    public function test_same_book_twice_is_rejected(): void
    {
        $lines = [$this->line('i1', 'b1'), $this->line('i2', 'b1')];
        $this->assertSame('DUPLICATE_BOOK_IN_LOAN', $this->errorCode(fn () => Loan::open('l1', 'm1', 'u1', $this->now, $lines, 0, false)));
    }

    public function test_member_cannot_exceed_three_unreturned_books(): void
    {
        $lines = [$this->line('i1', 'b1'), $this->line('i2', 'b2')];
        $this->assertSame('MEMBER_LIMIT_EXCEEDED', $this->errorCode(fn () => Loan::open('l1', 'm1', 'u1', $this->now, $lines, 2, false)));
    }

    public function test_member_with_overdue_books_cannot_borrow(): void
    {
        $this->assertSame('MEMBER_HAS_OVERDUE', $this->errorCode(fn () => Loan::open('l1', 'm1', 'u1', $this->now, [$this->line('i1', 'b1')], 0, true)));
    }

    public function test_status_open_overdue_closed(): void
    {
        $due = $this->now->modify('+14 days');
        $this->assertSame('open', Loan::statusOf([['due_at' => $due, 'returned_at' => null]], $this->now));
        $this->assertSame('overdue', Loan::statusOf([['due_at' => $due, 'returned_at' => null]], $this->now->modify('+15 days')));
        $this->assertSame('closed', Loan::statusOf([['due_at' => $due, 'returned_at' => $this->now]], $this->now->modify('+15 days')));
    }
}
