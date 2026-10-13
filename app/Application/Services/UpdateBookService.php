<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\CategoryRepository;
use App\Domain\DomainError;

/** HU-03 CA-03.3: PUT replaces title, author, category and total. */
final class UpdateBookService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly CategoryRepository $categories,
    ) {
    }

    public function execute(string $id, string $title, string $author, string $categoryId, int $totalCopies): void
    {
        $book = $this->books->findActive($id) ?? throw DomainError::notFound('El libro');
        if ($this->categories->find($categoryId) === null) {
            throw DomainError::validation('category_id', 'categoría inexistente');
        }
        $this->books->update($book->replace($title, $author, $categoryId, $totalCopies));
    }
}
