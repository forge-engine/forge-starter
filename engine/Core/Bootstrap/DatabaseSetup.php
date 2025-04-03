<?php

declare(strict_types=1);

namespace Forge\Core\Bootstrap;

use Forge\Core\Config\Environment;
use Forge\Core\Database\Connection;
use Forge\Core\Database\DatabaseConfig;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Container;
use PDO;

final class DatabaseSetup
{
    public static function setup(Container $container, Environment $env): void
    {
        self::ensureDatabaseDirectoryExists();
        self::initConnection($container, $env);
        self::bindQueryBuilder($container);
    }

    private static function ensureDatabaseDirectoryExists(): void
    {
        if (!is_dir(BASE_PATH . "/storage/database")) {
            mkdir(BASE_PATH . "/storage/database", 0755, true);
        }
    }
    /**
     * @throws \ReflectionException
     */
    private static function initConnection(
        Container $container,
        Environment $env
    ): void {
        $container->singleton(DatabaseConfig::class, function () use ($env) {
            $env->get("DB_DRIVER") . "\n";
            return new DatabaseConfig(
                driver: $env->get("DB_DRIVER"),
                database: $env->get("DB_DRIVER") === "sqlite"
                         ? BASE_PATH . '/storage/database/database.sqlite'
                         : $env->get("DB_NAME"),
                host: $env->get("DB_HOST"),
                username: $env->get("DB_USER"),
                password: $env->get("DB_PASS"),
                port: $env->get("DB_PORT")
            );
        });

        $container->bind(Connection::class, function () use ($container) {
            $config = $container->get(DatabaseConfig::class);
            return new Connection($config);
        });

        $container->bind(PDO::class, function () use ($container) {
            $connection = $container->get(Connection::class);
            return $connection->getPdo();
        });
    }

    private static function bindQueryBuilder(Container $container): void
    {
        $container->bind(QueryBuilder::class, function () use ($container) {
            return new QueryBuilder($container->get(PDO::class));
        });
    }
}
