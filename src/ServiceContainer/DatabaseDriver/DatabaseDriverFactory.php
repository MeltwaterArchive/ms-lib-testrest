<?php

namespace DataSift\TestRestExtension\ServiceContainer\DatabaseDriver;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

interface DatabaseDriverFactory
{
    /**
     * Gets the name of the driver being configured.
     *
     * This will be the key of the configuration for the driver.
     *
     * @return string
     */
    public function getDriverName();

    /**
     * Setups configuration for the driver factory.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function configure(ArrayNodeDefinition $builder);

    /**
     * Builds the service definition for the driver.
     *
     * @param array $config
     *
     * @return Definition
     */
    public function buildDriver(array $config);
}
