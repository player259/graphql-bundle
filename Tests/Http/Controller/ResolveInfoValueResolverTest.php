<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Http\Controller;

use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Player259\GraphQLBundle\Http\Controller\ResolveInfoValueResolver;
use Player259\GraphQLBundle\Service\Context;
use Player259\GraphQLBundle\Service\ResolveRequest;
use Player259\GraphQLBundle\Tests\FakeContext;
use Player259\GraphQLBundle\Tests\FakeResolveInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ResolveInfoValueResolverTest extends TestCase
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
        $resolver = new ResolveInfoValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(true, $actual);
    }

    public function provideSupportsData(): \Generator
    {
        yield [
            new ArgumentMetadata('resolveInfo', ResolveInfo::class, false, false, null, false),
        ];

        yield [
            new ArgumentMetadata('test', ResolveInfo::class, false, false, null, false),
        ];

        yield [
            new ArgumentMetadata('resolveInfo', null, false, false, null, false),
        ];
    }

    /**
     * @dataProvider provideNotSupportsData
     */
    public function testNotSupports(ArgumentMetadata $argumentMetadata)
    {
        $resolver = new ResolveInfoValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(false, $actual);
    }

    public function provideNotSupportsData(): \Generator
    {
        yield [
            new ArgumentMetadata('resolveInfo', 'array', false, false, null, false),
        ];
    }

    public function testNotSupportsNotGraphQLRequest()
    {
        $resolver = new ResolveInfoValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [self::class, 'test']);

        $argumentMetadata = new ArgumentMetadata('resolveInfo', ResolveInfo::class, false, false, null, false);
        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(false, $actual);
    }

    public function testNotSupportsNoResolveRequest()
    {
        $context = $this->createContext();
        $context->setCurrentResolveRequest(null);

        $resolver = new ResolveInfoValueResolver($context);

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $argumentMetadata = new ArgumentMetadata('resolveInfo', ResolveInfo::class, false, false, null, false);
        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(false, $actual);
    }

    public function testResolve()
    {
        $resolveInfo = FakeResolveInfo::create('ParentType', 'typeField');
        $resolveRequest = new ResolveRequest(null, [], $resolveInfo);

        $context = new FakeContext();
        $context->setCurrentResolveRequest($resolveRequest);

        $resolver = new ResolveInfoValueResolver($context);

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $argumentMetadata = new ArgumentMetadata('resolveInfo', ResolveInfo::class, false, false, null, false);

        $actual = $resolver->resolve($request, $argumentMetadata)->current();

        $this->assertSame($resolveInfo, $actual);
    }
}
