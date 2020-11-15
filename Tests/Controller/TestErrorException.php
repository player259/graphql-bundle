<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Controller;

use GraphQL\Error\Error;

class TestErrorException extends Error
{
    public function getCategory()
    {
        return 'test';
    }

    public function isClientSafe()
    {
        return false;
    }
}
