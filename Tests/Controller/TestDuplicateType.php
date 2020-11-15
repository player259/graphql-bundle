<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Controller;

use GraphQL\Type\Definition\ObjectType;

class TestDuplicateType extends ObjectType
{
    public function __construct()
    {
        parent::__construct(['name' => 'Test']);
    }
}
