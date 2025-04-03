<?php

declare(strict_types=1);

namespace Forge\Traits;

use RuntimeException;

trait ModuleHelper
{
    private function checkModuleRequirements(): void
    {
        foreach ($this->moduleRequirements as $moduleName => $requirements) {
            foreach ($requirements as $interface => $version) {
                if (!$this->container->has($interface)) {
                    throw new RuntimeException(
                        "Module '{$moduleName}' requires service '{$interface}' (version {$version}) which is not provided."
                    );
                }
            }
        }
    }
}
