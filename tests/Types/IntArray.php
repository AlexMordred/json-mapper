<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class IntArray
{
    /**
     * @param int[] $items
     */
    public function __construct(public array $items) {}
}
