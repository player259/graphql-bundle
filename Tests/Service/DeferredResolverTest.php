<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Player259\GraphQLBundle\Service\DeferredResolver;
use Player259\GraphQLBundle\Service\ResolveRequest;
use Player259\GraphQLBundle\Service\ResolveRequestCollection;
use Player259\GraphQLBundle\Tests\FakeResolveInfo;

class DeferredResolverTest extends TestCase
{
    public function testDeferResolve()
    {
        $resolveInfo = FakeResolveInfo::create('Test', 'test');

        $deferredResolver = new DeferredResolver($resolveInfo);

        $resolveRequest = new ResolveRequest(null, [], $resolveInfo);

        $deferredResolver->defer($resolveRequest);

        $deferredResolver->resolve(function (ResolveRequestCollection $requests) use ($resolveRequest) {
            $this->assertCount(1, $requests);
            $this->assertSame([$resolveRequest], $requests->toArray());
        });

        $this->assertTrue($deferredResolver->isResolved());
    }

    public function testIsResolved()
    {
        $resolveInfo = FakeResolveInfo::create('Test', 'test');
        $deferredResolver = new DeferredResolver($resolveInfo);

        $deferredResolver->resolve(function () {
            return null;
        });

        $this->assertTrue($deferredResolver->isResolved());
    }

    public function testDeferAfterResolve()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t defer request if DeferredResolver already resolved: Test->test');

        $resolveInfo = FakeResolveInfo::create('Test', 'test');

        $deferredResolver = new DeferredResolver($resolveInfo);

        $deferredResolver->resolve(function () {
            return null;
        });

        $resolveRequest = new ResolveRequest(null, [], $resolveInfo);

        $deferredResolver->defer($resolveRequest);
    }

    public function testAnotherField()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t defer request for another field: Test->testtest, expected: Test->test');

        $resolveInfo = FakeResolveInfo::create('Test', 'test');

        $deferredResolver = new DeferredResolver($resolveInfo);

        $deferredResolver->resolve(function () {
            return null;
        });

        $resolveRequest = new ResolveRequest(null, [], FakeResolveInfo::create('Test', 'testtest'));

        $deferredResolver->defer($resolveRequest);
    }
}
