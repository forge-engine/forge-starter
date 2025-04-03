<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;
use Forge\Core\Services\TemplateGenerator;
use Forge\Traits\StringHelper;

#[CLICommand(name: 'make:middleware', description: 'Create a new middleware')]
final class MakeMiddlewareCommand extends Command
{
    use StringHelper;

    private const MIDDLEWARE_PATH = BASE_PATH . '/app/Middlewares';

    public function __construct(private TemplateGenerator $templateGenerator)
    {
    }

    public function execute(array $args): int
    {
        $middlewareName = $this->getModuleName($args);
        if (!$middlewareName) {
            return 1;
        }

        $this->generateFiles(self::MIDDLEWARE_PATH, $middlewareName);
        $this->info("Middleware '{$middlewareName}' created successfully!");
        return 0;
    }

    private function getModuleName(array $args): ?string
    {
        $middlewareName = $args[0] ?? $this->askForModuleName();
        if (!$middlewareName) {
            $this->error("Module name is required.");
            return null;
        }

        $middlewareName = $this->toPascalCase($middlewareName);
        if (!$this->isPascalCase($middlewareName)) {
            $this->error("Invalid middleware name. Use PascalCase (e.g., MyModule).");
            return null;
        }

        return $middlewareName;
    }


    private function generateFiles(string $moduleDir, string $middlewareName): void
    {
        $this->generateModuleCommand($moduleDir, $middlewareName);
    }

    private function generateModuleCommand(string $moduleDir, string $middlewareName): void
    {
        $this->generateFileFromTemplate(
            'app/middleware.php.template',
            $moduleDir . '/' . $middlewareName . 'Middleware.php',
            [
                '{{ middlewareName }}' => $middlewareName . 'Middleware',
            ],
            $moduleDir
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
        return $this->templateGenerator->askQuestion("Enter middleware name (e.g., Auth): ", '');
    }
}
