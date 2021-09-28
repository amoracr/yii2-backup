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
use \SQLite3;

/**
 * Component for dumping and restoring database data for SQLite databases
 *
 * @author Alonso Mora <alonso.mora@gmail.com>
 * @since 1.0
 */
class Sqlite extends Database
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->delimiter = ';';
        $this->dbEngine = 'SQLite';
        $this->beginCommands = [
            "PRAGMA foreign_keys=OFF;\n",
            "BEGIN TRANSACTION;\n",
        ];
        $this->endCommands = [
            "\nCOMMIT;\n\n",
            "-- Dump completed\n",
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

        $this->saveToFile("\n--\n-- Dumping tables for database '{$this->dbName}'\n-- \n");
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $this->dumpTableSchema($table);
            $this->dumpTableData($table);
        }
        $this->dumpTableSequences();

        $this->saveToFile("\n-- \n-- Dumping views for database '{$this->dbName}'\n-- \n");
        $views = $this->getViews();
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

        $this->closeDump();
        $this->connection->close();

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
        $query = '';

        while ($line = fgets($fp, 65535)) {
            $lineLen = strlen($line);
            $lineStart = substr($line, 0, 2);

            if ($lineLen <= 0 || in_array($lineStart, ['--', '/*'])) {
                continue;
            }

            if (strtoupper(substr($line, 0, 10)) === 'DELIMITER ') {
                $this->delimiter = trim(substr($line, 10));
            } elseif (substr($trimmedLine = rtrim($line), -strlen($this->delimiter)) === $this->delimiter) {
                $query .= substr($trimmedLine, 0, -strlen($this->delimiter));
                $this->connection->exec($query);
                $query = '';
            } else {
                $query .= $line;
            }
        }

        if (trim($query) !== '') {
            $this->connection->exec($query);
        }

        fclose($fp);
        $this->connection->close();
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
        $this->saveToFile("\n-- Index `$index`\n");
        $this->saveToFile("DROP INDEX IF EXISTS `$index`;\n");
        $sql = $this->connection->querySingle("SELECT sql FROM sqlite_master WHERE type = 'index' AND name = '$index'") . ";";
        $this->saveToFile("$sql\n");
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
        $this->saveToFile("\n-- Data for table `$table`\n");
        $numeric = [];
        $statement = "INSERT INTO `%s` (%s) VALUES (%s);\n";

        $rows = $this->connection->query("SELECT * FROM $table");
        if ($rows->numColumns() > 0 && $rows->columnType(0) != SQLITE3_NULL) {
            $fields = $this->getTableColumns($table, $numeric);

            while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
                $values = $this->getTableRowValues($row, $numeric);
                $sql = sprintf($statement, $table, $fields, $values);
                $this->saveToFile($sql);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function dumpTableSchema($table)
    {
        $this->saveToFile("\n-- Table structure for table  `$table`\n");
        $this->saveToFile("DROP TABLE IF EXISTS `$table`;\n");
        $sql = $this->connection->querySingle("SELECT sql FROM sqlite_master WHERE type ='table' AND name = '$table'") . ";";
        $this->saveToFile("$sql\n");
    }

    /**
     * @inheritdoc
     */
    protected function dumpTrigger($tableTrigger)
    {
        $this->saveToFile("\n-- Trigger `$tableTrigger`\n");
        $this->saveToFile("DROP TRIGGER IF EXISTS `$tableTrigger`;\n");
        $sql = $this->connection->querySingle("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = '$tableTrigger'") . ";";
        $this->saveToFile("$sql\n");
    }

    /**
     * @inheritdoc
     */
    protected function dumpView($view)
    {
        $this->saveToFile("\n-- View `$view`\n");
        $this->saveToFile("DROP VIEW IF EXISTS `$view`;\n");
        $sql = $this->connection->querySingle("SELECT sql FROM sqlite_master WHERE type = 'view' AND name = '$view'") . ";";
        $this->saveToFile("$sql\n");
    }

    /**
     * @inheritdoc
     */
    protected function escapeName($string)
    {
        return '"' . str_replace('"', '""', $string) . '"';
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
        $res = $this->connection->query("SELECT name, tbl_name FROM sqlite_master WHERE type ='index' AND name NOT LIKE 'sqlite_autoindex%' ORDER BY tbl_name");
        while ($row = $res->fetchArray(SQLITE3_NUM)) {
            array_push($list, $row[0]);
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
    protected function getTableColumns(&$table, &$numericColumns): string
    {
        $fieldnames = [];
        $columns = $this->connection->query("PRAGMA table_info($table)");
        while ($column = $columns->fetchArray(SQLITE3_ASSOC)) {
            $col = $column["name"];
            array_push($fieldnames, $col);
            $numericColumns[$col] = (bool) preg_match('#^[^(]*(BYTE|COUNTER|SERIAL|INT|LONG$|CURRENCY|REAL|MONEY|FLOAT|DOUBLE|DECIMAL|NUMERIC|NUMBER)#i', $column['type']);
        }

        $fields = implode(",", $fieldnames);
        return $fields;
    }

    /**
     * @inheritdoc
     */
    protected function getTableRowValues(&$row, &$numericColumns): string
    {
        $values = [];
        foreach ($row as $key => $value) {
            if (null === $value) {
                array_push($values, "NULL");
            } elseif (true === $numericColumns[$key]) {
                array_push($values, $value);
            } else {
                $s = "'" . SQLite3::escapeString($value) . "'";
                array_push($values, $s);
            }
        }
        $rowValues = implode(",", $values);
        return $rowValues;
    }

    /**
     * @inheritdoc
     */
    protected function getTables()
    {
        $list = [];
        $res = $this->connection->query("SELECT name FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%';");
        while ($row = $res->fetchArray(SQLITE3_NUM)) {
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
        $res = $this->connection->query("SELECT name FROM sqlite_master WHERE type = 'trigger';");
        while ($row = $res->fetchArray(SQLITE3_NUM)) {
            array_push($list, $row[0]);
        }
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        return $list;
    }

    /**
     * @inheritdoc
     */
    protected function getViews()
    {
        $list = [];
        $res = $this->connection->query("SELECT name FROM sqlite_master WHERE type = 'view';");
        while ($row = $res->fetchArray(SQLITE3_NUM)) {
            array_push($list, $row[0]);
        }
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        return $list;
    }

    /**
     * @inheritdoc
     */
    protected function initDb($dbHandle)
    {
        $dsn = str_replace('sqlite:', '', Yii::$app->$dbHandle->dsn);
        $database = Yii::getAlias($dsn);
        $this->dbName = $database;
        $this->dbVersion = Yii::$app->$dbHandle->serverVersion;
        $dsn = 'sqlite:' . $database;
        $this->connection = new SQLite3($database, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $this->connection->busyTimeout(5000);
    }

    /**
     * Dumps data of table 'sqlite_sequence'
     */
    private function dumpTableSequences()
    {
        $table = 'sqlite_sequence';
        $sequences = $this->connection->query("SELECT * FROM $table");
        if ($sequences->numColumns() > 0 && $sequences->columnType(0) != SQLITE3_NULL) {
            $this->saveToFile("\n-- Data for table `$table`\n");
            $this->saveToFile("DELETE FROM $table;\n");
            $statement = "INSERT INTO \"%s\" (%s) VALUES (%s);\n";
            $numeric = [];
            $fields = $this->getTableColumns($table, $numeric);
            while ($row = $sequences->fetchArray(SQLITE3_ASSOC)) {
                $values = $this->getRowValues($row, $numeric);
                $sql = sprintf($statement, $table, $fields, $values);
                $this->saveToFile($sql);
            }
        }
    }

}
