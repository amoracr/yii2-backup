<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <adelfunscr@gmail.com>
 */

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
 * Backup component
 *
 * @author Alonso Mora <adelfunscr@gmail.com>
 * @since 1.0
 */
class Backup extends Component
{

    const EXPIRE_TIME_MIN = 86400;
    const EXPIRE_TIME_MAX = 31536000;
    const FILE_NAME_FORMAT = '%sT%s_%s';

    /** @var string Path/Alias to folder for backups storing. */
    public $backupDir = '';

    /**
     * Number of seconds after which the file is considered deprecated and
     * will be deleted during clean up.
     * Default value is 86400 secs (1 day)
     *
     * @var int
     */
    public $expireTime = 86400;

    /**
     * List of files or directories to include in backup.
     * Format: <inner backup filename> => <path/to/dir>
     *
     * @var array
     */
    public $directories = [];

    /** @var array List of files to ignore in backup. */
    public $skipFiles = [];

    /** @var array List of databases connections to backup. */
    public $databases = ['db'];

    /** @var string Suffix for backup file. */
    public $fileName = 'backup';

    /**
     * Compression method to apply to backup file.
     * Available options:
     * 'none' or 'tar' for tar files, backup file is not compressed.
     * 'bzip2' for tar.bz2 files, backup file is compressed with Bzip2 compression.
     * 'gzip' for tar.gz files, backup file is compressed with Gzip compression.
     * 'zip' for zip files, backup file is compressed with Zip compression.
     *
     * @var string
     */
    public $compression = 'none';

    /** @var int Timestamp of the backup. */
    private $backupTime;

