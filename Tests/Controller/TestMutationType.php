<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Controller;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class TestMutationType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name'   => 'Mutation',
            'fields' => [
                'do' => [
                    'type'    => Type::string(),
                    'args'    => [
                        'value' => Type::string(),
                    ],
                    'resolve' => function ($root, array $args) {
                        return $args['value'] ?? null;
                    },
                ],
            ],
        ];

        parent::__construct($config);
    }
}
