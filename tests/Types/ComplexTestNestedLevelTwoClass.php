<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class ComplexTestNestedLevelTwoClass
{
    /**
     * @param string[] $stringArray
     */
    public function __construct(
        public string $string,
        public ?int $nullableField,
        public array $stringArray,
    ) {}
}
