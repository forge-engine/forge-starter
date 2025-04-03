<?php

declare(strict_types=1);

namespace Forge\Core\Http\Middlewares;

use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Config\Config;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Traits\ResponseHelper;

#[Service]
class RateLimitMiddleware extends Middleware
{
    use OutputHelper;
    use ResponseHelper;

    public function __construct(private Config $config, private QueryBuilder $queryBuilder)
    {
    }
    public function handle(Request $request, callable $next): Response
    {
        $queryBuilder = clone $this->queryBuilder;

        $maxRequests = $this->config->get('security.rate_limit.max_requests', 2);
        $timeWindow = $this->config->get('security.rate_limit.time_window', 60);
        $clientIp = $request->getClientIp();
        $table = 'rate_limits';
        $now = time();

        $record = $queryBuilder->setTable($table)
            ->select('*')
            ->where('ip_address', '=', $clientIp)
            ->first();

        if ($record) {
            $timeDiff = $now - strtotime($record['last_request']);

            if ($timeDiff >= $timeWindow) {
                $this->resetRequestCount($record['id'], $queryBuilder);
            } elseif ($record['request_count'] >= $maxRequests) {
                return $this->createErrorResponse($request);
            } else {
                $this->incrementRequestCount($record['id'], $record, $queryBuilder);
            }
        } else {
            $this->createNewRateLimitRecord($clientIp, $queryBuilder);
        }

        return $next($request);
    }

    private function resetRequestCount(int $recordId, QueryBuilder $queryBuilder): void
    {
        $queryBuilder->setTable('rate_limits')
            ->where('id', '=', $recordId)
            ->update([
                'request_count' => 1,
                'last_request' => date('Y-m-d H:i:s'),
            ]);
    }

    private function incrementRequestCount(int $recordId, object|array $record, QueryBuilder $queryBuilder): void
    {
        $queryBuilder->setTable('rate_limits')
            ->where('id', '=', $recordId)
            ->update([
                'request_count' => $record['request_count'] + 1,
                'last_request' => date('Y-m-d H:i:s'),
            ]);
    }

    private function createNewRateLimitRecord(string $clientIp, QueryBuilder $queryBuilder): void
    {
        $queryBuilder->setTable('rate_limits')
            ->insert([
                'ip_address' => $clientIp,
                'request_count' => 1,
                'last_request' => date('Y-m-d H:i:s'),
            ]);
    }
}
