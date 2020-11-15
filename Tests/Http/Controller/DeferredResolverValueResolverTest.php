<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Http\Controller;

use PHPUnit\Framework\TestCase;
use Player259\GraphQLBundle\Http\Controller\DeferredResolverValueResolver;
use Player259\GraphQLBundle\Service\Context;
use Player259\GraphQLBundle\Service\DeferredResolver;
use Player259\GraphQLBundle\Service\ResolveRequest;
use Player259\GraphQLBundle\Tests\FakeContext;
use Player259\GraphQLBundle\Tests\FakeResolveInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class DeferredResolverValueResolverTest extends TestCase
{
    protected function createContext(): Context
    {
        $resolveInfo = FakeResolveInfo::create('ParentType', 'typeField');
        $resolveRequest = new ResolveRequest(null, [], $resolveInfo);

        $context = new FakeContext();
        $context->setCurrentResolveRequest($resolveRequest);

        return $context;
    }

    /**
     * @dataProvider provideSupportsData
     */
    public function testSupports(ArgumentMetadata $argumentMetadata)
    {
        $resolver = new DeferredResolverValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(true, $actual);
    }

    public function provideSupportsData(): \Generator
    {
        yield [
            new ArgumentMetadata('deferredResolver', DeferredResolver::class, false, false, null, false),
        ];

        yield [
            new ArgumentMetadata('test', DeferredResolver::class, false, false, null, false),
        ];

        yield [
            new ArgumentMetadata('deferredResolver', null, false, false, null, false),
        ];
    }

    /**
     * @dataProvider provideNotSupportsData
     */
    public function testNotSupports(ArgumentMetadata $argumentMetadata)
    {
        $resolver = new DeferredResolverValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(false, $actual);
    }

    public function provideNotSupportsData(): \Generator
    {
        yield [
            new ArgumentMetadata('deferredResolver', 'array', false, false, null, false),
        ];
    }

    public function testNotSupportsNotGraphQLRequest()
    {
        $resolver = new DeferredResolverValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [self::class, 'test']);

        $argumentMetadata = new ArgumentMetadata('deferredResolver', DeferredResolver::class, false, false, null, false);
        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(false, $actual);
    }

    public function testNotSupportsNoResolveRequest()
    {
        $context = $this->createContext();
        $context->setCurrentResolveRequest(null);

        $resolver = new DeferredResolverValueResolver($context);

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $argumentMetadata = new ArgumentMetadata('deferredResolver', DeferredResolver::class, false, false, null, false);
        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(false, $actual);
    }

    public function testResolve()
    {
        $deferredResolver = $this->createMock(DeferredResolver::class);

        $context = $this->createMock(Context::class);
        $context->expects($this->once())->method('getCurrentDeferredResolver')->with(false)->willReturn($deferredResolver);

        $resolver = new DeferredResolverValueResolver($context);

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $argumentMetadata = new ArgumentMetadata('deferredResolver', DeferredResolver::class, false, false, null, false);

        $actual = $resolver->resolve($request, $argumentMetadata)->current();

        $this->assertSame($deferredResolver, $actual);
    }
}
