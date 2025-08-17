<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class UntypedObject
{
    public function __construct(
        public $untypedField,
        public array $untypedArray,
        public array $untypedMap,
    ) {}
}
