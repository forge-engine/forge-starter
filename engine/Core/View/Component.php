<?php

declare(strict_types=1);

namespace Forge\Core\View;

use Attribute;
use Forge\Core\Helpers\Strings;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS)]
class Component
{
    public function __construct(public string $name, public bool $useDto = false)
    {
    }

    /**
     * Render a component.
     *
     * @throws \ReflectionException
     */
    public static function render(string $name, array $props = []): string
    {
        $componentClass = self::findComponentClass($name);

        if (!$componentClass) {
            throw new \RuntimeException("Component class not found for: {$name}");
        }

        return self::instantiateComponent($componentClass, $props);
    }

    /**
     * Finds a component class by scanning the app/views/components/ and modules.
     */
    private static function findComponentClass(string $name): ?string
    {
        foreach (get_declared_classes() as $class) {
            if (self::isComponentMatch($class, $name)) {
                return $class;
            }
        }

        return self::scanForComponent($name);
    }

    private static function scanForComponent(string $name): ?string
    {
        $className = Strings::toPascalCase($name);
        $searchPaths = [
            BASE_PATH . "/app/views/components/",
            BASE_PATH . "/modules/*/views/components/"
        ];

        foreach ($searchPaths as $basePath) {
            foreach (glob($basePath . "*", GLOB_ONLYDIR) as $componentDir) {
                $componentFile = "{$componentDir}/{$className}.php";
                if (file_exists($componentFile)) {
                    require_once $componentFile;

                    $namespace = str_replace(
                        [BASE_PATH . "/app/views/components/", BASE_PATH . "/modules/"],
                        ["App\\View\\Components\\", "Modules\\"],
                        $componentDir
                    );
                    $fullClassNameFromFile = rtrim($namespace, "/") . "\\{$className}";

                    if (class_exists($fullClassNameFromFile) && is_subclass_of($fullClassNameFromFile, BaseComponent::class)) {
                        $reflection = new ReflectionClass($fullClassNameFromFile);
                        $attributes = $reflection->getAttributes(Component::class);
                        foreach ($attributes as $attribute) {
                            $instance = $attribute->newInstance();
                            if ($instance->name === $name) {
                                return $fullClassNameFromFile;
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Checks if a given class is a component and matches the requested name.
     */
    private static function isComponentMatch(string $class, string $name): bool
    {
        if (!is_subclass_of($class, BaseComponent::class)) {
            return false;
        }

        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(Component::class);

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Instantiates the component and handles props.
     */
    private static function instantiateComponent(string $componentClass, array $props): string
    {
        $reflection = new ReflectionClass($componentClass);
        $attributes = $reflection->getAttributes(Component::class);
        $componentInstance = $attributes[0]->newInstance();

        $componentProps = $props;
        if ($componentInstance->useDto) {
            $componentPropsClassName = $componentClass . "PropsDto";
            if (class_exists($componentPropsClassName)) {
                $propsDto = new $componentPropsClassName();
                foreach ($props as $key => $value) {
                    if (property_exists($propsDto, $key)) {
                        $propsDto->$key = $value;
                    }
                }
                $componentProps = $propsDto;
            }
        }

        $component = new $componentClass($componentProps);

        ob_start();
        $renderResult = $component->render();

        if (is_string($renderResult)) {
            return $renderResult;
        }

        return ob_get_clean();
    }
}
