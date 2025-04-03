<?php

declare(strict_types=1);

namespace Forge\Core\Bootstrap;

use Forge\Core\DI\Container;
use Forge\Core\Routing\ControllerLoader;
use Forge\Core\Routing\Router;

final class RouterSetup
{
    public static function setup(Container $container): Router
    {
        return self::initRouter($container);
    }
    /**
     * @throws \ReflectionException
     */
    private static function initRouter(Container $container): Router
    {
        $controllerDirs = [
        BASE_PATH . "/app/Controllers",
        ...glob(BASE_PATH . "/modules/*/src/Controllers", GLOB_ONLYDIR)
    ];

        $loader = new ControllerLoader($container, $controllerDirs);
        $controllers = $loader->registerControllers();

        $middlewareConfig = require BASE_PATH . "/config/middleware.php";

        $router = new Router($container, $middlewareConfig);

        foreach ($controllers as $controller) {
            $router->registerControllers($controller);
        }

        return $router;
    }
}
