<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\CategoryRepository;
use App\Application\Ports\IdGenerator;
use App\Domain\Book;
use App\Domain\DomainError;
use App\Domain\Isbn;

/** HU-03 CA-03.1 / CA-03.2 */
final class CreateBookService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly CategoryRepository $categories,
        private readonly IdGenerator $ids,
    ) {
    }

    public function execute(string $title, string $author, string $isbn, string $categoryId, int $totalCopies): string
    {
        if ($this->categories->find($categoryId) === null) {
            throw DomainError::validation('category_id', 'categoría inexistente');
        }
        $book = Book::create($this->ids->newId(), $title, $author, Isbn::of($isbn), $categoryId, $totalCopies);
        $this->books->add($book);

        return $book->id;
    }
}
