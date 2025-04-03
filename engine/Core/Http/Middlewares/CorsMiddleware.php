<?php

namespace Forge\Core\Http\Middlewares;

use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Exceptions\InvalidMiddlewareResponse;

#[Service]
class CorsMiddleware extends Middleware
{
    public function __construct(private Config $config)
    {
    }
    /**
     * @throws InvalidMiddlewareResponse
     */
    public function handle(Request $request, callable $next): Response
    {
        $origin = $request->getHeader("Origin");
        $response = $next($request);

        if (!$response instanceof Response) {
            throw new InvalidMiddlewareResponse();
        }
        if ($origin === null) {
            $response->setHeader("Access-Control-Allow-Origin", $this->config->get('app.cors.allowed_origins')[0] ?? "*");
        } else {
            $response->setHeader("Access-Control-Allow-Origin", $origin);
        }
        $response->setHeader(
            "Access-Control-Allow-Methods",
            $this->config->get('app.cors.allowed_methods')[0] ?? "GET, POST, PUT, DELETE, OPTIONS"
        );
        $response->setHeader(
            "Access-Control-Allow-Headers",
            $this->config->get('app.cors.allowed_headers')[0] ?? "Content-Type, Authorization"
        );
        $response->setHeader("Access-Control-Allow-Credentials", "true");
        $response->setHeader("Access-Control-Max-Age", "86400");
        return $response;
    }
}
