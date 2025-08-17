<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class ObjectField
{
    public function __construct(public object $field) {}
}
