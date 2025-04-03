<?php

declare(strict_types=1);

namespace Forge\Core\Dto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Sanitize
{
    public function __construct(
        public array $properties
    ) {
    }
}
