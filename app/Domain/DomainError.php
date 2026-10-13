<?php

namespace App\Domain;

/**
 * Business error. Carries the contract code (api-contract.md §2) and a user message in Spanish.
 * The HTTP status is decided by the HTTP adapter, not by the domain.
 */
final class DomainError extends \DomainException
{
    /** @param array<int, array{field: string, issue: string}> $details */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public static function notFound(string $what = 'El recurso'): self
    {
        return new self('NOT_FOUND', "{$what} no existe.");
    }

    public static function validation(string $field, string $issue, string $message = 'Los datos enviados no son válidos.'): self
    {
        return new self('VALIDATION_ERROR', $message, [['field' => $field, 'issue' => $issue]]);
    }
}
