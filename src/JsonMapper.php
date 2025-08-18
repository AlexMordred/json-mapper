<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper;

use Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException;
use Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException;
use Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException;
use Azavyalov\JsonMapper\Exceptions\InvalidMapKeyTypeException;
use Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException;
use Azavyalov\JsonMapper\Exceptions\InvalidTypeException;
use Azavyalov\JsonMapper\Exceptions\MissingRequiredPropertyException;
use Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException;
use Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException;
use Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException;
use Azavyalov\JsonMapper\Exceptions\NullNotAllowedException;
use Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException;
use Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException;
use Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException;

final class JsonMapper
{
    private ClassPropertyParser $classPropertyParser;
    private bool $allowUntypedProperties = false;
    private bool $allowIntToFloatConversion = false;

    public function __construct(
        bool $allowUntypedProperties = false,
        bool $allowIntToFloatConversion = false,
    )
    {
        $this->allowUntypedProperties = $allowUntypedProperties;
        $this->allowIntToFloatConversion = $allowIntToFloatConversion;

        $this->classPropertyParser = new ClassPropertyParser();
    }

    /**
     * @template TClass
     * @param array<string|int, mixed> $json
     * @param class-string<TClass> $class
     *
     * @return TClass
     *
     * @throws \Azavyalov\JsonMapper\Exceptions\JsonMapperException
     */
    public function map(array $json, string $class)
    {
        return $this->mapJsonToClass($json, $class);
    }

