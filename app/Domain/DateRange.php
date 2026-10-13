<?php

namespace App\Domain;

/** Value object: inclusive date range, end >= start (CA-10.2). */
final class DateRange
{
    private function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
    ) {
    }

    public static function of(string $from, string $to): self
    {
        $utc = new \DateTimeZone('UTC');
        $f = \DateTimeImmutable::createFromFormat('!Y-m-d', $from, $utc);
        $t = \DateTimeImmutable::createFromFormat('!Y-m-d', $to, $utc);
        if ($f === false || $t === false) {
            throw DomainError::validation('from', 'formato de fecha AAAA-MM-DD');
        }
        if ($t < $f) {
            throw new DomainError('INVALID_DATE_RANGE', 'La fecha final no puede ser anterior a la inicial.');
        }

        return new self($f, $t);
    }

    /** First instant after the range, so "to" is inclusive (spec-gaps.md G-8). */
    public function endExclusive(): \DateTimeImmutable
    {
        return $this->to->modify('+1 day');
    }
}
