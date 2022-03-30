<?php


namespace EffectConnect\Marketplaces\Helper;


use EffectConnect\Marketplaces\Exception\FileCreationFailedException;
use EffectConnect\Marketplaces\Constants\FilePathConstants;

class XmlFileHelper implements FilePathConstants
{
    /**
     * Check if the file exists, if not create one.
     *
     * @param string $directory
     * @param string $filename
     * @return string
     * @throws FileCreationFailedException
     */
    public static function guaranteeFileLocation(string $directory, string $filename): string
    {
        $fileLocation = $directory . $filename;

        if (!file_exists($directory)) {
            if (!@mkdir($directory, 0777, true)) {
                $error = error_get_last();
                throw new FileCreationFailedException($directory, $error['message'] ?? '-');
            }
        }

        if (!file_exists($fileLocation)) {
            if (!@touch($fileLocation)) {
                $error = error_get_last();
                throw new FileCreationFailedException($fileLocation, $error['message'] ?? '-');
            }
        }

        if (!is_writable($fileLocation)) {
                throw new FileCreationFailedException($fileLocation, 'File is not writable.');
        }

        return $fileLocation;
    }

    /**
     * Generate a file for a certain type and shop id.
     * @param string $contentType
     * @param $connectionId
     * @return string
     * @throws FileCreationFailedException
     */
    public static function generateFile(string $contentType, $connectionId): string
    {
        $directory = sprintf(static::DIR_NAME, $contentType);
        $filename  = sprintf(static::FILE_NAME, $connectionId, $contentType, time());

        return static::guaranteeFileLocation($directory, $filename);
    }

}