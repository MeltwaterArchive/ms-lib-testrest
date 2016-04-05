<?php

namespace DataSift\TestRestExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use DataSift\TestRestExtension\Context\MountebankAwareContext;

class MountebankAwareInitializer implements ContextInitializer
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Initializes initializer.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Initializes provided context.
     *
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof MountebankAwareContext) {
            $context->setMountebankConfig($this->config['mountebank']);
        }
    }
}
