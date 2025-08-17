<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Exceptions;

final class UnsupportedMapKeyTypeException extends JsonMapperException
{
    protected function makeMessage(): void
    {
        $propertyPath = $this->getPropertyPath();

        $this->message = "Map {$propertyPath} key type should be either 'string' or 'int'.";
    }
}
