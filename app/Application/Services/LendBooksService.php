<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Domain\DomainError;
use App\Domain\Loan;

/** HU-06. Lends books and does nothing else (SRP). */
final class LendBooksService
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly BookRepository $books,
        private readonly MemberRepository $members,
        private readonly IdGenerator $ids,
        private readonly Clock $clock,
    ) {
    }

    /** @param list<string> $bookIds */
    public function execute(string $memberId, string $userId, array $bookIds): string
    {
        $this->members->view($memberId) ?? throw DomainError::notFound('El socio');
        $now = $this->clock->now();

        $lines = [];
        foreach ($bookIds as $i => $bookId) {
            $book = $this->books->view($bookId);
            if ($book === null) {
                throw new DomainError('NOT_FOUND', 'El libro no existe.', [['field' => "items[$i].book_id", 'issue' => 'no existe']]);
            }
            $lines[] = [
                'id' => $this->ids->newId(),
                'book_id' => $book['id'],
                'book_title' => $book['title'],
                'isbn' => $book['isbn'],
                'category_name' => $book['category']['name'],
            ];
        }

        $loan = Loan::open(
            $this->ids->newId(), $memberId, $userId, $now, $lines,
            $this->loans->unreturnedCountForMember($memberId),
            $this->loans->memberHasOverdue($memberId, $now),
        );
        $this->loans->saveNew($loan);

        return $loan->id;
    }
}
