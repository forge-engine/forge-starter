<?php

declare(strict_types=1);

namespace Forge\Core\Module\ModuleLoader;

use Forge\Core\Module\Attributes\Repository;
use Forge\Traits\NamespaceHelper;
use ReflectionClass;

final class RegisterModuleRepository
{
    use NamespaceHelper;

    public function __construct(private ReflectionClass $reflectionClass)
    {
    }

    public function init(): void
    {
        $this->initModuleRepository();
    }

    private function initModuleRepository(): void
    {
        $repositoryAttribute = $this->reflectionClass->getAttributes(Repository::class)[0] ?? null;
        if ($repositoryAttribute) {
            $repositoryInstance = $repositoryAttribute->newInstance();
            // You might want to store this information somewhere for later use (e.g., for module management)
            // $this->moduleRepositories[$moduleAttributeInstance->name] = ['type' => $repositoryInstance->type, 'url' => $repositoryInstance->url];
        }
    }
}
