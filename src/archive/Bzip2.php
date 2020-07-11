<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <adelfunscr@gmail.com>
 */

namespace amoracr\backup\archive;

use amoracr\backup\archive\Tar as TarArchive;
use Yii;
use yii\base\InvalidConfigException;
use \BadMethodCallException;
use \Exception;
use \Phar;
use \PharData;
use \UnexpectedValueException;

/**
 * Component for packing and extracting files and directories using Bzip2 compression.
 *
 * @author Alonso Mora <adelfunscr@gmail.com>
 * @since 1.0
 */
class Bzip2 extends TarArchive
{

    /**
     * @inheritdoc
     * @throws InvalidConfigException if extension "bzip2"  is not enabled
     */
    public function init()
    {
        parent::init();
        $this->extension = '.tar.bz2';

        if (!empty($this->file)) {
            $this->backup = $this->file;
        } else {
            $this->backup = $this->path . $this->name . '.tar';
        }

        if (!Phar::canCompress(Phar::BZ2)) {
            throw new InvalidConfigException('Extension "bzip2" must be enabled.');
        }
    }

    /**
     * Closes backup file and tries to compress it
     *
     * @return boolean True if backup file was closed and compressed, false otherwise
     */
    public function close()
    {
        try {
            $archiveFile = new PharData($this->backup);
            $archiveFile->compress(Phar::BZ2, $this->extension);
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
