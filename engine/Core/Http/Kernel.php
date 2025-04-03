<?php

declare(strict_types=1);

namespace Forge\Core\Http;

use Forge\Core\Routing\Router;
use Forge\Core\DI\Container;
use Forge\Core\Module\HookManager;
use Forge\Core\Module\LifecycleHookName;

final class Kernel
{
    public function __construct(
        private Router $router,
        private Container $container
    ) {
    }

    public function handler(Request $request): Response
    {
        HookManager::triggerHook(LifecycleHookName::BEFORE_REQUEST);

        $content = null;

        try {
            $content = $this->router->dispatch($request);
        } catch (\Throwable $exception) {
            throw $exception;
        }

        if ($content instanceof Response) {
            return $content;
        }

        return new Response((string) $content);
    }
}
