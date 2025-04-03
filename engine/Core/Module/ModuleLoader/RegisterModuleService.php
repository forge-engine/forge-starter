<?php

declare(strict_types=1);

namespace Forge\Core\Module\ModuleLoader;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Traits\NamespaceHelper;
use ReflectionClass;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

final class RegisterModuleService
{
    use NamespaceHelper;

    public function __construct(private Container $container, private ReflectionClass $reflectionClass)
    {
    }

    public function init(): void
    {
        $this->registerModuleServices();
    }

    private function registerModuleServices(): void
    {
        $moduleNamespace = $this->reflectionClass->getNamespaceName();
        $modulePath = dirname($this->reflectionClass->getFileName());

        $directoryIterator = new RecursiveDirectoryIterator($modulePath);
        $iterator = new RecursiveIteratorIterator($directoryIterator);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getRealPath();
                $fileNamespace = $this->getNamespaceFromFile($filePath, BASE_PATH);
                if ($fileNamespace !== null && str_starts_with($fileNamespace, $moduleNamespace)) {
                    $className = $fileNamespace . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
                    if (class_exists($className)) {
                        $classReflection = new ReflectionClass($className);
                        if ($classReflection->getAttributes(Service::class)) {
                            $this->container->register($className);
                        }
                    }
                }
            }
        }
    }
}
