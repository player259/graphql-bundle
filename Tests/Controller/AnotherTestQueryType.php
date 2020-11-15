<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Controller;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class AnotherTestQueryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name'   => 'Query',
            'fields' => [
                'anotherStub' => [
                    'type'    => Type::string(),
                    'resolve' => function () {
                        return 'hi';
                    },
                ],
            ],
        ];

        parent::__construct($config);
    }
}
