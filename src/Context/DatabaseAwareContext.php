<?php

namespace DataSift\TestRestExtension\Context;

use Behat\Behat\Context\Context;
use DataSift\TestRestExtension\Driver\Database\DatabaseDriver;

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
