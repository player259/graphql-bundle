<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Controller;

use Player259\GraphQLBundle\Controller\GraphQLController;
use Player259\GraphQLBundle\Player259GraphQLBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class TestControllerKernel extends Kernel
{
    use MicroKernelTrait;

    protected $configuration = [];

    protected $services = [];

    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;

        parent::__construct('test', true);
    }

    public function setService(string $id, $service): self
    {
        $this->services[$id] = $service;

        return $this;
    }

    public function registerBundles()
    {
        return [
            new Player259GraphQLBundle(),
            new FrameworkBundle(),
        ];
    }

    public function getCacheDir()
    {
        return __DIR__ . '/../cache/' . spl_object_hash($this);
    }

    public function getLogDir()
    {
        return sys_get_temp_dir();
    }

    public function boot()
    {
        parent::boot();

        foreach ($this->services as $id => $service) {
            if (is_object($service) && !$service instanceof Definition) {
                $this->container->set($id, $service);
            }
        }
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->add('/graphql-endpoint', GraphQLController::class, 'graphql');

        $routes->import(__DIR__ . '/../../Resources/config/routing.xml', '/api');
    }

    protected function configureContainer(ContainerBuilder $containerBuilder, LoaderInterface $loader)
    {
        $frameworkConfiguration = $this->configuration['framework'] ?? [];
        $frameworkConfiguration += [
            'secret' => 'test',
            'router' => ['utf8' => true],
        ];
        $graphQLBundleConfiguration = $this->configuration['player259_graphql'] ?? [];

        $containerBuilder->loadFromExtension('framework', $frameworkConfiguration);
        $containerBuilder->loadFromExtension('player259_graphql', $graphQLBundleConfiguration);
    }

    protected function build(ContainerBuilder $container)
    {
        foreach ($this->services as $id => $service) {
            if (is_string($service) && class_exists($service)) {
                $definition = new Definition($service);
                $definition
                    ->setAutoconfigured(true)
                    ->setAutowired(true)
                    ->setPublic(true);
            } elseif (!$service instanceof Definition) {
                $definition = new Definition(get_class($service));
                $definition
                    ->setPublic(true)
                    ->setSynthetic(true);
            } else {
                $definition = $service;
            }

            $container->setDefinition($id, $definition);
        }
    }
}
