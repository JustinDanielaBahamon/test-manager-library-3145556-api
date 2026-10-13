<?php

namespace App\Adapters\System;

use App\Application\Ports\IdGenerator;
use Illuminate\Support\Str;

final class UuidV4Generator implements IdGenerator
{
    public function newId(): string
    {
        return (string) Str::uuid(); // UUID v4 (spec-gaps.md G-5)
    }
}
