<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Exceptions;

class JsonMapperException extends \Exception
{
    private string $className;
    private string $propertyName;

    public function __construct(string $className, string $propertyName) {
        $this->className = $className;
        $this->propertyName = $propertyName;

        $this->makeMessage();
    }

    protected function getPropertyPath(): string
    {
        $propertyPath = '$' . $this->propertyName;

        if ($this->className) {
            $propertyPath = $this->className . '::' . $propertyPath;
        }

        return $propertyPath;
    }

    protected function makeMessage(): void
    {
        $propertyPath = $this->getPropertyPath();

        $this->message = "Something is wrong with the value for the {$propertyPath} property in the given JSON.";
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function getPropertyName(): ?string
    {
        return $this->propertyName;
    }
}
