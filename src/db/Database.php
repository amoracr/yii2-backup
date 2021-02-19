<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <adelfunscr@gmail.com>
 */

namespace amoracr\backup\db;

use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Abstract class for managing the database engines.
 *
 * @author Alonso Mora <adelfunscr@gmail.com>
 * @since 1.0
 */
abstract class Database extends Component
{

    /**  @var string Command to use for dumping data */
    public $dumpCommand;

    /**  @var string  Command to use for importing data */
    public $loadCommand;

    /**
     * Dumps database data into a file
     *
     * @param string $dbHandle Database connection name
     * @param string $path Full path of the dump file
     */
    abstract public function dumpDatabase($dbHandle, $path);

    /**
     * Imports data from dump file to database
     *
     * @param string $dbHandle Database connection name
     * @param string $file Full path of the dump file
     */
    abstract public function importDatabase($dbHandle, $file);

    /**
     * Prepares command according to datase connection
     *
     * @param string $dbHandle Database connection name
     * @param string $templateCommand Template command to use
     */
    abstract protected function prepareCommand($dbHandle, $templateCommand);

    /**
     * Checks if property dumpCommand is valid
     *
     * @return boolean True if property value is valid
     * @throws InvalidConfigException if the property value is not valid
     */
    protected function validateDumpCommand()
    {
        if (!is_string($this->dumpCommand)) {
            throw new InvalidConfigException('"' . get_class($this) . '::dumpCommand" should be string, "' . gettype($this->dumpCommand) . '" given.');
        } else if (empty($this->dumpCommand)) {
            throw new InvalidConfigException('"' . get_class($this) . '::dumpCommand" can not be empty"');
        }
        return true;
    }

    /**
     * Checks if property loadCommand is valid
     *
     * @return boolean True if property value is valid
     * @throws InvalidConfigException if the property value is not valid
     */
    protected function validateLoadCommand()
    {
        if (!is_string($this->loadCommand)) {
            throw new InvalidConfigException('"' . get_class($this) . '::loadCommand" should be string, "' . gettype($this->loadCommand) . '" given.');
        } else if (empty($this->loadCommand)) {
            throw new InvalidConfigException('"' . get_class($this) . '::loadCommand" can not be empty"');
        }
        return true;
    }

    /**
     * Replaces parameter names with their respective values
     *
     * @param string $templateCommand Template command to use
     * @param array $params List of parameters and its values
     * @return string Final command to use
     */
    protected function replaceParams($templateCommand, $params)
    {
        $command = $templateCommand;
        foreach ($params as $key => $value) {
            $command = str_replace('{' . $key . '}', $value, $command);
        }
        return $command;
    }

}
