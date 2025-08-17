<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Exceptions;

final class MixedTypeNotAllowedException extends JsonMapperException
{
    protected function makeMessage(): void
    {
        $propertyPath = $this->getPropertyPath();

        $this->message = "{$propertyPath}: mixed-type properties are not allowed. Give your class property a proper type.";
    }
}
