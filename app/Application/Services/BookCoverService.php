<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\CoverStorage;
use App\Domain\DomainError;

/** HU-03 CA-03.4: JPG/PNG, max 2 MB, opaque key; without cover the key is null. */
final class BookCoverService
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    public function __construct(
        private readonly BookRepository $books,
        private readonly CoverStorage $storage,
    ) {
    }

    /** $mime is detected from the content, not from the extension (api-contract.md §4). */
    public function upload(string $id, string $contents, string $mime): void
    {
        $this->books->findActive($id) ?? throw DomainError::notFound('El libro');
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => throw DomainError::validation('file', 'solo JPG o PNG', 'La portada debe ser JPG o PNG.'),
        };
        if (strlen($contents) > self::MAX_BYTES) {
            throw DomainError::validation('file', 'máximo 2 MB', 'La portada no puede superar 2 MB.');
        }
        $old = $this->books->coverKey($id);
        $this->books->setCoverKey($id, $this->storage->put($contents, $extension));
        if ($old !== null) {
            $this->storage->delete($old); // binary removed only after the new key is saved
        }
    }

    public function remove(string $id): void
    {
        $this->books->findActive($id) ?? throw DomainError::notFound('El libro');
        $old = $this->books->coverKey($id);
        $this->books->setCoverKey($id, null); // data-model.md §7: first null the key, then delete the binary
        if ($old !== null) {
            $this->storage->delete($old);
        }
    }

    public function read(string $id): string
    {
        $this->books->findActive($id) ?? throw DomainError::notFound('El libro');
        $key = $this->books->coverKey($id) ?? throw DomainError::notFound('La portada');

        return $this->storage->get($key) ?? throw DomainError::notFound('La portada');
    }
}
