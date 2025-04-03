<?php

declare(strict_types=1);

namespace Forge\Core\Http\Middlewares;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Security\InputSanitizer;

#[Service]
class SanitizeInputMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        InputSanitizer::sanitizeRequest();
        return $next($request);
    }
}
