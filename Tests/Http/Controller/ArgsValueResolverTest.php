<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Http\Controller;

use PHPUnit\Framework\TestCase;
use Player259\GraphQLBundle\Http\Controller\ArgsValueResolver;
use Player259\GraphQLBundle\Service\Context;
use Player259\GraphQLBundle\Service\ResolveRequest;
use Player259\GraphQLBundle\Tests\FakeContext;
use Player259\GraphQLBundle\Tests\FakeResolveInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ArgsValueResolverTest extends TestCase
{
    protected function createContext(): Context
    {
        $resolveInfo = FakeResolveInfo::create('ParentType', 'typeField');
        $resolveRequest = new ResolveRequest(null, ['id' => 111, 'name' => 'test'], $resolveInfo);

        $context = new FakeContext();
        $context->setCurrentResolveRequest($resolveRequest);

        return $context;
    }

    /**
     * @dataProvider provideSupportsData
     */
    public function testSupports(ArgumentMetadata $argumentMetadata)
    {
        $resolver = new ArgsValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(true, $actual);
    }

    public function provideSupportsData(): \Generator
    {
        yield [
            new ArgumentMetadata('args', 'array', false, false, null, true),
        ];

        yield [
            new ArgumentMetadata('args', null, false, false, null, true),
        ];
    }

    /**
     * @dataProvider provideNotSupportsData
     */
    public function testNotSupports(ArgumentMetadata $argumentMetadata)
    {
        $resolver = new ArgsValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(false, $actual);
    }

    public function provideNotSupportsData(): \Generator
    {
        yield [
            new ArgumentMetadata('test', 'array', false, false, null, true),
        ];

        yield [
            new ArgumentMetadata('args', 'string', false, false, null, true),
        ];
    }

    public function testNotSupportsNotGraphQLRequest()
    {
        $resolver = new ArgsValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [self::class, 'test']);

        $argumentMetadata = new ArgumentMetadata('args', 'array', false, false, null, true);
        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(false, $actual);
    }

    public function testNotSupportsNoResolveRequest()
    {
        $context = $this->createContext();
        $context->setCurrentResolveRequest(null);

        $resolver = new ArgsValueResolver($context);

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $argumentMetadata = new ArgumentMetadata('args', 'array', false, false, null, true);
        $actual = $resolver->supports($request, $argumentMetadata);

        $this->assertEquals(false, $actual);
    }

    public function testResolve()
    {
        $resolver = new ArgsValueResolver($this->createContext());

        $request = new Request();
        $request->attributes->set('_controller', [TestType::class, 'test']);

        $argumentMetadata = new ArgumentMetadata('args', 'array', false, false, null, true);

        $actual = $resolver->resolve($request, $argumentMetadata)->current();

        $this->assertEquals(['id' => 111, 'name' => 'test'], $actual);
    }
}
