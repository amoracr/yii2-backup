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
use \mysqli;

/**
 * Component for dumping and restoring database data for MySql databases
 *
 * @author Alonso Mora <alonso.mora@gmail.com>
 * @since 1.0
 */
class Mysql extends Database
{

    protected const MAX_SQL_SIZE = 1e6;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->delimiter = ';';
        $this->dbEngine = 'MySQL';
        $this->beginCommands = [
            "SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;\n",
            "SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;\n",
            "SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;\n",
            "SET NAMES utf8;\n",
            "SET @OLD_TIME_ZONE=@@TIME_ZONE;\n",
            "SET TIME_ZONE='+00:00';\n",
            "SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;\n",
            "SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;\n",
            "SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n",
            "SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0;\n",
        ];
        $this->endCommands = [
            "\nCOMMIT;\n\n",
            "SET TIME_ZONE=@OLD_TIME_ZONE;\n\n",
            "SET SQL_MODE=@OLD_SQL_MODE;\n",
            "SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;\n",
            "SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;\n",
            "SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;\n",
            "SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;\n",
            "SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;\n",
            "SET SQL_NOTES=@OLD_SQL_NOTES;\n\n",
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
        $tables = $this->getTables();
        $views = $this->getViews();
        $locks = array_merge($tables, $views);

        $this->connection->query('LOCK TABLES `' . implode('` READ, `', $locks) . '` READ');
        $this->saveToFile("\n-- \n-- Dumping tables for database '{$this->dbName}'\n-- \n");
        foreach ($tables as $table) {
            $this->dumpTableSchema($table);
            $this->dumpTableData($table);
        }

        $this->saveToFile("\n-- \n-- Dumping views for database '{$this->dbName}'\n-- \n");
        foreach ($views as $view) {
            $this->dumpView($view);
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

        $this->connection->query('UNLOCK TABLES');
        $this->connection->close();
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
                if (!$this->connection->query($sql)) {
                    throw new Exception($this->connection->error . ': ' . $sql);
                }
                $sql = '';
            } else {
                $sql .= $line;
            }
        }

        if (trim($sql) !== '') {
            if (!$this->connection->query($sql)) {
                throw new Exception($this->connection->error . ': ' . $sql);
            }
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
        $escapedName = $this->escapeName($function);
        $this->saveToFile("\n-- Function $escapedName\nDROP FUNCTION IF EXISTS $escapedName;\n");

        $this->saveToFile("DELIMITER ;;\n\n");
        $res = $this->connection->query("SHOW CREATE FUNCTION $escapedName");
        $row = $res->fetch_assoc();
        $sql = (is_array($row) && isset($row['Create Function'])) ? $row['Create Function'] : '';
        $this->saveToFile("$sql;\n\n");
        $this->saveToFile("DELIMITER ;\n\n");
        $res->close();
    }

    /**
     * @inheritdoc
     */
    protected function dumpIndex($index)
    {

    }

    /**
     * @inheritdoc
     */
    protected function dumpProcedure($procedure)
    {
        $escapedName = $this->escapeName($procedure);
        $this->saveToFile("\n-- Procedure $escapedName\nDROP PROCEDURE IF EXISTS $escapedName;\n");

        $this->saveToFile("DELIMITER ;;\n\n");
        $res = $this->connection->query("SHOW CREATE PROCEDURE $escapedName");
        $row = $res->fetch_assoc();
        $sql = (is_array($row) && isset($row['Create Procedure'])) ? $row['Create Procedure'] : '';
        $this->saveToFile("$sql;\n\n");
        $this->saveToFile("DELIMITER ;\n\n");
        $res->close();
    }

    /**
     * @inheritdoc
     */
    protected function dumpTableData($table)
    {
        $escapedName = $this->escapeName($table);
        $numeric = [];
        $this->saveToFile("\n-- Data for table $escapedName\n");
        $fields = $this->getTableColumns($escapedName, $numeric);
        $this->saveToFile("LOCK TABLES $escapedName WRITE;\n");
        $size = 0;
        $res = $this->connection->query("SELECT * FROM $escapedName", MYSQLI_USE_RESULT);

        while ($row = $res->fetch_assoc()) {
            $s = $this->getTableRowValues($row, $numeric);
            $s = ($size == 0) ? "INSERT INTO $escapedName ($fields) VALUES\n$s" : ",\n$s";

            $len = strlen($s);
            $this->saveToFile($s);

            $size += $len;
            if ($size > self::MAX_SQL_SIZE) {
                $this->saveToFile(";\n");
                $size = 0;
            }
        }

        $res->close();
        if ($size > 0) {
            $this->saveToFile(";\n");
        }
        $this->saveToFile("UNLOCK TABLES;\n");
    }

    /**
     * @inheritdoc
     */
    protected function dumpTableSchema($table)
    {
        $escapedName = $this->escapeName($table);
        $this->saveToFile("\n-- Table structure for table $escapedName\nDROP TABLE IF EXISTS $escapedName;\n");

        $res = $this->connection->query("SHOW CREATE TABLE $escapedName");
        $row = $res->fetch_assoc();
        $sql = (is_array($row) && isset($row['Create Table'])) ? $row['Create Table'] : '';
        $this->saveToFile("$sql;\n");
        $res->close();
    }

