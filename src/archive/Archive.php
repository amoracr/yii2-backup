<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <alonso.mora@gmail.com>
 */

namespace amoracr\backup\archive;

use yii\base\Component;
use \FilesystemIterator;
use \RecursiveCallbackFilterIterator;
use \RecursiveDirectoryIterator;

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
     * @param mixed $folder Full path or configuration of directory to append
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

    /**
     * List all files under path
     *
     * @param string $path Full path to directory to explore
     * @param string $pattern Regular expression for searching files
     * @return \RecursiveIteratorIterator Iterator with found files
     */
    protected function getDirectoryFiles($path, $pattern = null)
    {
        $directory = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $filter = new RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) use ($path, $pattern) {
                if (!$current->isFile()) {
                    return false;
                }

                $fileName = $current->getFilename();
                if (in_array($fileName, $this->skipFiles)) {
                    return false;
                }

                if (null !== $pattern) {
                    $pathName = $current->getPathname();

                    $flagMatch = (preg_match($pattern, $pathName) == 1) ? true : false;
                } else {
                    $flagMatch = true;
                }

                return $flagMatch;
            });
        $iterator = new \RecursiveIteratorIterator($filter);
        return $iterator;
    }

}
