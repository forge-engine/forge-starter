<?php

declare(strict_types=1);

namespace Forge\Core;

if (preg_match('/\.env$/i', $_SERVER["REQUEST_URI"])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

require_once BASE_PATH . "/engine/Core/Support/helpers.php";

use Forge\Core\Bootstrap\Bootstrap;
use Forge\Core\Http\Request;
use Forge\Core\Module\HookManager;
use Forge\Core\Module\LifecycleHookName;

final class Engine
{
    public static function init()
    {
        $bootstrap = Bootstrap::getInstance();
        $kernel = $bootstrap->getKernel();

        HookManager::triggerHook(LifecycleHookName::AFTER_REQUEST);
        $response = $kernel->handler(Request::createFromGlobals());
        HookManager::triggerHook(LifecycleHookName::AFTER_RESPONSE);

        $response->send();
    }
}
