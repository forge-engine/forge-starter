<?php

declare(strict_types=1);

namespace Forge\Core\Http\Middlewares;

use Forge\Core\Config\Config;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Traits\ResponseHelper;

#[Service]
class CircuitBreakerMiddleware extends Middleware
{
    use ResponseHelper;

    public function __construct(private Config $config, private QueryBuilder $queryBuilder)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $maintenacePage = file_get_contents(BASE_PATH . "/engine/Core/Http/ErrorPages/maintenance.html");
        $queryBuilder = clone $this->queryBuilder;

        $maxFailures = $this->config->get('security.circuit_breaker.max_failures', 5);
        $resetTime = $this->config->get('security.circuit_breaker.reset_time', 300);

        $clientIp = $request->getClientIp();
        $now = time();
        $table = 'circuit_breaker';

        $record = $queryBuilder->setTable($table)
            ->select('*')
            ->where('ip_address', '=', $clientIp)
            ->first();

        if ($record) {
            $failCount = $record['fail_count'];
            $firstFailureTime = strtotime($record['first_failure']);

            if ($failCount >= $maxFailures && ($now - $firstFailureTime) < $resetTime) {
                return $this->createErrorResponse($request, $maintenacePage, 503);
            }

            if (($now - $firstFailureTime) >= $resetTime) {
                $this->resetFailureCount($record['id'], $queryBuilder);
            }
        }

        $response = $next($request);

        if ($response->getStatusCode() >= 500) {
            if ($record) {
                $this->incrementFailureCount($record['id'], $record, $queryBuilder);
            } else {
                $this->createNewFailureRecord($clientIp, $queryBuilder);
            }
        }

        return $response;
    }

    private function resetFailureCount(int $recordId, QueryBuilder $queryBuilder): void
    {
        $queryBuilder->setTable('circuit_breaker')
            ->where('id', '=', $recordId)
            ->update([
                'fail_count' => 1,
                'first_failure' => date('Y-m-d H:i:s'),
            ]);
    }

    private function incrementFailureCount(int $recordId, object|array $record, QueryBuilder $queryBuilder): void
    {
        $queryBuilder->setTable('circuit_breaker')
            ->where('id', '=', $recordId)
            ->update([
                'fail_count' => $record['fail_count'] + 1,
                'first_failure' => date('Y-m-d H:i:s'),
            ]);
    }

    private function createNewFailureRecord(string $clientIp, QueryBuilder $queryBuilder): void
    {
        $queryBuilder->setTable('circuit_breaker')
            ->insert([
                'ip_address' => $clientIp,
                'fail_count' => 1,
                'first_failure' => date('Y-m-d H:i:s'),
            ]);
    }
}
