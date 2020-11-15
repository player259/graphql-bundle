<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Util;

use GraphQL\Deferred;
use GraphQL\Type\Definition\ObjectType;
use PHPUnit\Framework\TestCase;
use Player259\GraphQLBundle\Service\Context;
use Player259\GraphQLBundle\Tests\FakeContext;
use Player259\GraphQLBundle\Tests\FakeResolveInfo;
use Player259\GraphQLBundle\Util\FieldResolverFactory;

class FieldResolverFactoryTest extends TestCase
{
    public function testResolveHandle()
    {
        $parentType = new TestType();

        $resolveInfo = new FakeResolveInfo($parentType, 'test');

        $context = new FakeContext();
        $context->addResponseData(TestType::class, 'test', 'response data');

        $resolver = FieldResolverFactory::createDefaultFieldResolver(null);

        $actual = $resolver(null, [], $context, $resolveInfo);

        $this->assertEquals('response data', $actual);
    }

    public function testResolveHandleFromAdditionalType()
    {
        $parentType = new TestType();

        $resolveInfo = FakeResolveInfo::create('SomeParentType', 'test');

        $context = new FakeContext();
        $context->addResponseData(TestType::class, 'test', 'response data');

        $resolver = FieldResolverFactory::createDefaultFieldResolver($parentType);

        $actual = $resolver(null, [], $context, $resolveInfo);

        $this->assertEquals('response data', $actual);
    }

    /**
     * @dataProvider provideResolveHandleSupposedMethodsData
     */
    public function testResolveHandleSupposedMethods(string $field, string $expectedMethod)
    {
        $parentType = new TestSupposedMethodsType();

        $resolveInfo = new FakeResolveInfo($parentType, $field);

        $context = $this->createMock(Context::class);
        $context
            ->expects($this->once())
            ->method('handle')
            ->with([$parentType, $expectedMethod])
            ->willReturn('test');

        $resolver = FieldResolverFactory::createDefaultFieldResolver(null);

        $actual = $resolver(null, [], $context, $resolveInfo);

        $this->assertEquals('test', $actual);
    }

    public function provideResolveHandleSupposedMethodsData()
    {
        yield ['c', 'resolveParentC'];
        yield ['d', 'parentD'];
        yield ['g', 'resolveG'];
        yield ['h', 'h'];
    }

    /**
     * @dataProvider provideResolveHandleSupposedDeferredMethodsData
     */
    public function testResolveHandleSupposedDeferredMethods(string $field, string $expectedMethod)
    {
        $parentType = new TestSupposedMethodsType();

        $resolveInfo = new FakeResolveInfo($parentType, $field);

        $context = $this->createMock(Context::class);
        $context
            ->expects($this->once())
            ->method('handle')
            ->with([$parentType, $expectedMethod])
            ->willReturn('test');

        $resolver = FieldResolverFactory::createDefaultFieldResolver(null);

        /** @var Deferred $result */
        $deferred = $resolver(null, [], $context, $resolveInfo);

        $this->assertInstanceOf(Deferred::class, $deferred);

        $deferred::runQueue();

        $this->assertEquals('test', $deferred->then()->result);
    }

    public function provideResolveHandleSupposedDeferredMethodsData()
    {
        yield ['a', 'resolveParentADeferred'];
        yield ['b', 'parentBDeferred'];
        yield ['e', 'resolveEDeferred'];
        yield ['f', 'fDeferred'];
    }

    public function testResolveArrayElement()
    {
        $resolveInfo = new FakeResolveInfo(new ObjectType(['name' => 'Test']), 'test');

        $context = new FakeContext();
        $context->addResponseData(TestType::class, 'test', 'response data');

        $resolver = FieldResolverFactory::createDefaultFieldResolver(null);

        $actual = $resolver(['test' => 'response data'], [], $context, $resolveInfo);

        $this->assertEquals('response data', $actual);
    }

    public function testResolvePrivateProperty()
    {
        $resolveInfo = new FakeResolveInfo(new ObjectType(['name' => 'Test']), 'info');

        $context = new FakeContext();
        $context->addResponseData(TestType::class, 'test', 'response data');

        $resolver = FieldResolverFactory::createDefaultFieldResolver(null);

        $actual = $resolver(new TestClass('response data'), [], $context, $resolveInfo);

        $this->assertEquals('response data', $actual);
    }

    public function testResolvePublicProperty()
    {
        $resolveInfo = new FakeResolveInfo(new ObjectType(['name' => 'Test']), 'publicInfo');

        $context = new FakeContext();
        $context->addResponseData(TestType::class, 'test', 'response data');

        $resolver = FieldResolverFactory::createDefaultFieldResolver(null);

        $root = new TestClass();
        $root->publicInfo = 'response data';

        $actual = $resolver($root, [], $context, $resolveInfo);

        $this->assertEquals('response data', $actual);
    }

    public function testNotResolved()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t resolve field `test` of type `Test` (GraphQL\Type\Definition\ObjectType)');

        $resolveInfo = new FakeResolveInfo(new ObjectType(['name' => 'Test']), 'test');

        $context = new FakeContext();

        $resolver = FieldResolverFactory::createDefaultFieldResolver(null);

        $resolver(null, [], $context, $resolveInfo);
    }
}
