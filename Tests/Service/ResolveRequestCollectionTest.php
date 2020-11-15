<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Player259\GraphQLBundle\Service\ResolveRequest;
use Player259\GraphQLBundle\Service\ResolveRequestCollection;

class ResolveRequestCollectionTest extends TestCase
{
    public function testIterate()
    {
        $collection = new ResolveRequestCollection();

        $itemA = $this->createMock(ResolveRequest::class);
        $itemB = $this->createMock(ResolveRequest::class);
        $itemC = $this->createMock(ResolveRequest::class);

        $collection->add($itemA);
        $collection->add($itemB);
        $collection->add($itemC);

        $actual = [];
        foreach ($collection as $item) {
            $actual[] = $item;
        }

        $this->assertSame([$itemA, $itemB, $itemC], $actual);
    }

    public function testCount()
    {
        $collection = new ResolveRequestCollection();

        $collection->add($this->createMock(ResolveRequest::class));
        $collection->add($this->createMock(ResolveRequest::class));
        $collection->add($this->createMock(ResolveRequest::class));

        $this->assertEquals(3, count($collection));
    }

    public function testToArray()
    {
        $collection = new ResolveRequestCollection();

        $itemA = $this->createMock(ResolveRequest::class);
        $itemB = $this->createMock(ResolveRequest::class);
        $itemC = $this->createMock(ResolveRequest::class);

        $collection->add($itemA);
        $collection->add($itemB);
        $collection->add($itemC);

        $this->assertSame([$itemA, $itemB, $itemC], $collection->toArray());
    }
}
