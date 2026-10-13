<?php

namespace App\Adapters\Http;

use Illuminate\Http\Request;

/** api-contract.md §1: ?page=1&size=20, size max 50, response { items, page, size, total }. */
final class Pagination
{
    /** @return array{0: int, 1: int} */
    public static function read(Request $request): array
    {
        $data = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'size' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        return [(int) ($data['page'] ?? 1), (int) ($data['size'] ?? 20)];
    }

    public static function response(array $result, int $page, int $size): array
    {
        return ['items' => $result['items'], 'page' => $page, 'size' => $size, 'total' => $result['total']];
    }
}
