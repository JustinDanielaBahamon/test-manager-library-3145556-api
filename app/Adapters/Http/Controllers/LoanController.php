<?php

namespace App\Adapters\Http\Controllers;

use App\Adapters\Http\Pagination;
use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Application\Services\LendBooksService;
use App\Application\Services\ReturnItemService;
use App\Domain\DateRange;
use App\Domain\DomainError;
use Illuminate\Http\Request;

final class LoanController
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly Clock $clock,
    ) {
    }

    /** POST /loans */
    public function store(Request $request, LendBooksService $lend)
    {
        $data = $request->validate([
            'member_id' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:3'],
            'items.*.book_id' => ['required', 'uuid'],
        ]);
        $id = $lend->execute(
            $data['member_id'],
            (string) $request->attributes->get('auth_user_id'),
            array_map(fn ($i) => $i['book_id'], $data['items'])
        );

        return response()->json($this->loans->view($id, $this->clock->now()), 201);
    }

    /** GET /loans?from=&to=&status=&member_id=&page=&size= */
    public function index(Request $request)
    {
        [$page, $size] = Pagination::read($request);
        $f = $request->validate([
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'in:open,overdue,closed'],
            'member_id' => ['sometimes', 'uuid'],
        ]);
        $from = isset($f['from']) ? new \DateTimeImmutable($f['from'].' 00:00:00', new \DateTimeZone('UTC')) : null;
        $toExclusive = isset($f['to']) ? new \DateTimeImmutable($f['to'].' 00:00:00 +1 day', new \DateTimeZone('UTC')) : null;
        if (isset($f['from'], $f['to'])) {
            DateRange::of($f['from'], $f['to']);
        }
        $result = $this->loans->search($from, $toExclusive, $f['status'] ?? null, $f['member_id'] ?? null, $this->clock->now(), $page, $size);

        return response()->json(Pagination::response($result, $page, $size));
    }

    /** GET /loans/{id} — lines plus computed status */
    public function show(string $id)
    {
        return response()->json($this->loans->view($id, $this->clock->now()) ?? throw DomainError::notFound('El préstamo'));
    }

    /** POST /loans/{loan_id}/items/{item_id}/return */
    public function returnItem(string $loanId, string $itemId, ReturnItemService $return)
    {
        return response()->json($return->execute($loanId, $itemId));
    }

    /** GET /loans/overdue — most days late first */
    public function overdue(Request $request)
    {
        [$page, $size] = Pagination::read($request);
        $result = $this->loans->overdue($this->clock->now(), $page, $size);

        return response()->json(Pagination::response($result, $page, $size));
    }
}
