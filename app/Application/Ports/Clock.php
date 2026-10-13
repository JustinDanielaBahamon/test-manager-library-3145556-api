<?php

namespace App\Application\Ports;

interface Clock
{
    /** Current instant in UTC (D-05). */
    public function now(): \DateTimeImmutable;
}
