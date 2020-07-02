<?php

namespace amoracr\backup\archive;

use Yii;
use yii\base\InvalidConfigException;
use \Phar;
use \PharData;
use \BadMethodCallException;
use \Exception;
use \UnexpectedValueException;
use amoracr\backup\archive\Tar as TarArchive;

/**
 * Description of Gzip
 *
 * @author alonso
 */
class Gzip extends TarArchive
{

    public function init()
    {
        parent::init();
        $this->extension = '.tar.gz';

        if (!empty($this->file)) {
            $this->backup = $this->file;
        } else {
            $this->backup = $this->path . $this->name . '.tar';
        }

        if (!Phar::canCompress(Phar::GZ)) {
            throw new InvalidConfigException('Extension "zlib" must be enabled.');
        }
    }

    public function close()
    {
        try {
            $archiveFile = new PharData($this->backup);
            $archiveFile->compress(Phar::GZ, $this->extension);
            $oldArchive = $this->backup;
            $this->backup = str_replace('.tar', $this->extension, $oldArchive);
            unset($archiveFile);
            Phar::unlinkArchive($oldArchive);
        } catch (UnexpectedValueException $ex) {
            Yii::error("Could not open '{$this->backup}'. Details: " . $ex->getMessage());
            return false;
        } catch (BadMethodCallException $ex) {
            Yii::error("Technically, this should not happen. Details: " . $ex->getMessage());
            return false;
        } catch (Exception $ex) {
            Yii::error("Unable to use backup file. Details: " . $ex->getMessage());
            return false;
        }
    }

}
