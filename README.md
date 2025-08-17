This package maps PHP arrays to strictly-typed objects. You can convert arrays to Data Transfer Objects (DTO), Value Objects (VO), validate API JSON responses, or basically instantiate any classes with arrays. Inspired by Go/Rust/Zig/... structs and was born out of a necesity to validate 3rd-party API JSON responses.

## Installation

```
composer require azavyalov/json-mapper
```

## Usage

### Example

Below is a working example demonstrating most of the features (read the comments in the code too). Please read the following sections to have a better idea of what is and what is not allowed.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

// Import the mapper
use Azavyalov\JsonMapper\JsonMapper;

// Create a class you want to map your data to. Each field must have a type.
final class SomeClass
{
    /**
     * @param string[] $stringArray
     * @param int[] $intArray
     * @param float[] $floatArray
     * @param bool[] $boolArray
     * @param CustomTypeArrayElement[] $customTypeArray
     * @param array<string, bool> $stringToBoolMap
     */
    public function __construct(
        public string $stringField,  // Property names should correspond to JSON field names
        public int $intField,
        public float $floatField,
        public bool $boolField,
        public ?int $nullableField,
        public ?int $missingNullableField,  // A nullable property will be set to `null` if the field is missing in the JSON
        public int $missingNonNullableField,  // If a non-nullable property is missing in the JSON - an exception will be thrown
        public array $stringArray,
        public array $intArray,
        public array $floatArray,
        public array $boolArray,
        public array $customTypeArray,
        public array $stringToBoolMap,
        public NestedObjectClass $nestedObject, // The depth of nested objects is not limited
    ) {}
}

final class CustomTypeArrayElement
{
    public function __construct(
        public readonly string $field_one,
        public readonly bool $field_two,
    ) {}
}

final class NestedObjectClass
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $releaseDate,
    ) {}
}

$array = [
    'stringField' => 'Some value',
    'intField' => 123,
    'floatField' => 1.23,
    'boolField' => false,
    'nullableField' => null,
    'missingNonNullableField' => 123,
    'stringArray' => ['String One', 'String Two'],
    'intArray' => [2, 5, 7],
    'floatArray' => [1.2, 2.5, 3.7],
    'boolArray' => [true, false, false],
    'customTypeArray' => [
        ['field_one' => '475754qweqw', 'field_two' => true],
        ['field_one' => '456457mhmty', 'field_two' => false],
    ],
    'nestedObject' => [
        'id' => 567,
        'name' => 'Something Wild',
        'releaseDate' => '1997-11-16',
    ],
    'stringToBoolMap' => [
        '1-2' => true,
        '2-5' => true,
        '5-10' => false,
    ],
];

$mapper = new JsonMapper();

try {
    $result = $mapper->map($array, SomeClass::class);
} catch (\Azavyalov\JsonMapper\Exceptions\JsonMapperException $e) {
    // Something in the provided JSON is unexpected. Handle the exception.
    // $e->getMessage();
    // $e->getClassName();
    // $e->getPropertyName();
    throw $e;
}

print_r($result);

```

### On Strictness

Initially I was not going to implement maps and allow untyped properties - both kind of defy the core idea of this project. However, we live in an imperfect world :) We have to deal with 3rd party data or APIs which we have no control over. A JSON object can have a key named `'1-2'` but PHP doesn't allow class properties and variables to be named like that - `$1-2`. I do incourage you to use nested objects instead of maps wherever the JSON keys allow you to.

Untyped properties are not allowed by default and should be an absolute last resort. Having an object with untyped properties is better than having raw PHP arrays, depending on your usage, but won't bring you the full benifits of this package. To enable untyped properties instantiating the mapper like this: `$mapper = new JsonMapper(allowUntypedProperties: true);`. This option is turned off by default.

### The Rules

This mapper is strict. Implicit conversions are not allowed, e.g. passing a `'0`' instead of `0` to an int field would result in an exception. Each class property has to have a type specified, which could be either a builtin PHP type or another class (nested objects). Properties can be nullable. Union and `mixed` types are not allowed.

Each array has to have its element type specified in the DocBlock in the `[]` format, e.g. `@param string[] $myArrayField`. Array elements could also be another class objects, e.g. `@param ArrayElementClass[] $myArrayField`.

If each element in an array is of the same type and cannot be null - you want a typed array, e.g. `string[]`. If elements of an array are of a different type - you want a nested object (another class with typed properties).

A proeprty described in the class must be present in the JSON unless the property is marked as nullable. Properties not described in the class but present in the JSON will be simply ignored.

By default, each property has to have a type. You can enable untyped properties instantiating the mapper like this: `$mapper = new JsonMapper(allowUntypedProperties: true);`. This is discouraged.

### Array type declarations

The type of PHP arrays has to be specified in the class' constructor's DocBlock. For arrays use the `[]` format and for maps use the `array<>` format with both key and value types specified.

What's the difference between an array and a map?

In PHP, an "array" is not an actual array and is more like a representation of a loose JSON object and not an actual array. An actual array (as seen in stritcly typed programming languages) is a sequence of values of the same type. The keys of an array are subsequential numbers starting from 0 (0 -> 1 -> 2 -> 3 -> ...).

A map is a collection of key-value pairs. With maps your keys could be both numbers and strings, and values could be whichiver type just like in an array. This package allows the keys to be either `int` or `string`, but not both (`int|string` is not allowed). All the values should be of the same type.

```
final class SomeClass
{
    /**
     * @param string[] $arrayOfStrings
     * @param CustomTypeArrayElement[] $arrayOfCustomObjects
     * @param array<string, int> $stringToIntMap
     * @param array<int, CustomObjectClass> $intToCustomObjectMap
     */
    public function __construct(
        public array $arrayOfStrings,
        public array $arrayOfCustomObjects,
        public array $stringToIntMap,
        public array $intToCustomObjectMap,
    ) {}
}
```
