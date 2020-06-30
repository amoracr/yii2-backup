<?php

namespace amoracr\backup;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use amoracr\backup\db\Mysql;
use amoracr\backup\db\Sqlite;
use amoracr\backup\archive\Bzip2;
use amoracr\backup\archive\Gzip;
use amoracr\backup\archive\Tar;
use amoracr\backup\archive\Zip;

/**
 * Description of Backup
 *
 * @author alonso
 */
class Backup extends Component
{

    const EXPIRE_TIME_MIN = 86400;
    const EXPIRE_TIME_MAX = 31536000;
    const FILE_NAME_FORMAT = '%sT%s_%s';

    public $backupDir = '';
    public $expireTime = 86400;
    public $directories = [];
    public $skipFiles = [];
    public $databases = ['db'];
    public $fileName = 'backup';
    public $compression = 'none';
    private $backupTime;
    private $backup;

    public function init()
    {
        parent::init();
        $this->backupTime = time();
    }

    public function create()
    {
        $this->validateSettings();
        $this->openArchive();
        foreach ($this->databases as $database) {
            $this->backupDatabase($database);
        }
        foreach ($this->directories as $name => $folder) {
            $this->backupFolder($name, $folder);
        }
        $this->closeArchive();
        return $this->backup->getBackupFile();
    }

    private function validateSettings()
    {
        $this->validateBackupDir();
        $this->validateExpireTime();
        $this->validateFiles();
        $this->validateSkipFiles();
        $this->validateDatabases();
        $this->validateFileName();
        $this->validateCompression();
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
            throw new InvalidConfigException('"' . get_class($this) . '::skipFiles" should be array, "' . gettype($this->skipFiles) . '" given.');
        }
        return true;
    }

    private function validateDatabases()
    {
        if (!is_array($this->databases)) {
            throw new InvalidConfigException('"' . get_class($this) . '::databases" should be array, "' . gettype($this->databases) . '" given.');
        } else if (empty($this->databases)) {
            throw new InvalidConfigException('"' . get_class($this) . '::databases" can not be empty"');
        }
        return true;
    }

    private function validateFileName()
    {
        if (!is_string($this->fileName)) {
            throw new InvalidConfigException('"' . get_class($this) . '::fileName" should be string, "' . gettype($this->fileName) . '" given.');
        } else if (empty($this->fileName)) {
            throw new InvalidConfigException('"' . get_class($this) . '::fileName" can not be empty"');
        }
        return true;
    }

    private function validateCompression()
    {
        if (!is_string($this->compression)) {
            throw new InvalidConfigException('"' . get_class($this) . '::compression" should be string, "' . gettype($this->fileName) . '" given.');
        } else if (empty($this->compression)) {
            throw new InvalidConfigException('"' . get_class($this) . '::compression" can not be empty"');
        } else if (!in_array($this->compression, ['none', 'tar', 'zip', 'gzip','bzip2'])) {
            throw new InvalidConfigException('"' . get_class($this) . '::compression" is not a valid option"');
        }
        return true;
    }

    private function getDriver($db)
    {
        $handler = null;
        $driver = \Yii::$app->$db->driverName;
        switch ($driver) {
            case 'mysql':
                $handler = new Mysql();
                break;
            case 'sqlite':
                $handler = new Sqlite();
                break;
            default :
                break;
        }
        return $handler;
    }

    private function openArchive()
    {
        $path = \Yii::getAlias($this->backupDir) . DIRECTORY_SEPARATOR;
        $name = sprintf(self::FILE_NAME_FORMAT, date('Y-m-d', $this->backupTime), date('HisO', $this->backupTime), $this->fileName);
        $config = [
            'path' => $path,
            'name' => $name,
        ];
        switch ($this->compression) {
            case 'bzip2':
                $this->backup = new Bzip2($config);
                break;
            case 'gzip':
                $this->backup = new Gzip($config);
                break;
            case 'zip':
                $this->backup = new Zip($config);
                break;
            case 'none':
            case 'tar':
            default :
                $this->backup = new Tar($config);
                break;
        }
        $this->backup->open();
    }

    private function closeArchive()
    {
        $this->backup->close();
    }

    private function backupDatabase($db)
    {
        $flag = true;
        $dbDump = $this->getDriver($db);
        $file = $dbDump->dumpDatabase($db, $this->backupDir);
        if ($file !== false) {
            $flag = $this->addFileToBackup('sql', $file);
            if (true === $flag) {
                @unlink($file);
            }
        } else {
            $flag = false;
        }
        return $flag;
    }

    private function addFileToBackup($name, $file)
    {
        return $this->backup->addFileToBackup($name, $file);
    }

    private function backupFolder($name, $folder)
    {
        return $this->backup->addFolderToBackup($name, $folder);
    }

}
