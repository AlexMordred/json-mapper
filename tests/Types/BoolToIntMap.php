<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class BoolToIntMap
{
    /**
     * @param array<bool, int> $items
     */
    public function __construct(public array $items) {}
}
