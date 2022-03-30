<?php


namespace EffectConnect\Marketplaces\Logging;


use DateTime;
use DateTimeZone;
use EffectConnect\Marketplaces\Exception\FileCreationFailedException;
use EffectConnect\Marketplaces\Helper\XmlFileHelper;
use EffectConnect\Marketplaces\Constants\FilePathConstants;
use EffectConnect\Marketplaces\Constants\LoggerConstants;
use Exception;
use Laminas\Validator\Date;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerContainer implements FilePathConstants, LoggerConstants
{

    static $_loggers = [];

    /**
     * Adds a logger for a specific process.
     * @param string $process
     * @return mixed
     */
    public static function getLogger(string $process = LoggerConstants::OTHER) {
        if (isset(static::$_loggers[$process])) return static::$_loggers[$process];
        $handler = self::getHandler($process);

        try {
            static::$_loggers[$process] = new Logger(static::LOG_CHANNEL, [
                'handler' => $handler
            ]);
        } catch (Exception $e) {
            static::$_loggers[$process] = new Logger(static::LOG_CHANNEL);
        }

        static::$_loggers[$process]->setTimezone(new DateTimeZone(static::TIME_ZONE));

        return static::$_loggers[$process];
    }

    /**
     * Gets the StreamHandler used by monolog to generate the logfiles.
     * @param $process
     * @return StreamHandler
     */
    private static function getHandler($process): StreamHandler
    {
        try {
            $dateTime = new DateTime('now', (new DateTimeZone(static::TIME_ZONE)));
        } catch (Exception $e) {

        }

        date_default_timezone_set(static::TIME_ZONE);
        try {
            $handler = new StreamHandler(
                XmlFileHelper::guaranteeFileLocation(
                    static::LOG_DIRECTORY,
                    sprintf(
                        static::LOG_FILENAME_FORMAT,
                        $process,
                        (
                        $dateTime
                        )->format(static::DATE_FORMAT)
                    )
                )
            );
        } catch (FileCreationFailedException $e) {

        }
        return $handler;
    }
}