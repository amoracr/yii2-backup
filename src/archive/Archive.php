<?php

namespace amoracr\backup\archive;

use yii\base\Component;

/**
 * Description of Archive
 *
 * @author alonso
 */
abstract class Archive extends Component
{

    public $path;
    public $name;
    public $file;
    public $skipFiles = [];
    protected $backup;
    protected $extension;

    public function open()
    {

    }

    public function close()
    {

    }

    abstract public function addFileToBackup($name, $file);

    abstract public function extractFileFromBackup($name, $file);

    abstract public function addFolderToBackup($name, $folder);

    public function getBackupFile()
    {
        return $this->backup;
    }

}
