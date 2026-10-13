<?php

namespace App\Domain;

/** One book inside a loan, with frozen title, ISBN and category name. */
final class LoanItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $bookId,
        public readonly string $bookTitle,
        public readonly string $isbn,
        public readonly string $categoryName,
        public readonly \DateTimeImmutable $dueAt,
        public readonly ?\DateTimeImmutable $returnedAt,
    ) {
    }
}
