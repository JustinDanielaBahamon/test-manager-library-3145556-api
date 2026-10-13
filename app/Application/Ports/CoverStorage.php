<?php

namespace App\Application\Ports;

interface CoverStorage
{
    /** Stores the binary and returns an opaque key (never a path). */
    public function put(string $contents, string $extension): string;

    public function get(string $key): ?string;

    public function delete(string $key): void;
}
