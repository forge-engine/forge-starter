<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;
use Forge\Core\Services\TemplateGenerator;
use Forge\Traits\StringHelper;

#[CLICommand(name: 'make:module', description: 'Create a new module with basic structure')]
final class MakeModuleCommand extends Command
{
    use StringHelper;

    public function __construct(private TemplateGenerator $templateGenerator)
    {
    }

    public function execute(array $args): int
    {
        $moduleName = $this->getModuleName($args);
        if (!$moduleName) {
            return 1;
        }

        $moduleDescription = $this->templateGenerator->askQuestion("Module description: ", "A brief description of the module.");
        $moduleVersion = $this->templateGenerator->askQuestion("Module version (e.g., 1.0.0): ", "1.0.0");

        $moduleDir = $this->createModuleDirectory($moduleName);
        if (!$moduleDir) {
            return 1;
        }

        $this->generateFiles($moduleDir, $moduleName, $moduleDescription, $moduleVersion);
        $this->info("Module '{$moduleName}' created successfully!");
        return 0;
    }

    private function getModuleName(array $args): ?string
    {
        $moduleName = $args[0] ?? $this->askForModuleName();
        if (!$moduleName) {
            $this->error("Module name is required.");
            return null;
        }

        $moduleName = $this->toPascalCase($moduleName);
        if (!$this->isPascalCase($moduleName)) {
            $this->error("Invalid module name. Use PascalCase (e.g., MyModule).");
            return null;
        }

        return $moduleName;
    }

    private function createModuleDirectory(string $moduleName): ?string
    {
        $moduleDir = BASE_PATH . '/modules/' . $moduleName;
        if (is_dir($moduleDir)) {
            $this->error("Module directory already exists: {$moduleDir}");
            return null;
        }

        mkdir($moduleDir, 0755, true);
        mkdir($moduleDir . '/src', 0755, true);
        return $moduleDir;
    }

    private function generateFiles(string $moduleDir, string $moduleName, string $moduleDescription, string $moduleVersion): void
    {
        $this->generateManifestFile($moduleDir, $moduleName, $moduleDescription, $moduleVersion);
        $this->generateConfigFile($moduleDir, $moduleName);
        $this->generateModuleCommand($moduleDir, $moduleName);
        $this->generateModuleInterfaceFile($moduleDir, $moduleName);
        $this->generateModuleService($moduleDir, $moduleName, $moduleVersion);
        $this->generateModuleClassFile($moduleDir, $moduleName, $moduleDescription);
    }

    private function generateManifestFile(string $moduleDir, string $moduleName, string $moduleDescription, string $moduleVersion): void
    {
        $schemaPath = BASE_PATH . '/engine/Core/Schema/module-schema.json';
        $schemaContent = file_get_contents($schemaPath);
        if ($schemaContent === false) {
            $this->error("Error: Could not read module schema file from: {$schemaPath}");
            return;
        }

        $schema = json_decode($schemaContent, true);
        if ($schema === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Error: Invalid JSON in module schema file: {$schemaPath}");
            return;
        }

        $this->generateFileFromTemplate(
            'modules/forge.json.template',
            $moduleDir . '/forge.json',
            [
                '{{ moduleName }}' => $moduleName,
                '{{ interfaceName }}' => $moduleName . 'Interface',
                '{{ moduleDescription }}' => $moduleDescription,
                '{{ moduleVersion }}' => $moduleVersion,
            ]
        );
    }

    private function generateConfigFile(string $moduleDir, string $moduleName): void
    {
        $this->generateFileFromTemplate(
            'modules/config.php.template',
            $moduleDir . '/config/' . $this->toSnakeCase($moduleName) . '.php',
            ['{{ moduleConfigName }}' => $this->toSnakeCase($moduleName)],
            $moduleDir . '/config'
        );
    }

    private function generateModuleCommand(string $moduleDir, string $moduleName): void
    {
        $this->generateFileFromTemplate(
            'modules/command.php.template',
            $moduleDir . '/src/Commands/' . $moduleName . 'Command.php',
            [
                '{{ moduleName }}' => $moduleName,
                '{{ command }}' => $this->toKebabCase($moduleName),
                '{{ commandName }}' => $moduleName . 'Command',
                '{{ moduleNameConfig }}' => strtolower($moduleName),
            ],
            $moduleDir . '/src/Commands'
        );
    }

    private function generateModuleService(string $moduleDir, string $moduleName, string $moduleVersion): void
    {
        $this->generateFileFromTemplate(
            'modules/service.php.template',
            $moduleDir . '/src/Services/' . $moduleName . 'Service.php',
            [
                '{{ moduleName }}' => $moduleName,
                '{{ moduleVersion }}' => $moduleVersion,
                '{{ serviceName }}' => $moduleName . "Service",
                '{{ interfaceName }}' => $moduleName . 'Interface',
            ],
            $moduleDir . '/src/Services'
        );
    }

    private function generateModuleInterfaceFile(string $moduleDir, string $moduleName): void
    {
        $this->generateFileFromTemplate(
            'modules/interface.php.template',
            $moduleDir . '/src/Contracts/' . $moduleName . 'Interface.php',
            [
                '{{ moduleName }}' => $moduleName,
                '{{ interfaceName }}' => $moduleName . 'Interface',
            ],
            $moduleDir . '/src/Contracts'
        );
    }

    private function generateModuleClassFile(string $moduleDir, string $moduleName, string $moduleDescription): void
    {
        $this->generateFileFromTemplate(
            'modules/module.php.template',
            $moduleDir . '/src/' . $moduleName . 'Module.php',
            [
                '{{ moduleName }}' => $moduleName,
                '{{ interfaceName }}' => $moduleName . 'Interface',
                '{{ serviceName }}' => $moduleName . 'Service',
                '{{ moduleDescription }}' => $moduleDescription,
            ]
        );
    }

    private function generateFileFromTemplate(string $templateName, string $outputPath, array $replacements, ?string $directory = null): void
    {
        if ($directory) {
            mkdir($directory, 0755, true);
        }
        $this->templateGenerator->generateFileFromTemplate($templateName, $outputPath, $replacements);
    }

    private function askForModuleName(): ?string
    {
        return $this->templateGenerator->askQuestion("Enter module name (e.g., MyModule, my module name): ", '');
    }
}
