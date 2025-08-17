<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class NullableStringField
{
    public function __construct(public ?string $field) {}
}
