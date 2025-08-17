<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Exceptions;

final class ArrayTypeMissingException extends JsonMapperException
{
    protected function makeMessage(): void
    {
        $propertyPath = $this->getPropertyPath();

        $this->message = "Array {$propertyPath} should have a type specified in the DocBlock.";
    }
}
