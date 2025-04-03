<?php

declare(strict_types=1);

namespace Forge\Core\Module\ModuleLoader;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Provides;
use Forge\Traits\NamespaceHelper;
use ReflectionClass;

final class RegisterModuleProvides
{
    use NamespaceHelper;

    public function __construct(private Container $container, private ReflectionClass $reflectionClass)
    {
    }

    public function init(): void
    {
        $this->initModuleProvides();
    }

    private function initModuleProvides(): void
    {
        foreach ($this->reflectionClass->getAttributes(Provides::class) as $attribute) {
            $provideInstance = $attribute->newInstance();
            $this->container->bind($provideInstance->interface, $this->reflectionClass->getName());
        }
    }
}
