<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class NullField
{
    public function __construct(public null $field) {}
}
