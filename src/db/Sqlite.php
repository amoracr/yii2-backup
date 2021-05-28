<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <adelfunscr@gmail.com>
 */

namespace amoracr\backup\db;

use amoracr\backup\db\Database;
use Yii;
use \PDO;
use \SQLite3;

/**
 * Component for dumping and restoring database data for SQLite databases
 *
 * @author Alonso Mora <adelfunscr@gmail.com>
 * @since 1.0
 */
class Sqlite extends Database
{

    /**
     * @inheritdoc
     */
    public function dumpDatabase($dbHandle, $path)
    {
        $dsn = str_replace('sqlite:', '', Yii::$app->$dbHandle->dsn);
        $database = Yii::getAlias($dsn);
        $file = Yii::getAlias($path) . DIRECTORY_SEPARATOR . $dbHandle . '.sql';
        $this->dump($database, $file);

        if (!file_exists($file)) {
            return false;
        }

        return $file;
    }

    /**
     * @inheritdoc
     */
    public function importDatabase($dbHandle, $file)
    {
        $dsn = str_replace('sqlite:', '', Yii::$app->$dbHandle->dsn);
        $database = Yii::getAlias($dsn);
        $dsn = 'sqlite:' . $database;
        $username = Yii::$app->$dbHandle->username;
        $password = Yii::$app->$dbHandle->password;
        $db = new PDO($dsn, $username, $password);

        $fp = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $query = '';
        foreach ($fp as $line) {
            if ($line !== '' && strpos($line, '--') === false) {
                $query .= $line;
                if (substr($query, -1) === ';') {
                    $qr = $db->exec($query);
                    $query = '';
                }
            }
        }
        return true;
    }

    /**
     * @inheritdoc
     * Does nothing for this driver
     */
    protected function prepareCommand($dbHandle, $templateCommand)
    {

    }

    /**
     * Creates a dump file for an SQLite3 database
     *
     * @param string $dbFile Full path of the database file
     * @param string $file Full path of the dump file
     */
    private function dump($dbFile, $file)
    {
        $db = new SQLite3($dbFile, SQLITE3_OPEN_READONLY);
        $db->busyTimeout(5000);

        $this->saveToFile("-- SQLite3 dump", $file);
        $this->saveToFile("PRAGMA foreign_keys=OFF;", $file);
        $this->saveToFile("BEGIN TRANSACTION;", $file);

        $tables = $db->query("SELECT name FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%';");
        while ($table = $tables->fetchArray(SQLITE3_NUM)) {
            $this->dumpTable($db, $table[0], $file);
        }

        $sequences = $db->query("SELECT * FROM sqlite_sequence");
        if ($sequences->numColumns() > 0 && $sequences->columnType(0) != SQLITE3_NULL) {
            $this->saveToFile("DELETE FROM sqlite_sequence;", $file);
            $statement = "INSERT INTO \"%s\" VALUES (%s);";
            while ($row = $sequences->fetchArray(SQLITE3_ASSOC)) {
                $values = $this->getRowValues($row);
                $sql = sprintf($statement, 'sqlite_sequence', $values);
                $this->saveToFile($sql, $file);
            }
        }

        $this->saveToFile("COMMIT;", $file);
    }

    /**
     * Adds content to dump file
     *
     * @param string $text Content to save
     * @param string $file Full path of dump file
     */
    private function saveToFile($text, $file)
    {
        $content = $text . "\n";
        file_put_contents($file, $content, FILE_APPEND);
    }

    /**
     * Dumps data of a database table in a file
     *
     * @param SQLite3 $db Database reference
     * @param string $table Name of table
     * @param string $file Full path of dump file
     */
    private function dumpTable(&$db, $table, $file)
    {
        $this->saveToFile("DROP TABLE IF EXISTS `$table`;", $file);
        $sql = $db->querySingle("SELECT sql FROM sqlite_master WHERE name = '$table'") . ";";
        $this->saveToFile($sql, $file);
        $statement = "INSERT INTO `%s` (%s) VALUES (%s);";

        $rows = $db->query("SELECT * FROM $table");
        if ($rows->numColumns() > 0 && $rows->columnType(0) != SQLITE3_NULL) {
            $fields = $this->getTableColumns($db, $table);

            while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
                $values = $this->getRowValues($row);
                $sql = sprintf($statement, $table, $fields, $values);
                $this->saveToFile($sql, $file);
            }
        }
    }

    /**
     * Gets the column names of a database table
     *
     * @param SQLite3 $db Database reference
     * @param string $table Name of table
     * @return string Column names separated by a comma
     */
    private function getTableColumns(&$db, $table)
    {
        $fieldnames = [];
        $columns = $db->query("PRAGMA table_info($table)");
        while ($column = $columns->fetchArray(SQLITE3_ASSOC)) {
            array_push($fieldnames, $column["name"]);
        }

        $fields = implode(",", $fieldnames);
        return $fields;
    }

    /**
     * Gets the column values of a row
     *
     * @param array $row Result set of a query
     * @return string values separated by a comma
     */
    private function getRowValues(&$row)
    {
        foreach ($row as $k => $v) {
            $row[$k] = "'" . SQLite3::escapeString($v) . "'";
        }
        $values = implode(",", $row);
        return $values;
    }

}
