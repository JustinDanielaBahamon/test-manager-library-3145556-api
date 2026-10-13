<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\Clock;
use App\Domain\DomainError;

/** HU-03 CA-03.5 / ADR-003: soft delete; not allowed with open loans. */
final class DeleteBookService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly Clock $clock,
    ) {
    }

    public function execute(string $id): void
    {
        $this->books->findActive($id) ?? throw DomainError::notFound('El libro');
        if ($this->books->hasOpenLoans($id)) {
            throw new DomainError('BOOK_HAS_OPEN_LOANS', 'El libro tiene préstamos abiertos y no se puede dar de baja.');
        }
        $this->books->softDelete($id, $this->clock->now());
    }
}
