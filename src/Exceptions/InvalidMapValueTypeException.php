<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Exceptions;

final class InvalidMapValueTypeException extends InvalidTypeException
{
    protected function makeMessage(): void
    {
        $propertyPath = $this->getPropertyPath();

        $this->message = "All values of map {$propertyPath} are expected to be of type '{$this->expectedType}', '{$this->actualType}' found.";
    }
}
