<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class RelativeCustomTypeArray
{
    /**
     * @param CustomTypeArrayChild[] $items
     */
    public function __construct(public array $items) {}
}
