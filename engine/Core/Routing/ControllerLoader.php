<?php

declare(strict_types=1);

namespace Forge\Core\Routing;

use Forge\Core\DI\Container;

final class ControllerLoader
{
    public function __construct(
        private Container $container,
        private array $controllerDirs = []
    ) {
    }

    /** Auto-register controllers from directory
     * @throws \ReflectionException
     */
    public function registerControllers(): array
    {
        $registeredControllers = [];

        foreach ($this->controllerDirs as $dir) {
            if (is_dir($dir)) {
                foreach (glob("$dir/*Controller.php", GLOB_BRACE) as $file) {
                    $class = $this->fileToClass($file, $dir);
                    if ($class) {
                        $this->container->register($class);
                        $registeredControllers[] = $class;
                    }
                }
            }
        }

        return $registeredControllers;
    }

    private function fileToClass(string $file, string $baseDir): ?string
    {
        $relativePath = str_replace($baseDir, '', $file);
        $class = str_replace(['/', '.php'], ['\\', ''], trim($relativePath, '/'));

        if (str_starts_with($baseDir, BASE_PATH . "/app/Controllers")) {
            return "App\\Controllers\\$class";
        }

        if (preg_match('#modules/([^/]+)/src/Controllers#', $baseDir, $matches)) {
            return "App\\Modules\\{$matches[1]}\\Controllers\\$class";
        }

        throw new \RuntimeException("Invalid controller path: $file");
    }
}
