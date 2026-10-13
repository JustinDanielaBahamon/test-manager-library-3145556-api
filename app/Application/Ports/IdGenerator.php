<?php

namespace App\Application\Ports;

interface IdGenerator
{
    /** UUID v4 as text (api-contract.md §1). */
    public function newId(): string;
}
