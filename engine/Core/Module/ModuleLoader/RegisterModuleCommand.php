<?php

declare(strict_types=1);

namespace Forge\Core\Module\ModuleLoader;

use Forge\CLI\Application;
use Forge\CLI\Command;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\CLICommand;
use Forge\Traits\NamespaceHelper;
use ReflectionClass;
use RecursiveIteratorIterator;

final class RegisterModuleCommand
{
    use NamespaceHelper;

    public function __construct(private Container $container, private ReflectionClass $reflectionClass)
    {
    }

    public function init(): void
    {
        $this->registerModuleCommands();
    }
    private function registerModuleCommands(): void
    {
        $moduleNamespace = $this->reflectionClass->getNamespaceName();
        $modulePath = dirname($this->reflectionClass->getFileName());

        $directoryIterator = new \RecursiveDirectoryIterator($modulePath);
        $iterator = new RecursiveIteratorIterator($directoryIterator);

        $cliApplication = $this->container->get(Application::class);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && str_ends_with($file->getFilename(), 'Command.php')) {
                $filePath = $file->getRealPath();
                $fileNamespace = $this->getNamespaceFromFile($filePath, BASE_PATH);
                if (str_starts_with($fileNamespace, $moduleNamespace)) {
                    $className = $fileNamespace . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
                    if (class_exists($className) && is_subclass_of($className, Command::class)) {
                        $commandAttribute = (new ReflectionClass($className))->getAttributes(CLICommand::class)[0] ?? null;
                        if ($commandAttribute) {
                            $commandInstance = $commandAttribute->newInstance();
                            $commandName = $commandInstance->name;
                            $description = $commandInstance->description;
                            $cliApplication->registerCommandClass($className, $commandName, $description);
                        } else {
                            error_log("CLICommand attribute not found on class: " . $className);
                        }
                    } else {
                        error_log("Class " . $className . " is not a valid CLI Command.");
                    }
                }
            }
        }
    }
}
