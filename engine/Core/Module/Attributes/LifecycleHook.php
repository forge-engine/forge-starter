<?php

declare(strict_types=1);

namespace Forge\Core\Module\Attributes;

use Attribute;
use Forge\Core\Module\LifecycleHookName;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class LifecycleHook
{
    public function __construct(
        public LifecycleHookName $hook,
        public bool $forSelf = true
    ) {
    }
}
