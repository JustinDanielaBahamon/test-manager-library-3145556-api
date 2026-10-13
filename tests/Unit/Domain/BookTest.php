<?php

namespace Tests\Unit\Domain;

use App\Domain\Book;
use App\Domain\DomainError;
use App\Domain\Isbn;
use PHPUnit\Framework\TestCase;

/** T-04 — CA-03.1 and CA-03.3, RN-1: available copies between 0 and total. */
final class BookTest extends TestCase
{
    private function book(int $total, int $available): Book
    {
        return Book::restore('b1', 'Cien años de soledad', 'Gabriel García Márquez', '9780307474728', 'c1', $total, $available);
    }

    public function test_available_copies_start_equal_to_total(): void
    {
        $book = Book::create('b1', '  Cien años  ', 'GGM', Isbn::of('9780307474728'), 'c1', 4);
        $this->assertSame(4, $book->availableCopies);
        $this->assertSame('Cien años', $book->title);
    }

    public function test_total_must_be_at_least_one(): void
    {
        $this->expectException(DomainError::class);
        Book::create('b1', 'Título', 'Autor', Isbn::of('9780307474728'), 'c1', 0);
    }

    public function test_changing_total_moves_available_by_the_same_difference(): void
    {
        $this->assertSame(3, $this->book(4, 1)->replace('T', 'A', 'c1', 6)->availableCopies);
    }

    public function test_never_leaves_available_below_zero(): void
    {
        $this->expectException(DomainError::class);
        $this->book(4, 1)->replace('T', 'A', 'c1', 2); // 3 copies are lent out
    }

    public function test_title_cannot_be_empty(): void
    {
        $this->expectException(DomainError::class);
        $this->book(4, 4)->replace('   ', 'A', 'c1', 4);
    }
}
