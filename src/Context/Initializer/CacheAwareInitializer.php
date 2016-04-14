<?php

namespace DataSift\TestRestExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use DataSift\TestRestExtension\Context\CacheAwareContext;
use DataSift\TestRestExtension\Driver\Cache\CacheDriver;

class CacheAwareInitializer implements ContextInitializer
{
    /**
     * @var CacheDriver
     */
    protected $driver;

    /**
     * Initializes initializer.
     *
     * @param CacheDriver $driver
     */
    public function __construct(CacheDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Initializes provided context.
     *
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof CacheAwareContext) {
            $context->setCacheDriver($this->driver);
        }
    }
}
