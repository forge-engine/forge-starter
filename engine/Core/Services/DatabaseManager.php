<?php

declare(strict_types=1);

namespace Forge\Core\Database;

use Forge\Core\DI\Attributes\Service;
use Forge\Database\Contracts\DatabaseDriverInterface;
use RuntimeException;

#[Service]
class DatabaseManager
{
    private array $connections = [];
    private ?string $defaultConnection = null;

    public function addConnection(string $name, DatabaseDriverInterface $driver, bool $isDefault = false): void
    {
        $this->connections[$name] = $driver;
        if ($isDefault || $this->defaultConnection === null) {
            $this->defaultConnection = $name;
        }
    }

    public function getConnection(?string $name = null): DatabaseDriverInterface
    {
        $name = $name ?? $this->defaultConnection;

        if (!$name || !isset($this->connections[$name])) {
            throw new RuntimeException("Database connection '$name' not found.");
        }

        return $this->connections[$name];
    }

    public function getDefaultConnection(): DatabaseDriverInterface
    {
        return $this->getConnection($this->defaultConnection);
    }
}
