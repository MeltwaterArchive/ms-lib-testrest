<?php

namespace DataSift\TestRestExtension\Driver\Database;

abstract class SQLDriver implements DatabaseDriver
{
    /**
     * @var string
     */
    protected $schema;

    /**
     * @var string
     */
    protected $data;

    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * @return \PDO
     */
    abstract protected function connect();

    /**
     * @inheritdoc
     */
    public function countRowsInTable($table)
    {
        $dbtest = $this->connect();
        $query = $dbtest->prepare("select COUNT(*) from " . $table);
        $query->execute();
        return $query->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * Processes schema and seed files
     *
     * @return array
     */
    protected function loadSqlQueries()
    {
        $sql = "";
        if (false !== $this->schema && is_readable($this->schema)) {
            $sql = file_get_contents($this->schema) . "\n";
        }
        if (false !== $this->data && is_readable($this->data)) {
            $sql .= file_get_contents($this->data) . "\n";
        }

        // split sql string into single line SQL statements
        $sql = str_replace("\r", '', $sql); // remove CR
        $sql = preg_replace("/\/\*([^\*]*)\*\//si", ' ', $sql); // remove comments (/* ... */)
        $sql = preg_replace("/\n([\s]*)\#([^\n]*)/si", '', $sql); // remove comments (lines starting with '#')
        $sql = preg_replace("/\n([\s]*)\-\-([^\n]*)/si", '', $sql); // remove comments (lines starting with '--')
        $sql = preg_replace("/;([\s]*)\n/si", ";\r", $sql); // mark valid new lines
        $sql = str_replace("\n", ' ', $sql); // replace new lines with a space character
        $sql = preg_replace("/(;\r)$/si", '', $sql); // remove last ";\r"

        return explode(";\r", trim($sql));
    }
}