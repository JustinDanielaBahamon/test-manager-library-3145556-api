<?php

namespace App\Adapters\Persistence;

use App\Application\Ports\MemberRepository;
use App\Domain\DomainError;
use App\Domain\Member;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class DbMemberRepository implements MemberRepository
{
    public function add(Member $member): void
    {
        try {
            DB::table('library.member')->insert([
                'id' => $member->id,
                'full_name' => $member->fullName,
                'document_number' => $member->documentNumber,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new DomainError('DOCUMENT_TAKEN', 'Ya existe un socio con ese documento.');
        }
    }

    public function view(string $id): ?array
    {
        $r = DB::table('library.member')->where('id', $id)->first();
        if (! $r) {
            return null;
        }
        // active_loans is computed, not stored (Article VII, spec-gaps.md G-9)
        $active = DB::table('library.loan_item as i')->join('library.loan as l', 'l.id', '=', 'i.loan_id')
            ->where('l.member_id', $id)->whereNull('i.returned_at')->count();

        return self::toView($r) + ['active_loans' => $active];
    }

    public function search(?string $q, int $page, int $size): array
    {
        $query = DB::table('library.member');
        if ($q !== null && trim($q) !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($q)).'%';
            $query->where(fn ($w) => $w->where('full_name', 'ilike', $like)->orWhere('document_number', 'ilike', $like));
        }
        $total = (clone $query)->count();
        $rows = $query->orderBy('full_name')->offset(($page - 1) * $size)->limit($size)->get();

        return ['items' => $rows->map(fn ($r) => self::toView($r))->all(), 'total' => $total];
    }

    private static function toView(object $r): array
    {
        return ['id' => $r->id, 'full_name' => $r->full_name, 'document_number' => $r->document_number];
    }
}
