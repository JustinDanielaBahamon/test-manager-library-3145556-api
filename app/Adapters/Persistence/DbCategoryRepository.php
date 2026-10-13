<?php

namespace App\Adapters\Persistence;

use App\Application\Ports\CategoryRepository;
use Illuminate\Support\Facades\DB;

final class DbCategoryRepository implements CategoryRepository
{
    public function all(): array
    {
        return DB::table('library.category')->orderBy('name')->get(['id', 'name'])
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->all();
    }

    public function find(string $id): ?array
    {
        $r = DB::table('library.category')->where('id', $id)->first(['id', 'name']);

        return $r ? ['id' => $r->id, 'name' => $r->name] : null;
    }
}
