<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class NullableParentClass
{
    public function __construct(public ?\Azavyalov\JsonMapper\Tests\Types\ChildClass $childClass) {}
}
