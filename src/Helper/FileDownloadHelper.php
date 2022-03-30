<?php

namespace EffectConnect\Marketplaces\Helper;

use EffectConnect\Marketplaces\Constants\FilePathConstants;
use EffectConnect\Marketplaces\Exception\FileZipCreationFailedException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Class FileDownloadHelper
 * @package EffectConnect\Marketplaces\Helper
 */
class FileDownloadHelper implements FilePathConstants
{
    /**
     * File extensions to zip.
     */
    protected const EXTENSIONS_TO_ZIP = ['xml', 'log'];

    /**
     * @return string
     * @throws FileZipCreationFailedException
     */
    public static function downloadDataFolderZip(): string
    {
        $zipFileName     = sprintf(static::ZIP_FILENAME_FORMAT, time());
        $zipFileLocation = static::TEMP_DIRECTORY . $zipFileName;

        if (!file_exists(static::TEMP_DIRECTORY) || !is_dir(static::TEMP_DIRECTORY)) {
            throw new FileZipCreationFailedException(TranslationHelper::translate('Data directory not found'));
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($zipFileLocation,  ZipArchive::CREATE)) {
            throw new FileZipCreationFailedException(TranslationHelper::translate('Failed to open zip archive'));
        }

        $di = new RecursiveDirectoryIterator(static::TEMP_DIRECTORY);
        foreach (new RecursiveIteratorIterator($di) as $filename => $file)
        {
            if (!in_array($file->getExtension(), self::EXTENSIONS_TO_ZIP)) {
                continue;
            }

            if (!file_exists($filename) || !is_file($filename)) {
                continue;
            }

            // Get real and relative path for current file.
            $filePath     = $file->getRealPath();
            $relativePath = substr($filePath, strlen(realpath(static::TEMP_DIRECTORY)) + 1);

            // Add current file to archive.
            if (false === $zip->addFile($filePath, $relativePath)) {
                throw new FileZipCreationFailedException(TranslationHelper::translate('Failed to add file to zip archive'));
            }
        }

        if (false === $zip->close()) {
            throw new FileZipCreationFailedException(TranslationHelper::translate('Failed to close zip archive'));
        }

        if (!file_exists($zipFileLocation)) {
            throw new FileZipCreationFailedException(TranslationHelper::translate('No log files to download'));
        }

        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=$zipFileName");
        header("Content-Length: " . filesize($zipFileLocation));
        readfile($zipFileLocation);
        exit();
    }
}