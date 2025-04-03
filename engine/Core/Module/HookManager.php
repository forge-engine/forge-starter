<?php

declare(strict_types=1);

namespace Forge\Core\Module;

final class HookManager
{
    private static array $hooks = [];

    public static function addHook(LifecycleHookName $hookName, callable|array $callback): void
    {
        $name = $hookName->value;

        if (!isset(self::$hooks[$name])) {
            self::$hooks[$name] = [];
        }

        foreach (self::$hooks[$name] as $registeredCallback) {
            if (is_array($registeredCallback) && is_array($callback) &&
                $registeredCallback[0] === $callback[0] && $registeredCallback[1] === $callback[1]) {
                return;
            } elseif ($registeredCallback === $callback) {
                return;
            }
        }

        self::$hooks[$name][] = $callback;
    }

    public static function triggerHook(LifecycleHookName $hookName, ...$args): void
    {
        $name = $hookName->value;
        if (isset(self::$hooks[$name])) {
            foreach (self::$hooks[$name] as $callback) {
                if (is_callable($callback)) {
                    call_user_func_array($callback, $args);
                } elseif (is_array($callback) && count($callback) === 2) {
                    call_user_func_array($callback, $args);
                } else {
                    error_log("Invalid callback format: " . print_r($callback, true));
                }
            }
        }
    }
}
