<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class NullableStringArray
{
    /**
     * @param string[]|null $items
     */
    public function __construct(public ?array $items) {}
}
