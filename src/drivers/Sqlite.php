<?php

namespace amoracr\backup\drivers;

use Yii;
use amoracr\backup\drivers\DbConnector;

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
        if (is_null($this->dumpCommand) || empty($this->dumpCommand)) {
            $this->dumpCommand = 'sqlite3 {db} ".dump" ';
        }
    }

    public function dumpDatabase($db, $path)
    {
        $this->validateDumpCommand();
        $file = Yii::getAlias($path) . DIRECTORY_SEPARATOR . $db . '.sql';
        $dumpCommand = $this->prepareDumpCommand($db, $this->dumpCommand);
        $command = sprintf("%s > %s  2> /dev/null", $dumpCommand, $file);
        system($command);

        if (!file_exists($file)) {
            return false;
        }

        return $file;
    }

    protected function prepareDumpCommand($dbHandle, $templateCommand)
    {

        $dsn = str_replace('sqlite:', '', Yii::$app->$dbHandle->dsn);
        $database = Yii::getAlias($dsn);
        $params = [
            'db' => $database,
        ];

        return $this->replaceParams($templateCommand, $params);
    }

}
