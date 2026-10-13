<?php

namespace App\Application\Services;

use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;

/** HU-07: one line at a time; never restocks twice. */
final class ReturnItemService
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly Clock $clock,
    ) {
    }

    public function execute(string $loanId, string $itemId): array
    {
        $this->loans->returnItem($loanId, $itemId, $this->clock->now());

        return $this->loans->itemView($itemId);
    }
}
