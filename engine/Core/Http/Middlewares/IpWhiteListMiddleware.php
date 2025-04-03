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
class IpWhiteListMiddleware extends Middleware
{
    public function __construct(private Config $config)
    {
    }
    public function handle(Request $request, callable $next): Response
    {
        $allowedIps = $this->config->get('security.ip_whitelist');
        $clientIp = $request->getClientIp();

        if (!in_array($clientIp, $allowedIps, true)) {
            return new ApiResponse(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
