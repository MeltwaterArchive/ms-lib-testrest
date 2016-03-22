<?php


namespace DataSift\TestRestExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use DataSift\TestRestExtension\Context\DatabaseAwareContext;
use DataSift\TestRestExtension\Driver\Database\DatabaseDriver;


class DatabaseAwareInitializer implements ContextInitializer
{
    /**
     * @var
     */
    protected $driver;

    /**
     * Initializes initializer.
     *
     * @param DatabaseDriver $driver
     */
    public function __construct(DatabaseDriver $driver)
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
        if ($context instanceof DatabaseAwareContext) {
            $context->setDatabaseDriver($this->driver);
        }
    }
}