    /**
     * @param ClassProperty[] $properties
     *
     * @throws \Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException
     */
    private function validatePropertyTypes(array $properties): void
    {
        foreach ($properties as $property) {
            $this->validatePropertyType($property);
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException
     */
    private function validatePropertyType(ClassProperty $property): void
    {
        if (!$this->allowUntypedProperties) {
            $this->assertPropertyHasType($property);
        }
        $this->assertPropertyTypeIsNotUnion($property);
        $this->assertPropertyTypeIsNotIntersection($property);
        $this->assertPropertyTypeIsNotMixed($property);

        if ($property->isArray && !$property->isMap) {
            if (!$this->allowUntypedProperties) {
                $this->assertArrayPropertyHasArrayTypeSet($property);
            }
            $this->assertArrayPropertyIsNotMixedType($property);
        }

        if ($property->isMap) {
            $this->assertMapKeyTypeIsCorrect($property);
            $this->assertMapValueTypeIsNotMixedType($property);
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException
     */
    private function assertPropertyHasType(ClassProperty $property): void
    {
        if ($property->mainType === null) {
            throw new PropertyTypeMissingException($property->class, $property->name);
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException
     */
    private function assertPropertyTypeIsNotUnion(ClassProperty $property): void
    {
        if ($property->isUnionType) {
            throw new UnionTypesNotAllowedException($property->class, $property->name);
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException
     */
    private function assertPropertyTypeIsNotIntersection(ClassProperty $property): void
    {
        if ($property->isIntersectionType) {
            throw new IntersectionTypesNotAllowedException($property->class, $property->name);
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException
     */
    private function assertPropertyTypeIsNotMixed(ClassProperty $property): void
    {
        if ($property->mainType?->type === 'mixed') {
            throw new MixedTypeNotAllowedException($property->class, $property->name);
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException
     */
    private function assertArrayPropertyHasArrayTypeSet(ClassProperty $property): void
    {
        if ($property->arrayType === null) {
            throw new ArrayTypeMissingException($property->class, $property->name);
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException
     */
    private function assertMapKeyTypeIsCorrect(ClassProperty $property): void
    {
        if (
            $property->mapType->keyType !== 'string'
            && $property->mapType->keyType !== 'int'
        ) {
            throw new UnsupportedMapKeyTypeException($property->class, $property->name);
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException
     */
    private function assertCustomTypeMapElementIsJsonObject(
        ClassProperty $property,
        mixed $element,
    ): void
    {
        $valueType = $this->getJsonValueType($element);

        if ($valueType !== 'array') {
            throw new InvalidMapValueTypeException(
                className: $property->class,
                propertyName: $property->name,
                expectedType: $property->mapType->valueType,
                actualType: $valueType,
            );
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException
     */
    private function assertArrayPropertyIsNotMixedType(ClassProperty $property): void
    {
        if ($property->arrayType?->elementType === 'mixed') {
            throw new MixedTypeArraysNotAllowedException($property->class, $property->name);
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException
     */
    private function assertMapValueTypeIsNotMixedType(ClassProperty $property): void
    {
        if ($property->mapType?->valueType === 'mixed') {
            throw new MixedTypeMapsNotAllowedException($property->class, $property->name);
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException
     */
    private function assertCustomTypeArrayElementIsJsonObject(
        ClassProperty $property,
        mixed $element,
    ): void
    {
        $elementType = $this->getJsonValueType($element);

        if ($elementType !== 'array') {
            throw new InvalidArrayElementTypeException(
                className: $property->class,
                propertyName: $property->name,
                expectedType: $property->arrayType->elementType,
                actualType: $elementType,
            );
        }
    }

    /**
     * @param array<string|int, mixed> $json
     * @throws \Azavyalov\JsonMapper\Exceptions\MissingRequiredPropertyException
     */
    private function assertJsonHasProperty(
        array $json,
        ClassProperty $property
    ): void
    {
        if (!array_key_exists($property->name, $json) && !$property->mainType->isNullable) {
            throw new MissingRequiredPropertyException($property->class, $property->name);
        }
    }

    /**
     * @template TClass
     * @param array<string|int, mixed> $json
     * @param class-string<TClass> $class
     *
     * @return TClass
     *
     * @throws \Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\MissingRequiredPropertyException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     */
    private function mapJsonToClass(array $json, string $class)
    {
        $properties = $this->classPropertyParser->parse($class);

        $this->validatePropertyTypes($properties);

        $arguments = $this->extractPropertyValuesFromJson($properties, $json);

        return new $class(...$arguments);
    }

    /**
     * @param ClassProperty[] $properties
     * @param mixed[] $json
     *
     * @return mixed[]
     *
     * @throws \Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\MissingRequiredPropertyException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     */
    private function extractPropertyValuesFromJson(
        array $properties,
        array $json,
    ): array
    {
        $arguments = [];

        foreach ($properties as $property) {
            $this->assertJsonHasProperty($json, $property);

            $arguments[] = $this->extractPropertyValueFromJson($property, $json);
        }

        return $arguments;
    }

    /**
     * @param array<string|int, mixed> $json
     *
     * @throws \Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\MissingRequiredPropertyException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     */
    private function extractPropertyValueFromJson(
        ClassProperty $property,
        array $json,
    ): mixed
    {
        $value = $json[$property->name] ?? null;

        if ($property->mainType === null) {
            return $value;
        } elseif ($property->mainType->isBuiltin) {
            return $this->mapBuiltinTypeValue($property, $value);
        } else {
            return $this->mapCustomTypeValue($property, $value);
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\MissingRequiredPropertyException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     */
    private function mapBuiltinTypeValue(
        ClassProperty $property,
        mixed $value,
    ): mixed
    {
        $this->validateBuiltinType(
            className: $property->class,
            propertyName: $property->name,
            value: $value,
            expectedType: $property->mainType->type,
            propertyIsNullable: $property->mainType->isNullable
        );

        if ($property->isMap) {
            return $this->mapMap($property, $value);
        } elseif ($property->isArray) {
            return $this->mapArray($property, $value);
        }

        return $value;
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\MissingRequiredPropertyException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     */
    private function mapCustomTypeValue(
        ClassProperty $property,
        mixed $value,
    ): mixed
    {
        $this->validateCustomType(
            className: $property->class,
            propertyName: $property->name,
            value: $value,
            propertyIsNullable: $property->mainType->isNullable,
        );

        if ($value === null) {
            return null;
        }

        return $this->mapJsonToClass($value, $property->mainType->type);
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\MissingRequiredPropertyException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     */
    private function mapMap(
        ClassProperty $property,
        mixed $value,
    ): mixed
    {
        if ($value === null) {
            return null;
        }

        $this->validateMapKeys($property, $value);

        if ($property->mapType->isValueTypeBuiltin) {
            return $this->mapBuiltinTypeMapValues($property, $value);
        } else {
            return $this->mapCustomTypeMapValues($property, $value);
        }
    }

    /**
     * @param array<string|int, mixed> $elements
     *
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapKeyTypeException
     */
    private function validateMapKeys(
        ClassProperty $property,
        array $elements,
    ): void
    {
        $expectedKeyType = $property->mapType->keyType;

        foreach ($elements as $mapKey => $_) {
            $actualKeyType = $this->getJsonValueType($mapKey);

            if ($expectedKeyType !== $actualKeyType) {
                throw new InvalidMapKeyTypeException(
                    className: $property->class,
                    propertyName: $property->name,
                    expectedType: $expectedKeyType,
                    actualType: $actualKeyType,
                );
            }
        }
    }

    /**
     * @param array<string|int, mixed> $values
     * @return array<string|int, mixed>
     *
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException
     */
    private function mapBuiltinTypeMapValues(
        ClassProperty $property,
        array $values,
    ): array
    {
        $processedValues = [];

        foreach ($values as $key => $value) {
            $this->validateBuiltinTypeMapValue($property, $value);

            $processedValues[$key] = $this
                ->mapBuiltinTypeMapValue($property, $value);
        }

        return $processedValues;
    }

    private function mapBuiltinTypeMapValue(
        ClassProperty $property,
        mixed $value,
    ): mixed
    {
        if (
            $property->mapType->valueType === 'float'
            && $this->allowIntToFloatConversion
        ) {
            return floatval($value);
        }

        return $value;
    }

    /**
     * @param array<string|int, mixed> $elements
     * @return array<string|int, object>
     *
     * @throws \Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\MissingRequiredPropertyException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     */
    private function mapCustomTypeMapValues(
        ClassProperty $property,
        array $elements,
    ): array
    {
        $mappedElements = [];

        foreach ($elements as $key => $value) {
            $this->assertCustomTypeMapElementIsJsonObject($property, $value);

            $mappedElements[$key] = $this
                ->mapJsonToClass($value, $property->mapType->valueType);
        }

        return $mappedElements;
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\MissingRequiredPropertyException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     */
    private function mapArray(
        ClassProperty $property,
        mixed $value,
    ): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($property->arrayType === null) {
            return $value;
        } elseif ($property->arrayType->isElementTypeBuiltin) {
            return $this->mapBuiltinTypeArrayElements($property, $value);
        } else {
            return $this->mapCustomTypeArrayElements($property, $value);
        }
    }

    /**
     * @param array<string|int, mixed> $elements
     * @return array<int, mixed>
     *
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException
     */
    private function mapBuiltinTypeArrayElements(
        ClassProperty $property,
        array $elements,
    ): array
    {
        $processedElements = [];

        foreach ($elements as $element) {
            $this->validateBuiltinTypeArrayElement($property, $element);

            $processedElements[] = $this
                ->mapBuiltinTypeArrayElement($property, $element);
        }

        return $processedElements;
    }

    private function mapBuiltinTypeArrayElement(
        ClassProperty $property,
        mixed $element,
    ): mixed
    {
        if (
            $property->arrayType->elementType === 'float'
            && $this->allowIntToFloatConversion
        ) {
            return floatval($element);
        }

        return $element;
    }

    /**
     * @param array<string|int, mixed> $elements
     * @return array<int, object>
     *
     * @throws \Azavyalov\JsonMapper\Exceptions\ArrayTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\IntersectionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeArraysNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeMapsNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\MixedTypeNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnionTypesNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\PropertyTypeMissingException
     * @throws \Azavyalov\JsonMapper\Exceptions\UnsupportedMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\MissingRequiredPropertyException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapKeyTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     */
    private function mapCustomTypeArrayElements(
        ClassProperty $property,
        array $elements,
    ): array
    {
        $mappedElements = [];

        foreach ($elements as $element) {
            $this->assertCustomTypeArrayElementIsJsonObject($property, $element);

            $mappedElements[] = $this->mapJsonToClass(
                $element,
                $property->arrayType->elementType,
            );
        }

        return $mappedElements;
    }

    /**
     * Reflection class and gettype() return types differently. This method
     * normalizes the gettype() result so that it matches the reflection class
     * values.
     */
    private function getJsonValueType(mixed $jsonValue): string
    {
        $type = gettype($jsonValue);

        return match ($type) {
            'integer' => 'int',
            'double' => 'float',
            'boolean' => 'bool',
            'NULL' => 'null',
            default => $type,
        };
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     */
    private function validateBuiltinType(
        string $className,
        string $propertyName,
        mixed $value,
        string $expectedType,
        bool $propertyIsNullable
    ): void
    {
        $actualType = $this->getJsonValueType($value);

        if ($expectedType === 'float' && $actualType === 'int' && $this->allowIntToFloatConversion) {
            // all good
        } elseif ($actualType === 'null' && $propertyIsNullable) {
            // all good
        } elseif ($actualType === 'null') {
            throw new NullNotAllowedException($className, $propertyName);
        } elseif ($expectedType !== $actualType) {
            throw new InvalidTypeException(
                $className, $propertyName, $expectedType, $actualType
            );
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\NullNotAllowedException
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidTypeException
     */
    private function validateCustomType(
        string $className,
        string $propertyName,
        mixed $value,
        bool $propertyIsNullable
    ): void
    {
        // Only arrays can be parsed into custom types (nested objects)
        $expectedType = 'array';
        $actualType = $this->getJsonValueType($value);

        if ($actualType === 'null' && $propertyIsNullable) {
            // all good
        } elseif ($actualType === 'null') {
            throw new NullNotAllowedException($className, $propertyName);
        } elseif ($expectedType !== $actualType) {
            throw new InvalidTypeException(
                $className, $propertyName, $expectedType, $actualType
            );
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidArrayElementTypeException
     */
    private function validateBuiltinTypeArrayElement(
        ClassProperty $property,
        mixed $element,
    ): void
    {
        try {
            $this->validateBuiltinType(
                className: $property->class,
                propertyName: $property->name,
                value: $element,
                expectedType: $property->arrayType->elementType,
                propertyIsNullable: false,  // Arrays are not allowed to have nulls as elements
            );
        } catch (NullNotAllowedException $e) {
            throw new InvalidArrayElementTypeException(
                className: $e->getClassName(),
                propertyName: $e->getPropertyName(),
                expectedType: $property->arrayType->elementType,
                actualType: 'null',
            );
        } catch (InvalidTypeException $e) {
            throw new InvalidArrayElementTypeException(
                className: $e->getClassName(),
                propertyName: $e->getPropertyName(),
                expectedType: $e->getExpectedType(),
                actualType: $e->getActualType(),
            );
        }
    }

    /**
     * @throws \Azavyalov\JsonMapper\Exceptions\InvalidMapValueTypeException
     */
    private function validateBuiltinTypeMapValue(
        ClassProperty $property,
        mixed $element,
    ): void
    {
        try {
            $this->validateBuiltinType(
                className: $property->class,
                propertyName: $property->name,
                value: $element,
                expectedType: $property->mapType->valueType,
                propertyIsNullable: $property->mapType->isValueNullable,
            );
        } catch (NullNotAllowedException $e) {
            throw new InvalidMapValueTypeException(
                className: $e->getClassName(),
                propertyName: $e->getPropertyName(),
                expectedType: $property->mapType->valueType,
                actualType: 'null',
            );
        } catch (InvalidTypeException $e) {
            throw new InvalidMapValueTypeException(
                className: $e->getClassName(),
                propertyName: $e->getPropertyName(),
                expectedType: $e->getExpectedType(),
                actualType: $e->getActualType(),
            );
        }
    }
}
