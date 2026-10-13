<?php

namespace App\Domain;

/** A person who borrows books. Does not log in (spec §1). Members are never deleted (CA-05.3). */
final class Member
{
    private function __construct(
        public readonly string $id,
        public readonly string $fullName,
        public readonly string $documentNumber,
    ) {
    }

    public static function create(string $id, string $fullName, string $documentNumber): self
    {
        $fullName = trim($fullName);
        $documentNumber = trim($documentNumber);
        if ($fullName === '' || mb_strlen($fullName) > 200) {
            throw DomainError::validation('full_name', 'obligatorio, máximo 200 caracteres');
        }
        if ($documentNumber === '' || mb_strlen($documentNumber) > 30) {
            throw DomainError::validation('document_number', 'obligatorio, máximo 30 caracteres');
        }

        return new self($id, $fullName, $documentNumber);
    }
}
