<?php

namespace DataSift\BehatExtension\ServiceContainer\DatabaseDriver;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

class SQLiteFactory implements DatabaseDriverFactory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'sqlite';
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->scalarNode('username')->end()
            ->scalarNode('password')->end()
            ->scalarNode('path')->isRequired()->end()
            ->scalarNode('schema')->end()
            ->scalarNode('data')->end()
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {

        return new Definition('DataSift\BehatExtension\Driver\Database\SQLiteDriver', array(
            $config['path'],
            isset($config['username']) ? $config['username'] : false,
            isset($config['password']) ? $config['password'] : false,
            isset($config['schema']) ? $config['schema'] : false,
            isset($config['data']) ? $config['data'] : false
        ));
    }
}