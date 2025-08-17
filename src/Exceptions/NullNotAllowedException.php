<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Exceptions;

final class NullNotAllowedException extends JsonMapperException
{
    protected function makeMessage(): void
    {
        $propertyPath = $this->getPropertyPath();

        $this->message = "Trying to set 'null` to a not-nullable property {$propertyPath}.";
    }
}
