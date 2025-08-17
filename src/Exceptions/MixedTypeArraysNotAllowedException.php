<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Exceptions;

final class MixedTypeArraysNotAllowedException extends JsonMapperException
{
    protected function makeMessage(): void
    {
        $propertyPath = $this->getPropertyPath();

        $this->message = "{$propertyPath}: mixed-type arrays (mixed[]) are not allowed. Use a nested object instead.";
    }
}
