<?php

namespace App\Domain;

/**
 * Aggregate root for a catalog title. Invariants: title not empty, total >= 1,
 * 0 <= available <= total (RN-1). Copies are counted, not identified.
 */
final class Book
{
    private function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $author,
        public readonly Isbn $isbn,
        public readonly string $categoryId,
        public readonly int $totalCopies,
        public readonly int $availableCopies,
    ) {
    }

    /** CA-03.1: available copies start equal to the total. */
    public static function create(string $id, string $title, string $author, Isbn $isbn, string $categoryId, int $totalCopies): self
    {
        self::assertTotal($totalCopies);

        return new self($id, self::cleanTitle($title), self::cleanAuthor($author), $isbn, $categoryId, $totalCopies, $totalCopies);
    }

    public static function restore(string $id, string $title, string $author, string $isbn, string $categoryId, int $total, int $available): self
    {
        return new self($id, $title, $author, Isbn::of($isbn), $categoryId, $total, $available);
    }

    /** CA-03.3: changing the total moves the available copies by the same difference, never below 0. */
    public function replace(string $title, string $author, string $categoryId, int $newTotal): self
    {
        self::assertTotal($newTotal);
        $available = $this->availableCopies + ($newTotal - $this->totalCopies);
        if ($available < 0) {
            throw DomainError::validation('total_copies', 'dejaría ejemplares disponibles en negativo',
                'No se puede reducir el total por debajo de los ejemplares prestados.');
        }

        return new self($this->id, self::cleanTitle($title), self::cleanAuthor($author), $this->isbn, $categoryId, $newTotal, $available);
    }

    private static function assertTotal(int $total): void
    {
        if ($total < 1) {
            throw DomainError::validation('total_copies', 'debe ser mayor o igual a 1');
        }
    }

    private static function cleanTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '' || mb_strlen($title) > 250) {
            throw DomainError::validation('title', 'obligatorio, máximo 250 caracteres');
        }

        return $title;
    }

    private static function cleanAuthor(string $author): string
    {
        $author = trim($author);
        if ($author === '' || mb_strlen($author) > 200) {
            throw DomainError::validation('author', 'obligatorio, máximo 200 caracteres');
        }

        return $author;
    }
}
