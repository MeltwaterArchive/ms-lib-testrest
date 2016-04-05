<?php

namespace DataSift\TestRestExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use DataSift\TestRestExtension\Context\FileAwareContext;

class FileAwareInitializer implements ContextInitializer
{
    /**
     * @var string
     */
    protected $path;

    /**
     * Initializes initializer.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Initializes provided context.
     *
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof FileAwareContext) {
            $context->setPath($this->path);
        }
    }
}
