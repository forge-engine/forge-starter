<?php

declare(strict_types=1);

namespace Forge\Core\View;

use Forge\Core\DI\Container;
use Forge\Core\View\View;
use ReflectionClass;

abstract class BaseComponent
{
    protected array $props;

    public function __construct(array|object $props)
    {
        $this->props = is_object($props) ? $this->convertDtoToArray($props) : $props;
    }

    abstract public function render(): mixed;

    protected function renderview(string $viewPath, array $data = []): string
    {
        $view = new View(Container::getInstance());
        return $view->renderComponent($viewPath, $data);
    }

    private function convertDtoToArray(object $dto): array
    {
        $reflection = new ReflectionClass($dto);
        $propsArray = [];
        foreach ($reflection->getProperties() as $prop) {
            $prop->setAccessible(true);
            $propsArray[$prop->getName()] = $prop->getValue($dto);
        }
        return $propsArray;
    }
}
