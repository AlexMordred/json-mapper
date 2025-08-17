<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class FalseField
{
    public function __construct(public false $field) {}
}
