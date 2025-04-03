<?php

declare(strict_types=1);

use Forge\Core\Database\Migrations\Migration;

class CreateCircuitBreakerTable extends Migration
{
    public function up(): void
    {
        $this->queryBuilder->setTable('circuit_breaker')
            ->createTable([
                'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                'ip_address' => 'VARCHAR(45) NOT NULL',
                'fail_count' => 'INT NOT NULL DEFAULT 1',
                'first_failure' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ]);
        $this->execute($this->queryBuilder->getSql());
    }

    public function down(): void
    {
        $this->execute($this->queryBuilder->dropTable('circuit_breaker'));
    }
}
