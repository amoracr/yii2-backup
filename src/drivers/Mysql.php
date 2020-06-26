<?php

namespace amoracr\backup\drivers;

use amoracr\backup\drivers\DbConnector;

/**
 * Description of Mysql
 *
 * @author alonso
 */
class Mysql extends DbConnector
{

    public function init()
    {
        parent::init();
        if (is_null($this->dumpCommand) || empty($this->dumpCommand)) {
            $this->dumpCommand = 'mysqldump --add-drop-table --allow-keywords -q -c -u "{username}" -h "{host}" -p\'{password}\' {db} ';
        }
    }

    public function dumpDatabase($db, $path)
    {
        $this->validateDumpCommand();
        $dumpCommand = $this->prepareDbCommand($db, $this->dumpCommand);
        $file = \Yii::getAlias($path) . DIRECTORY_SEPARATOR . $db . '.sql';
        $command = sprintf("%s > %s  2> /dev/null", $dumpCommand, $file);
        system($command);

        if (!file_exists($file)) {
            return false;
        }

        return $file;
    }

    private function prepareDbCommand($dbHandle, $templateCommand)
    {
        $command = $templateCommand;
        $database = \Yii::$app->$dbHandle->createCommand("SELECT DATABASE()")->queryScalar();
        $params = [
            'username' => \Yii::$app->$dbHandle->username,
            'host' => 'localhost',
            'password' => \Yii::$app->$dbHandle->password,
            'db' => $database,
        ];

        if ((string) $params['password'] === '') {
            $command = str_replace('-p\'{password}\'', '', $command);
            unset($params['password']);
        }

        foreach ($params as $k => $v) {
            $command = str_replace('{' . $k . '}', $v, $command);
        }
        return $command;
    }

}
