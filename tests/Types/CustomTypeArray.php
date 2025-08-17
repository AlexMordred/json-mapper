<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class CustomTypeArray
{
    /**
     * @param \Azavyalov\JsonMapper\Tests\Types\CustomTypeArrayChild[] $items
     */
    public function __construct(public array $items) {}
}
