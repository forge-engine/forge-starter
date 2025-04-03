<?php

declare(strict_types=1);

namespace Forge\Core\Services;

use Exception;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class TemplateGenerator
{
    private string $baseTemplatePath;

    public function __construct()
    {
        $this->baseTemplatePath = BASE_PATH . "/engine/Core/Templates/";
    }

    public function generateFileFromTemplate(string $templateName, string $outputPath, array $replacements): void
    {
        $templatePath = $this->baseTemplatePath . $templateName;
        $templateContent = file_get_contents($templatePath);

        if ($templateContent === false) {
            throw new Exception("Error: Could not read template file from: {$templatePath}");
        }

        $fileContent = str_replace(array_keys($replacements), array_values($replacements), $templateContent);
        file_put_contents($outputPath, $fileContent);
    }

    public function askQuestion(string $questionText, string $default): string
    {
        $answer = readline($questionText . " [$default]: ");
        return $answer ?: $default;
    }
}
