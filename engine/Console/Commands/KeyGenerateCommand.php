<?php

namespace Forge\Console\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;

class KeyGenerateCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'key:generate';
    }

    public function getDescription(): string
    {
        return 'Generate a new application key and set it in the .env file';
    }

    public function execute(array $args): int
    {
        $envFile = BASE_PATH . '/.env';
        $envExampleFile = BASE_PATH . '/env-example';
        $keyLinePrefix = 'FORGE_APP_KEY=';

        if (!file_exists($envFile)) {
            if (!file_exists($envExampleFile)) {
                $this->error("Error: .env.example file not found. Cannot create .env file.");
                return 1;
            }

            if (!copy($envExampleFile, $envFile)) {
                $this->error("Error: Failed to copy .env.example to .env.");
                return 1;
            }
            $this->info(".env file created from .env.example");
        }

        // Generate a new secure key (32 bytes -> 64 hex characters)
        $key = bin2hex(random_bytes(32));

        // Read .env content
        $envContent = file_get_contents($envFile);
        if ($envContent === false) {
            $this->error("Error: Could not read .env file.");
            return 1;
        }

        // Update or add the FORGE_APP_KEY in the .env file
        $updated = false;
        $lines = explode("\n", $envContent);

        foreach ($lines as &$line) {
            if (str_starts_with($line, $keyLinePrefix)) {
                $line = $keyLinePrefix . $key;
                $updated = true;
                break; // Key found and updated
            }
        }

        if (!$updated) {
            // If key was not found, append it
            $lines[] = $keyLinePrefix . $key;
        }

        $newEnvContent = implode("\n", $lines);

        // Write updated content back to .env file
        if (file_put_contents($envFile, $newEnvContent) === false) {
            $this->error("Error: Failed to write to .env file.");
            return 1;
        }

        $this->info("✅ Application key generated successfully!");
        $this->line("🔑 New application key: {$key}");
        $this->line("🔒 Key has been set in your .env file.");

        return 0;
    }
}