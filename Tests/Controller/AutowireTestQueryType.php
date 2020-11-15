<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Controller;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Player259\GraphQLBundle\Service\DeferredResolver;
use Player259\GraphQLBundle\Util\FieldResolverFactory;

class AutowireTestQueryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name'   => 'Query',
            'fields' => [
                'root'                   => new ObjectType([
                    'name'         => 'Root',
                    'fields'       => [
                        'nested' => Type::string(),
                    ],
                    'resolveField' => FieldResolverFactory::createDefaultFieldResolver($this),
                ]),
                'rootNoHint'             => new ObjectType([
                    'name'         => 'RootNoHint',
                    'fields'       => [
                        'nested' => Type::string(),
                    ],
                    'resolveField' => FieldResolverFactory::createDefaultFieldResolver($this),
                ]),
                'args'                   => [
                    'type' => Type::listOf(Type::string()),
                    'args' => [
                        'id'   => Type::int(),
                        'name' => Type::string(),
                    ],
                ],
                'argsNoHint'             => [
                    'type' => Type::listOf(Type::string()),
                    'args' => [
                        'id'   => Type::int(),
                        'name' => Type::string(),
                    ],
                ],
                'resolveInfo'            => [
                    'type' => Type::string(),
                ],
                'resolveInfoNoHint'      => [
                    'type' => Type::string(),
                ],
                'deferredResolver'       => [
                    'type' => Type::string(),
                ],
                'deferredResolverNoHint' => [
                    'type' => Type::string(),
                ],
                'deferredResolverError'  => [
                    'type' => Type::string(),
                ],
                'service'                => [
                    'type' => Type::string(),
                ],
                'all'                    => [
                    'type' => Type::listOf(Type::string()),
                    'args' => [
                        'id'   => Type::int(),
                        'name' => Type::string(),
                    ],
                ],
                'allNoHint'              => [
                    'type' => Type::listOf(Type::string()),
                    'args' => [
                        'id'   => Type::int(),
                        'name' => Type::string(),
                    ],
                ],
            ],
        ];

        parent::__construct($config);
    }

    public function root()
    {
        return new TestAutowireClass();
    }

    public function rootNested($root)
    {
        return get_class($root);
    }

    public function rootNoHint()
    {
        return new TestAutowireClass();
    }

    public function rootNoHintNested(TestAutowireClass $entity)
    {
        return get_class($entity);
    }

    public function args(array $args)
    {
        return array_merge(array_keys($args), array_values($args));
    }

    public function argsNoHint($args)
    {
        return array_merge(array_keys($args), array_values($args));
    }

    public function resolveInfo(ResolveInfo $resolveInfo)
    {
        return implode('/', array_merge([$resolveInfo->parentType->name], $resolveInfo->path));
    }

    public function resolveInfoNoHint($resolveInfo)
    {
        return implode('/', array_merge([$resolveInfo->parentType->name], $resolveInfo->path));
    }

    public function deferredResolverDeferred(DeferredResolver $deferredResolver)
    {
        return get_class($deferredResolver);
    }

    public function deferredResolverNoHintDeferred($deferredResolver)
    {
        return get_class($deferredResolver);
    }

    public function deferredResolverError(DeferredResolver $deferredResolver)
    {
        return get_class($deferredResolver);
    }

    public function service(TestAutowireClass $service)
    {
        return $service->getInfo();
    }

    public function all(TestAutowireClass $service, $root, ResolveInfo $resolveInfo, array $args)
    {
        return [
            $root,
            $args['id'],
            $args['name'],
            implode('/', array_merge([$resolveInfo->parentType->name], $resolveInfo->path)),
            $service->getInfo(),
        ];
    }

    public function allNoHint(TestAutowireClass $service, $root, $resolveInfo, $args)
    {
        return [
            $root,
            $args['id'],
            $args['name'],
            implode('/', array_merge([$resolveInfo->parentType->name], $resolveInfo->path)),
            $service->getInfo(),
        ];
    }
}
