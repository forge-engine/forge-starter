<?php

declare(strict_types=1);

namespace Forge\Core\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $path,
        public string $method = 'GET',
        public string $prefix = '',
        public array $middlewares = [],
    ) {
    }
}
