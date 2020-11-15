<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Controller;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Player259\GraphQLBundle\Service\Context;
use Player259\GraphQLBundle\Service\DeferredResolver;
use Player259\GraphQLBundle\Service\ResolveRequest;
use Player259\GraphQLBundle\Service\ResolveRequestCollection;
use Player259\GraphQLBundle\Service\TypeRegistry;
use Player259\GraphQLBundle\Util\FieldResolverFactory;

class DeferredTestQueryType extends ObjectType
{
    protected $calls = [];

    public function __construct(TypeRegistry $typeRegistry)
    {
        $deferrableType = new ObjectType([
            'name'         => 'Deferrable',
            'fields'       => function () use ($typeRegistry) {
                return [
                    'field'  => [
                        'type' => Type::string(),
                        'args' => [
                            'id' => Type::int(),
                        ],
                    ],
                    'nested' => $typeRegistry->get('Deferrable'),
                ];
            },
            'resolveField' => FieldResolverFactory::createDefaultFieldResolver($this),
        ]);

        $typeRegistry->add($deferrableType);

        $config = [
            'name'   => 'Query',
            'fields' => [
                'field'      => [
                    'type' => Type::string(),
                    'args' => [
                        'id' => Type::int(),
                    ],
                ],
                'list'       => [
                    'type' => Type::listOf(new ObjectType([
                        'name'         => 'ListItem',
                        'fields'       => [
                            'field' => Type::string(),
                        ],
                        'resolveField' => FieldResolverFactory::createDefaultFieldResolver($this),
                    ])),
                ],
                'deferrable' => $deferrableType,
            ],
        ];

        parent::__construct($config);
    }

    public function fieldDeferred(
        $root,
        array $args,
        Context $context,
        ResolveInfo $info,
        DeferredResolver $deferredResolver
    ) {
        $functionName = __FUNCTION__;

        $resolvedData = $deferredResolver->resolve(function (ResolveRequestCollection $collection) use ($functionName) {
            $ids = array_map(function (ResolveRequest $request) {
                return $request->getArgs()['id'];
            }, $collection->toArray());

            $this->calls[] = $functionName . '__' . implode('_', $ids);

            return array_combine($ids, array_map(function (ResolveRequest $request) use ($functionName) {
                return $functionName . '_' . $request->getArgs()['id'];
            }, $collection->toArray()));
        });

        return $resolvedData[$args['id']] ?? null;
    }

    public function list()
    {
        return [
            ['id' => 1],
            ['id' => 3],
            ['id' => 5],
        ];
    }

    public function listItemFieldDeferred(
        $root,
        array $args,
        Context $context,
        ResolveInfo $info,
        DeferredResolver $deferredResolver
    ) {
        $functionName = __FUNCTION__;

        $resolvedData = $deferredResolver->resolve(function (ResolveRequestCollection $collection) use ($functionName) {
            $ids = array_map(function (ResolveRequest $request) {
                return $request->getRoot()['id'];
            }, $collection->toArray());

            $this->calls[] = $functionName . '__' . implode('_', $ids);

            return array_combine($ids, array_map(function (ResolveRequest $request) use ($functionName) {
                return $functionName . '_' . $request->getRoot()['id'];
            }, $collection->toArray()));
        });

        return $resolvedData[$root['id']] ?? null;
    }

    public function deferrable()
    {
        return [];
    }

    public function deferrableNested()
    {
        return [];
    }

    public function getCalls(): array
    {
        return $this->calls;
    }
}
