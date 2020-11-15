<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Service;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class AnotherTestQueryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name'   => 'Query',
            'fields' => [
                'b' => [
                    'type' => Type::string(),
                ],
            ],
        ];

        parent::__construct($config);
    }
}
