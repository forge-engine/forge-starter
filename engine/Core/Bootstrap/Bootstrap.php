<?php

declare(strict_types=1);

namespace Forge\Core\Bootstrap;

use Forge\Core\Bootstrap\ContainerWebSetup;
use Forge\Core\Bootstrap\ContainerCLISetup;
use Forge\Core\Bootstrap\RouterSetup;
use Forge\Core\Config\Config;
use Forge\Core\Config\Environment;
use Forge\Core\Config\EnvParser;
use Forge\Core\DI\Container;
use Forge\Core\Http\Kernel;

require_once('Version.php');

final class Bootstrap
{
    private static ?self $instance = null;
    private ?Kernel $kernel = null;

    private function __construct()
    {
        $this->kernel = $this->init();
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @throws \ReflectionException
     */
    private static function init(): Kernel
    {
        self::initEnvironment();
        self::initSession();
        $container = ContainerWebSetup::initOnce();
        $router = RouterSetup::setup($container);

        return new Kernel($router, $container);
    }

    public static function shouldCacheViews(): bool
    {
        return Environment::getInstance()->get("VIEW_CACHE") &&
                !Environment::getInstance()->isDevelopment();
    }

    public static function initCliContainer(): Container
    {
        self::configSetup(Container::getInstance());
        return ContainerCLISetup::setup();
    }

    public function getKernel(): ?Kernel
    {
        return $this->kernel;
    }

    private static function configSetup(Container $container): void
    {
        $container->singleton(Config::class, function () {
            return new Config(BASE_PATH . '/config');
        });
    }

    private static function initSession(): void
    {
        ini_set('session.cookie_httponly', true);
        ini_set('session.cookie_secure', true);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', true);
        ini_set('session.use_only_cookies', true);
    }

    private static function initEnvironment(): void
    {
        $envPath = BASE_PATH . "/.env";

        if (file_exists($envPath)) {
            EnvParser::load($envPath);
        }
        Environment::getInstance();
    }

    public static function initErrorHandling(): void
    {
        ini_set(
            "display_errors",
            Environment::getInstance()->isDevelopment() ? "1" : "0"
        );
        error_reporting(E_ALL);
    }
}
