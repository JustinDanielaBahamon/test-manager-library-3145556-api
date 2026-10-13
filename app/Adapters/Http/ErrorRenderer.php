<?php

namespace App\Adapters\Http;

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
    public static function render(Throwable $e): JsonResponse
    {
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
