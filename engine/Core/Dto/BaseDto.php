<?php

declare(strict_types=1);

namespace Forge\Core\Dto;

use ReflectionClass;
use Forge\Core\Dto\Attributes\Sanitize;

abstract class BaseDto
{
    /**
     * Sanitize the DTO by removing or nullifying properties specified in the Sanitize attribute.
     *
     * @return static
     */
    public function sanitize(): self|null
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();
        $sanitizedData = [];

        $sanitizeAttribute = $reflection->getAttributes(Sanitize::class)[0] ?? null;
        $propertiesToSanitize = $sanitizeAttribute ? $sanitizeAttribute->newInstance()->properties : [];

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $property->setAccessible(true);

            if (in_array($propertyName, $propertiesToSanitize)) {
                $sanitizedData[$propertyName] = null;
            } else {
                $sanitizedData[$propertyName] = $property->getValue($this);
            }
        }

        return $reflection->newInstanceArgs($sanitizedData);
    }
}
