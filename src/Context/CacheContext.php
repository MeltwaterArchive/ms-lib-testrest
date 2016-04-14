<?php

namespace DataSift\TestRestExtension\Context;

use DataSift\TestRestExtension\Driver\Cache\CacheDriver;

class CacheContext implements CacheAwareContext
{
    /**
     * @var CacheDriver
     */
    protected $driver;

    /**
     * {@inheritdoc}
     */
    public function setCacheDriver(CacheDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @BeforeScenario
     */
    public function before()
    {
        $this->driver->flushCache();
    }
}
