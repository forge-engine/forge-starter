<?php

declare(strict_types=1);

namespace Forge\Core\Bootstrap;

use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Module\HookManager;
use Forge\Core\Module\LifecycleHookName;
use Forge\Core\Module\ModuleLoader\Loader;

final class ModuleSetup
{
    private static bool $modulesLoaded = false;

    public static function loadModules(Container $container): void
    {
        if (self::$modulesLoaded) {
            return;
        }

        $container->singleton(Loader::class, function () use ($container) {
            return new Loader(
                container: $container,
                config: $container->get(Config::class)
            );
        });

        $moduleLoader = $container->get(Loader::class);
        $moduleLoader->loadModules();

        HookManager::triggerHook(LifecycleHookName::AFTER_MODULE_LOAD);
        self::$modulesLoaded = true;
    }
}
