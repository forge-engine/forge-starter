<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'down', description: 'Put the app in maintenance mode')]
class MaintenanceDownCommand extends Command
{
    use OutputHelper;

    private const SOURCE = BASE_PATH . '/engine/Core/Http/ErrorPages/maintenance.html';
    private const DESTINATION = BASE_PATH . '/storage/framework/maintenance.html';

    public function execute(array $args): int
    {
        if (file_exists(self::SOURCE)) {
            if (copy(self::SOURCE, self::DESTINATION)) {
                $this->success("Maintenance mode enabled. File copied to");
            } else {
                $this->error("Failed to copy maintenance file");
            }
        } else {
            $this->error("Maintenance file not found at ". self::SOURCE);
        }
        return 0;
    }
}
