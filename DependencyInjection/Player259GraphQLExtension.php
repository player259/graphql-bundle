<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\DependencyInjection;

use GraphQL\Type\Definition\NamedType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class Player259GraphQLExtension extends Extension
{
    public function getAlias(): string
    {
        return 'player259_graphql';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config as $i => $item) {
            $container->setParameter('player259_graphql.' . $i, $item);
        }

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container
            ->registerForAutoconfiguration(NamedType::class)
            ->addTag('player259_graphql.type');
    }
}
