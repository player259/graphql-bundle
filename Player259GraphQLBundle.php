<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle;

use Player259\GraphQLBundle\DependencyInjection\Compiler\LoggerCompilerPass;
use Player259\GraphQLBundle\DependencyInjection\Compiler\TypeCompilerPass;
use Player259\GraphQLBundle\DependencyInjection\Player259GraphQLExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Player259GraphQLBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        // Should be run before Symfony\Component\HttpKernel\DependencyInjection\RegisterControllerArgumentLocatorsPass
        // It has default params PassConfig::TYPE_BEFORE_OPTIMIZATION with 0 priority
        // TypeCompilerPass adds `controller.service_arguments` tag
        $container->addCompilerPass(new TypeCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new LoggerCompilerPass());
    }

    /**
     * @return ExtensionInterface|null
     */
    public function getContainerExtension()
    {
        return new Player259GraphQLExtension();
    }
}
