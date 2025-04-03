<?php

declare(strict_types=1);

namespace Forge\Database\Drivers;

use Forge\Database\Contracts\DatabaseDriverInterface;
use PDO;
use PDOException;
use InvalidArgumentException;

final class PdoDatabaseDriver implements DatabaseDriverInterface
{
    protected array $config;
    protected ?PDO $pdo = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connect(): PDO
    {
        if (!$this->pdo) {
            try {
                $dsn = $this->getDsn();
                $this->pdo = new PDO(
                    $dsn,
                    $this->config['username'] ?? null,
                    $this->config['password'] ?? null,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e) {
                throw new \RuntimeException("Database connection failed: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }

    protected function getDsn(): string
    {
        return match ($this->config['driver']) {
            'sqlite' => "sqlite:" . $this->config['database'],
            'mysql' => "mysql:host={$this->config['host']};dbname={$this->config['database']};charset=utf8mb4",
            'pgsql' => "pgsql:host={$this->config['host']};dbname={$this->config['database']}" ,
            default => throw new InvalidArgumentException("Unsupported database driver: {$this->config['driver']}"),
        };
    }
}
