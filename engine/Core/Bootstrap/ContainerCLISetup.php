<?php

declare(strict_types=1);

namespace Forge\Core\Bootstrap;

use Forge\CLI\Application;
use Forge\CLI\Commands\HelpCommand;
use Forge\Core\Config\Environment;
use Forge\Core\Database\Connection;
use Forge\Core\Database\Migrator;
use Forge\Core\DI\Container;

final class ContainerCLISetup
{
    private static bool $cliContainerSetup = false;

    public static function setup(): Container
    {
        if (self::$cliContainerSetup) {
            return Container::getInstance();
        }

        $env = Environment::getInstance();
        $container = Container::getInstance();

        $container->singleton(Application::class, function () use ($container) {
            $application = Application::getInstance($container);
            return $application;
        });

        DatabaseSetup::setup($container, $env);

        $container->singleton(Migrator::class, function () use ($container) {
            return new Migrator($container->get(Connection::class));
        });

        $container->singleton(HelpCommand::class, function () use ($container) {
            return new HelpCommand($container);
        });

        ModuleSetup::loadModules($container);
        ServiceDiscoverSetup::setup($container);

        self::$cliContainerSetup = true;

        return $container;
    }
}
