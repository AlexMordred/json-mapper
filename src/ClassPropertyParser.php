<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper;

use ReflectionClass;

final class ClassPropertyParser
{
    private const array BUILTIN_TYPES = [
        'bool' => true,
        'int' => true,
        'float' => true,
        'string' => true,

        'null' => true,
        'false' => true,
        'true' => true,

        'array' => true,
        'object' => true,

        'mixed' => true,

        'self' => true,
    ];

    /**
     * @param class-string $class
     *
     * @return ClassProperty[]
     */
    public function parse(string $class): array
    {
        $reflectionProperties = $this->getClassProperties($class);
        $arrayTypes = $this->getArrayTypesForClass($class);
        $mapTypes = $this->getMapTypesForClass($class);

        return $this->parseProperties($reflectionProperties, $arrayTypes, $mapTypes);
    }

    /**
     * @param class-string $class
     *
     * @return \ReflectionProperty[]
     */
    private function getClassProperties(string $class): array
    {
        $reflectionClass = new ReflectionClass($class);

        return $reflectionClass->getProperties();
    }

    /**
     * @param class-string $class
     *
     * @return array<string, string>
     */
    private function getArrayTypesForClass(string $class): array
    {
        $reflectionClass = new ReflectionClass($class);

        $docblock = $reflectionClass->getConstructor()->getDocComment() ?: null;

        return $this->parseArrayTypesFromDocblock($docblock);
    }

    /**
     * @param class-string $class
     *
     * @return array<string, MapType>
     */
    private function getMapTypesForClass(string $class): array
    {
        $reflectionClass = new ReflectionClass($class);

        $docblock = $reflectionClass->getConstructor()->getDocComment() ?: null;

        return $this->parseMapTypesFromDocblock($docblock);
    }

    /**
     * @return array<string, string>
     */
    private function parseArrayTypesFromDocblock(string|null $docblock): array
    {
        if (!$docblock) {
            return [];
        }

        // Nullability is parsed from the actual typhints and is ignored in the
        // docblock for simplicity
        $docblockWithoutNulls = $this->removeNullsFromDocblock($docblock);

        $paramMatches = [];
        preg_match_all(
            '/@param[\s]+.*\[\][\s]+\$.*/',
            $docblockWithoutNulls,
            $paramMatches
        );

        $arrayTypes = [];

        foreach ($paramMatches[0] as $match) {
            $splitResult = preg_split('/[\[\]\s\$]+/', $match);

            if ($splitResult === false) {
                continue;
            }

            [$_, $type, $propertyName] = $splitResult;

            $arrayTypes[$propertyName] = $type;
        }

        return $arrayTypes;
    }

    /**
     * @return array<string, MapType>
     */
    private function parseMapTypesFromDocblock(string|null $docblock): array
    {
        if ($docblock === null) {
            return [];
        }

        $paramMatches = [];
        preg_match_all(
            '/@param[\s]+array<.+,.+>[\s]+\$.*/',
            $docblock,
            $paramMatches
        );

        $mapTypes = [];

        foreach ($paramMatches[0] as $match) {
            $mapTypesMatches = [];
            $propertyNameMatches = [];

            preg_match_all('/<(.+),[\s]*(.+)>/', $match, $mapTypesMatches);
            preg_match_all('/\$(.+)/', $match, $propertyNameMatches);

            $propertyName = $propertyNameMatches[1][0] ?? null;

            if ($propertyName === null) {
                continue;
            }

            $keyType = $mapTypesMatches[1][0] ?? null;
            $valueType = $mapTypesMatches[2][0] ?? null;

            if ($valueType !== null) {
                // Trim the backward slashes in case a class-string starts with '\'
                $valueType = trim($valueType, '\\');
            }

            $valueIsNullable = false;

            if (str_starts_with($valueType, '?')) {
                $valueIsNullable = true;
                $valueType = substr($valueType, 1);
            } elseif (str_contains($valueType, 'null')) {
                $valueIsNullable = true;
                $valueType = str_replace('|null', '', $valueType);
                $valueType = str_replace('null|', '', $valueType);
            }

            $mapTypes[$propertyName] = new MapType(
                keyType: $keyType,
                valueType: $valueType,
                isValueTypeBuiltin: $this->isTypeBuiltin($valueType),
                isValueNullable: $valueIsNullable,
            );
        }

        return $mapTypes;
    }

    private function removeNullsFromDocblock(string $dockblock): string
    {
        $withoutPrecedingNulls = str_replace('null|', '', $dockblock);
        $withoutAnyNulls = str_replace('|null', '', $withoutPrecedingNulls);

        return $withoutAnyNulls;
    }

