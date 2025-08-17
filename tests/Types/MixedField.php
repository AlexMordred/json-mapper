<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class MixedField
{
    public function __construct(public mixed $field) {}
}
