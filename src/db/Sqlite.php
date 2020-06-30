<?php

namespace amoracr\backup\db;

use Yii;
use \SQLite3;
use amoracr\backup\db\DbConnector;

/**
 * Description of Sqlite
 *
 * @author alonso
 */
class Sqlite extends DbConnector
{

    public function init()
    {
        parent::init();
    }

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

    protected function prepareDumpCommand($dbHandle, $templateCommand)
    {

    }

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
        if ($sequences->numColumns() && $sequences->columnType(0) != SQLITE3_NULL) {
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

    private function saveToFile($text, $file)
    {
        $content = $text . "\n";
        file_put_contents($file, $content, FILE_APPEND);
    }

    private function dumpTable(&$db, $table, $file)
    {
        $sql = $db->querySingle("SELECT sql FROM sqlite_master WHERE name = '$table'") . ";";
        $this->saveToFile($sql, $file);
        $statement = "INSERT INTO \"%s\" (%s) VALUES (%s);";

        $rows = $db->query("SELECT * FROM $table");
        if ($rows->numColumns() && $rows->columnType(0) != SQLITE3_NULL) {
            $fields = $this->getTableColumns($db, $table);

            while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
                $values = $this->getRowValues($row);
                $sql = sprintf($statement, $table, $fields, $values);
                $this->saveToFile($sql, $file);
            }
        }
    }

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

    private function getRowValues(&$row)
    {
        foreach ($row as $k => $v) {
            $row[$k] = "'" . SQLite3::escapeString($v) . "'";
        }
        $values = implode(",", $row);
        return $values;
    }

}
