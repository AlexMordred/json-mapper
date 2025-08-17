<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class InterfaceField
{
    public function __construct(public DummyInterface $field) {}
}
