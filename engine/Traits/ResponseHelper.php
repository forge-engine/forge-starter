<?php

declare(strict_types=1);

namespace Forge\Traits;

use Forge\Core\Http\ApiResponse;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

trait ResponseHelper
{
    private function createErrorResponse(Request $request, string $errorMessage = 'Too Many Requests', int $statusCode = 429): Response
    {
        if ($request->getHeader('Accept') === 'application/json') {
            return new ApiResponse(['error' => $errorMessage], $statusCode);
        }
        return new Response($errorMessage, $statusCode);
    }

    private function createResponse(Request $request, mixed $content, int $statusCode = 200): Response
    {
        if ($request->getHeader('Accept') === 'application/json') {
            return new ApiResponse(['data' => $content], $statusCode);
        }
        return (new Response($content, $statusCode))->setHeader('Content-Type', 'text/html');
    }
}
