<?php

namespace amoracr\backup;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use \ZipArchive;

/**
 * Description of Backup
 *
 * @author alonso
 */
class Backup extends Component
{

    const EXPIRE_TIME_MIN = 86400;
    const EXPIRE_TIME_MAX = 2592000;
    const FILE_NAME_FORMAT = '%sT%s_%s.zip';

    public $backupDir = '';
    public $expireTime = 2592000;
    public $directories = [];
    public $skipFiles = [];
    public $databases = ['db'];
    public $fileName = 'backup';
    public $backupTime;
    private $backup;

    public function init()
    {
        parent::init();
        $this->backupTime = time();
    }

    public function create()
    {
        $this->validateSettings();
        $this->backup = \Yii::getAlias($this->backupDir) . DIRECTORY_SEPARATOR;
        $this->backup .= sprintf(self::FILE_NAME_FORMAT, date('Y-m-d', $this->backupTime), date('HisO', $this->backupTime), $this->fileName);
        foreach ($this->databases as $database) {
            $this->backupDatabase($database);
        }
        foreach ($this->directories as $name => $folder) {
            $this->backupFolder($name, $folder);
        }
        return $this->backup;
    }

    private function validateSettings()
    {
        $this->validateBackupDir();
        $this->validateExpireTime();
        $this->validateFiles();
        $this->validateSkipFiles();
        $this->validateDatabases();
        $this->validateFileName();
    }

    private function validateBackupDir()
    {
        $backupDir = Yii::getAlias($this->backupDir);
        if (empty($this->backupDir)) {
            throw new InvalidConfigException('"' . get_class($this) . '::backupDir" can not be empty"');
        }
        if (!file_exists($backupDir)) {
            throw new InvalidConfigException('"' . $this->backupDir . '" does not exists"');
        }
        if (!is_writable($backupDir)) {
            throw new InvalidConfigException('"' . $this->backupDir . '" is not writeable');
        }
        return true;
    }

    private function validateExpireTime()
    {
        if (!is_int($this->expireTime)) {
            throw new InvalidConfigException('"' . get_class($this) . '::expireTime" should be integer, "' . gettype($this->expireTime) . '" given.');
        } else if (self::EXPIRE_TIME_MIN > $this->expireTime) {
            throw new InvalidConfigException('"' . get_class($this) . '::expireTime" should be at least ' . self::EXPIRE_TIME_MIN . ' seconds');
        } else if (self::EXPIRE_TIME_MAX < $this->expireTime) {
            throw new InvalidConfigException('"' . get_class($this) . '::expireTime" should be at most ' . self::EXPIRE_TIME_MAX . ' seconds');
        }
        return true;
    }

    private function validateFiles()
    {
        if (!is_array($this->directories)) {
            throw new InvalidConfigException('"' . get_class($this) . '::directories" should be array, "' . gettype($this->directories) . '" given.');
        }
        return true;
    }

    private function validateSkipFiles()
    {
        if (!is_array($this->skipFiles)) {
            throw new InvalidConfigException('"' . get_class($this) . '::skipFiles" should be array, "' . gettype($this->directories) . '" given.');
        }
        return true;
    }

    private function validateDatabases()
    {
        if (!is_array($this->databases)) {
            throw new InvalidConfigException('"' . get_class($this) . '::databases" should be array, "' . gettype($this->directories) . '" given.');
        } else if (empty($this->databases)) {
            throw new InvalidConfigException('"' . get_class($this) . '::databases" can not be empty"');
        }
        return true;
    }

    private function validateFileName()
    {
        if (!is_string($this->fileName)) {
            throw new InvalidConfigException('"' . get_class($this) . '::fileName" should be string, "' . gettype($this->directories) . '" given.');
        } else if (empty($this->fileName)) {
            throw new InvalidConfigException('"' . get_class($this) . '::fileName" can not be empty"');
        }
        return true;
    }

    private function backupDatabase($dbHandle)
    {
        $flag = true;
        return $flag;
    }

    private function backupFolder($name, $folder)
    {
        $zipFile = new ZipArchive();
        $zipFile->open($this->backup, ZipArchive::CREATE);
        $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(Yii::getAlias($folder)), \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            if (!$file->isDir() && !in_array($fileName, $this->skipFiles)) {
                $filePath = $file->getRealPath();
                $relativePath = $name . DIRECTORY_SEPARATOR . substr($filePath, strlen(Yii::getAlias($folder)) + 1);
                $zipFile->addFile($filePath, $relativePath);
            }
        }
        return $zipFile->close();
    }

}
