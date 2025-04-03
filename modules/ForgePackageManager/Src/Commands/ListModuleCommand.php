<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Commands;

use App\Modules\ForgePackageManager\Services\PackageManagerService;
use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name:'package:list-modules', description: 'List modules available in the package repositories')]
final class ListModuleCommand extends Command
{
    private array $modules = [];

    public function __construct(private PackageManagerService $packageManagerService)
    {
    }
    public function execute(array $args): int
    {
        $registries = $this->packageManagerService->getRegisties();

        if (empty($registries)) {
            $this->warning("No package registries configured in forge.json.");
            $registries = $this->packageManagerService->getDefaultRegistryDetails();
        }

        foreach ($registries as $registryDetails) {
            $registryName = $registryDetails['name'] ?? 'Default Registry';
            $this->info("Fetching module list from registry: " . $registryName);

            $modulesData = $this->packageManagerService->getModuleInfo(null);

            if (is_array($modulesData)) {
                foreach ($modulesData as $moduleName => $moduleInfo) {
                    $this->modules[] = [
                        'Module' => $moduleName,
                        'Description' => $moduleInfo['description'] ?? 'No description available',
                        'Registry' => $registryName,
                        'Versions' => implode(', ', array_keys($moduleInfo['versions'] ?? []))
                    ];
                }
            } else {
                $this->error("Failed to load module list from registry: " . $registryName);
            }
        }

        if (empty($this->modules)) {
            $this->warning("No modules found in the configured registries.");
            return 0;
        }

        $this->table(['Module', 'Description', 'Registry', 'Versions'], $this->modules);

        return 0;
    }
}
