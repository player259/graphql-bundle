<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Player259\GraphQLBundle\Util\RequestUtil;
use Symfony\Component\HttpFoundation\Request;

class RequestUtilTest extends TestCase
{
    /**
     * @dataProvider provideValidRequestData
     */
    public function testCheck($controller, bool $expected)
    {
        $request = new Request();
        $request->attributes->set('_controller', $controller);

        $this->assertEquals($expected, RequestUtil::isGraphQLSubRequest($request));
    }

    public function provideValidRequestData(): \Generator
    {
        yield [
            [TestType::class, 'test'],
            true,
        ];

        yield [
            [TestType::class],
            true,
        ];

        yield [
            [TestClass::class, 'test'],
            false,
        ];

        yield [
            [new \stdClass(), 'test'],
            false,
        ];

        yield [
            [],
            false,
        ];
    }
}
