<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Service;

use GraphQL\Type\Definition\ObjectType;
use PHPUnit\Framework\TestCase;
use Player259\GraphQLBundle\Service\TypeRegistry;

class TypeRegistryTest extends TestCase
{
    public function testAddGet()
    {
        $typeResistry = new TypeRegistry();

        $expectedType = new TestType();

        $typeResistry->add($expectedType);

        $this->assertSame($expectedType, $typeResistry->get(TestType::class));
        $this->assertSame($expectedType, $typeResistry->get('TestTestType'));
    }

    public function testAddSameClassTypeTwice()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Type already in registry: ' . TestType::class);

        $typeResistry = new TypeRegistry();

        $expectedType = new TestType();

        $typeResistry->add($expectedType);
        $typeResistry->add($expectedType);
    }

    public function testAddSameNameTypeTwice()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Type already in registry: TestTestType');

        $typeResistry = new TypeRegistry();

        $typeResistry->add(new TestType());
        $typeResistry->add(new ObjectType(['name' => 'TestTestType']));
    }

    public function testAddGenericQueryTypeError()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t add mergable type to registry, please extend ObjectType: Query');

        $typeResistry = new TypeRegistry();

        $typeResistry->add(new ObjectType(['name' => 'Query']));
    }

    public function testNotFound()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Type not found: Test');

        $typeResistry = new TypeRegistry();

        $typeResistry->get('Test');
    }

    public function testMergeQueryType()
    {
        $typeResistry = new TypeRegistry();

        $typeA = new TestQueryType();
        $typeB = new AnotherTestQueryType();
        $typeResistry->add($typeA);
        $typeResistry->add($typeB);

        $type = $typeResistry->get('Query');

        $this->assertEquals(ObjectType::class, get_class($type));
        $this->assertEquals('a', $type->getField('a')->name);
        $this->assertEquals('b', $type->getField('b')->name);
        $this->assertSame([$typeA, $typeB], $type->config['mergedTypes']);
    }

    public function testMergeDuplicateFields()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Found duplicate field names during merging: Query with fields a');

        $typeResistry = new TypeRegistry();

        $typeResistry->add(new TestQueryType());
        $typeResistry->add(new TestDuplicateQueryType());

        $typeResistry->get('Query');
    }

    public function testAll()
    {
        $typeResistry = new TypeRegistry();

        $typeA = new TestQueryType();
        $typeB = new AnotherTestQueryType();
        $typeC = new TestType();
        $typeD = new ObjectType(['name' => 'ObjectTestType']);

        $typeResistry->add($typeA);
        $typeResistry->add($typeB);
        $typeResistry->add($typeC);
        $typeResistry->add($typeD);

        $actual = $typeResistry->all();
        $this->assertCount(3, $actual);

        $this->assertEquals('Query', $actual[0]->name);
        $this->assertEquals('TestTestType', $actual[1]->name);
        $this->assertEquals('ObjectTestType', $actual[2]->name);
    }
}
