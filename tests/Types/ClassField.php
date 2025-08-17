<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class ClassField
{
    public function __construct(public ChildClass $field) {}
}
