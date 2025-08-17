<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper;

final class ClassProperty
{
    /**
     * @param class-string $class
     */
    public function __construct(
        public readonly string $class,
        public readonly string $name,
        public readonly bool $isArray,
        public readonly bool $isMap,
        public readonly bool $isUnionType,
        public readonly bool $isIntersectionType,
        public readonly ?MainType $mainType,
        public readonly ?ArrayType $arrayType,
        public readonly ?MapType $mapType,
    ) {}
}
