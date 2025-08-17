<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class PartiallyTypedObject
{
    public function __construct(
        public string $typedField,
        public $untypedField,
        public UntypedField $childWithUntypedField,
    ) {}
}
