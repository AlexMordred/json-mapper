<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Exceptions;

class InvalidTypeException extends JsonMapperException
{
    protected string $expectedType;
    protected string $actualType;

    public function __construct(
        string $className,
        string $propertyName,
        string $expectedType,
        string $actualType,
    ) {
        $this->expectedType = $expectedType;
        $this->actualType = $actualType;

        parent::__construct($className, $propertyName);
    }

    protected function makeMessage(): void
    {
        $propertyPath = $this->getPropertyPath();

        $this->message = "Property {$propertyPath} expects a value of type '{$this->expectedType}', '{$this->actualType}' found.";
    }

    public function getExpectedType(): string
    {
        return $this->expectedType;
    }

    public function getActualType(): string
    {
        return $this->actualType;
    }
}
