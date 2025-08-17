<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class StringToNullableIntMap2
{
    /**
     * @param array<string, null|int> $items
     */
    public function __construct(public array $items) {}
}
