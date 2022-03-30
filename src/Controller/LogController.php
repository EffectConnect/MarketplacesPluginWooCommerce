<?php

namespace EffectConnect\Marketplaces\Controller;

use EffectConnect\Marketplaces\Constants\FilePathConstants;
use EffectConnect\Marketplaces\Exception\FileZipCreationFailedException;
use EffectConnect\Marketplaces\Helper\FileDownloadHelper;
use EffectConnect\Marketplaces\Helper\TranslationHelper;
use EffectConnect\Marketplaces\Interfaces\ControllerInterface;
use EffectConnect\Marketplaces\Logic\ConfigContainer;

class LogController extends BaseController implements ControllerInterface
{
    /**
     * LogController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        if (isset($_REQUEST['download_log_files'])) {
            try {
                $this->downloadLogs();
            } catch (FileZipCreationFailedException $e) {
                $this->messagesContainer->addError($e->getMessage());
            }
        }
    }

    /**
     * @return void
     */
    public function init()
    {
        $this->render('logs/ec_logs.html.twig', [
            'dataFolder'        => realpath(FilePathConstants::TEMP_DIRECTORY),
            'logExpirationDays' => ConfigContainer::getInstance()->getLogExpirationValueInDays(),
            'downloadButton'    => get_submit_button(TranslationHelper::translate('Download log files'), 'primary', 'download_log_files'),
        ]);
    }

    /**
     * @return void
     * @throws FileZipCreationFailedException
     */
    protected function downloadLogs()
    {
        FileDownloadHelper::downloadDataFolderZip();
    }
}