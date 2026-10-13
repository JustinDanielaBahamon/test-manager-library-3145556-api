<?php

namespace App\Domain;

/**
 * A loan is an immutable fact: who borrowed, who registered it and when (CA-06.7).
 * Only returned_at of a line changes afterwards. The status is computed, never stored (Article VII).
 */
final class Loan
{
    public const LOAN_DAYS = 14;          // RN-2
    public const MAX_UNRETURNED = 3;      // RN-2
    public const MAX_ITEMS = 3;           // CA-06.1

    /** @param list<LoanItem> $items */
    private function __construct(
        public readonly string $id,
        public readonly string $memberId,
        public readonly string $loanedByUserId,
        public readonly \DateTimeImmutable $loanedAt,
        public readonly array $items,
    ) {
    }

    /**
     * @param list<array{id: string, book_id: string, book_title: string, isbn: string, category_name: string}> $lines
     *        values frozen at this instant (CA-06.6, RN-4)
     */
    public static function open(string $id, string $memberId, string $userId, \DateTimeImmutable $now, array $lines, int $memberUnreturned, bool $memberHasOverdue): self
    {
        if (count($lines) < 1 || count($lines) > self::MAX_ITEMS) {
            throw DomainError::validation('items', 'de 1 a 3 libros', 'Un préstamo debe tener de 1 a 3 libros.');
        }
        $bookIds = array_column($lines, 'book_id');
        if (count($bookIds) !== count(array_unique($bookIds))) {
            throw new DomainError('DUPLICATE_BOOK_IN_LOAN', 'No se puede repetir el mismo libro en un préstamo.');
        }
        if ($memberHasOverdue) {
            throw new DomainError('MEMBER_HAS_OVERDUE', 'El socio tiene libros vencidos y no puede pedir otro.');
        }
        if ($memberUnreturned + count($lines) > self::MAX_UNRETURNED) {
            throw new DomainError(
                'MEMBER_LIMIT_EXCEEDED',
                "El socio ya tiene {$memberUnreturned} libro(s) sin devolver; el máximo es ".self::MAX_UNRETURNED.'.',
                [['field' => 'items', 'issue' => "sin devolver actualmente: {$memberUnreturned}"]]
            );
        }
        $due = $now->modify('+'.self::LOAN_DAYS.' days');
        $items = array_map(
            fn (array $l) => new LoanItem($l['id'], $l['book_id'], $l['book_title'], $l['isbn'], $l['category_name'], $due, null),
            $lines
        );

        return new self($id, $memberId, $userId, $now, $items);
    }

    /**
     * open: some line not returned and none overdue; overdue: some line not returned and past due; closed: all returned.
     * @param list<array{due_at: \DateTimeImmutable, returned_at: ?\DateTimeImmutable}> $lines
     */
    public static function statusOf(array $lines, \DateTimeImmutable $now): string
    {
        $pending = array_filter($lines, fn ($l) => $l['returned_at'] === null);
        if ($pending === []) {
            return 'closed';
        }
        foreach ($pending as $l) {
            if ($l['due_at'] < $now) {
                return 'overdue';
            }
        }

        return 'open';
    }
}
