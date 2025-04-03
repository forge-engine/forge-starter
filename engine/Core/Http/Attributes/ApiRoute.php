<?php

declare(strict_types=1);

namespace Forge\Core\Http\Attributes;

use Attribute;
use Forge\Core\Routing\Route;

#[Attribute(Attribute::TARGET_METHOD)]
final class ApiRoute extends Route
{
    public function __construct(
        string $path,
        string $method = 'GET',
        public array $middlewares = [],
        string $prefix = 'api',
        string $version = 'v1'
    ) {
        parent::__construct($path, $method, '/'.$prefix . '/' . $version);
    }
}
