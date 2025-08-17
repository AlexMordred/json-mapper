<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class FloatToIntMap
{
    /**
     * @param array<float, int> $items
     */
    public function __construct(public array $items) {}
}
