<?php

namespace EffectConnect\Marketplaces\Api;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use EffectConnect\Marketplaces\Constants\LoggerConstants;
use EffectConnect\Marketplaces\Exception\ApiCallFailedException;
use EffectConnect\Marketplaces\Exception\InitSdkException;
use EffectConnect\Marketplaces\Exception\SdkCoreNotInitializedException;
use EffectConnect\Marketplaces\Logging\LoggerContainer;
use EffectConnect\Marketplaces\Model\ConnectionResource;
use EffectConnect\PHPSdk\Core\Helper\Keychain;
use EffectConnect\PHPSdk\Core;
use EffectConnect\PHPSdk\ApiCall;
use EffectConnect\PHPSdk\Core\Exception\InvalidKeyException;
use EffectConnect\PHPSdk\Core\Interfaces\ResponseContainerInterface;
use EffectConnect\PHPSdk\Core\Model\Response\Response;
use Exception;

/**
 * This class will be used to make api calls to the EffectConnect api.
 */
abstract class ApiCallHandler
{
    /**
     * @var Core
     */
    protected $core;

    /**
     * @var int
     */
    protected $timeOut = 300;

    /**
     * @param ConnectionResource $connection
     * @return Core
     * @throws InitSdkException
     */
    protected function getCoreForConnection(ConnectionResource $connection): Core
    {
        $keychain = new Keychain();

        try {
            $keychain
                ->setPublicKey($connection->getPublicKey())
                ->setSecretKey($connection->getPrivateKey());
        } catch (InvalidKeyException $e) {
            LoggerContainer::getLogger(LoggerConstants::OTHER)->error('Invalid public or secret key detected', [
                'process' => LoggerConstants::OTHER,
                'message' => $e->getMessage(),
            ]);
            throw new InitSdkException($e->getMessage());
        }

        try {
            $this->core = new Core($keychain);
        } catch (Exception $e) {
            LoggerContainer::getLogger(LoggerConstants::OTHER)->error('Communication with EffectConnect failed because of an exception: ', [
                'process' => LoggerConstants::OTHER,
                'message' => $e->getMessage(),
            ]);
            throw new InitSdkException($e->getMessage());
        }

        return $this->core;
    }

    /**
     * @return Core
     * @throws SdkCoreNotInitializedException
     */
    public function getSdkCore(): Core
    {
        if (!($this->core instanceof Core)) {
            throw new SdkCoreNotInitializedException();
        }
        return $this->core;
    }

    /**
     * @param ApiCall $apiCall
     * @param int $timeOut
     * @return ResponseContainerInterface
     * @throws ApiCallFailedException
     */
    protected function callAndResolveResponse(ApiCall $apiCall, int $timeOut = 0): ResponseContainerInterface
    {
        if ($timeOut > 0) {
            $this->timeOut = $timeOut;
        }

        $apiCall->setTimeout($this->timeOut)->call();
        if (!$apiCall->isSuccess())
        {
            $errorMessageString = '[' . implode('] [', $apiCall->getErrors()) . ']';
            throw new ApiCallFailedException($errorMessageString);
        }

        $response   = $apiCall->getResponseContainer();
        $result     = $response->getResponse()->getResult();

        // Check if response is successful
        if ($result == Response::STATUS_FAILURE)
        {
            $errorMessages = [];
            foreach ($response->getErrorContainer()->getErrorMessages() as $errorMessage)
            {
                $errorMessages[] = vsprintf('%s. Code: %s. Message: %s', [
                    $errorMessage->getSeverity(),
                    $errorMessage->getCode(),
                    $errorMessage->getMessage()
                ]);
            }
            $errorMessageString = '[' . implode('] [', $errorMessages) . ']';
            throw new ApiCallFailedException($errorMessageString);
        }

        return $response->getResponse()->getResponseContainer();
    }

    /**
     * @param string $loggerType
     * @param ConnectionResource $connection
     * @return void
     */
    protected function startLogging(string $loggerType, ConnectionResource $connection)
    {
        LoggerContainer::getLogger($loggerType)->info('Process started.', [
            'process' => $loggerType,
            'connection_id' => $connection->getConnectionId(),
            'connection_public_key' => $connection->getPublicKey(),
        ]);
    }

    /**
     * @param string $loggerType
     * @param ConnectionResource $connection
     * @return void
     */
    protected function stopLogging(string $loggerType, ConnectionResource $connection)
    {
        LoggerContainer::getLogger($loggerType)->info('Process ended.', [
            'process' => $loggerType,
            'connection_id' => $connection->getConnectionId(),
        ]);
    }
}
