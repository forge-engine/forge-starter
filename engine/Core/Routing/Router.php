<?php

declare(strict_types=1);

namespace Forge\Core\Routing;

use Forge\Core\DI\Container;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Attributes\ApiRoute;
use Forge\Core\Http\Middleware as HttpMiddleware;
use ReflectionClass;
use Forge\Core\Http\Request;
use Forge\Exceptions\InvalidMiddlewareException;
use Forge\Traits\ResponseHelper;
use ReflectionMethod;

final class Router
{
    use ResponseHelper;

    private array $middlewareGroups;
    /** @var array<string, array{controller: class-string, method: string}> */
    private array $routes = [];
    private Container $container;

    public function __construct(Container $container, array $middlewareConfig = [])
    {
        $this->container = $container;
        $this->middlewareGroups = $middlewareConfig;
    }

    /**
     * @throws \ReflectionException
     */
    public function registerControllers(string $controllerClass): void
    {
        $reflection = new ReflectionClass($controllerClass);

        foreach ($reflection->getMethods() as $method) {
            $routeAttributes = array_merge(
                $method->getAttributes(Route::class),
                $method->getAttributes(ApiRoute::class)
            );

            $middlewareAttributes = array_merge(
                $reflection->getAttributes(Middleware::class),
                $method->getAttributes(Middleware::class)
            );

            $middleware = [];
            foreach ($middlewareAttributes as $attr) {
                $instance = $attr->newInstance();
                if (isset($this->middlewareGroups[$instance->nameOrClass])) {
                    $middleware = array_merge($middleware, $this->middlewareGroups[$instance->nameOrClass]);
                } else {
                    $middleware[] = $instance->nameOrClass;
                }
            }
            foreach ($routeAttributes as $attr) {
                $route = $attr->newInstance();
                $routeMiddlewares = $this->resolveMiddlewareGroups($route->middlewares);
                $middleware = array_merge($middleware, $routeMiddlewares);

                if ($route instanceof ApiRoute) {
                    $middleware = array_merge($middleware, $this->resolveMiddlewareGroups($route->middlewares));
                    $route->path = $route->prefix . $route->path;
                }

                $params = [];
                $pattern = preg_replace_callback(
                    "/\{([a-zA-Z0-9_]+)(?::(.+))?\}/",
                    function ($matches) use (&$params) {
                        $paramName = $matches[1];
                        $constraint = $matches[2] ?? null;
                        $params[] = $paramName;

                        if ($constraint === '.+') {
                            return "(.+)";
                        } else {
                            return "([a-zA-Z0-9_-]+)";
                        }
                    },
                    $route->path
                );

                $regex = "#^{$pattern}/?$#";

                $this->routes[$route->method . $regex] = [
                    "controller" => $controllerClass,
                    "method" => $method->getName(),
                    "params" => $params,
                    "middleware" => $middleware,
                ];
            }
        }
    }

    public function dispatch(Request $request): mixed
    {
        $uri = $request->serverParams["REQUEST_URI"];
        $method = $request->getMethod();
        $path = parse_url($uri, PHP_URL_PATH);
        $routeKey = $method . "#^{$path}/?$#";

        $routeFound = false;
        foreach ($this->routes as $routeRegex => $routeInfo) {
            if (strpos($routeRegex, $method) === 0) {
                $regex = substr($routeRegex, strlen($method));
                if (preg_match($regex, $path, $matches)) {
                    $routeInfo["regex_matches"] = $matches;
                    $route = $routeInfo;
                    $routeFound = true;
                    break;
                }
            }
        }

        if (!$routeFound) {
            $errorCode = 404;

            require_once BASE_PATH . '/engine/Templates/Views/error_page.php';
            return $this->createErrorResponse($request, '', (int)$errorCode);
        }

        // load global middleware from config
        $configMiddlewares = require BASE_PATH . "/config/middleware.php";
        $globalMiddlewares = $configMiddlewares['global'] ?? [];

        // merge global middleware and route middleware
        $allMiddlewares = array_merge($globalMiddlewares, $route['middleware'] ?? []);

        $pipeline = array_reduce(
            array_reverse($allMiddlewares),
            fn ($next, $middlewareClass) => function ($req) use ($middlewareClass, $next) {
                $middlewareInstance = $this->container->make($middlewareClass);
                if (!($middlewareInstance instanceof HttpMiddleware)) {
                    throw new InvalidMiddlewareException($middlewareClass);
                }
                return $middlewareInstance->handle($req, $next);
            },
            fn ($request) => $this->runController($route, $request)
        );

        return $pipeline($request);
    }

    private function runController(array $route, Request $request): mixed
    {
        $controllerClass = $route["controller"];
        $methodName = $route["method"];
        $params = [];
        $arguments = [];

        if (isset($route["params"], $route["regex_matches"])) {
            array_shift($route["regex_matches"]);
            foreach ($route["params"] as $index => $paramName) {
                $params[$paramName] = $route["regex_matches"][$index];
            }

            $reflectionMethod = new ReflectionMethod($controllerClass, $methodName);
            $methodParameters = $reflectionMethod->getParameters();

            foreach ($methodParameters as $param) {
                $paramName = $param->getName();
                if ($paramName === 'request') {
                    $arguments[] = $request;
                } elseif (isset($params[$paramName])) {
                    $arguments[] = $params[$paramName];
                }
            }
        } else {
            $reflectionMethod = new ReflectionMethod($controllerClass, $methodName);
            $methodParameters = $reflectionMethod->getParameters();
            foreach ($methodParameters as $param) {
                if ($param->getName() === 'request') {
                    $arguments[] = $request;
                    break;
                }
            }
        }

        $controllerInstance = $this->container->make($controllerClass);
        return $controllerInstance->$methodName(...$arguments);
    }

    private function resolveMiddlewareGroups(array $groups): array
    {
        $middlewares = [];
        foreach ($groups as $group) {
            $middlewares = array_merge(
                $middlewares,
                $this->middlewareGroups[$group] ?? []
            );
        }
        return $middlewares;
    }
}
