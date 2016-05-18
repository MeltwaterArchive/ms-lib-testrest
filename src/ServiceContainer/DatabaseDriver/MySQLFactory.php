<?php

namespace DataSift\BehatExtension\ServiceContainer\DatabaseDriver;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

class MySQLFactory implements DatabaseDriverFactory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'mysql';
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->scalarNode('host')->defaultValue('127.0.0.1')->end()
            ->scalarNode('port')->defaultValue(3306)->end()
            ->scalarNode('dbname')->isRequired()->end()
            ->scalarNode('username')->isRequired()->end()
            ->scalarNode('password')->defaultValue('')->end()
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
        return new Definition('DataSift\BehatExtension\Driver\Database\MySQLDriver', array(
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['username'],
            $config['password'],
            isset($config['schema']) ? $config['schema'] : false,
            isset($config['data']) ? $config['data'] : false
        ));
    }
}