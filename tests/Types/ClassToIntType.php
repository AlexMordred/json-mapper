<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class ClassToIntType
{
    /**
     * @param array<\Azavyalov\JsonMapper\Tests\Types\ChildClass, int> $items
     */
    public function __construct(public array $items) {}
}
