<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class StringToFloatMap
{
    /**
     * @param array<string, float> $items
     */
    public function __construct(public array $items) {}
}
