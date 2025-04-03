<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'storage:link', description: 'Create a symbolic link from "public/storage" to "storage/app"')]
final class StorageLinkCommand extends Command
{
    private const TARGET_PATH = BASE_PATH . '/storage/app';
    private const LINK_PATH = BASE_PATH . '/public/storage';

    public function execute(array $args): int
    {
        $this->checkIfStorageIsLinked();
        $this->ensureTargetDirectoryExist();
        $this->symLinkStorage();

        return 0;
    }

    private function checkIfStorageIsLinked(): int
    {
        if (file_exists(self::LINK_PATH)) {
            $this->info("The [public/storage] link already exists");
            return 0;
        }
        return 0;
    }

    private function ensureTargetDirectoryExist(): int
    {
        if (!self::TARGET_PATH) {
            if (!mkdir(self::TARGET_PATH, 0755, true) && !is_dir(self::TARGET_PATH)) {
                $this->error("Unable to create the [{".self::TARGET_PATH."}]");
                return 1;
            }
            return 0;
        }
        return 0;
    }

    private function symLinkStorage(): int
    {
        if (symlink(self::TARGET_PATH, self::LINK_PATH)) {
            $this->info("The [public/storage] link has been created");
            return 0;
        } else {
            $this->error("Failed to create the [public/storage] link");
            return 1;
        }
        return 0;
    }
}
