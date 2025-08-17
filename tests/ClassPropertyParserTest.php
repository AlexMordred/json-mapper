<?php

namespace Azavyalov\JsonMapper\Tests;

use Azavyalov\JsonMapper\ArrayType;
use Azavyalov\JsonMapper\ClassProperty;
use Azavyalov\JsonMapper\ClassPropertyParser;
use Azavyalov\JsonMapper\MainType;
use Azavyalov\JsonMapper\MapType;
use Azavyalov\JsonMapper\Tests\Types\BoolField;
use Azavyalov\JsonMapper\Tests\Types\ChildClass;
use Azavyalov\JsonMapper\Tests\Types\ClassField;
use Azavyalov\JsonMapper\Tests\Types\CustomTypeArray;
use Azavyalov\JsonMapper\Tests\Types\CustomTypeArrayChild;
use Azavyalov\JsonMapper\Tests\Types\CustomTypeMap;
use Azavyalov\JsonMapper\Tests\Types\DummyEnum;
use Azavyalov\JsonMapper\Tests\Types\DummyInterface;
use Azavyalov\JsonMapper\Tests\Types\EnumField;
use Azavyalov\JsonMapper\Tests\Types\FalseField;
use Azavyalov\JsonMapper\Tests\Types\FloatField;
use Azavyalov\JsonMapper\Tests\Types\InterfaceField;
use Azavyalov\JsonMapper\Tests\Types\IntersectionTypeField;
use Azavyalov\JsonMapper\Tests\Types\IntField;
use Azavyalov\JsonMapper\Tests\Types\InvalidMapDocblock;
use Azavyalov\JsonMapper\Tests\Types\StringToIntMap;
use Azavyalov\JsonMapper\Tests\Types\MixedField;
use Azavyalov\JsonMapper\Tests\Types\MixedTypeArray;
use Azavyalov\JsonMapper\Tests\Types\NullableStringArray;
use Azavyalov\JsonMapper\Tests\Types\NullableStringField;
use Azavyalov\JsonMapper\Tests\Types\NullField;
use Azavyalov\JsonMapper\Tests\Types\ObjectField;
use Azavyalov\JsonMapper\Tests\Types\SelfField;
use Azavyalov\JsonMapper\Tests\Types\StringAndNullUnionField;
use Azavyalov\JsonMapper\Tests\Types\StringArray;
use Azavyalov\JsonMapper\Tests\Types\StringField;
use Azavyalov\JsonMapper\Tests\Types\StringToNullableIntMap;
use Azavyalov\JsonMapper\Tests\Types\StringToNullableIntMap2;
use Azavyalov\JsonMapper\Tests\Types\TrueField;
use Azavyalov\JsonMapper\Tests\Types\UnionTypeField;
use Azavyalov\JsonMapper\Tests\Types\UnionWithIntersectionTypeField;
use Azavyalov\JsonMapper\Tests\Types\UntypedArray;
use Azavyalov\JsonMapper\Tests\Types\UntypedField;
use PHPUnit\Framework\TestCase;


class ClassPropertyParserTest extends TestCase
{
    private function assertPropertiesSame(
        ClassProperty $expected,
        ClassProperty $actual
    ): void
    {
        $expectedArray = [
            'class' => $expected->class,
            'name' => $expected->name,
            'isArray' => $expected->isArray,
            'isMap' => $expected->isMap,
            'isUnionType' => $expected->isUnionType,
            'isIntersectionType' => $expected->isIntersectionType,
            'mainType' => [
                'type' => $expected->mainType?->type,
                'isBuiltin' => $expected->mainType?->isBuiltin,
                'isNullable' => $expected->mainType?->isNullable,
            ],
            'arrayType' => [
                'elementType' => $expected->arrayType?->elementType,
                'isElementTypeBuiltin' => $expected->arrayType?->isElementTypeBuiltin,
            ],
            'mapType' => [
                'keyType' => $expected->mapType?->keyType,
                'valueType' => $expected->mapType?->valueType,
                'isValueTypeBuiltin' => $expected->mapType?->isValueTypeBuiltin,
                'isValueNullable' => $expected->mapType?->isValueNullable,
            ],
        ];
        $actualArray = [
            'class' => $actual->class,
            'name' => $actual->name,
            'isArray' => $actual->isArray,
            'isMap' => $actual->isMap,
            'isUnionType' => $actual->isUnionType,
            'isIntersectionType' => $actual->isIntersectionType,
            'mainType' => [
                'type' => $actual->mainType?->type,
                'isBuiltin' => $actual->mainType?->isBuiltin,
                'isNullable' => $actual->mainType?->isNullable,
            ],
            'arrayType' => [
                'elementType' => $actual->arrayType?->elementType,
                'isElementTypeBuiltin' => $actual->arrayType?->isElementTypeBuiltin,
            ],
            'mapType' => [
                'keyType' => $actual->mapType?->keyType,
                'valueType' => $actual->mapType?->valueType,
                'isValueTypeBuiltin' => $actual->mapType?->isValueTypeBuiltin,
                'isValueNullable' => $actual->mapType?->isValueNullable,
            ],
        ];

        $this->assertSame(
            $expectedArray,
            $actualArray,
            'Failed asserting that two properites are identical.'
        );
    }

