<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class MixedTypeArray
{
    /**
     * @param mixed[] $items
     */
    public function __construct(public array $items) {}
}
