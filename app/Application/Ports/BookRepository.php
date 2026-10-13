<?php

namespace App\Application\Ports;

use App\Domain\Book;

interface BookRepository
{
    /** Active books only (ADR-003). */
    public function findActive(string $id): ?Book;

    /** @throws \App\Domain\DomainError ISBN_TAKEN */
    public function add(Book $book): void;

    public function update(Book $book): void;

    public function softDelete(string $id, \DateTimeImmutable $at): void;

    public function hasOpenLoans(string $id): bool;

    public function coverKey(string $id): ?string;

    public function setCoverKey(string $id, ?string $key): void;

    /** Read model used by the HTTP layer (api-contract.md §3.4). */
    public function view(string $id): ?array;

    /** @return array{items: list<array>, total: int} */
    public function search(?string $q, ?string $categoryId, int $page, int $size): array;
}
