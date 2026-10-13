<?php

namespace App\Adapters\Persistence;

use App\Application\Ports\BookRepository;
use App\Domain\Book;
use App\Domain\DomainError;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class DbBookRepository implements BookRepository
{
    public function findActive(string $id): ?Book
    {
        $r = DB::table('library.book')->where('id', $id)->whereNull('deleted_at')->first();

        return $r ? Book::restore($r->id, $r->title, $r->author, $r->isbn, $r->category_id, (int) $r->total_copies, (int) $r->available_copies) : null;
    }

    public function add(Book $book): void
    {
        try {
            DB::table('library.book')->insert([
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'isbn' => $book->isbn->value,
                'category_id' => $book->categoryId,
                'total_copies' => $book->totalCopies,
                'available_copies' => $book->availableCopies,
                'cover_key' => null,
                'deleted_at' => null,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new DomainError('ISBN_TAKEN', 'Ya existe un libro activo con ese ISBN.');
        }
    }

    public function update(Book $book): void
    {
        // The difference is applied in SQL so a concurrent loan is not overwritten; the CHECK is the last barrier.
        $current = DB::table('library.book')->where('id', $book->id)->value('total_copies');
        $delta = $book->totalCopies - (int) $current;
        $affected = DB::update(
            'UPDATE library.book SET title = ?, author = ?, category_id = ?, total_copies = ?, available_copies = available_copies + ?
             WHERE id = ? AND deleted_at IS NULL AND available_copies + ? >= 0',
            [$book->title, $book->author, $book->categoryId, $book->totalCopies, $delta, $book->id, $delta]
        );
        if ($affected === 0) {
            throw DomainError::validation('total_copies', 'dejaría ejemplares disponibles en negativo',
                'No se puede reducir el total por debajo de los ejemplares prestados.');
        }
    }

    public function softDelete(string $id, \DateTimeImmutable $at): void
    {
        DB::table('library.book')->where('id', $id)->update(['deleted_at' => $at->format('Y-m-d H:i:sP')]);
    }

    public function hasOpenLoans(string $id): bool
    {
        return DB::table('library.loan_item')->where('book_id', $id)->whereNull('returned_at')->exists();
    }

    public function coverKey(string $id): ?string
    {
        return DB::table('library.book')->where('id', $id)->value('cover_key');
    }

    public function setCoverKey(string $id, ?string $key): void
    {
        DB::table('library.book')->where('id', $id)->update(['cover_key' => $key]);
    }

    public function view(string $id): ?array
    {
        $r = $this->baseQuery()->where('b.id', $id)->first();

        return $r ? self::toView($r) : null;
    }

    public function search(?string $q, ?string $categoryId, int $page, int $size): array
    {
        $query = $this->baseQuery();
        if ($q !== null && trim($q) !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($q)).'%';
            $query->where(fn ($w) => $w->where('b.title', 'ilike', $like)->orWhere('b.author', 'ilike', $like));
        }
        if ($categoryId !== null && $categoryId !== '') {
            $query->where('b.category_id', $categoryId);
        }
        $total = (clone $query)->count();
        $rows = $query->orderBy('b.title')->offset(($page - 1) * $size)->limit($size)->get();

        return ['items' => $rows->map(fn ($r) => self::toView($r))->all(), 'total' => $total];
    }

    private function baseQuery()
    {
        return DB::table('library.book as b')
            ->join('library.category as c', 'c.id', '=', 'b.category_id')
            ->whereNull('b.deleted_at')
            ->select('b.*', 'c.name as category_name');
    }

    private static function toView(object $r): array
    {
        return [
            'id' => $r->id,
            'title' => $r->title,
            'author' => $r->author,
            'isbn' => $r->isbn,
            'category' => ['id' => $r->category_id, 'name' => $r->category_name],
            'total_copies' => (int) $r->total_copies,
            'available_copies' => (int) $r->available_copies,
            'cover_key' => $r->cover_key,
        ];
    }
}
