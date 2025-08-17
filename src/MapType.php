<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper;

final class MapType
{
    /**
     * @param class-string|string|null $keyType
     */
    public function __construct(
        public readonly ?string $keyType,
        public readonly ?string $valueType,
        public readonly bool $isValueTypeBuiltin,
        public readonly bool $isValueNullable,
    ) {}
}
