<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <alonso.mora@gmail.com>
 */

namespace amoracr\backup\db;

use yii\base\Component;

/**
 * Abstract class for managing the database engines.
 *
 * @author Alonso Mora <alonso.mora@gmail.com>
 * @since 1.0
 */
abstract class Database extends Component
{

    /**  @var string Database name */
    protected $dbName;

    /**  @var string Database version */
    protected $dbVersion;

    /**  @var string Database engine name */
    protected $dbEngine;

    /**  @var string Delimiter for query statements */
    protected $delimiter;

    /**  @var string Full path of the dump file  */
    protected $dumpFile;

    /** @var mixed Database connection object */
    protected $connection;

    /** @var array Comments at the begining of dump file */
    protected $beginCommands = [];

    /** @var array Comments at the end of dump file */
    protected $endCommands = [];

    /**
     * Dumps database data into a file
     *
     * @param string $dbHandle Database connection name
     * @param string $path Full path of the dump file
     */
    abstract public function dumpDatabase($dbHandle, $path);

    /**
     * Imports data from dump file to database
     *
     * @param string $dbHandle Database connection name
     * @param string $file Full path of the dump file
     */
    abstract public function importDatabase($dbHandle, $file);

    /**
     * Initializes the database connection
     *
     * @param string $dbHandle Database connection name
     */
    abstract protected function initDb($dbHandle);

    /**
     * Add special quotes to string
     *
     * @param string $string String to add special quotes for SQL
     * @return string String with special quotes for SQL
     */
    abstract protected function escapeName($string);

    /**
     * Dumps schema of a database table in a file
     *
     * @param string $table Name of table
     */
    abstract protected function dumpTableSchema($table);

    /**
     * Dumps data of a database table in a file
     *
     * @param string $table Name of table
     */
    abstract protected function dumpTableData($table);

    /**
     * Dumps triggers of a database table in a file
     *
     * @param string $tableTrigger Name of table
     */
    abstract protected function dumpTrigger($tableTrigger);

    /**
     * Dumps data of a database view in a file
     *
     * @param string $view Name of table
     */
    abstract protected function dumpView($view);

    /**
     * Dumps data of a database function in a file
     *
     * @param string $function Name of function
     */
    abstract protected function dumpFunction($function);

    /**
     * Dumps data of a database procedure in a file
     *
     * @param string $procedure Name of procedure
     */
    abstract protected function dumpProcedure($procedure);

    /**
     * Dumps data of a database index in a file
     *
     * @param string $index Name of index
     */
    abstract protected function dumpIndex($index);

    /**
     * Gets the list of user defined indexes of database
     *
     * @return array Array with list of indexes names
     */
    abstract protected function getIndexes();

    /**
     * Gets the list of tables of database
     *
     * @return array Array with list of tables names
     */
    abstract protected function getTables();

    /**
     * Gets the list of views of database
     *
     * @return array Array with list of views names
     */
    abstract protected function getViews();

    /**
     * Gets the list of triggers of database
     *
     * @return array Array with list of triggers names
     */
    abstract protected function getTriggers();

    /**
     * Gets the list of stored functions of database
     *
     * @return array Array with list of stored functions names
     */
    abstract protected function getFunctions();

    /**
     * Gets the list of stored procedures of database
     *
     * @return array Array with list of stored procedures names
     */
    abstract protected function getProcedures();

    /**
     * Gets the column names of a table
     *
     * @param string $table Name of table
     * @param array $numericColumns Array for storing if a column is numeric or not
     * @return string String with the columns names of table
     */
    abstract protected function getTableColumns(&$table, &$numericColumns);

    /**
     * Gets the data of a row
     *
     * @param array $row Row Array with result set from a query
     * @param array $numericColumns Array that indicates if a column is numeric or not
     * @return string String with the values ready to be put in a file
     */
    abstract protected function getTableRowValues(&$row, &$numericColumns);

    /**
     * Adds heading comments to dump file
     */
    protected function openDump()
    {
        if (file_exists($this->dumpFile)) {
            @unlink($this->dumpFile);
        }

        $engine = sprintf("-- %s database dump\n", $this->dbEngine);
        $version = sprintf("-- %s version: %s\n", $this->dbEngine, $this->dbVersion);
        $database = sprintf("-- Database: %s\n", $this->dbName);
        $this->saveToFile("-- ------------------------------------------------------\n");
        $this->saveToFile($engine);
        $this->saveToFile($database);
        $this->saveToFile($version);
        $this->saveToFile("-- ------------------------------------------------------\n\n");

        foreach ($this->beginCommands as $command) {
            $this->saveToFile($command);
        }
    }

    /**
     * Adds closing comments to dump file
     */
    protected function closeDump()
    {
        foreach ($this->endCommands as $command) {
            $this->saveToFile($command);
        }
    }

    /**
     * Adds content to dump file
     *
     * @param string $text Content to save
     */
    protected function saveToFile($text)
    {
        file_put_contents($this->dumpFile, $text, FILE_APPEND);
    }

}
