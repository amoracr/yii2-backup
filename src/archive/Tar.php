<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <adelfunscr@gmail.com>
 */

namespace amoracr\backup\archive;

use amoracr\backup\archive\Archive;
use Yii;
use \BadMethodCallException;
use \Exception;
use \PharData;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \UnexpectedValueException;

/**
 * Component for packing and extracting files and directories using tar packing.
 *
 * @author Alonso Mora <adelfunscr@gmail.com>
 * @since 1.0
 */
class Tar extends Archive
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->extension = '.tar';

        if (!empty($this->file)) {
            $this->backup = $this->file;
        } else {
            $this->backup = $this->path . $this->name . $this->extension;
        }
    }

    /**
     * @inheritdoc
     * @return boolean True if file was appended, false otherwise
     */
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

    /**
     * @inheritdoc
     * @return boolean True if directory was appended, false otherwise
     */
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

    /**
     * @inheritdoc
     * @return boolean True if file was extracted, false otherwise
     */
    public function extractFileFromBackup($name, $file)
    {
        try {
            $archiveFile = new PharData($this->backup);
            $content = $archiveFile[$name]->getContent();
            file_put_contents($file, $content);
        } catch (UnexpectedValueException $ex) {
            Yii::error("Could not open '{$this->backup}'. Details: " . $ex->getMessage());
        } catch (BadMethodCallException $ex) {
            Yii::error("Technically, this should not happen. Details: " . $ex->getMessage());
        } catch (Exception $ex) {
            Yii::error("Unable to use backup file. Details: " . $ex->getMessage());
        }
        return file_exists($file);
    }

    /**
     * @inheritdoc
     * @return boolean True if directory was extracted, false otherwise
     */
    public function extractFolderFromBackup($name, $folder)
    {
        $flag = true;
        try {
            $archiveFile = new PharData($this->backup);
            $archiveFile->extractTo($folder, $name . '/');
        } catch (UnexpectedValueException $ex) {
            Yii::error("Could not open '{$this->backup}'. Details: " . $ex->getMessage());
            $flag = false;
        } catch (BadMethodCallException $ex) {
            Yii::error("Technically, this should not happen. Details: " . $ex->getMessage());
            $flag = false;
        } catch (Exception $ex) {
            Yii::error("Unable to use backup file. Details: " . $ex->getMessage());
            $flag = false;
        }
        return $flag;
    }

}
