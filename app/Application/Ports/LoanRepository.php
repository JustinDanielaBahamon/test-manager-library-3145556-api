<?php

namespace App\Application\Ports;

use App\Domain\Loan;

interface LoanRepository
{
    public function unreturnedCountForMember(string $memberId): int;

    public function memberHasOverdue(string $memberId, \DateTimeImmutable $now): bool;

    /**
     * Withdraws one copy per line and stores the loan in ONE transaction (CA-06.3).
     * @throws \App\Domain\DomainError BOOK_NOT_AVAILABLE — nothing is stored
     */
    public function saveNew(Loan $loan): void;

    /**
     * Sets returned_at and restocks one copy in one transaction (CA-07.1, CA-07.2).
     * @throws \App\Domain\DomainError NOT_FOUND | ITEM_ALREADY_RETURNED
     */
    public function returnItem(string $loanId, string $itemId, \DateTimeImmutable $at): void;

    public function view(string $loanId, \DateTimeImmutable $now): ?array;

    /** @return array{items: list<array>, total: int} */
    public function search(?\DateTimeImmutable $from, ?\DateTimeImmutable $toExclusive, ?string $status, ?string $memberId, \DateTimeImmutable $now, int $page, int $size): array;

    /** @return array{items: list<array>, total: int} */
    public function overdue(\DateTimeImmutable $now, int $page, int $size): array;

    public function itemView(string $itemId): ?array;
}
