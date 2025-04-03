<?php

declare(strict_types=1);

namespace Forge\Core\Module\ModuleLoader;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\HookManager;
use Forge\Traits\NamespaceHelper;
use ReflectionClass;

final class RegisterModuleHooks
{
    use NamespaceHelper;

    public function __construct(private Container $container, private ReflectionClass $reflectionClass)
    {
    }

    public function init(): void
    {
        $this->initModuleHooks();
    }

    private function initModuleHooks(): void
    {
        $moduleAttribute = $this->reflectionClass->getAttributes(Module::class)[0] ?? null;
        if ($moduleAttribute) {
            $moduleAttributeInstance = $moduleAttribute->newInstance();
            $moduleName = $moduleAttributeInstance->name;

            $moduleInstance = $this->container->make($this->reflectionClass->getName());

            foreach ($this->reflectionClass->getMethods() as $method) {
                $lifecycleHookAttributes = $method->getAttributes(LifecycleHook::class);
                foreach ($lifecycleHookAttributes as $attribute) {
                    $hookInstance = $attribute->newInstance();
                    $hookName = $hookInstance->hook;
                    $methodName = $method->getName();
                    $callback = [$moduleInstance, $methodName];

                    if ($hookInstance->forSelf) {
                        $wrappedCallback = function (...$args) use ($moduleName, $callback) {
                            $passedModuleName = $args[0] ?? '';
                            if ($passedModuleName === $moduleName) {
                                call_user_func_array($callback, $args);
                            }
                        };
                        HookManager::addHook($hookName, $wrappedCallback);
                    } else {
                        HookManager::addHook($hookName, $callback);
                    }
                }
            }
        }
    }
}
