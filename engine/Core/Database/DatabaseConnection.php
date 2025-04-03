<?php

declare(strict_types=1);

namespace Forge\Core\Database;

use Forge\Database\Contracts\DatabaseDriverInterface;
use PDO;

final class Connection
{
    protected DatabaseDriverInterface $driver;

    public function __construct(DatabaseDriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function getPdo(): PDO
    {
        return $this->driver->connect();
    }
}
