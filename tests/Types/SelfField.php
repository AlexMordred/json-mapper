<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class SelfField
{
    public function __construct(public self $field) {}
}
