<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'storage:unlink', description: 'Remove the symbolic link from "public/storage"')]
final class StorageUnlinkCommand extends Command
{
    private const LINK_PATH = BASE_PATH . '/public/storage';

    public function execute(array $args): int
    {
        if (file_exists(self::LINK_PATH)) {
            if (is_link(self::LINK_PATH)) {
                if (unlink(self::LINK_PATH)) {
                    $this->info("The [public/storage] link has been removed.");
                    return 0;
                } else {
                    $this->error("Failed to remove the [public/storage] link.");
                    return 1;
                }
            } else {
                $this->error("The [public/storage] path is not a symbolic link.");
                return 1;
            }
        } else {
            $this->info("The [public/storage] link does not exist.");
            return 0;
        }
    }
}
