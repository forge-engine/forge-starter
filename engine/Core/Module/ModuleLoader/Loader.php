<?php

declare(strict_types=1);

namespace Forge\Core\Module\ModuleLoader;

use Forge\CLI\Application;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\HookManager;
use Forge\Core\Module\LifecycleHookName;
use Forge\Traits\ModuleHelper;
use Forge\Traits\NamespaceHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

#[Service]
final class Loader
{
    use NamespaceHelper;
    use ModuleHelper;
    use OutputHelper;

    private array $modules = [];
    private array $moduleRequirements = [];
    /***
     * @var Application $cliApplication
     */

    public function __construct(
        private Container $container,
        private Config $config,
    ) {
    }

    public function loadModules(): void
    {
        $moduleDirectory = BASE_PATH . '/modules';

        if (!is_dir($moduleDirectory)) {
            return;
        }

        $directories = array_filter(
            scandir($moduleDirectory),
            fn ($item) =>
            is_dir("$moduleDirectory/$item") && !in_array($item, ['.', '..'])
        );

        $modulesToLoad = [];

        foreach ($directories as $directoryName) {
            $modulePath = "$moduleDirectory/$directoryName/src";
            if (!is_dir($modulePath)) {
                continue;
            }

            $moduleName = basename($directoryName);
            $this->registerModuleAutoloadPath($moduleName, $moduleDirectory . '/' . $directoryName);

            $moduleClass = $this->findModuleClass($modulePath);
            if ($moduleClass) {
                $modulesToLoad[] = [
                    'path' => $moduleDirectory . '/' . $directoryName,
                    'class' => $moduleClass,
                    'order' => $moduleClass['order'] ?? 999,
                ];
            }
        }

        if (empty($modulesToLoad)) {
            return;
        }

        usort($modulesToLoad, fn ($a, $b) => $a['order'] <=> $b['order']);

        foreach ($modulesToLoad as $moduleInfo) {
            $this->loadModule($moduleInfo['path'], $moduleInfo['class']);
        }

        $this->checkModuleRequirements();
    }

    private function findModuleClass(string $srcPath): ?array
    {
        $directoryIterator = new RecursiveDirectoryIterator($srcPath);
        $iterator = new RecursiveIteratorIterator($directoryIterator);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $namespace = $this->getNamespaceFromFile($file->getRealPath(), BASE_PATH);
                if ($namespace) {
                    $className = $namespace . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
                    if (class_exists($className)) {
                        try {
                            $reflectionClass = new ReflectionClass($className);
                            $attributes = $reflectionClass->getAttributes(Module::class);
                            if (!empty($attributes)) {
                                $moduleInstance = $attributes[0]->newInstance();
                                return ['name' => $className, 'order' => $moduleInstance->order ?? 999];
                            }
                        } catch (\ReflectionException $e) {
                            $this->error("Reflection Exception for class: $className - " . $e->getMessage());
                        }
                    }
                }
            }
        }

        return null;
    }

    private function loadModule(string $modulePath, array $moduleClass): void
    {
        $moduleName = basename($modulePath);
        $className = $moduleClass['name'];

        try {
            $reflectionClass = new ReflectionClass($className);
            $attributes = $reflectionClass->getAttributes(Module::class);

            if (!empty($attributes)) {
                $moduleInstance = $attributes[0]->newInstance();
                if (!$moduleInstance->core) {
                    $this->registerModule($moduleName, $className, $moduleInstance, $reflectionClass);
                }
            }
        } catch (\ReflectionException $e) {
            $this->error("Failed to load module: $moduleName - " . $e->getMessage());
        }
    }

    private function registerModule(string $moduleName, string $className, Module $moduleInstance, ReflectionClass $reflectionClass): void
    {
        $this->modules[$moduleName] = $className;

        (new RegisterModuleService($this->container, $reflectionClass))->init();
        (new RegisterModuleCommand($this->container, $reflectionClass))->init();
        (new RegisterModuleConfig($this->config, $reflectionClass))->init();
        (new RegisterModuleHooks($this->container, $reflectionClass))->init();
        (new RegisterModuleProvides($this->container, $reflectionClass))->init();
        (new RegisterModuleRequires($reflectionClass, $this->moduleRequirements));
        (new RegisterModuleCompatibility($reflectionClass, $moduleInstance))->init();
        (new RegisterModuleRepository($reflectionClass))->init();

        $moduleInstance = $this->container->make($className);
        if (method_exists($moduleInstance, 'register')) {
            $moduleInstance->register($this->container);
        }

        HookManager::triggerHook(LifecycleHookName::AFTER_MODULE_REGISTER, $moduleName, $className, $moduleInstance);
    }


    public function getModules(): array
    {
        return $this->modules;
    }

    public static function loadCoreModule(string $modulePath, Container $container, Config $config): void
    {
        if (!is_dir($modulePath)) {
            echo "Module path does not exist: $modulePath";
            return;
        }

        $moduleName = basename($modulePath);
        $srcPath = "$modulePath/src";

        if (!is_dir($srcPath)) {
            echo "Module source path does not exist: $srcPath";
            return;
        }

        $loader = new self($container, $config);
        $loader->registerModuleAutoloadPath($moduleName, $modulePath);

        $moduleClass = $loader->findModuleClass($srcPath);
        if ($moduleClass) {
            $loader->loadModule($modulePath, $moduleClass);
        } else {
            echo "No module class found in: $srcPath";
        }
    }
}
