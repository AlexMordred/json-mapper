<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class EnumField
{
    public function __construct(public DummyEnum $field) {}
}
