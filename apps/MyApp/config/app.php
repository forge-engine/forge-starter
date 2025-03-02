<?php
return [
    'name' => 'MyApp',
    "key" => \Forge\Core\Helpers\App::env("FORGE_APP_KEY"),
    'middleware' => [
        \Forge\Http\Middleware\SecurityHeadersMiddleware::class,
        \Forge\Http\Middleware\CorsMiddleware::class,
        \Forge\Http\Middleware\CompressionMiddleware::class,
    ],
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
        'allowed_headers' => ['Content-Type', 'Authorization'],
    ],
    "paths" => [
        "resources" => [
            "views" => "apps/MyApp/resources/views",
            "components" => "apps/MyApp/resources/components",
            "layouts" => "apps/MyApp/resources/layouts",
        ],
        "public" => [
            "assets" => "public/assets",
            "modules" => "public/modules",
            "uploads" => "public/uploads"
        ],
        "database" => [
        ],
        "controllers" => "apps/MyApp/Controllers",
        "routes" => "app/MyApp/routes",
        "helpers" => "apps/MyApp/Helpers",
        "middlewares" => "apps/MyApp/Middleware",
        "commands" => "apps/MyApp/Commands",
        "config" => "apps/MyApp/config",
    ],
];
