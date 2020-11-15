<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Http\Controller;

use PHPUnit\Framework\TestCase;
use Player259\GraphQLBundle\Http\Controller\RootValueResolver;
use Player259\GraphQLBundle\Service\Context;
use Player259\GraphQLBundle\Service\ResolveRequest;
use Player259\GraphQLBundle\Tests\FakeContext;
use Player259\GraphQLBundle\Tests\FakeResolveInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class RootValueResolverTest extends TestCase
{
    protected function createContext(): Context
    {
        $resolveInfo = FakeResolveInfo::create('ParentType', 'typeField');
        $resolveRequest = new ResolveRequest(new TestClass(), [], $resolveInfo);

        $context = new FakeContext();
        $context->setCurrentResolveRequest($resolveRequest);

        return $context;
    }

    /**
     * @dataProvider provideSupportsData
     */
    public function testSupports(ArgumentMetadata $argumentMetadata)
    {
        $resolver = new RootValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(true, $actual);
    }

    public function provideSupportsData(): \Generator
    {
        yield [
            new ArgumentMetadata('root', 'array', false, false, null, true),
        ];

        yield [
            new ArgumentMetadata('root', null, false, false, null, true),
        ];

        yield [
            new ArgumentMetadata('test', TestClass::class, false, false, null, true),
        ];
    }

    /**
     * @dataProvider provideNotSupportsData
     */
    public function testNotSupports(ArgumentMetadata $argumentMetadata)
    {
        $resolver = new RootValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(false, $actual);
    }

    public function provideNotSupportsData(): \Generator
    {
        yield [
            new ArgumentMetadata('test', null, false, false, null, true),
        ];
    }

    public function testNotSupportsNotGraphQLRequest()
    {
        $resolver = new RootValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [self::class, 'test']);

        $argumentMetadata = new ArgumentMetadata('root', null, false, false, null, true);
        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(false, $actual);
    }

    public function testNotSupportsNoResolveRequest()
    {
        $context = $this->createContext();
        $context->setCurrentResolveRequest(null);

        $resolver = new RootValueResolver($context);

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $argumentMetadata = new ArgumentMetadata('root', null, false, false, null, true);
        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(false, $actual);
    }

    public function testResolve()
    {
        $root = new TestClass();

        $resolveInfo = FakeResolveInfo::create('ParentType', 'typeField');
        $resolveRequest = new ResolveRequest($root, [], $resolveInfo);

        $context = new FakeContext();
        $context->setCurrentResolveRequest($resolveRequest);

        $resolver = new RootValueResolver($context);

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $argumentMetadata = new ArgumentMetadata('root', null, false, false, null, true);

        $actual = $resolver->resolve($request, $argumentMetadata)->current();

        $this->assertSame($root, $actual);
    }
}
