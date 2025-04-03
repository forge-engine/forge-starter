<?php

declare(strict_types=1);

namespace Forge\Core\Bootstrap;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

final class ServiceDiscoverSetup
{
    private const CLASS_MAP_CACHE_FILE =
      BASE_PATH . "/storage/framework/cache/class-map.php";

    public static function setup(Container $container): void
    {
        if (self::isProductionOrStaging()) {
            $classMap = self::loadClassMapCache();

            if ($classMap) {
                foreach ($classMap as $class => $filepath) {
                    if (class_exists($class)) {
                        try {
                            $reflectionClass = new ReflectionClass($class);
                            if (
                                         !$reflectionClass->isInterface() &&
                                         !$reflectionClass->isAbstract() &&
                                         !empty($reflectionClass->getAttributes(Service::class))
                                    ) {
                                $container->register($class);
                            }
                        } catch (\ReflectionException $e) {
                            // Handle reflection exceptions (e.g., class not found)
                        }
                    }
                }
                return;
            }
        }

        $serviceDirectories = [
                BASE_PATH . "/app/Repositories",
                BASE_PATH . "/app/Middlewares",
                BASE_PATH . "/app/Services",
                BASE_PATH . "/engine/Core/Database",
                BASE_PATH . "/engine/Core/Http",
                BASE_PATH . "/engine/Core/Http/Middlewares",
                BASE_PATH . "/engine/Core/Services"
          ];

        $newClassMap = [];

        foreach ($serviceDirectories as $directory) {
            if (is_dir($directory)) {
                $directoryIterator = new RecursiveDirectoryIterator($directory);
                $iterator = new RecursiveIteratorIterator($directoryIterator);

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === "php") {
                        $filepath = $file->getPathname();

                        if (strpos($filepath, '/config/') !== false) {
                            continue;
                        }

                        $contents = file_get_contents($filepath);
                        if (strpos($contents, '#[Service]') === false) {
                            continue;
                        }

                        $class = self::fileToClass($filepath, BASE_PATH);
                        if (class_exists($class)) {
                            try {
                                $reflectionClass = new ReflectionClass($class);
                                if (
                                                !$reflectionClass->isInterface() &&
                                                !$reflectionClass->isAbstract() &&
                                                !empty($reflectionClass->getAttributes(Service::class))
                                          ) {
                                    $container->register($class);
                                    $newClassMap[$class] = $filepath;
                                }
                            } catch (\ReflectionException $e) {
                                // Handle reflection exceptions
                            }
                        }
                    }
                }
            }
        }
        self::generateClassMapCache($newClassMap);
    }

    /**
     * Loads the class map from cache if it exists and is valid.
     * @return array<string, string>|null Class map array or null if cache is not found or invalid.
     */
    private static function loadClassMapCache(): ?array
    {
        if (file_exists(self::CLASS_MAP_CACHE_FILE)) {
            try {
                $cachedData = include self::CLASS_MAP_CACHE_FILE;
                if (is_array($cachedData)) {
                    return $cachedData;
                }
            } catch (\Exception $e) {
            }
        }
        return null;
    }

    /**
     * Generates and caches the class map to a file.
     * @param array<string, string> $classMap
     */
    private static function generateClassMapCache(array $classMap): void
    {
        if (!is_dir(dirname(self::CLASS_MAP_CACHE_FILE))) {
            mkdir(dirname(self::CLASS_MAP_CACHE_FILE), 0777, true);
        }

        $cacheContent = "<?php return " . var_export($classMap, true) . ";";
        file_put_contents(self::CLASS_MAP_CACHE_FILE, $cacheContent);
    }

    private static function isProductionOrStaging(): bool
    {
        return isset($_ENV['APP_ENV']) && in_array($_ENV['APP_ENV'], ['production', 'staging'], true);
    }

    /**
     * Helper function to convert file path to class name
     */
    private static function fileToClass(
        string $filepath,
        string $basePath
    ): string {
        $relativePath = str_replace($basePath, "", $filepath);
        $class = str_replace([".php", "/"], ["", "\\"], $relativePath);

        $class = ltrim($class, "\\");
        if (str_starts_with($class, "engine\\Core\\")) {
            $class = str_replace("engine\\Core\\", "Forge\\Core\\", $class);
        } elseif (str_starts_with($class, "app\\")) {
            $class = str_replace("app\\", "App\\", $class);
        }
        return $class;
    }
}
