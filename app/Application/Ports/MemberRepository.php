<?php

namespace App\Application\Ports;

use App\Domain\Member;

interface MemberRepository
{
    /** @throws \App\Domain\DomainError DOCUMENT_TAKEN */
    public function add(Member $member): void;

    /** @return array{id: string, full_name: string, document_number: string, active_loans: int}|null */
    public function view(string $id): ?array;

    /** @return array{items: list<array>, total: int} */
    public function search(?string $q, int $page, int $size): array;
}
