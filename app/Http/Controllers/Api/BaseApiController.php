<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    protected function success($data, string $message = 'success', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'request_id' => (string) (request()->attributes->get('request_id') ?? ''),
            'data' => $data,
        ], $code);
    }

    protected function error(
        string $message,
        int $code = 400,
        $errors = null,
        ?string $requiredPermission = null,
        ?string $errorCode = null
    ): JsonResponse
    {
        $payload = [
            'status' => 'error',
            'message' => $message,
            'request_id' => (string) (request()->attributes->get('request_id') ?? ''),
        ];

        if ($errorCode !== null) {
            $payload['error_code'] = $errorCode;
        }

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        if ($requiredPermission !== null) {
            $payload['required_permission'] = $requiredPermission;
        }

        return response()->json($payload, $code);
    }

    protected function paginated($resource): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $resource->items(),
            'meta' => [
                'current_page' => $resource->currentPage(),
                'last_page' => $resource->lastPage(),
                'per_page' => $resource->perPage(),
                'total' => $resource->total(),
            ],
        ]);
    }
}

