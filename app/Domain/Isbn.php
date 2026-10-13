<?php

namespace App\Domain;

/** Value object: 13 digits (CA-03.2). */
final class Isbn
{
    private function __construct(public readonly string $value)
    {
    }

    public static function of(string $raw): self
    {
        $value = trim($raw);
        if (! preg_match('/^\d{13}$/', $value)) {
            throw DomainError::validation('isbn', 'debe tener 13 dígitos', 'El ISBN debe tener 13 dígitos.');
        }

        return new self($value);
    }
}
