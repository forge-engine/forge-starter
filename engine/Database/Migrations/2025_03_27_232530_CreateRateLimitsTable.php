<?php

declare(strict_types=1);

use Forge\Core\Database\Migrations\Migration;

class CreateRateLimitsTable extends Migration
{
    public function up(): void
    {
        $this->queryBuilder->setTable('rate_limits')
        ->createTable([
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'ip_address' => 'VARCHAR(45) NOT NULL',
            'request_count' => 'INT NOT NULL DEFAULT 1',
            'last_request' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        ]);
        $this->execute($this->queryBuilder->getSql());
    }

    public function down(): void
    {
        $this->execute($this->queryBuilder->dropTable('rate_limits'));
    }
}
