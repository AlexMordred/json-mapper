<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper;

final class MainType
{
    /**
     * @param class-string|string $type
     */
    public function __construct(
        public readonly string $type,
        public readonly bool $isBuiltin,
        public readonly bool $isNullable,
    ) {}
}
