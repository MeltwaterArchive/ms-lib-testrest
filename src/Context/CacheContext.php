<?php

namespace DataSift\BehatExtension\Context;

use DataSift\BehatExtension\Driver\Cache\CacheDriver;

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
