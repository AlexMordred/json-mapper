<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Exceptions;

final class IntersectionTypesNotAllowedException extends JsonMapperException
{
    protected function makeMessage(): void
    {
        $propertyPath = $this->getPropertyPath();

        $this->message = "Union types are not allowed in {$propertyPath}.";
    }
}
