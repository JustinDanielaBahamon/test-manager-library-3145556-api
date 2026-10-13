<?php

namespace App\Adapters\Http;

use App\Domain\DomainError;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Single error body for every HTTP error (api-contract.md §1, RNF-4):
 * { "code": "...", "message": "...", "details": [ ... ] }
 * Never includes traces, SQL, server paths or passwords.
 */
final class ErrorRenderer
{
    /** api-contract.md §2: business code -> HTTP status. */
    private const STATUS = [
        'INVALID_CREDENTIALS' => 401,
        'UNAUTHENTICATED' => 401,
        'FORBIDDEN_ROLE' => 403,
        'NOT_FOUND' => 404,
        'USERNAME_TAKEN' => 409,
        'ISBN_TAKEN' => 409,
        'DOCUMENT_TAKEN' => 409,
        'BOOK_HAS_OPEN_LOANS' => 409,
        'BOOK_NOT_AVAILABLE' => 409,
        'MEMBER_HAS_OVERDUE' => 409,
        'ITEM_ALREADY_RETURNED' => 409,
        'MEMBER_LIMIT_EXCEEDED' => 422,
        'DUPLICATE_BOOK_IN_LOAN' => 422,
        'VALIDATION_ERROR' => 422,
        'INVALID_DATE_RANGE' => 422,
    ];

    public static function render(Throwable $e): JsonResponse
    {
        if ($e instanceof DomainError) {
            return self::body(self::STATUS[$e->errorCode] ?? 400, $e->errorCode, $e->getMessage(), $e->details);
        }

        if ($e instanceof ValidationException) {
            $details = [];
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $details[] = ['field' => $field, 'issue' => $message];
                }
            }

            return self::body(422, 'VALIDATION_ERROR', 'Los datos enviados no son válidos.', $details);
        }

        if ($e instanceof NotFoundHttpException) {
            return self::body(404, 'NOT_FOUND', 'El recurso solicitado no existe.');
        }

        if ($e instanceof HttpExceptionInterface) {
            return self::body($e->getStatusCode(), 'HTTP_ERROR', 'La solicitud no se pudo procesar.');
        }

        report($e);

        return self::body(500, 'INTERNAL_ERROR', 'Ocurrió un error inesperado.');
    }

    public static function body(int $status, string $code, string $message, array $details = []): JsonResponse
    {
        return new JsonResponse(
            ['code' => $code, 'message' => $message, 'details' => $details],
            $status,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
}
