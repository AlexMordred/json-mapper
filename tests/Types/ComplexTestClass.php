<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class ComplexTestClass
{
    /**
     * @param string[] $stringArray
     * @param int[] $intArray
     * @param float[] $floatArray
     * @param bool[] $boolArray
     * @param \Azavyalov\JsonMapper\Tests\Types\CustomTypeArrayChild[] $customTypeArray
     */
    public function __construct(
        public string $string,
        public int $int,
        public float $float,
        public bool $bool,
        public ?int $nullableField,
        public ?int $missingNullableField,
        public array $stringArray,
        public array $intArray,
        public array $floatArray,
        public array $boolArray,
        public array $customTypeArray,
        public ComplexTestNestedLevelOneClass $nestedObjectLevelOne,
    ) {}
}
