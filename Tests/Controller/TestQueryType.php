<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Controller;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class TestQueryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name'   => 'Query',
            'fields' => [
                'stub'           => [
                    'type'    => Type::string(),
                    'resolve' => function () {
                        return 'hello';
                    },
                ],
                'exception'      => [
                    'type'    => Type::string(),
                    'resolve' => function () {
                        throw new TestException('Test exception');
                    },
                ],
                'errorException' => [
                    'type'    => Type::string(),
                    'resolve' => function () {
                        throw new TestErrorException('Test error exception');
                    },
                ],
            ],
        ];

        parent::__construct($config);
    }
}
