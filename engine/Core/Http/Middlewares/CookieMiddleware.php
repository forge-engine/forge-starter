<?php

declare(strict_types=1);

namespace Forge\Core\Http\Middlewares;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Cookie;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

#[Service]
class CookieMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        foreach ($response->getCookies() as $cookie) {
            $this->setCookieHeader($response, $cookie);
        }

        return $response;
    }

    private function setCookieHeader(Response $response, Cookie $cookie): void
    {
        $header = sprintf('%s=%s', $cookie->name, $cookie->value);

        $fields = [
            'expires' => $cookie->expires,
            'path' => $cookie->path,
            'domain' => $cookie->domain,
            'secure' => $cookie->secure,
            'httponly' => $cookie->httponly,
            'samesite' => $cookie->samesite
        ];

        foreach ($fields as $key => $value) {
            if ($value !== null && $value !== '') {
                $header .= "; $key=$value";
            }
        }

        $response->setHeader('Set-Cookie', $header);
    }
}
