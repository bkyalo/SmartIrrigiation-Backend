<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class BaseController extends Controller
{
    /**
     * Success response method.
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendResponse($data, string $message = '', int $code = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response, $code);
    }

    /**
     * Error response method.
     *
     * @param string $error
     * @param array $errorMessages
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendError(string $error, $errorMessages = [], int $code = Response::HTTP_NOT_FOUND): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    /**
     * Handle model not found error.
     *
     * @param string $modelName
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleModelNotFound(string $modelName = 'Resource'): JsonResponse
    {
        return $this->sendError(
            "{$modelName} not found.",
            [],
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Handle validation error.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleValidationError($validator): JsonResponse
    {
        return $this->sendError(
            'Validation Error.',
            $validator->errors(),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /**
     * Handle exception.
     *
     * @param \Throwable $e
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleException(\Throwable $e, string $message = 'Something went wrong'): JsonResponse
    {
        Log::error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        $statusCode = method_exists($e, 'getStatusCode')
            ? $e->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        return $this->sendError(
            config('app.debug') ? $e->getMessage() : $message,
            config('app.debug') ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] : [],
            $statusCode
        );
    }
    // Base controller does not implement resource methods
    // Individual controllers should extend this class and implement their own methods
}
