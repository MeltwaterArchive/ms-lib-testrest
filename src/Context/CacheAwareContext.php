<?php

namespace DataSift\TestRestExtension\Context;

use Behat\Behat\Context\Context;
use DataSift\TestRestExtension\Driver\Cache\CacheDriver;

interface CacheAwareContext extends Context
{
    /**
     * Sets Cache driver instance.
     *
     * @param CacheDriver $cache
     *
     * @return void
     */
    public function setCacheDriver(CacheDriver $cache);
}
