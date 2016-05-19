<?php

namespace DataSift\BehatExtension\Context;

use Behat\Behat\Context\Context;

interface FileAwareContext extends Context
{
    /**
     * Sets the working path config
     *
     * @param string $path
     *
     * @return void
     */
    public function setPath($path);
}
