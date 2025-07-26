<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Handle API requests
        if ($request->is('api/*') || $request->wantsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException($request, Throwable $e): JsonResponse
    {
        $e = $this->prepareException($e);

        if ($e instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($e, $request);
        }

        if ($e instanceof ModelNotFoundException) {
            $model = strtolower(class_basename($e->getModel()));
            return response()->json([
                'success' => false,
                'message' => "Unable to locate the specified {$model}.",
                'errors' => ['resource_not_found' => ["The requested resource was not found."]],
            ], Response::HTTP_NOT_FOUND);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'errors' => ['unauthenticated' => ['You are not authenticated.']],
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
                'errors' => ['unauthorized' => ['You are not authorized to perform this action.']],
            ], Response::HTTP_FORBIDDEN);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'The specified method for the request is invalid.',
                'errors' => ['method_not_allowed' => ['The requested method is not supported for this route.']],
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'The specified URL cannot be found.',
                'errors' => ['not_found' => ['The requested resource was not found.']],
            ], Response::HTTP_NOT_FOUND);
        }

        if ($e instanceof HttpException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['http_error' => [$e->getMessage()]],
            ], $e->getStatusCode());
        }

        // Default error response
        $statusCode = method_exists($e, 'getStatusCode') 
            ? $e->getStatusCode() 
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $response = [
            'success' => false,
            'message' => 'An error occurred while processing your request.',
            'errors' => [
                'error' => [$e->getMessage()],
            ]
        ];

        // Add debug info if in development
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        if ($e->response) {
            return $e->response;
        }

        return response()->json([
            'success' => false,
            'message' => 'The given data was invalid.',
            'errors' => $e->errors(),
        ], $e->status);
    }
}
