<?php

namespace App\Application\Ports;

interface CategoryRepository
{
    /** @return list<array{id: string, name: string}> ordered by name */
    public function all(): array;

    /** @return array{id: string, name: string}|null */
    public function find(string $id): ?array;
}
