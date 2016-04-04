<?php

namespace DataSift\TestRestExtension\Driver\Cache;

interface CacheDriver
{
    /**
     * Makes a connection to the database and applies the schema and seed data
     *
     * @return mixed
     */
    public function flushCache();
}
