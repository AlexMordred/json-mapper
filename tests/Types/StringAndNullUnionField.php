<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class StringAndNullUnionField
{
    public function __construct(public string|null $field) {}
}
