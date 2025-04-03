<?php

declare(strict_types=1);

namespace Forge\Core\Http\Middlewares;

use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\ApiResponse;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

#[Service]
class ApiKeyMiddleware extends Middleware
{
    public function __construct(private Config $config)
    {
    }
    public function handle(Request $request, callable $next): Response
    {
        $validKeys = $this->config->get('security.api_keys', []);
        $apiKey = $request->getHeader('X-API-KEY', null);

        if (!$apiKey || !in_array($apiKey, $validKeys, true)) {
            return new ApiResponse(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
