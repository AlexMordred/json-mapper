<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class FloatArray
{
    /**
     * @param float[] $items
     */
    public function __construct(public array $items) {}
}
