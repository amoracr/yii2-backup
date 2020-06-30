<?php

namespace amoracr\backup\db;

use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Description of DB
 *
 * @author alonso
 */
abstract class Database extends Component
{

    public $dumpCommand;

    abstract public function dumpDatabase($dbHandle, $path);

    abstract protected function prepareDumpCommand($dbHandle, $templateCommand);

    protected function validateDumpCommand()
    {
        if (!is_string($this->dumpCommand)) {
            throw new InvalidConfigException('"' . get_class($this) . '::dumpCommand" should be string, "' . gettype($this->directories) . '" given.');
        } else if (empty($this->dumpCommand)) {
            throw new InvalidConfigException('"' . get_class($this) . '::dumpCommand" can not be empty"');
        }
        return true;
    }

    protected function replaceParams($templateCommand, $params)
    {
        $command = $templateCommand;
        foreach ($params as $key => $value) {
            $command = str_replace('{' . $key . '}', $value, $command);
        }
        return $command;
    }

}
