<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Util;

use GraphQL\Type\Definition\NamedType;
use Symfony\Component\HttpFoundation\Request;

class RequestUtil
{
    public static function isGraphQLSubRequest(Request $request): bool
    {
        $controller = $request->attributes->get('_controller');

        return $controller
            && is_array($controller)
            && isset($controller[0])
            && is_string($controller[0])
            && is_a($controller[0], NamedType::class, true);
    }
}
