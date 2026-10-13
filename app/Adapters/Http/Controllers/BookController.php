<?php

namespace App\Adapters\Http\Controllers;

use App\Adapters\Http\Pagination;
use App\Application\Ports\BookRepository;
use App\Application\Services\BookCoverService;
use App\Application\Services\CreateBookService;
use App\Application\Services\DeleteBookService;
use App\Application\Services\UpdateBookService;
use App\Domain\DomainError;
use Illuminate\Http\Request;

final class BookController
{
    public function __construct(private readonly BookRepository $books)
    {
    }

    /** GET /books?q=&category_id=&page=&size= */
    public function index(Request $request)
    {
        [$page, $size] = Pagination::read($request);
        $request->validate(['q' => ['sometimes', 'nullable', 'string'], 'category_id' => ['sometimes', 'nullable', 'uuid']]);
        $result = $this->books->search($request->query('q'), $request->query('category_id'), $page, $size);

        return response()->json(Pagination::response($result, $page, $size));
    }

    /** GET /books/{id} — 404 when missing or soft-deleted (CA-04.3) */
    public function show(string $id)
    {
        return response()->json($this->books->view($id) ?? throw DomainError::notFound('El libro'));
    }

    /** POST /books (admin) */
    public function store(Request $request, CreateBookService $create)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'author' => ['required', 'string'],
            'isbn' => ['required', 'string'],
            'category_id' => ['required', 'uuid'],
            'total_copies' => ['required', 'integer'],
        ]);
        $id = $create->execute($data['title'], $data['author'], $data['isbn'], $data['category_id'], (int) $data['total_copies']);

        return response()->json($this->books->view($id), 201);
    }

    /** PUT /books/{id} (admin) — replaces title, author, category and total */
    public function update(Request $request, string $id, UpdateBookService $update)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'author' => ['required', 'string'],
            'category_id' => ['required', 'uuid'],
            'total_copies' => ['required', 'integer'],
        ]);
        $update->execute($id, $data['title'], $data['author'], $data['category_id'], (int) $data['total_copies']);

        return response()->json($this->books->view($id));
    }

    /** DELETE /books/{id} (admin) — soft delete */
    public function destroy(string $id, DeleteBookService $delete)
    {
        $delete->execute($id);

        return response()->noContent();
    }

    /** PUT /books/{id}/cover (admin) — multipart field "file" */
    public function uploadCover(Request $request, string $id, BookCoverService $covers)
    {
        $file = $request->file('file');
        if ($file === null || ! $file->isValid()) {
            throw DomainError::validation('file', 'obligatorio');
        }
        $contents = (string) file_get_contents($file->getRealPath());
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: '';
        $covers->upload($id, $contents, $mime);

        return response()->json($this->books->view($id));
    }

    /** DELETE /books/{id}/cover (admin) */
    public function deleteCover(string $id, BookCoverService $covers)
    {
        $covers->remove($id);

        return response()->noContent();
    }

    /** GET /books/{id}/cover — binary or 404 (spec-gaps.md G-3) */
    public function cover(string $id, BookCoverService $covers)
    {
        $contents = $covers->read($id);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'application/octet-stream';

        return response($contents, 200, ['Content-Type' => $mime]);
    }
}
