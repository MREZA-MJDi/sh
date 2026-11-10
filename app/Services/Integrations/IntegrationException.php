<?php

namespace App\Services\Integrations;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class IntegrationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 422,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $this->getMessage(),
                'code' => 'INTEGRATION_OPERATION_UNAVAILABLE',
            ], $this->httpStatus);
        }

        return back()->withErrors(['integration' => $this->getMessage()])->withInput();
    }
}
