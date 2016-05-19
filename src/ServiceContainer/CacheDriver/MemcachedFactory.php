<?php

namespace DataSift\BehatExtension\ServiceContainer\CacheDriver;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

class MemcachedFactory implements CacheDriverFactory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'memcached';
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->scalarNode('host')->defaultValue('127.0.0.1')->end()
            ->scalarNode('port')->defaultValue(11211)->end()
            ->end()
        ;
    }
    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        return new Definition('DataSift\BehatExtension\Driver\Cache\MemcachedDriver', array(
            $config['host'],
            $config['port']
        ));
    }
}