    /**
     * @param \ReflectionProperty[] $reflectionProperties
     * @param array<string, string> $arrayTypes
     * @param array<string, MapType> $mapTypes
     *
     * @return ClassProperty[]
     */
    private function parseProperties(
        array $reflectionProperties,
        array $arrayTypes,
        array $mapTypes,
    ): array
    {
        $properties = [];

        foreach ($reflectionProperties as $reflectionProperty) {
            $properties[] = $this->parseReflectionProperty(
                $reflectionProperty,
                $arrayTypes,
                $mapTypes,
            );
        }

        return $properties;
    }

    /**
     * @param array<string, string> $arrayTypes
     * @param array<string, MapType> $mapTypes
     */
    private function parseReflectionProperty(
        \ReflectionProperty $reflectionProperty,
        array $arrayTypes,
        array $mapTypes,
    ): ClassProperty
    {
        $mainType = $this->getMainTypeForProperty($reflectionProperty);
        $arrayType = $this->getArrayTypeForProperty(
            $reflectionProperty,
            $arrayTypes,
        );
        $mapType = $mapTypes[$reflectionProperty->name] ?? null;
        $isMap = ($mapType !== null);

        return new ClassProperty(
            class: $reflectionProperty->class,
            name: $reflectionProperty->getName(),
            isArray: $this->isTypeArray($mainType?->type),
            isMap: $isMap,
            isUnionType: $this->isPropertyUnionType($reflectionProperty),
            isIntersectionType: $this->isPropertyIntersectionType($reflectionProperty),
            mainType: $mainType,
            arrayType: $arrayType,
            mapType: $mapType,
        );
    }

    private function getReflectionPropertyType(\ReflectionProperty $property): ?string
    {
        $propertyType = $property->getType();

        if ($propertyType === null) {
            return null;
        }

        if ($propertyType instanceof \ReflectionNamedType) {
            return $propertyType->getName();
        }

        if ($this->isPropertyIntersectionType($property)) {
            /** @var \ReflectionIntersectionType $propertyType */
            return $this->getIntersectionPropertyType($propertyType);
        }

        if ($this->isPropertyUnionType($property)) {
            /** @var \ReflectionUnionType $propertyType */
            return $this->getUnionPropertyType($propertyType);
        }

        return null;
    }

    private function isPropertyIntersectionType(\ReflectionProperty $property): bool
    {
        $propertyType = $property->getType();

        return $propertyType instanceof \ReflectionIntersectionType;
    }

    private function isPropertyUnionType(\ReflectionProperty $property): bool
    {
        $propertyType = $property->getType();

        return $propertyType instanceof \ReflectionUnionType;
    }

    private function isPropertyNullable(\ReflectionProperty $property): bool
    {
        $reflectionPropertyType = $property->getType();

        return ($reflectionPropertyType !== null)
            ? $reflectionPropertyType->allowsNull()
            : false;
    }

    private function getIntersectionPropertyType(
        \ReflectionIntersectionType $intersectionType
    ): string
    {
        $intersectionSubTypes = array_map(function (\ReflectionType|\ReflectionNamedType $subtype) {
            if (method_exists($subtype, 'getName')) {
                return $subtype->getName();
            }

            return '';
        }, $intersectionType->getTypes());

        return implode('&', $intersectionSubTypes);
    }

    private function getUnionPropertyType(
        \ReflectionUnionType $unionType
    ): string
    {
        $unionSubTypes = array_map(function (\ReflectionNamedType|\ReflectionIntersectionType $subtype) {
            if ($subtype instanceof \ReflectionIntersectionType) {
                return '(' . $this->getIntersectionPropertyType($subtype) . ')';
            }

            return $subtype->getName();
        }, $unionType->getTypes());

        return implode('|', $unionSubTypes);
    }

    private function isTypeBuiltin(?string $type): bool
    {
        if ($type === null) {
            return false;
        }

        return isset(static::BUILTIN_TYPES[$type]);
    }

    private function isTypeArray(?string $type): bool
    {
        return $type === 'array';
    }

    private function getMainTypeForProperty(
        \ReflectionProperty $reflectionProperty,
    ): ?MainType
    {
        $type = $this->getReflectionPropertyType($reflectionProperty);

        if ($type === null) {
            return null;
        }

        return new MainType(
            type: $type,
            isBuiltin: $this->isTypeBuiltin($type),
            isNullable: $this->isPropertyNullable($reflectionProperty),
        );
    }

    /**
     * @param array<string, string> $arrayTypes
     */
    private function getArrayTypeForProperty(
        \ReflectionProperty $reflectionProperty,
        array $arrayTypes,
    ): ?ArrayType
    {
        $propertyName = $reflectionProperty->getName();

        $arrayType = $arrayTypes[$propertyName] ?? null;

        if ($arrayType === null) {
            return null;
        }

        // Trim the backward slashes in case a class-string starts with '\'
        $arrayType = trim($arrayType, '\\');

        return new ArrayType(
            elementType: $arrayType,
            isElementTypeBuiltin: $this->isTypeBuiltin($arrayType),
        );
    }
}
