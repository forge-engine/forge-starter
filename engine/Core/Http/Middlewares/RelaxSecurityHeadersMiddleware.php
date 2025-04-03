<?php

declare(strict_types=1);

namespace Forge\Core\Http\Middlewares;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

#[Service]
class RelaxSecurityHeadersMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');

        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'";
        $response->setHeader('Content-Security-Policy', $csp);

        return $response;
    }
}
