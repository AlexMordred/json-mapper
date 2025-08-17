<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests\Types;

final class IntersectionTypeField
{
    public function __construct(public StringField&IntField $field) {}
}
