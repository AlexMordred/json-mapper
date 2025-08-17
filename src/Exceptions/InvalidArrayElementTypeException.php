<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Exceptions;

final class InvalidArrayElementTypeException extends InvalidTypeException
{
    protected function makeMessage(): void
    {
        $propertyPath = $this->getPropertyPath();

        $this->message = "Typed array {$propertyPath} expects all of its elements to be of type '{$this->expectedType}', '{$this->actualType}' found.";
    }
}
