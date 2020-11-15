<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Service;

use GraphQL\Type\Definition\ObjectType;

class TestType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name'   => 'TestTestType',
            'fields' => [],
        ];

        parent::__construct($config);
    }

    public function test()
    {
        return func_get_args();
    }
}
