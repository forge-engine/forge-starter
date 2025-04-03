<?php

declare(strict_types=1);

namespace Forge\Traits;

use Forge\Core\Autoloader;

trait NamespaceHelper
{
    private function getNamespaceFromFile(string $filePath, string $basePath): ?string
    {
        $content = file_get_contents($filePath);
        if (preg_match('#^namespace\s+(.+?);#sm', $content, $match)) {
            return trim($match[1]);
        }
        return null;
    }

    private function registerModuleAutoloadPath(string $moduleName, string $modulePath): void
    {
        $moduleNamespacePrefix = 'App\\Modules\\' . str_replace('-', '\\', $moduleName);
        Autoloader::addPath($moduleNamespacePrefix . '\\', $modulePath . '/src/');
    }
}
