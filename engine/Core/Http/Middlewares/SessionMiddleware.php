<?php

declare(strict_types=1);

namespace Forge\Core\Http\Middlewares;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Session\SessionInterface;

#[Service]
class SessionMiddleware extends Middleware
{
    public function __construct(
        private SessionInterface $session
    ) {
    }
    public function handle(Request $request, callable $next): Response
    {
        $this->session->start();

        try {
            $response = $next($request);
        } finally {
            $this->session->save();
        }

        return $response;
    }
}
