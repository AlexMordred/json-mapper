<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Exceptions;

final class MixedTypeMapsNotAllowedException extends JsonMapperException
{
    protected function makeMessage(): void
    {
        $propertyPath = $this->getPropertyPath();

        $this->message = "{$propertyPath}: maps with mixed-type values (mixed) are not allowed. Use a nested object instead.";
    }
}
