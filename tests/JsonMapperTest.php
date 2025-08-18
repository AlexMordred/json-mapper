<?php

declare(strict_types=1);

namespace Azavyalov\JsonMapper\Tests;

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
use Azavyalov\JsonMapper\JsonMapper;
use Azavyalov\JsonMapper\Tests\Types\BoolArray;
use Azavyalov\JsonMapper\Tests\Types\BoolField;
use Azavyalov\JsonMapper\Tests\Types\BoolToIntMap;
use Azavyalov\JsonMapper\Tests\Types\ChildClass;
use Azavyalov\JsonMapper\Tests\Types\ClassToIntType;
use Azavyalov\JsonMapper\Tests\Types\ComplexTestClass;
use Azavyalov\JsonMapper\Tests\Types\CustomTypeArray;
use Azavyalov\JsonMapper\Tests\Types\CustomTypeArrayChild;
use Azavyalov\JsonMapper\Tests\Types\CustomTypeMap;
use Azavyalov\JsonMapper\Tests\Types\FloatArray;
use Azavyalov\JsonMapper\Tests\Types\FloatField;
use Azavyalov\JsonMapper\Tests\Types\FloatToIntMap;
use Azavyalov\JsonMapper\Tests\Types\IntArray;
use Azavyalov\JsonMapper\Tests\Types\IntersectionTypeField;
use Azavyalov\JsonMapper\Tests\Types\IntField;
use Azavyalov\JsonMapper\Tests\Types\IntToIntMap;
use Azavyalov\JsonMapper\Tests\Types\InvalidMapDocblock;
use Azavyalov\JsonMapper\Tests\Types\StringToIntMap;
use Azavyalov\JsonMapper\Tests\Types\MixedField;
use Azavyalov\JsonMapper\Tests\Types\MixedTypeArray;
use Azavyalov\JsonMapper\Tests\Types\MixedTypeMap;
use Azavyalov\JsonMapper\Tests\Types\MultipleBasicFields;
use Azavyalov\JsonMapper\Tests\Types\NullableParentClass;
use Azavyalov\JsonMapper\Tests\Types\NullableStringArray;
use Azavyalov\JsonMapper\Tests\Types\NullableStringField;
use Azavyalov\JsonMapper\Tests\Types\ParentClass;
use Azavyalov\JsonMapper\Tests\Types\PartiallyTypedObject;
use Azavyalov\JsonMapper\Tests\Types\StringArray;
use Azavyalov\JsonMapper\Tests\Types\StringField;
use Azavyalov\JsonMapper\Tests\Types\StringToFloatMap;
use Azavyalov\JsonMapper\Tests\Types\StringToNullableIntMap;
use Azavyalov\JsonMapper\Tests\Types\UnionTypeField;
use Azavyalov\JsonMapper\Tests\Types\UntypedArray;
use Azavyalov\JsonMapper\Tests\Types\UntypedField;
use Azavyalov\JsonMapper\Tests\Types\UntypedObject;
use PHPUnit\Framework\TestCase;

class JsonMapperTest extends TestCase
{
    public function test_json_should_contain_all_the_required_fields(): void
    {
        $expectedException = new MissingRequiredPropertyException(StringField::class, 'field');
        $this->expectExceptionObject($expectedException);

        $mapper = new JsonMapper();
        $mapper->map([], StringField::class);
    }

    public function test_nullable_fields_missing_in_json_will_be_set_to_null(): void
    {
        $mapper = new JsonMapper();
        $result = $mapper->map([], NullableStringField::class);

        $this->assertNull($result->field);
    }

    public function test_all_mappable_class_properties_should_have_a_type(): void
    {
        $expectedException = new PropertyTypeMissingException(UntypedField::class, 'field');
        $this->expectExceptionObject($expectedException);

        $mapper = new JsonMapper();
        $mapper->map(['field' => '666'], UntypedField::class);
    }

    public function test_mixed_type_is_not_allowed(): void
    {
        $expectedException = new MixedTypeNotAllowedException(MixedField::class, 'field');
        $this->expectExceptionObject($expectedException);

        $mapper = new JsonMapper();
        $mapper->map(['field' => '666'], MixedField::class);
    }

