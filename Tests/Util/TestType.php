<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Util;

use GraphQL\Type\Definition\ObjectType;

class TestType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([]);
    }

    public function test()
    {
    }
}
