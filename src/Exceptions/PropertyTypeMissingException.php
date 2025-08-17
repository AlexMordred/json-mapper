<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Exceptions;

final class PropertyTypeMissingException extends JsonMapperException
{
    protected function makeMessage(): void
    {
        $propertyPath = $this->getPropertyPath();

        $this->message = "Property {$propertyPath} should have a type specified.";
    }
}
