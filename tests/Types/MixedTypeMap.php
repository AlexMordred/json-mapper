<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class MixedTypeMap
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(public array $items) {}
}
