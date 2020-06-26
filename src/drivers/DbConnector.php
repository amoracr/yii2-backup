<?php

namespace amoracr\backup\drivers;

use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Description of DB
 *
 * @author alonso
 */
abstract class DbConnector extends Component
{

    public $dumpCommand;

    abstract public function dumpDatabase($db, $path);

    protected function validateDumpCommand()
    {
        if (!is_string($this->dumpCommand)) {
            throw new InvalidConfigException('"' . get_class($this) . '::dumpCommand" should be string, "' . gettype($this->directories) . '" given.');
        } else if (empty($this->dumpCommand)) {
            throw new InvalidConfigException('"' . get_class($this) . '::dumpCommand" can not be empty"');
        }
        return true;
    }

}
