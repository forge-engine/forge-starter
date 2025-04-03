<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;
use Forge\Core\Services\TemplateGenerator;
use Forge\Traits\StringHelper;

#[CLICommand(name: 'make:controller', description: 'Create a new controller')]
final class MakeControllerCommand extends Command
{
    use StringHelper;

    private const CONTROLLER_PATH = BASE_PATH . '/app/Controllers';
    private const VIEW_PATH = BASE_PATH . '/app/views/pages';

    public function __construct(private TemplateGenerator $templateGenerator)
    {
    }

    public function execute(array $args): int
    {
        $middlewareName = $this->getModuleName($args);
        if (!$middlewareName) {
            return 1;
        }

        $this->generateFiles(self::CONTROLLER_PATH, $middlewareName);
        $this->info("Controller '{$middlewareName}' created successfully!");
        return 0;
    }

    private function getModuleName(array $args): ?string
    {
        $middlewareName = $args[0] ?? $this->askForModuleName();
        if (!$middlewareName) {
            $this->error("Controller name is required.");
            return null;
        }

        return $middlewareName;
    }


    private function generateFiles(string $moduleDir, string $controllerName): void
    {
        $this->generateControllerCommand($moduleDir, $controllerName);
        $this->generateViewCommand(self::VIEW_PATH, $controllerName);
    }

    private function generateControllerCommand(string $moduleDir, string $controllerName): void
    {
        $this->generateFileFromTemplate(
            'app/controller.php.template',
            $moduleDir . '/' . $this->toPascalCase($controllerName) . 'Controller.php',
            [
                '{{ controllerName }}' => $this->toPascalCase($controllerName) . 'Controller',
                '{{ controllerRoute }}' => $this->toKebabCase($controllerName),
                '{{ controllerView }}' => $this->toKebabCase($controllerName),
            ],
            $moduleDir
        );
    }

    private function generateViewCommand(string $moduleDir, string $controllerName): void
    {
        $this->generateFileFromTemplate(
            'app/view.php.template',
            $moduleDir . '/' . $this->toKebabCase($controllerName) . '/index.php',
            [],
            $moduleDir . '/' . $this->toKebabCase($controllerName)
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
        return $this->templateGenerator->askQuestion("Enter controller name (e.g., Auth): ", '');
    }
}
