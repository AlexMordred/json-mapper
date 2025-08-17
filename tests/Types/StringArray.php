<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class StringArray
{
    /**
     * @param string[] $items
     */
    public function __construct(public array $items) {}
}
