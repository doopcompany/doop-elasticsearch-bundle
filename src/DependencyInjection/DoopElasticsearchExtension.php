<?php

namespace Doop\ElasticsearchBundle\DependencyInjection;

use Massive\Bundle\SearchBundle\MassiveSearchBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

class DoopElasticsearchExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('doop_elasticsearch.indices', $config['indices']);
        $container->setParameter('doop_elasticsearch.hosts', $config['hosts']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $bundles = $container->getParameter('kernel.bundles');
        if (
            true === $config['massive_search_hooks_enabled'] &&
            MassiveSearchBundle::class === ($bundles['MassiveSearchBundle'] ?? null)
        ) {
            $loader->load('services/massive_search_hooks.yaml');
        } else {
            $loader->load('services/elasticsearch.yaml');
        }
        $loader->load('services.yaml');
    }
}
