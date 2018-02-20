<?php
/**
 *
 *
 * This software is for internal use only, as such it is not licensed for
 * release in the public domain.
 *
 * @author         Nathan Macnamara <nathan.macnamara@datasift.com>
 * @copyright      Copyright (c) 2016 MediaSift Ltd. (http://mediasift.com)
 * @license        http://mediasift.com
 *
 * @package
 * @subpackage
 */

namespace DataSift\BehatExtension\Context;

use DataSift\BehatExtension\Driver\Database\DatabaseDriver;
use Webmozart\Assert\Assert;

class DatabaseContext implements DatabaseAwareContext
{
    /**
     * @var DatabaseDriver
     */
    protected $driver;

    /**
     * {@inheritdoc}
     */
    public function setDatabaseDriver(DatabaseDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @BeforeScenario
     */
    public function before()
    {
        $this->driver->setupDatabase();
    }

    /**
     * Checks the number of results in the database
     *
     * Example:
     *    Given that the "test" table has "3" rows
     *
     * @param string $table Name of the database table
     * @param string $count Count of the number of rows in the database
     *
     * @Given /^that the "([^"]*)" table has "([^"]*)" rows$/
     */
    public function thatTheTableHasNRows($table, $count)
    {
        $actual = $this->driver->countRowsInTable($table);
        Assert::eq($count, $actual, 'Row count mismatch! (given: '.$count.', match: '.$actual.')');
    }
}
