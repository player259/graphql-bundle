<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Player259\GraphQLBundle\Service\Context;
use Player259\GraphQLBundle\Service\ResolveRequest;
use Player259\GraphQLBundle\Tests\FakeResolveInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

class ContextTest extends TestCase
{
    protected function createContext(): Context
    {
        $resolveInfo = FakeResolveInfo::create('ParentType', 'typeField');
        $resolveRequest = new ResolveRequest(null, [], $resolveInfo);

        $context = new Context($this->createMock(ArgumentResolverInterface::class));
        $context->setCurrentResolveRequest($resolveRequest);

        return $context;
    }

    public function testCreateCurrentDeferredResolver()
    {
        $context = $this->createContext();

        $resolver = $context->getCurrentDeferredResolver(true);

        $this->assertNotNull($resolver);
    }

    public function testGetCurrentDeferredResolverNoCurrentResolveRequest()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no current ResolveRequest in Context');

        $context = $this->createContext();
        $context->setCurrentResolveRequest(null);

        $context->getCurrentDeferredResolver();
    }

    public function testGetNonExistCurrentDeferredResolver()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('DeferredResolver was not initialized, may be you try to use it outside of *Deferred resolve method: ParentType->typeField');

        $context = $this->createContext();

        $context->getCurrentDeferredResolver();
    }

    public function testGetSameCurrentDeferredResolver()
    {
        $context = $this->createContext();

        $expectedResolver = $context->getCurrentDeferredResolver(true);

        $actualResolver = $context->getCurrentDeferredResolver(true);

        $this->assertSame($expectedResolver, $actualResolver);
    }

    public function testRecreateCurrentDeferredResolver()
    {
        $context = $this->createContext();

        $resolvedResolver = $context->getCurrentDeferredResolver(true);
        $resolvedResolver->resolve(function () {
            return null;
        });

        $actualResolver = $context->getCurrentDeferredResolver(true);

        $this->assertNotSame($resolvedResolver, $actualResolver);
    }

    public function testHandle()
    {
        $type = new TestType();

        $argumentResolver = $this->createMock(ArgumentResolverInterface::class);
        $argumentResolver
            ->expects($this->once())
            ->method('getArguments')
            ->with(
                $this->callback(function ($request) {
                    $this->assertInstanceOf(Request::class, $request);
                    $this->assertTrue($request->attributes->has('_controller'));
                    $this->assertSame([TestType::class, 'test'], $request->attributes->get('_controller'));

                    return true;
                }),
                [$type, 'test']
            )
            ->willReturn(['test']);

        $context = new Context($argumentResolver);

        $actual = $context->handle([$type, 'test']);

        $this->assertEquals(['test'], $actual);
    }

    public function testHandleAnonymousFunction()
    {
        $argumentResolver = $this->createMock(ArgumentResolverInterface::class);
        $argumentResolver->expects($this->once())->method('getArguments')->willReturn(['test']);

        $context = new Context($argumentResolver);

        $actual = $context->handle(function () {
            return func_get_args();
        });

        $this->assertEquals(['test'], $actual);
    }
}
