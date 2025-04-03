<?php

declare(strict_types=1);

namespace Forge\Database\Contracts;

use PDO;

interface DatabaseDriverInterface
{
    public function connect(): PDO;
}
