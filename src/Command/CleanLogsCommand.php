<?php

namespace EffectConnect\Marketplaces\Command;

use DateTime;
use EffectConnect\Marketplaces\Constants\FilePathConstants;
use EffectConnect\Marketplaces\Constants\LoggerConstants;
use EffectConnect\Marketplaces\Logging\LoggerContainer;
use EffectConnect\Marketplaces\Logic\ConfigContainer;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CleanLogsCommand implements LoggerConstants
{
    /**
     * File extensions to clean.
     */
    protected const EXTENSIONS_TO_CLEAN = ['xml', 'log', 'zip'];

    /**
     * Run command.
     *
     * @return void
     */
    public function __construct()
    {
        LoggerContainer::getLogger()->info('Log cleaner started.');

        $logExpirationDays = ConfigContainer::getInstance()->getLogExpirationValueInDays();
        if ($logExpirationDays === 0) {
            LoggerContainer::getLogger()->error('Error when processing log interval time.');
            return;
        }

        LoggerContainer::getLogger()->info('Cleaning logs older than ' . $logExpirationDays . ' day(s).');

        try {
            $this->cleanFiles($logExpirationDays);
        } catch (Exception $e) {
            LoggerContainer::getLogger()->error('Error when cleaning up logs: ' . $e->getMessage());
            return;
        }

        LoggerContainer::getLogger()->info('Log cleaner ended.');
    }

    /**
     * Clean up all files older than x ays with defined extensions within temp folder.
     *
     * @param int $logExpirationDays
     * @return void
     */
    protected function cleanFiles(int $logExpirationDays)
    {
        if (!file_exists(FilePathConstants::TEMP_DIRECTORY) || !is_dir(FilePathConstants::TEMP_DIRECTORY)) {
            LoggerContainer::getLogger()->info('No temp folder found to clean.');
            return;
        }

        $di = new RecursiveDirectoryIterator(FilePathConstants::TEMP_DIRECTORY);
        foreach (new RecursiveIteratorIterator($di) as $filename => $file)
        {
            if (!in_array($file->getExtension(), static::EXTENSIONS_TO_CLEAN)) {
                continue;
            }

            if (!file_exists($filename) || !is_file($filename)) {
                continue;
            }

            try {
                $now  = new DateTime();
                $then = (new DateTime())->setTimestamp($file->getMTime());
                $diff = $then->diff($now);
                $days = intval($diff->format('%r%a'));
            } catch(Exception $e) {
                $days = 0;
            }

            if ($days < $logExpirationDays) {
                continue;
            }

            wp_delete_file($filename);
        }
    }
}