    public function test_value_types_are_strictly_checked(): void
    {
        // type => value
        $values = [
            'string' => '666',
            'int' => 666,
            'float' => 66.6,
            'bool' => true,
        ];

        // type => class with the field of type
        $classes = [
            'string' => StringField::class,
            'int' => IntField::class,
            'float' => FloatField::class,
            'bool' => BoolField::class,
        ];

        $mapper = new JsonMapper();

        foreach ($values as $valueType => $value) {
            foreach ($classes as $classFieldType => $class) {
                if ($valueType === $classFieldType) {
                    continue;
                }

                $callback = function () use ($mapper, $value, $class) {
                    $json = ['field' => $value];
                    $mapper->map($json, $class);
                };

                $this->assertInvalidTypeExceptionThrown(
                    $callback,
                    $class,
                    'field',
                    $classFieldType,
                    $valueType
                );
            }
        }
    }

    public function test_non_nullable_fields_cannot_be_set_to_null(): void
    {
        $expectedException = new NullNotAllowedException(StringField::class, 'field');
        $this->expectExceptionObject($expectedException);

        $mapper = new JsonMapper();
        $mapper->map(['field' => null], StringField::class);
    }

    public function test_nullable_fields_can_be_set_to_null(): void
    {
        $mapper = new JsonMapper();
        $result = $mapper->map(['field' => null], NullableStringField::class);

        $this->assertNull($result->field);
    }

    public function test_union_types_are_not_allowed(): void
    {
        $expectedException = new UnionTypesNotAllowedException(UnionTypeField::class, 'field');
        $this->expectExceptionObject($expectedException);

        $mapper = new JsonMapper();
        $mapper->map(['field' => '666'], UnionTypeField::class);
    }

    public function test_intersection_types_are_not_allowed(): void
    {
        $expectedException = new IntersectionTypesNotAllowedException(IntersectionTypeField::class, 'field');
        $this->expectExceptionObject($expectedException);

        $mapper = new JsonMapper();
        $mapper->map(['field' => '666'], IntersectionTypeField::class);
    }

    public function test_mapping_basic_builtin_types(): void
    {
        // type => value
        $values = [
            'string' => '666',
            'int' => 666,
            'float' => 66.6,
            'bool' => true,
        ];

        // type => class with the field of type
        $classes = [
            'string' => StringField::class,
            'int' => IntField::class,
            'float' => FloatField::class,
            'bool' => BoolField::class,
        ];

        $mapper = new JsonMapper();

        foreach ($values as $type => $value) {
            $class = $classes[$type];
            $result = $mapper->map(['field' => $value], $class);

            $this->assertSame($value, $result->field);
        }
    }

    public function test_mapping_multiple_fields_at_the_same_time(): void
    {
        $json = [
            'string_field' => '666',
            'int_field' => 666,
            'float_field' => 66.6,
            'bool_field' => true,
        ];

        $mapper = new JsonMapper();
        $result = $mapper->map($json, MultipleBasicFields::class);

        foreach ($json as $field => $value) {
            $this->assertSame($value, $result->{$field});
        }
    }