    /**
     * @inheritdoc
     */
    protected function dumpTrigger($tableTrigger)
    {
        $escapedName = $this->escapeName($tableTrigger);
        $this->saveToFile("\n-- Triggers for table $tableTrigger\n");
        $res = $this->connection->query("SHOW TRIGGERS LIKE '" . $this->connection->real_escape_string($tableTrigger) . "'");
        if ($res->num_rows > 0) {
            $this->saveToFile("DELIMITER ;;\n");
            while ($row = $res->fetch_assoc()) {
                $triggerName = $this->escapeName($row['Trigger']);
                $sql = sprintf("CREATE TRIGGER %s %s %s ON %s FOR EACH ROW\n%s;;\n\n", $triggerName, $row['Timing'], $row['Event'], $escapedName, $row['Statement']);
                $this->saveToFile($sql);
            }
            $this->saveToFile("DELIMITER ;\n\n");
        }
        $res->close();
    }

    /**
     * @inheritdoc
     */
    protected function dumpView($view)
    {
        $escapedName = $this->escapeName($view);
        $this->saveToFile("\n-- View $escapedName\nDROP VIEW IF EXISTS $escapedName;\n");

        $res = $this->connection->query("SHOW CREATE TABLE $escapedName");
        $row = $res->fetch_assoc();
        $sql = (is_array($row) && isset($row['Create View'])) ? $row['Create View'] : '';
        $this->saveToFile("$sql;\n\n");
        $res->close();
    }

    /**
     * @inheritdoc
     */
    protected function escapeName($string)
    {
        return '`' . str_replace('`', '``', $string) . '`';
    }

    /**
     * @inheritdoc
     */
    protected function getFunctions()
    {
        $list = [];
        $res = $this->connection->query("SHOW FUNCTION STATUS WHERE Db = '{$this->dbName}';");
        while ($row = $res->fetch_assoc()) {
            array_push($list, $row['Name']);
        }
        $res->close();
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        return $list;
    }

    /**
     * @inheritdoc
     */
    protected function getIndexes()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function getProcedures()
    {
        $list = [];
        $res = $this->connection->query("SHOW PROCEDURE STATUS WHERE Db = '{$this->dbName}';");
        while ($row = $res->fetch_assoc()) {
            array_push($list, $row['Name']);
        }
        $res->close();
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        return $list;
    }

    /**
     * @inheritdoc
     */
    protected function getTableColumns(&$table, &$numericColumns)
    {
        $fieldnames = [];
        $res = $this->connection->query("SHOW COLUMNS FROM $table");
        while ($row = $res->fetch_assoc()) {
            $col = $row['Field'];
            $fieldnames[] = $this->escapeName($col);
            $numericColumns[$col] = (bool) preg_match('#^[^(]*(BYTE|COUNTER|SERIAL|INT|LONG$|CURRENCY|REAL|MONEY|FLOAT|DOUBLE|DECIMAL|NUMERIC|NUMBER)#i', $row['Type']);
        }

        $res->close();
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
            } elseif (true === $numericColumns[$key]) {
                array_push($values, $value);
            } else {
                $s = "'" . $this->connection->real_escape_string($value) . "'";
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
        $res = $this->connection->query("SHOW FULL TABLES WHERE table_type != 'VIEW'");
        while ($row = $res->fetch_row()) {
            array_push($list, $row[0]);
        }
        $res->close();
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        return $list;
    }

    /**
     * @inheritdoc
     */
    protected function getTriggers()
    {
        $list = [];
        $res = $this->connection->query("SHOW TRIGGERS");
        while ($row = $res->fetch_assoc()) {
            array_push($list, $row['Table']);
        }
        $res->close();
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
        $res = $this->connection->query("SHOW FULL TABLES WHERE table_type = 'VIEW'");
        while ($row = $res->fetch_row()) {
            array_push($list, $row[0]);
        }
        $res->close();
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        return $list;
    }

    /**
     * @inheritdoc
     */
    protected function initDb($dbHandle)
    {
        $dbParams = explode(';', substr(Yii::$app->$dbHandle->dsn, 6));
        $host = '';
        $port = 3306;
        $charset = Yii::$app->$dbHandle->charset;
        $this->dbName = Yii::$app->$dbHandle->createCommand("SELECT DATABASE()")->queryScalar();
        $this->dbVersion = Yii::$app->$dbHandle->serverVersion;

        foreach ($dbParams as $param) {
            list($paramName, $paramvalue) = explode('=', $param);
            if ('host' === $paramName) {
                $host = $paramvalue;
            }
            if ('port' === $paramName) {
                $port = $paramvalue;
            }
        }

        $this->connection = new mysqli($host, Yii::$app->$dbHandle->username, Yii::$app->$dbHandle->password, $this->dbName, $port);

        if ($this->connection->connect_errno > 0) {
            throw new Exception($this->connection->connect_error);
        } elseif (!$this->connection->set_charset($charset)) {
            throw new Exception($this->connection->error);
        }
    }

}
