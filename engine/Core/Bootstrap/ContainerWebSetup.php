<?php

declare(strict_types=1);

namespace Forge\Core\Bootstrap;

use App\Modules\ForgeErrorHandler\Services\ForgeErrorHandlerService;
use Forge\CLI\Application;
use Forge\Core\Config\Config;
use Forge\Core\Config\Environment;
use Forge\Core\DI\Container;
use Forge\Core\Module\HookManager;
use Forge\Core\Module\LifecycleHookName;
use Forge\Core\Module\ModuleLoader\Loader;
use Forge\Core\Session\Drivers\FileSessionDriver;
use Forge\Core\Session\Session;
use Forge\Core\Session\SessionInterface;

final class ContainerWebSetup
{
    private static bool $containerLoaded = false;

    public static function setup(): Container
    {
        $env = Environment::getInstance();
        $container = Container::getInstance();

        $container->singleton(Config::class, function () {
            return new Config(BASE_PATH . '/config');
        });

        $container->singleton(Application::class, function () use ($container) {
            $application = Application::getInstance($container);
            return $application;
        });

        $container->singleton(SessionInterface::class, function () {
            $fileDriver = new FileSessionDriver();
            return new Session($fileDriver);
        });

        DatabaseSetup::setup($container, $env);
        self::loadCoreErrorHandler($container);
        Bootstrap::initErrorHandling();
        $container->get(ForgeErrorHandlerService::class);
        (new ForgeErrorHandlerService());

        ModuleSetup::loadModules($container);
        ServiceDiscoverSetup::setup($container);

        HookManager::triggerHook(LifecycleHookName::APP_BOOTED);

        return $container;
    }

    public static function initOnce(): Container
    {
        if (!self::$containerLoaded) {
            $container = self::setup();
            self::$containerLoaded = true;
            return $container;
        }
        return Container::getInstance();
    }

    private static function loadCoreErrorHandler(Container $container): void
    {
        $modulePath = BASE_PATH . '/modules/ForgeErrorHandler';
        $config = $container->get(Config::class);

        Loader::loadCoreModule($modulePath, $container, $config);
    }
}
