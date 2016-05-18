<?php

namespace DataSift\BehatExtension\Context;

use Behat\Behat\Context\Context;
use DataSift\BehatExtension\Driver\Database\DatabaseDriver;

interface DatabaseAwareContext extends Context
{
    /**
     * Sets database driver instance.
     *
     * @param DatabaseDriver $driver Database driver
     *
     * @return void
     */
    public function setDatabaseDriver(DatabaseDriver $driver);
}
