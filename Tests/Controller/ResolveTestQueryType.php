<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Controller;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Player259\GraphQLBundle\Util\FieldResolverFactory;

class ResolveTestQueryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name'   => 'Query',
            'fields' => [
                'field'                     => [
                    'type' => Type::string(),
                ],
                'resolvableField'           => [
                    'type' => Type::string(),
                ],
                'deferrableField'           => [
                    'type' => Type::string(),
                ],
                'deferrableResolvableField' => [
                    'type' => Type::string(),
                ],
                'rootField'                 => [
                    'type' => new ObjectType([
                        'name'         => 'Root',
                        'fields'       => [
                            'bypassedField'   => Type::string(),
                            'handledField'    => Type::string(),
                            'deferrableField' => Type::string(),
                        ],
                        'resolveField' => FieldResolverFactory::createDefaultFieldResolver($this),
                    ]),
                ],
                'resolvableRootField'       => [
                    'type' => new ObjectType([
                        'name'         => 'ResolvableRoot',
                        'fields'       => [
                            'bypassedField'   => Type::string(),
                            'handledField'    => Type::string(),
                            'deferrableField' => Type::string(),
                        ],
                        'resolveField' => FieldResolverFactory::createDefaultFieldResolver($this),
                    ]),
                ],
            ],
        ];

        parent::__construct($config);
    }

    public function field()
    {
        return __FUNCTION__;
    }

    public function resolveResolvableField()
    {
        return __FUNCTION__;
    }

    public function deferrableFieldDeferred()
    {
        return __FUNCTION__;
    }

    public function resolveDeferrableResolvableFieldDeferred()
    {
        return __FUNCTION__;
    }

    public function rootField()
    {
        return ['bypassedField' => __FUNCTION__];
    }

    public function rootHandledField()
    {
        return __FUNCTION__;
    }

    public function rootDeferrableFieldDeferred()
    {
        return __FUNCTION__;
    }

    public function resolveResolvableRootField()
    {
        return ['bypassedField' => __FUNCTION__];
    }

    public function resolveResolvableRootHandledField()
    {
        return __FUNCTION__;
    }

    public function resolveResolvableRootDeferrableFieldDeferred()
    {
        return __FUNCTION__;
    }
}