    /** @var Archive Instance of archive class to handle the backup file. */
    private $backup;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->backupTime = time();
    }

    /**
     * Creates dump of all directories and all databases and saves result to
     * backup folder with timestamp named file.
     *
     * @return string Full path to created backup file
     * @throws Exception if the configuration is not valid
     */
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

    /**
     * Restores files and databases from backup file.
     *
     * @param string $file Backup file to restore
     * It can be a full path or a file inside the backup folder.
     * @return boolean True if the file exists, false otherwise
     * @throws Exception if the configuration is not valid
     */
    public function restore($file)
    {
        $this->validateSettings();
        $localBackup = Yii::getAlias($this->backupDir) . DIRECTORY_SEPARATOR . $file;
        if (file_exists($file)) {
            $this->backup = $this->getArchive($file);
        } else if (file_exists($localBackup)) {
            $this->backup = $this->getArchive($localBackup);
        } else {
            return false;
        }

        foreach ($this->databases as $database) {
            $this->extractDatabase($database);
        }

        foreach ($this->directories as $name => $value) {
            $folder = Yii::getAlias($value);
            $this->extractFolder($name, $folder);
        }

        return true;
    }

    /**
     * Checks if the component configuration is valid
     *
     * @throws InvalidConfigException
     */
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

    /**
     * Checks if property backupDir is valid
     *
     * @return boolean True if property value is valid
     * @throws InvalidConfigException if the property value is not valid
     */
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

    /**
     * Checks if property expirteTime is valid
     *
     * @return boolean True if property value is valid
     * @throws InvalidConfigException if the property value is not valid
     */
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

    /**
     * Checks if property directories is valid
     *
     * @return boolean True if property value is valid
     * @throws InvalidConfigException if the property value is not valid
     */
    private function validateFiles()
    {
        if (!is_array($this->directories)) {
            throw new InvalidConfigException('"' . get_class($this) . '::directories" should be array, "' . gettype($this->directories) . '" given.');
        }
        return true;
    }

    /**
     * Checks if property skipfiles is valid
     *
     * @return boolean True if property value is valid
     * @throws InvalidConfigException if the property value is not valid
     */
    private function validateSkipFiles()
    {
        if (!is_array($this->skipFiles)) {
            throw new InvalidConfigException('"' . get_class($this) . '::skipFiles" should be array, "' . gettype($this->skipFiles) . '" given.');
        }
        return true;
    }

    /**
     * Checks if property databases is valid
     *
     * @return boolean True if property value is valid
     * @throws InvalidConfigException if the property value is not valid
     */
    private function validateDatabases()
    {
        if (!is_array($this->databases)) {
            throw new InvalidConfigException('"' . get_class($this) . '::databases" should be array, "' . gettype($this->databases) . '" given.');
        } else if (empty($this->databases)) {
            throw new InvalidConfigException('"' . get_class($this) . '::databases" can not be empty"');
        }
        return true;
    }

    /**
     * Checks if property fileName is valid
     *
     * @return boolean True if property value is valid
     * @throws InvalidConfigException if the property value is not valid
     */
    private function validateFileName()
    {
        if (!is_string($this->fileName)) {
            throw new InvalidConfigException('"' . get_class($this) . '::fileName" should be string, "' . gettype($this->fileName) . '" given.');
        } else if (empty($this->fileName)) {
            throw new InvalidConfigException('"' . get_class($this) . '::fileName" can not be empty"');
        }
        return true;
    }

    /**
     * Checks if property compression is valid
     *
     * @return boolean True if property value is valid
     * @throws InvalidConfigException if the property value is not valid
     */
    private function validateCompression()
    {
        if (!is_string($this->compression)) {
            throw new InvalidConfigException('"' . get_class($this) . '::compression" should be string, "' . gettype($this->fileName) . '" given.');
        } else if (empty($this->compression)) {
            throw new InvalidConfigException('"' . get_class($this) . '::compression" can not be empty"');
        } else if (!in_array($this->compression, ['none', 'tar', 'zip', 'gzip', 'bzip2'])) {
            throw new InvalidConfigException('"' . get_class($this) . '::compression" is not a valid option"');
        }
        return true;
    }

    /**
     * Gets a database instance according to used database driver of the connection
     *
     * @param string $db Name of database connection
     * @return Database|null Database instance if driver is supported, null otherwise
     */
    private function getDriver($db)
    {
        $handler = null;
        $driver = \Yii::$app->$db->driverName;
        switch ($driver) {
            case 'mysql':
                $handler = new Mysql([
                    'dumpCommand' => 'mysqldump --add-drop-table --allow-keywords -q -c -u "{username}" -h "{host}" -p\'{password}\' {db} ',
                    'loadCommand' => 'mysql -u "{username}" -h "{host}" -p\'{password}\' {db} ',
                ]);
                break;
            case 'sqlite':
                $handler = new Sqlite();
                break;
            default :
                break;
        }
        return $handler;
    }

    /**
     * Gets an archive instance according to backup file
     *
     * @param string $file Full path to backup file
     * @return Archive Instance to handle the backup file
     */
    private function getArchive($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $config = [
            'file' => $file,
        ];
        $archive = null;
        switch ($extension) {
            case 'bz2':
                $archive = new Bzip2($config);
                break;
            case 'gz':
                $archive = new Gzip($config);
                break;
            case 'zip':
                $archive = new Zip($config);
                break;
            case 'tar':
            default :
                $archive = new Tar($config);
                break;
        }
        return $archive;
    }

    /**
     * Inits the backup instance and creates the backup file.
     * It sets the path, name and list of ignored files of the archive instance.
     */
    private function openArchive()
    {
        $config = [
            'path' => Yii::getAlias($this->backupDir) . DIRECTORY_SEPARATOR,
            'name' => sprintf(self::FILE_NAME_FORMAT, date('Y-m-d', $this->backupTime), date('HisO', $this->backupTime), $this->fileName),
            'skipFiles' => $this->skipFiles,
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

    /**
     * Triggers the close action of the archive instance
     */
    private function closeArchive()
    {
        $this->backup->close();
    }

    /**
     * Creates the database dump file and adds it to backup file
     *
     * @param string $db Name of database connection
     * @return boolean True if dump file was created and added to backup, false otherwise
     */
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

    /**
     * Appends a file to backup file
     *
     * @param string $name File name inside the backup
     * @param string $file Full path of the file to append
     * @return boolean True if file was appended to backup file, false otherwise
     */
    private function addFileToBackup($name, $file)
    {
        return $this->backup->addFileToBackup($name, $file);
    }

    /**
     * Appends a whole directory to backup file
     *
     * @param string $name Directory name inside the backup
     * @param type $folder Full path of the directory to append
     * @return boolean True if directory was appended to backup file, false otherwise
     */
    private function backupFolder($name, $folder)
    {
        return $this->backup->addFolderToBackup($name, $folder);
    }

    /**
     * Extracts database dump file from backup and imports data into the database
     * of the database connection.
     * The dump file must match the database connection name.
     *
     * @param string $db Connection name to use
     * @return boolean True if dump was imported, false otherwise
     */
    private function extractDatabase($db)
    {
        $flag = true;
        $name = 'sql/' . $db . '.sql';
        $file = Yii::getAlias($this->backupDir) . DIRECTORY_SEPARATOR . $db . '.sql';

        if ($this->backup->extractFileFromBackup($name, $file)) {
            $dbDump = $this->getDriver($db);
            $flag = $dbDump->importDatabase($db, $file);
            @unlink($file);
        } else {
            $flag = false;
        }

        return $flag;
    }

    /**
     * Extracts a directory from backup and restores it to a target location
     *
     * @param string $name Directory name to extract
     * @param string $folder Full path of target directory
     */
    private function extractFolder($name, $folder)
    {
        $this->backup->extractFolderFromBackup($name, $folder);
    }

}
