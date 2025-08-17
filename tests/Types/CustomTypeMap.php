<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class CustomTypeMap
{
    /**
     * @param array<string, \Azavyalov\JsonMapper\Tests\Types\ChildClass> $items
     */
    public function __construct(public array $items) {}
}
