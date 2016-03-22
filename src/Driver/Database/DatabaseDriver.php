<?php

namespace DataSift\TestRestExtension\Driver\Database;

interface DatabaseDriver
{
    /**
     * Makes a connection to the database and applies the schema and seed data
     *
     * @return mixed
     */
    public function setupDatabase();

    /**
     * Returns the count for the number of rows in the database
     *
     * @param $table
     *
     * @return mixed
     */
    public function countRowsInTable($table);
}
