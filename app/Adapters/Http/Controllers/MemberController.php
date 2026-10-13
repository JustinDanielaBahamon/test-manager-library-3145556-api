<?php

namespace App\Adapters\Http\Controllers;

use App\Adapters\Http\Pagination;
use App\Application\Ports\MemberRepository;
use App\Application\Services\CreateMemberService;
use App\Domain\DomainError;
use Illuminate\Http\Request;

final class MemberController
{
    public function __construct(private readonly MemberRepository $members)
    {
    }

    /** GET /members?q=&page=&size= */
    public function index(Request $request)
    {
        [$page, $size] = Pagination::read($request);
        $result = $this->members->search($request->query('q'), $page, $size);

        return response()->json(Pagination::response($result, $page, $size));
    }

    /** GET /members/{id} — includes active_loans */
    public function show(string $id)
    {
        return response()->json($this->members->view($id) ?? throw DomainError::notFound('El socio'));
    }

    /** POST /members */
    public function store(Request $request, CreateMemberService $create)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string'],
            'document_number' => ['required', 'string'],
        ]);
        $id = $create->execute($data['full_name'], $data['document_number']);

        return response()->json($this->members->view($id), 201);
    }
}
