<?php

namespace App\Adapters\Persistence;

/** Converts between PostgreSQL timestamptz and the contract format (ISO 8601 UTC with Z). */
final class Time
{
    public static function toDb(\DateTimeImmutable $t): string
    {
        return $t->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');
    }

    public static function fromDb(?string $value): ?\DateTimeImmutable
    {
        return $value === null ? null : (new \DateTimeImmutable($value))->setTimezone(new \DateTimeZone('UTC'));
    }

    public static function iso(?\DateTimeImmutable $t): ?string
    {
        return $t?->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
