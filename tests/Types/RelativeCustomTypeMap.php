<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class RelativeCustomTypeMap
{
    /**
     * @param array<string, ChildClass> $items
     */
    public function __construct(public array $items) {}
}
