<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\DependencyInjection\Compiler;

use Player259\GraphQLBundle\Service\TypeRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $typeRegistry = $container->getDefinition(TypeRegistry::class);
        $typeServices = $container->findTaggedServiceIds('player259_graphql.type');

        foreach ($typeServices as $id => $tags) {
            $typeRegistry->addMethodCall('add', [new Reference($id)]);

            $container->getDefinition($id)->addTag('controller.service_arguments');
        }
    }
}