    /**
     * @param class-string $classWithProperties
     */
    private function assertParsedCorrectly(
        string $classWithProperties,
        ClassProperty $expectedResult
    ): void
    {
        $parser = new ClassPropertyParser();

        $properties = $parser->parse($classWithProperties);
        $this->assertCount(1, $properties);

        $this->assertPropertiesSame($expectedResult, $properties[0]);
    }

    public function test_parsing_a_field_without_type(): void
    {
        $expected = new ClassProperty(
            class: UntypedField::class,
            name: 'field',
            isArray: false,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: null,
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(UntypedField::class, $expected);
    }

    public function test_parsing_builting_type_fields(): void
    {
        $classes = [
            'string' => StringField::class,
            'int' => IntField::class,
            'float' => FloatField::class,
            'bool' => BoolField::class,
            'object' => ObjectField::class,
            'false' => FalseField::class,
            'true' => TrueField::class,
            'self' => SelfField::class,
        ];

        foreach ($classes as $expectedPropertyType => $class) {
            $expected = new ClassProperty(
                class: $class,
                name: 'field',
                isArray: false,
                isMap: false,
                isUnionType: false,
                isIntersectionType: false,
                mainType: new MainType(
                    type: $expectedPropertyType,
                    isBuiltin: true,
                    isNullable: false,
                ),
                arrayType: null,
                mapType: null,
            );

            $this->assertParsedCorrectly($class, $expected);
        }
    }

    public function test_parsing_intersection_type_properties(): void
    {
        $expected = new ClassProperty(
            class: IntersectionTypeField::class,
            name: 'field',
            isArray: false,
            isMap: false,
            isUnionType: false,
            isIntersectionType: true,
            mainType: new MainType(
                type: StringField::class . '&' . IntField::class,
                isBuiltin: false,
                isNullable: false,
            ),
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(IntersectionTypeField::class, $expected);
    }

    public function test_parsing_union_type_properties(): void
    {
        $expected = new ClassProperty(
            class: UnionTypeField::class,
            name: 'field',
            isArray: false,
            isMap: false,
            isUnionType: true,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'string|int',
                isBuiltin: false,
                isNullable: false,
            ),
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(UnionTypeField::class, $expected);
    }

    public function test_parsing_union_with_child_intersection_type_properties(): void
    {
        $expected = new ClassProperty(
            class: UnionWithIntersectionTypeField::class,
            name: 'field',
            isArray: false,
            isMap: false,
            isUnionType: true,
            isIntersectionType: false,
            mainType: new MainType(
                type: '(' . StringField::class . '&' . IntField::class . ')|bool',
                isBuiltin: false,
                isNullable: false,
            ),
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(UnionWithIntersectionTypeField::class, $expected);
    }

    public function test_parsing_class_type(): void
    {
        $expected = new ClassProperty(
            class: ClassField::class,
            name: 'field',
            isArray: false,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: ChildClass::class,
                isBuiltin: false,
                isNullable: false,
            ),
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(ClassField::class, $expected);
    }

    public function test_parsing_interface_type(): void
    {
        $expected = new ClassProperty(
            class: InterfaceField::class,
            name: 'field',
            isArray: false,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: DummyInterface::class,
                isBuiltin: false,
                isNullable: false,
            ),
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(InterfaceField::class, $expected);
    }

    public function test_parsing_enum_type(): void
    {
        $expected = new ClassProperty(
            class: EnumField::class,
            name: 'field',
            isArray: false,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: DummyEnum::class,
                isBuiltin: false,
                isNullable: false,
            ),
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(EnumField::class, $expected);
    }

    public function test_properties_can_be_nullable_if_type_marked_with_question_mark(): void
    {
        $expected = new ClassProperty(
            class: NullableStringField::class,
            name: 'field',
            isArray: false,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'string',
                isBuiltin: true,
                isNullable: true,
            ),
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(NullableStringField::class, $expected);
    }

    public function test_parsing_mixed_type_field(): void
    {
        $expected = new ClassProperty(
            class: MixedField::class,
            name: 'field',
            isArray: false,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'mixed',
                isBuiltin: true,
                isNullable: true,
            ),
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(MixedField::class, $expected);
    }

    public function test_parsing_null_type_field(): void
    {
        $expected = new ClassProperty(
            class: NullField::class,
            name: 'field',
            isArray: false,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'null',
                isBuiltin: true,
                isNullable: true,
            ),
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(NullField::class, $expected);
    }

    public function test_type_union_with_null(): void
    {
        $expected = new ClassProperty(
            class: StringAndNullUnionField::class,
            name: 'field',
            isArray: false,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'string',
                isBuiltin: true,
                isNullable: true,
            ),
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(StringAndNullUnionField::class, $expected);
    }

    public function test_parsing_untyped_array(): void
    {
        $expected = new ClassProperty(
            class: UntypedArray::class,
            name: 'items',
            isArray: true,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'array',
                isBuiltin: true,
                isNullable: false,
            ),
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(UntypedArray::class, $expected);
    }

    public function test_parsing_array_typed_with_mixed_type(): void
    {
        $expected = new ClassProperty(
            class: MixedTypeArray::class,
            name: 'items',
            isArray: true,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'array',
                isBuiltin: true,
                isNullable: false,
            ),
            arrayType: new ArrayType(
                elementType: 'mixed',
                isElementTypeBuiltin: true,
            ),
            mapType: null,
        );

        $this->assertParsedCorrectly(MixedTypeArray::class, $expected);
    }

    public function test_parsing_array_typed_with_bultin_type(): void
    {
        $expected = new ClassProperty(
            class: StringArray::class,
            name: 'items',
            isArray: true,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'array',
                isBuiltin: true,
                isNullable: false,
            ),
            arrayType: new ArrayType(
                elementType: 'string',
                isElementTypeBuiltin: true,
            ),
            mapType: null,
        );

        $this->assertParsedCorrectly(StringArray::class, $expected);
    }

    public function test_parsing_array_typed_with_a_class(): void
    {
        $expected = new ClassProperty(
            class: CustomTypeArray::class,
            name: 'items',
            isArray: true,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'array',
                isBuiltin: true,
                isNullable: false,
            ),
            arrayType: new ArrayType(
                elementType: CustomTypeArrayChild::class,
                isElementTypeBuiltin: false,
            ),
            mapType: null,
        );

        $this->assertParsedCorrectly(CustomTypeArray::class, $expected);
    }

