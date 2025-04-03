<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'up', description: 'Disable maintenance mode')]
class MaintenanceUpCommand extends Command
{
    use OutputHelper;

    private const FILE = BASE_PATH . '/storage/framework/maintenance.html';

    public function execute(array $args): int
    {
        if (file_exists(self::FILE)) {
            if (unlink(self::FILE)) {
                $this->success("Maintenance mode disabled. File deleted from");
            } else {
                $this->error("Failed to delete maintenance file");
            }
        } else {
            $this->error("Maintenance file not found at ". self::FILE);
        }
        return 0;
    }
}
