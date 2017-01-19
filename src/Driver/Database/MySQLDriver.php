<?php

namespace DataSift\BehatExtension\Driver\Database;

class MySQLDriver extends SQLDriver implements DatabaseDriver
{
    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $database;

    /**
     * MySQLDriver constructor.
     *
     * @param string      $host
     * @param int         $port
     * @param string      $database
     * @param string      $username
     * @param string      $password
     * @param string|bool $schema
     * @param string|bool $data
     */
    public function __construct($host, $port, $database, $username, $password, $schema = false, $data = false)
    {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->schema = $schema;
        $this->data = $data;
    }

    /**
     * Setups up the database
     *
     * @throws \Exception
     */
    public function setupDatabase()
    {
        $dbtest = $this->connect();

        // execute all queries
        @$dbtest->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->loadSqlQueries() as $query) {
            try {
                if (! empty($query)) {
                    $dbtest->query($query);
                }
            } catch (\Exception $ex) {
                print "$query\n";
                throw $ex;
            }
        }
        @$dbtest->query('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Connect to the database
     *
     * @return \PDO
     */
    protected function connect()
    {
        if (is_null($this->connection)) {
            $dsn = 'mysql:dbname=' . $this->database
                . ';host=' . $this->host
                . ';port=' . $this->port;
            $this->connection = new \PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }

        return $this->connection;
    }
}
