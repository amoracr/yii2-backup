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
use yii\base\InvalidConfigException;

/**
 * Component for dumping and restoring database data for MySql databases
 *
 * @author Alonso Mora <alonso.mora@gmail.com>
 * @since 1.0
 */
class Mysql extends Database
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty($this->dumpCommand)) {
            $this->dumpCommand = 'mysqldump --add-drop-table --add-drop-trigger --allow-keywords --quick --complete-insert --add-locks --routines --triggers -u "{username}" -h "{host}" -p\'{password}\' {db} ';
        }
        if (empty($this->loadCommand)) {
            $this->loadCommand = 'mysql -u "{username}" -h "{host}" -p\'{password}\' {db} ';
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException if configuration is not valid
     */
    public function dumpDatabase($dbHandle, $path)
    {
        $this->validateDumpCommand();
        $dumpCommand = $this->prepareCommand($dbHandle, $this->dumpCommand);
        $file = Yii::getAlias($path) . DIRECTORY_SEPARATOR . $dbHandle . '.sql';
        $command = sprintf("%s > %s", $dumpCommand, $file);
        system($command);

        if (!file_exists($file)) {
            return false;
        }

        return $file;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException if configuration is not valid
     */
    public function importDatabase($dbHandle, $file)
    {
        $this->validateLoadCommand();
        $importCommand = $this->prepareCommand($dbHandle, $this->loadCommand);
        $command = sprintf("%s < %s", $importCommand, $file);
        system($command);

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function prepareCommand($dbHandle, $templateCommand)
    {
        $command = $templateCommand;
        $database = Yii::$app->$dbHandle->createCommand("SELECT DATABASE()")->queryScalar();
        $host = explode("=", explode(';', Yii::$app->getDb()->dsn)[0])[1];
        $params = [
            'username' => Yii::$app->$dbHandle->username,
            'host' => $host,
            'password' => Yii::$app->$dbHandle->password,
            'db' => $database,
        ];

        if ((string) $params['password'] === '') {
            $command = str_replace('-p\'{password}\'', '', $command);
            unset($params['password']);
        }

        return $this->replaceParams($command, $params);
    }

}
