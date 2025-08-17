<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class IntToIntMap
{
    /**
     * @param array<int, int> $items
     */
    public function __construct(public array $items) {}
}
