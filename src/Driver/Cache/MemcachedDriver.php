<?php

namespace DataSift\BehatExtension\Driver\Cache;

class MemcachedDriver implements CacheDriver
{
    /**
     * @var \Memcached
     */
    protected $memcached;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * MemcachedDriver constructor.
     *
     * @param string      $host
     * @param int         $port
     */
    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Makes a connection to the database and applies the schema and seed data
     *
     * @return mixed
     */
    public function flushCache()
    {
        $this->connect()
            ->flush();
    }

    /**
     * Make a connect to the memcached instance
     *
     * @return \Memcached
     */
    protected function connect()
    {
        if (is_null($this->memcached)) {
            $this->memcached = new \Memcached();
            $this->memcached->addServer($this->host, $this->port);
        }

        return $this->memcached;
    }
}
