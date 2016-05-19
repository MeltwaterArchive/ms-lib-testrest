<?php

namespace DataSift\BehatExtension\Driver\Database;

class SQLiteDriver extends SQLDriver implements DatabaseDriver
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var bool|string
     */
    protected $username;

    /**
     * @var bool|string
     */
    protected $password;

    /**
     * MySQLDriver constructor.
     *
     * @param string      $path
     * @param string|bool $username
     * @param string|bool $password
     * @param string|bool $schema
     * @param string|bool $data
     */
    public function __construct($path, $username = false, $password = false, $schema = false, $data = false)
    {
        $this->path = $path;
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
        $sql_queries = $this->loadSqlQueries();

        //wrapping the SQL statements to improve performance.
        array_unshift($sql_queries, 'BEGIN');
        $sql_queries[] = 'COMMIT';

        //execute all queries
        foreach ($sql_queries as $query) {
            try {
                $dbtest->query($query);
            } catch (\Exception $ex) {
                print "$query\n";
                throw $ex;
            }
        }
    }

    /**
     * Connect to the database
     *
     * @return \PDO
     */
    protected function connect()
    {
        if (is_null($this->connection)) {
            // connect to the database
            $dsn = 'sqlite:' . $this->path;
            $this->connection = new \PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }

        return $this->connection;
    }
}