    public function test_cannot_set_a_json_object_to_field_typed_as_basic_type(): void
    {
        $expectedException = new InvalidTypeException(
            StringField::class, 'field', 'string', 'array'
        );
        $this->expectExceptionObject($expectedException);

        $json = [
            'field' => [
                'subfield' => '666',
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, StringField::class);
    }

    public function test_cannot_set_a_basic_value_to_field_typed_as_object(): void
    {
        $expectedException = new InvalidTypeException(
            ParentClass::class, 'childClass', 'array', 'string'
        );
        $this->expectExceptionObject($expectedException);

        $json = [
            'childClass' => '666'
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, ParentClass::class);
    }

    public function test_nested_json_object_can_be_mapped_to_custom_class(): void
    {
        $stringValue = '666';
        $json = [
            'childClass' => [
                'field' => $stringValue,
            ],
        ];

        $mapper = new JsonMapper();
        $result = $mapper->map($json, ParentClass::class);

        $this->assertEquals('object', gettype($result->childClass));
        $this->assertEquals(ChildClass::class, $result->childClass::class);
        $this->assertSame($stringValue, $result->childClass->field);
    }

    public function test_cannot_map_nested_json_object_to_custom_class_if_fields_dont_match(): void
    {
        $expectedException = new MissingRequiredPropertyException(ChildClass::class, 'field');
        $this->expectExceptionObject($expectedException);

        $json = [
            'childClass' => [
                'unexpected-field' => '666',
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, ParentClass::class);
    }

    public function test_non_nullable_object_fields_cannot_be_set_to_null(): void
    {
        $expectedException = new NullNotAllowedException(ParentClass::class, 'childClass');
        $this->expectExceptionObject($expectedException);

        $mapper = new JsonMapper();
        $mapper->map(['childClass' => null], ParentClass::class);
    }

    public function test_nullable_objects_fields_can_be_set_to_null(): void
    {
        $mapper = new JsonMapper();
        $result = $mapper->map(['childClass' => null], NullableParentClass::class);

        $this->assertNull($result->childClass);
    }

    public function test_array_types_must_be_specified_in_docblock(): void
    {
        $expectedException = new ArrayTypeMissingException(UntypedArray::class, 'items');
        $this->expectExceptionObject($expectedException);

        $json = [
            'items' => [
                555,
                666,
                777,
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, UntypedArray::class);
    }

    public function test_cannot_assign_a_non_array_value_to_an_array(): void
    {
        $expectedException = new InvalidTypeException(
            StringArray::class,
            'items',
            'array',
            'string'
        );
        $this->expectExceptionObject($expectedException);

        $json = [
            'items' => '666',
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, StringArray::class);
    }

    public function test_cannot_assign_null_to_a_non_nullable_array(): void
    {
        $expectedException = new NullNotAllowedException(
            StringArray::class,
            'items',
        );
        $this->expectExceptionObject($expectedException);

        $json = [
            'items' => null,
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, StringArray::class);
    }

    public function test_can_assign_null_to_a_nullable_array(): void
    {
        $json = [
            'items' => null,
        ];

        $mapper = new JsonMapper();
        $result = $mapper->map($json, NullableStringArray::class);

        $this->assertNull($result->items);
    }

    public function test_array_can_contain_simple_values(): void
    {
        // type => value
        $values = [
            StringArray::class => ['555', '666'],
            IntArray::class => [555, 666],
            FloatArray::class => [5.55, 6.66],
            BoolArray::class => [true, false],
        ];

        $mapper = new JsonMapper();

        foreach ($values as $class => $value) {
            $json = ['items' => $value];

            $result = $mapper->map($json, $class);

            $this->assertSame($value, $result->items);
        }
    }

    public function test_array_can_contain_typed_objects(): void
    {
        $json = [
            'items' => [
                ['field' => '555'],
                ['field' => '666'],
            ],
        ];

        $mapper = new JsonMapper();
        $result = $mapper->map($json, CustomTypeArray::class);

        foreach ($json['items'] as $i => $value) {
            $this->assertEquals(CustomTypeArrayChild::class, $result->items[$i]::class);
            $this->assertSame($value['field'], $result->items[$i]->field);
        }
    }

    public function test_cannot_set_a_json_object_to_array_element_typed_as_basic_type(): void
    {
        $expectedException = new InvalidArrayElementTypeException(
            StringArray::class, 'items', 'string', 'array'
        );
        $this->expectExceptionObject($expectedException);

        $json = [
            'items' => [
                ['field' => '555'],
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, StringArray::class);
    }

    public function test_cannot_set_a_basic_value_to_array_element_typed_as_object(): void
    {
        $expectedException = new InvalidArrayElementTypeException(
            CustomTypeArray::class, 'items', CustomTypeArrayChild::class, 'string'
        );
        $this->expectExceptionObject($expectedException);

        $json = [
            'items' => [
                '555',
                '666',
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, CustomTypeArray::class);
    }

    public function test_array_elements_cannot_be_null(): void
    {
        $expectedException = new InvalidArrayElementTypeException(
            StringArray::class, 'items', 'string', 'null'
        );
        $this->expectExceptionObject($expectedException);

        $json = [
            'items' => [
                '555',
                null,
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, StringArray::class);
    }

    public function test_cannot_map_nested_json_object_in_array_to_custom_class_if_fields_dont_match(): void
    {
        $expectedException = new MissingRequiredPropertyException(CustomTypeArrayChild::class, 'field');
        $this->expectExceptionObject($expectedException);

        $json = [
            'items' => [
                ['unexpected-field' => '666'],
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, CustomTypeArray::class);
    }

    public function test_mixed_type_arrays_are_not_allowed(): void
    {
        $expectedException = new MixedTypeArraysNotAllowedException(MixedTypeArray::class, 'items');
        $this->expectExceptionObject($expectedException);

        $json = [
            'items' => [
                555,
                '666',
                77.7,
                true,
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, MixedTypeArray::class);
    }

    public function test_key_and_value_types_of_a_map_must_be_specified(): void
    {
        // We don't know the difference between array and map without a proper
        // docblock
        $expectedException = new ArrayTypeMissingException(
            InvalidMapDocblock::class,
            'items',
        );
        $this->expectExceptionObject($expectedException);

        $json = ['items' => []];

        $mapper = new JsonMapper();
        $mapper->map($json, InvalidMapDocblock::class);
    }

    public function test_map_key_type_cannot_be_float(): void
    {
        $expectedException = new UnsupportedMapKeyTypeException(
            FloatToIntMap::class,
            'items',
        );
        $this->expectExceptionObject($expectedException);

        $json = ['items' => []];

        $mapper = new JsonMapper();
        $mapper->map($json, FloatToIntMap::class);
    }

    public function test_map_key_type_cannot_be_bool(): void
    {
        $expectedException = new UnsupportedMapKeyTypeException(
            BoolToIntMap::class,
            'items',
        );
        $this->expectExceptionObject($expectedException);

        $json = ['items' => []];

        $mapper = new JsonMapper();
        $mapper->map($json, BoolToIntMap::class);
    }

    public function test_map_key_type_cannot_be_a_class_string(): void
    {
        $expectedException = new UnsupportedMapKeyTypeException(
            ClassToIntType::class,
            'items',
        );
        $this->expectExceptionObject($expectedException);

        $json = ['items' => []];

        $mapper = new JsonMapper();
        $mapper->map($json, ClassToIntType::class);
    }

    public function test_map_key_type_can_be_string(): void
    {
        $this->expectNotToPerformAssertions();

        $json = ['items' => []];

        $mapper = new JsonMapper();
        $mapper->map($json, StringToIntMap::class);
    }

    public function test_map_key_type_can_be_int(): void
    {
        $this->expectNotToPerformAssertions();

        $json = ['items' => []];

        $mapper = new JsonMapper();
        $mapper->map($json, IntToIntMap::class);
    }

    public function test_failes_when_int_keys_are_provided_to_a_map_expecting_string_keys(): void
    {
        $expectedException = new InvalidMapKeyTypeException(
            StringToIntMap::class,
            'items',
            'string',
            'int',
        );
        $this->expectExceptionObject($expectedException);

        $json = ['items' => [
            123 => 555,
            234 => 666,
        ]];

        $mapper = new JsonMapper();
        $mapper->map($json, StringToIntMap::class);
    }

    public function test_failes_when_string_keys_are_provided_to_a_map_expecting_int_keys(): void
    {
        $expectedException = new InvalidMapKeyTypeException(
            IntToIntMap::class,
            'items',
            'int',
            'string',
        );
        $this->expectExceptionObject($expectedException);

        $json = ['items' => [
            'someKey' => 555,
            'anotherKey' => 666,
        ]];

        $mapper = new JsonMapper();
        $mapper->map($json, IntToIntMap::class);
    }

    public function test_all_map_keys_should_be_of_the_same_type(): void
    {
        $expectedException = new InvalidMapKeyTypeException(
            StringToIntMap::class,
            'items',
            'string',
            'int',
        );
        $this->expectExceptionObject($expectedException);

        $json = ['items' => [
            'stringKey' => 555,
            123 => 666,
        ]];

        $mapper = new JsonMapper();
        $mapper->map($json, StringToIntMap::class);
    }

    public function test_actual_map_values_should_be_of_the_expected_type(): void
    {
        $expectedException = new InvalidMapValueTypeException(
            StringToIntMap::class,
            'items',
            'int',
            'bool',
        );
        $this->expectExceptionObject($expectedException);

        $json = [
            'items' => [
                '1-2' => true,
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, StringToIntMap::class);
    }

    public function tests_mapping_a_map_with_builtin_type_values(): void
    {
        $json = [
            'items' => [
                '0.25-0.5' => 276,
                '1-2' => 21,
                '2-5' => 12,
            ],
        ];

        $mapper = new JsonMapper();
        $result = $mapper->map($json, StringToIntMap::class);

        $this->assertSame($json['items'], $result->items);
    }

    public function test_null_cannot_be_assigned_to_a_non_nullable_map_property(): void
    {
        $expectedException = new InvalidMapValueTypeException(
            StringToIntMap::class,
            'items',
            'int',
            'null',
        );
        $this->expectExceptionObject($expectedException);

        $json = [
            'items' => [
                '0.25-0.5' => null,
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, StringToIntMap::class);
    }

    public function test_null_can_be_assigned_to_a_non_nullable_map_property(): void
    {
        $json = [
            'items' => [
                'someKey' => null,
            ],
        ];

        $mapper = new JsonMapper();
        $result = $mapper->map($json, StringToNullableIntMap::class);

        $this->assertSame($json['items'], $result->items);
    }

    public function tests_mapping_a_map_with_custom_type_values(): void
    {
        $json = [
            'items' => [
                'someKey' => ['field' => 'someString'],
                'anotherKey' => ['field' => 'anotherString'],
            ],
        ];

        $mapper = new JsonMapper();
        $result = $mapper->map($json, CustomTypeMap::class);

        $expectedResult = new CustomTypeMap(
            items: [
                'someKey' => new ChildClass(field: 'someString'),
                'anotherKey' => new ChildClass(field: 'anotherString'),
            ],
        );

        $this->assertCount(2, $result->items);
        $this->assertSame(count($expectedResult->items), count($result->items));

        $this->assertInstanceOf(ChildClass::class, $result->items['someKey']);
        $this->assertInstanceOf(ChildClass::class, $result->items['anotherKey']);

        $this->assertSame(
            $expectedResult->items['someKey']->field,
            $result->items['someKey']->field
        );
        $this->assertSame(
            $expectedResult->items['anotherKey']->field,
            $result->items['anotherKey']->field
        );
    }

    public function test_cannot_set_a_basic_value_to_a_map_with_values_typed_as_object(): void
    {
        $expectedException = new InvalidMapValueTypeException(
            CustomTypeMap::class, 'items', ChildClass::class, 'string'
        );
        $this->expectExceptionObject($expectedException);

        $json = [
            'items' => [
                'someKey' => '555',
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, CustomTypeMap::class);
    }

    public function test_mixed_type_values_are_not_allowed_in_maps(): void
    {
        $expectedException = new MixedTypeMapsNotAllowedException(MixedTypeMap::class, 'items');
        $this->expectExceptionObject($expectedException);

        $json = [
            'items' => [
                'key1' => 555,
                'key2' => '666',
                'key3' => 77.7,
                'key4' => true,
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, MixedTypeMap::class);
    }

    public function test_mapping_a_complex_json(): void
    {
        // As long as it doesn't fail with an exception - we're good
        $this->expectNotToPerformAssertions();

        $json = [
            'string' => '666',
            'int' => 666,
            'float' => 66.6,
            'bool' => true,
            'nullableField' => null,
            'stringArray' => ['555', '666'],
            'intArray' => [555, 666],
            'floatArray' => [55.5, 66.6],
            'boolArray' => [true, false],
            'customTypeArray' => [
                ['field' => '555'],
                ['field' => '666'],
            ],
            'nestedObjectLevelOne' => [
                'string' => '666',
                'nullableField' => null,
                'stringArray' => ['555', '666'],
                'nestedObjectLevelTwo' => [
                    'string' => '666',
                    'nullableField' => null,
                    'stringArray' => ['555', '666'],
                ],
            ],
        ];

        $mapper = new JsonMapper();
        $mapper->map($json, ComplexTestClass::class);
    }

    public function test_untyped_fields_can_be_explicitly_enabled_as_a_last_resort(): void
    {
        $json = [
            'untypedField' => 666,
            'untypedArray' => [
                123,
                '675',
                true,
                null,
            ],
            'untypedMap' => [
                'key1' => 234,
                'key2' => '675',
                123 => true,
                'key4' => null,
            ],
        ];

        $mapper = new JsonMapper(allowUntypedProperties: true);
        $result = $mapper->map($json, UntypedObject::class);

        $this->assertSame($json['untypedField'], $result->untypedField);
        $this->assertSame($json['untypedArray'], $result->untypedArray);
        $this->assertSame($json['untypedMap'], $result->untypedMap);
    }

    public function test_mapping_partially_typed_objects(): void
    {
        $json = [
            'typedField' => '666',
            'untypedField' => true,
            'childWithUntypedField' => ['field' => 666],
        ];

        $mapper = new JsonMapper(allowUntypedProperties: true);
        $result = $mapper->map($json, PartiallyTypedObject::class);

        $this->assertSame($json['typedField'], $result->typedField);
        $this->assertSame($json['untypedField'], $result->untypedField);

        $this->assertInstanceOf(
            UntypedField::class,
            $result->childWithUntypedField
        );
        $this->assertSame(
            $json['childWithUntypedField']['field'],
            $result->childWithUntypedField->field
        );
    }

    public function test_properties_with_type_within_partially_typed_objects_are_still_checked_for_type(): void
    {
        $expectedException = new InvalidTypeException(
            PartiallyTypedObject::class, 'typedField', 'string', 'int'
        );
        $this->expectExceptionObject($expectedException);

        $json = [
            'typedField' => 666,    // 'string' value expected
            'untypedField' => true,
            'childWithUntypedField' => ['field' => 666],
        ];

        $mapper = new JsonMapper(allowUntypedProperties: true);
        $mapper->map($json, PartiallyTypedObject::class);
    }

    public function test_untyped_fields_can_accept_null_as_value(): void
    {
        $json = ['field' => null];

        $mapper = new JsonMapper(allowUntypedProperties: true);
        $result = $mapper->map($json, UntypedField::class);

        $this->assertNull($result->field);
    }

    private function assertInvalidTypeExceptionThrown(
        callable $callback,
        string $className,
        string $propertyName,
        string $expectedType,
        string $actualType,
    ): void
    {
        try {
            call_user_func($callback);

            $this->fail('The expected InvalidTypeException was not thrown.');
        } catch (InvalidTypeException $e) {
            $this->assertEquals($className, $e->getClassName());
            $this->assertEquals($propertyName, $e->getPropertyName());
            $this->assertEquals($expectedType, $e->getExpectedType());
            $this->assertEquals($actualType, $e->getActualType());
        } catch (\Exception $e) {
            $actualExceptionClass = $e::class;

            $this->fail("Expected InvalidTypeException, found {$actualExceptionClass} instead ({$e->getMessage()}).");
        }
    }

    public function test_int_values_will_be_converted_to_float_when_the_option_is_enabled(): void
    {
        $mapper = new JsonMapper(allowIntToFloatConversion: true);

        $json = ['field' => 5];

        $result = $mapper->map($json, FloatField::class);

        $this->assertSame(5.0, $result->field);
    }

    public function test_int_array_elements_will_be_converted_to_float_when_the_option_is_enabled(): void
    {
        $mapper = new JsonMapper(allowIntToFloatConversion: true);

        $json = [
            'items' => [555, 666],
        ];

        $result = $mapper->map($json, FloatArray::class);

        $this->assertSame(555.0, $result->items[0]);
        $this->assertSame(666.0, $result->items[1]);
    }

    public function test_int_map_values_will_be_converted_to_float_when_the_option_is_enabled(): void
    {
        $mapper = new JsonMapper(allowIntToFloatConversion: true);

        $json = [
            'items' => [
                'keyOne' => 555,
                'keyTwo' => 666,
            ],
        ];

        $result = $mapper->map($json, StringToFloatMap::class);

        $this->assertSame(555.0, $result->items['keyOne']);
        $this->assertSame(666.0, $result->items['keyTwo']);
    }
}
