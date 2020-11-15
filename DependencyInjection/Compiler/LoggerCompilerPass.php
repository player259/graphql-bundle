<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\DependencyInjection\Compiler;

use Player259\GraphQLBundle\Controller\GraphQLController;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

class LoggerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $loggerServiceId = $container->getParameter('player259_graphql.logger');
        $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;

        if (empty($loggerServiceId)) {
            return;
        }

        if (strpos($loggerServiceId, '?') === 0) {
            $invalidBehavior = ContainerInterface::IGNORE_ON_INVALID_REFERENCE;
            $loggerServiceId = substr($loggerServiceId, 1);
        }

        $controllerDefinition = $container->getDefinition(GraphQLController::class);
        $controllerDefinition
            ->addMethodCall('setLogger', [new Reference($loggerServiceId, $invalidBehavior)]);
    }
}
