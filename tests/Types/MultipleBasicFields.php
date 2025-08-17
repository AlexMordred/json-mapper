<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class MultipleBasicFields
{
    public function __construct(
        public string $string_field,
        public int $int_field,
        public float $float_field,
        public bool $bool_field,
    ) {}
}
