<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <alonso.mora@gmail.com>
 */

namespace amoracr\backup\db;

use amoracr\backup\db\Database;
use Yii;
use \Exception;

/**
 * Component for dumping and restoring database data for PostgreSQL databases
 *
 * @author Alonso Mora <alonso.mora@gmail.com>
 * @since 1.1.0
 */
class PostgreSQL extends Database
{

    protected const MAX_SQL_SIZE = 2147483648;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->delimiter = ';';
        $this->dbEngine = 'PostgreSQL';
        $this->beginCommands = [
            "SET statement_timeout = 0;\n",
            "SET lock_timeout = 0;\n",
            "SET idle_in_transaction_session_timeout = 0;\n",
            "SET client_encoding = 'UTF8';\n",
            "SET standard_conforming_strings = on;\n",
            "SELECT pg_catalog.set_config('search_path', '', false);\n",
            "SET check_function_bodies = false;\n",
            "SET xmloption = content;\n",
            "SET client_min_messages = warning;\n",
            "SET row_security = off;\n\n",
            "SET default_tablespace = '';\n\n",
            "SET default_with_oids = false;\n",
        ];
        $this->endCommands = [
            "\n-- Dump completed\n",
        ];
    }

    /**
     * @inheritdoc
     */
    public function dumpDatabase($dbHandle, $path)
    {
        $this->dumpFile = Yii::getAlias($path) . DIRECTORY_SEPARATOR . $dbHandle . '.sql';
        $this->initDb($dbHandle);
        $this->openDump();

        $this->saveToFile("\n-- \n-- Dumping tables for database '{$this->dbName}'\n-- \n");
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $this->dumpTableSchema($table);
            $this->dumpTableData($table);
        }

        $this->dumpTablesConstraints();
        $this->dumpTablesRelations();

        $views = $this->getViews();
        $this->saveToFile("\n-- \n-- Dumping views for database '{$this->dbName}'\n-- \n");
        foreach ($views as $view) {
            $this->dumpView($view);
        }

        $this->saveToFile("\n-- \n-- Dumping indexes for database '{$this->dbName}'\n-- \n");
        $indexes = $this->getIndexes();
        foreach ($indexes as $index) {
            $this->dumpIndex($index);
        }

        $this->saveToFile("\n-- \n-- Dumping triggers for database '{$this->dbName}'\n-- \n");
        $triggers = $this->getTriggers();
        foreach ($triggers as $trigger) {
            $this->dumpTrigger($trigger);
        }

        $this->saveToFile("\n-- \n-- Dumping routines for database '{$this->dbName}'\n-- \n");
        $functions = $this->getFunctions();
        foreach ($functions as $function) {
            $this->dumpFunction($function);
        }

        $procedures = $this->getProcedures();
        foreach ($procedures as $procedure) {
            $this->dumpProcedure($procedure);
        }

        pg_close($this->connection);
        $this->closeDump();
        if (!file_exists($this->dumpFile)) {
            return false;
        }

        return $this->dumpFile;
    }

    /**
     * @inheritdoc
     */
    public function importDatabase($dbHandle, $file)
    {
        $this->initDb($dbHandle);
        $fp = fopen($file, "r");
        $sql = '';

        while ($line = fgets($fp, 65535)) {
            $lineLen = strlen($line);
            $lineStart = substr($line, 0, 2);

            if ($lineLen <= 0 || in_array($lineStart, ['--', '/*'])) {
                continue;
            }

            if (strtoupper(substr($line, 0, 10)) === 'DELIMITER ') {
                $this->delimiter = trim(substr($line, 10));
            } elseif (substr($trimmedLine = rtrim($line), -strlen($this->delimiter)) === $this->delimiter) {
                $sql .= substr($trimmedLine, 0, -strlen($this->delimiter));
                if (false === pg_query($this->connection, $sql)) {
                    $msg = pg_last_error($this->connection);
                    throw new Exception($msg);
                }
                $sql = '';
            } else {
                $sql .= $line;
            }
        }

        if (trim($sql) !== '') {
            if (false === pg_query($this->connection, $sql)) {
                $msg = pg_last_error($this->connection);
                throw new Exception($msg);
            }
        }

        fclose($fp);
        pg_close($this->connection);
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function dumpFunction($function)
    {

    }

    /**
     * @inheritdoc
     */
    protected function dumpIndex($index)
    {
        $result = pg_query($this->connection, "
            SELECT indexname,
                   indexdef
            FROM pg_indexes
            WHERE indexname =  '$index'
            ");
        while ($row = pg_fetch_row($result)) {
            $def = (string) $row[1];
            $this->saveToFile("\n-- Index $index\nDROP INDEX IF EXISTS $index;\n");
            $sql = trim($def);
            $this->saveToFile("$sql;\n");
        }
    }

    /**
     * @inheritdoc
     */
    protected function dumpProcedure($procedure)
    {

    }

    /**
     * @inheritdoc
     */
    protected function dumpTableData($table)
    {
        $numeric = [];
        $fields = $this->getTableColumns($table, $numeric);
        $result = pg_query($this->connection, "SELECT * FROM \"$table\"");
        $this->saveToFile("\n-- Data for table '$table'\n");
        $size = 0;
        while ($row = pg_fetch_assoc($result)) {
            $s = $this->getTableRowValues($row, $numeric);
            $s = ($size == 0) ? "INSERT INTO \"$table\" ($fields) VALUES\n$s" : ",\n$s";

            $len = strlen($s);
            $this->saveToFile($s);

            $size += $len;
            if ($size > self::MAX_SQL_SIZE) {
                $this->saveToFile(";\n");
                $size = 0;
            }
        }
        if ($size > 0) {
            $this->saveToFile(";\n");
        }
    }

    /**
     * @inheritdoc
     */
    protected function dumpTableSchema($table)
    {
        $this->saveToFile("\n-- Table structure for table '$table'\nDROP TABLE \"$table\" CASCADE;\n");
        $sql = $this->getTableSchema($table);
        $this->saveToFile("$sql");
    }

    /**
     * @inheritdoc
     */
    protected function dumpTrigger($tableTrigger)
    {

    }

    /**
     * @inheritdoc
     */
    protected function dumpView($view)
    {
        $result = pg_query($this->connection, "
        SELECT schemaname,
               viewname,
               definition
        FROM pg_catalog.pg_views
        WHERE viewname = '$view'
            AND schemaname NOT IN ('pg_catalog', 'information_schema')
        ");
        while ($row = pg_fetch_row($result)) {
            $schema = (string) $row[0];
            $def = (string) $row[2];
            $this->saveToFile("\n-- View $view\nDROP VIEW IF EXISTS $view;\n");
            $sql = sprintf("CREATE VIEW %s.%s AS \n%s", trim($schema), $view, trim($def));
            $this->saveToFile("$sql\n");
        }
    }

    /**
     * @inheritdoc
     */
    protected function escapeName($string)
    {
        return $string;
    }

    /**
     * @inheritdoc
     */
    protected function getFunctions()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function getIndexes()
    {
        $list = [];
        $result = pg_query($this->connection, "
            SELECT *
            FROM pg_indexes
            WHERE tablename NOT LIKE 'pg%'
                AND indexname not like '%_pkey'
                AND indexname not like '%_key'
            ");
        while ($row = pg_fetch_row($result)) {
            array_push($list, $row[2]);
        }
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        return $list;
    }

    /**
     * @inheritdoc
     */
    protected function getProcedures()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function getTableColumns(&$table, &$numericColumns)
    {
        $fieldnames = [];
        $result = pg_query($this->connection, "
            SELECT table_name,
                   column_name,
                   data_type
            FROM information_schema.columns
            WHERE table_name = '$table'
        ");

        while ($row = pg_fetch_row($result)) {
            $name = (string) $row[1];
            array_push($fieldnames, $name);
            $numericColumns[$name] = $row[2];
        }

        $fields = implode(",", $fieldnames);
        return $fields;
    }

    /**
     * @inheritdoc
     */
    protected function getTableRowValues(&$row, &$numericColumns)
    {
        $values = [];
        foreach ($row as $key => $value) {
            if (null === $value) {
                array_push($values, "NULL");
            } elseif ((bool) preg_match('#^[^(]*(SMALLINT|INTEGER|BIGINT|DECIMAL|NUMERIC|REAL|DOUBLE$|SERIAL|BIGSERIAL|MONEY)#i', $numericColumns[$key])) {
                array_push($values, $value);
            } else {
                $s = "'" . $value . "'";
                array_push($values, $s);
            }
        }

        $rowValues = "(" . implode(",", $values) . ")";
        return $rowValues;
    }

    /**
     * @inheritdoc
     */
    protected function getTables()
    {
        $list = [];
        $result = pg_query($this->connection, "
            SELECT relname AS tablename
            FROM pg_class
            WHERE relkind IN ('r')
                AND relname NOT LIKE 'pg_%'
                AND relname NOT LIKE 'sql_%'
            ORDER BY tablename ASC
        ");

        while ($row = pg_fetch_row($result)) {
            array_push($list, $row[0]);
        }
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        return $list;
    }

    /**
     * @inheritdoc
     */
    protected function getTriggers()
    {
        $list = [];
        $result = pg_query($this->connection, "
            SELECT  event_object_table AS table_name,
                    trigger_name
            FROM information_schema.triggers
            GROUP BY table_name, trigger_name
            ORDER BY table_name, trigger_name
            ");
        while ($row = pg_fetch_row($result)) {
            array_push($list, $row[0]);
        }

        $list = array_unique($list, SORT_STRING);
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        return $list;
    }

    /**
     * @inheritdoc
     */
    protected function getViews()
    {
        $list = [];
        $result = pg_query($this->connection, "
            SELECT schemaname,
                   viewname
            FROM pg_catalog.pg_views
            WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
            ORDER BY schemaname, viewname
            ");

        while ($row = pg_fetch_row($result)) {
            array_push($list, $row[1]);
        }
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        return $list;
    }

    /**
     * @inheritdoc
     */
    protected function initDb($dbHandle)
    {
        $dsn = str_replace('pgsql:', '', Yii::$app->$dbHandle->dsn);
        $dbParams = explode(';', $dsn);
        $database = $host = '';
        $port = 5432;

        foreach ($dbParams as $param) {
            list($paramName, $paramvalue) = explode('=', $param);
            if ('dbname' === $paramName) {
                $database = $paramvalue;
            }
            if ('host' === $paramName) {
                $host = $paramvalue;
            }
            if ('port' === $paramName) {
                $port = intval($paramvalue);
            }
        }
        $this->dbName = $database;
        $this->dbVersion = Yii::$app->$dbHandle->serverVersion;
        $conn_string = sprintf("host=%s port=%d dbname=%s user=%s password=%s", $host, $port, $database, Yii::$app->$dbHandle->username, Yii::$app->$dbHandle->password);

        $this->connection = pg_pconnect($conn_string);
        $stat = pg_connection_status($this->connection);
        if ($stat != PGSQL_CONNECTION_OK) {
            $msg = pg_last_error($this->connection);
            throw new Exception($msg);
        }
    }

    /**
     * Gets the schema of a database table
     *
     * @param string $table Name of table
     * @return string String with table schema
     */
    private function getTableSchema($table)
    {
        $str = "CREATE TABLE \"$table\" (";
        $result = pg_query($this->connection, "
            SELECT attnum,
                   attname,
                   typname,
                   atttypmod-4,
                   attnotnull,
                   atthasdef, adsrc AS def
            FROM pg_attribute,
                 pg_class,
                 pg_type,
                 pg_attrdef
            WHERE pg_class.oid=attrelid
                AND pg_type.oid=atttypid
                AND attnum>0
                AND pg_class.oid=adrelid
                AND adnum=attnum
                AND atthasdef='t'
                AND lower(relname)= '$table'
            UNION
            SELECT attnum,
                   attname,
                   typname,
                   atttypmod-4,
                   attnotnull,
                   atthasdef,
                   '' AS def
            FROM pg_attribute,
                 pg_class,
                 pg_type
            WHERE pg_class.oid=attrelid
                AND pg_type.oid=atttypid
                AND attnum>0
                AND atthasdef='f'
                AND lower(relname)= '$table'
        ");

        while ($row = pg_fetch_row($result)) {
            $str .= "\n" . $row[1] . " " . $row[2];
            if ($row[2] === "varchar") {
                $str .= "(" . $row[3] . ")";
            }
            if ($row[4] === "t") {
                $str .= " NOT NULL";
            }
            if ($row[5] === "t") {
                $str .= " DEFAULT " . $row[6];
            }
            $str .= ",";
        }

        $sql = rtrim($str, ",");
        $sql .= "\n);";
        return $sql;
    }

    /**
     * Dumps indexes of a database table in file
     *
     */
    private function dumpTablesConstraints()
    {
        $currentTable = $sql = '';
        $result = pg_query($this->connection, "
            SELECT pgc.conname AS constraint_name,
                   ccu.table_schema AS table_schema,
                   ccu.table_name AS table_name,
                   ccu.column_name,
                   contype,
                   pg_get_constraintdef(pgc.oid) AS constraint_def
            FROM pg_constraint pgc
            JOIN pg_namespace nsp ON nsp.oid = pgc.connamespace
            JOIN pg_class cls ON pgc.conrelid = cls.oid
            LEFT JOIN information_schema.constraint_column_usage ccu ON pgc.conname = ccu.constraint_name AND nsp.nspname = ccu.constraint_schema
            WHERE contype != 'f'
            ORDER BY table_name ASC, constraint_name ASC
            ");

        while ($row = pg_fetch_row($result)) {
            $table = (string) $row[2];
            $schema = (string) $row[1];
            $constraint = (string) $row[0];
            $def = (string) $row[5];
            if ($currentTable !== $table) {
                $currentTable = $table;
                $sql .= "\n-- Constraints for table '$table'\n";
            }
            $sql .= sprintf("ALTER TABLE ONLY %s.%s ADD CONSTRAINT %s %s;\n", trim($schema), trim($table), trim($constraint), trim($def));
        }
        $this->saveToFile("$sql");
    }

    /**
     * Dumps relations data for database tables
     */
    private function dumpTablesRelations()
    {
        $currentTable = $sql = '';
        $result = pg_query($this->connection, "
            SELECT cl.relname AS table_name,
                   ccu.table_schema AS table_schema,
                   ct.conname AS constraint_name,
                   pg_get_constraintdef(ct.oid) AS constraint_def
            FROM pg_catalog.pg_attribute a
            JOIN pg_catalog.pg_class cl ON (a.attrelid = cl.oid AND cl.relkind = 'r')
            JOIN pg_catalog.pg_namespace n ON (n.oid = cl.relnamespace)
            JOIN pg_catalog.pg_constraint ct ON (a.attrelid = ct.conrelid AND ct.confrelid != 0 AND ct.conkey[1] = a.attnum)
            JOIN pg_catalog.pg_class clf ON (ct.confrelid = clf.oid AND clf.relkind = 'r')
            JOIN pg_catalog.pg_namespace nf ON (nf.oid = clf.relnamespace)
            JOIN pg_catalog.pg_attribute af ON (af.attrelid = ct.confrelid AND  af.attnum = ct.confkey[1])
            LEFT JOIN information_schema.constraint_column_usage ccu ON ct.conname = ccu.constraint_name AND n.nspname = ccu.constraint_schema
            ORDER BY table_name ASC, constraint_name ASC
            ");

        while ($row = pg_fetch_row($result)) {
            $table = (string) $row[0];
            $schema = (string) $row[1];
            $constraint = (string) $row[2];
            $def = (string) $row[3];
            if ($currentTable !== $table) {
                $currentTable = $table;
                $sql .= "\n-- Foreign Keys for table '$table'\n";
            }

            $sql .= sprintf("ALTER TABLE ONLY %s.%s ADD CONSTRAINT %s %s;\n", trim($schema), trim($table), trim($constraint), trim($def));
        }
        $this->saveToFile("$sql\n");
    }

}
