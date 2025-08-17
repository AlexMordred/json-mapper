<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper;

final class ArrayType
{
    /**
     * @param class-string|string $elementType
     */
    public function __construct(
        public readonly string $elementType,
        public readonly bool $isElementTypeBuiltin,
    ) {}
}