    public function test_parsing_nullable_array(): void
    {
        $expected = new ClassProperty(
            class: NullableStringArray::class,
            name: 'items',
            isArray: true,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'array',
                isBuiltin: true,
                isNullable: true,
            ),
            arrayType: new ArrayType(
                elementType: 'string',
                isElementTypeBuiltin: true,
            ),
            mapType: null,
        );

        $this->assertParsedCorrectly(NullableStringArray::class, $expected);
    }

    public function test_parsing_a_map_with_builtin_value_type(): void
    {
        $expected = new ClassProperty(
            class: StringToIntMap::class,
            name: 'items',
            isArray: true,
            isMap: true,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'array',
                isBuiltin: true,
                isNullable: false,
            ),
            arrayType: null,
            mapType: new MapType(
                keyType: 'string',
                valueType: 'int',
                isValueTypeBuiltin: true,
                isValueNullable: false,
            ),
        );

        $this->assertParsedCorrectly(StringToIntMap::class, $expected);
    }

    public function test_parsing_a_map_with_custom_value_type(): void
    {
        $expected = new ClassProperty(
            class: CustomTypeMap::class,
            name: 'items',
            isArray: true,
            isMap: true,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'array',
                isBuiltin: true,
                isNullable: false,
            ),
            arrayType: null,
            mapType: new MapType(
                keyType: 'string',
                valueType: ChildClass::class,
                isValueTypeBuiltin: false,
                isValueNullable: false,
            ),
        );

        $this->assertParsedCorrectly(CustomTypeMap::class, $expected);
    }

    public function test_invalid_map_dockblock_description_wont_be_parsed_as_map(): void
    {
        $expected = new ClassProperty(
            class: InvalidMapDocblock::class,
            name: 'items',
            isArray: true,
            isMap: false,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'array',
                isBuiltin: true,
                isNullable: false,
            ),
            arrayType: null,
            mapType: null,
        );

        $this->assertParsedCorrectly(InvalidMapDocblock::class, $expected);
    }

    public function test_map_value_type_can_be_nullable_if_marked_with_a_question_mark(): void
    {
        $expected = new ClassProperty(
            class: StringToNullableIntMap::class,
            name: 'items',
            isArray: true,
            isMap: true,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'array',
                isBuiltin: true,
                isNullable: false,
            ),
            arrayType: null,
            mapType: new MapType(
                keyType: 'string',
                valueType: 'int',
                isValueTypeBuiltin: true,
                isValueNullable: true,
            ),
        );

        $this->assertParsedCorrectly(StringToNullableIntMap::class, $expected);
    }

    public function test_map_value_type_can_be_nullable_if_marked_as_a_union_with_null(): void
    {
        $expected = new ClassProperty(
            class: StringToNullableIntMap2::class,
            name: 'items',
            isArray: true,
            isMap: true,
            isUnionType: false,
            isIntersectionType: false,
            mainType: new MainType(
                type: 'array',
                isBuiltin: true,
                isNullable: false,
            ),
            arrayType: null,
            mapType: new MapType(
                keyType: 'string',
                valueType: 'int',
                isValueTypeBuiltin: true,
                isValueNullable: true,
            ),
        );

        $this->assertParsedCorrectly(StringToNullableIntMap2::class, $expected);
    }
}
