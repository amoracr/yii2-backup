<?php

namespace amoracr\backup\archive;

use Yii;
use \PharData;
use \BadMethodCallException;
use \Exception;
use \UnexpectedValueException;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use amoracr\backup\archive\Archive;

/**
 * Description of Tar
 *
 * @author alonso
 */
class Tar extends Archive
{

    public function init()
    {
        parent::init();
        $this->extension = '.tar';
        $this->backup = Yii::getAlias($this->path) . DIRECTORY_SEPARATOR;
        $this->backup .= $this->name . $this->extension;
    }

    public function addFileToBackup($name, $file)
    {
        try {
            $archiveFile = new PharData($this->backup);
            $relativePath = $name . DIRECTORY_SEPARATOR;
            $relativePath .= pathinfo($file, PATHINFO_BASENAME);
            $archiveFile->addFile($file, $relativePath);
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
        return true;
    }

    public function addFolderToBackup($name, $folder)
    {
        try {
            $archiveFile = new PharData($this->backup);
            $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(Yii::getAlias($folder)), RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                $fileName = $file->getFilename();
                if (!$file->isDir() && !in_array($fileName, $this->skipFiles)) {
                    $filePath = $file->getRealPath();
                    $relativePath = $name . DIRECTORY_SEPARATOR . substr($filePath, strlen(Yii::getAlias($folder)) + 1);
                    $archiveFile->addFile($filePath, $relativePath);
                }
            }
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
        return true;
    }

}
