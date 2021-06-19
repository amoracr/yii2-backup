<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <alonso.mora@gmail.com>
 */

namespace amoracr\backup\archive;

use yii\base\Component;

/**
 * Component for packing and extracting files and directories.
 *
 * @author Alonso Mora <alonso.mora@gmail.com>
 * @since 1.0
 */
abstract class Archive extends Component
{

    /** @var string Path/Alias to folder for backups storing. */
    public $path;

    /**  @var string File name for the backup. */
    public $name;

    /**  @var string Full file path of backup file. */
    public $file;

    /** @var array List of files to ignore in backup. */
    public $skipFiles = [];

    /**  @var string Full path of backup file. */
    protected $backup;

    /**  @var string File extension for the backup. */
    protected $extension;

    /**
     * Stub method for opening the compressed file
     *
     */
    public function open()
    {

    }

    /**
     * Stub method for closing the compressed file
     */
    public function close()
    {

    }

    /**
     * Appends file to backup
     *
     * @param string $name Internal name of file
     * @param string $file Full path of file to append
     */
    abstract public function addFileToBackup($name, $file);

    /**
     * Extracts file from backup
     *
     * @param string $name Internal name of file
     * @param string $file Full path of file to extract to
     */
    abstract public function extractFileFromBackup($name, $file);

    /**
     * Appends directoy to backup
     *
     * @param string $name Internal name of file
     * @param string $folder Full path of directory to append
     */
    abstract public function addFolderToBackup($name, $folder);

    /**
     * Extracts directory from backup
     *
     * @param string $name Internal name of directory
     * @param string $folder Full path of directory to extract to
     */
    abstract public function extractFolderFromBackup($name, $folder);

    /**
     * Gets the backup file name
     *
     * @return string Full path of backup file
     */
    public function getBackupFile()
    {
        return $this->backup;
    }

}
