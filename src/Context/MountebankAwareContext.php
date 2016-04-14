<?php

namespace DataSift\TestRestExtension\Context;

use Behat\Behat\Context\Context;

interface MountebankAwareContext extends Context
{
    /**
     * Sets the mountebank config
     *
     * @param array $config
     *
     * @return void
     */
    public function setMountebankConfig(array $config);
}
