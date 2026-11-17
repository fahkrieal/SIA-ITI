<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Response Sukses Standar Envelope (200 OK / 201 Created)
     * Memenuhi skema: success, message, data, errors (null), meta
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Permintaan berhasil diproses.',
        ?array $meta = null,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
            'meta'    => $meta ? [
                'page'  => (int) ($meta['page'] ?? 1),
                'limit' => (int) ($meta['limit'] ?? (is_countable($data) ? count($data) : 1)),
                'total' => (int) ($meta['total'] ?? (is_countable($data) ? count($data) : 1)),
            ] : null,
        ], $statusCode);
    }

    /**
     * Response Gagal Standar Envelope (400, 401, 403, 404, 422, 500)
     * Memenuhi skema: success (false), message, data (null), errors (object), meta (null)
     */
    protected function errorResponse(
        string $message = 'Terjadi kesalahan.',
        mixed $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        $errorObject = null;
        if (is_array($errors) || is_object($errors)) {
            $errorObject = (object) $errors;
        } elseif (is_string($errors)) {
            $errorObject = (object) ['code' => $errors];
        } else {
            $errorObject = (object) ['code' => 'ERROR_' . $statusCode];
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'errors'  => $errorObject,
            'meta'    => null,
        ], $statusCode);
    }
}
