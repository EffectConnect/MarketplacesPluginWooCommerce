<?php

namespace EffectConnect\Marketplaces\Controller;

use EffectConnect\Marketplaces\Constants\ConfigConstants;
use EffectConnect\Marketplaces\DB\ConnectionRepository;
use EffectConnect\Marketplaces\Helper\TranslationHelper;
use EffectConnect\Marketplaces\Helper\WpmlHelper;
use EffectConnect\Marketplaces\Interfaces\ControllerInterface;
use EffectConnect\Marketplaces\Model\ConnectionResource;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ConnectionController extends BaseController implements ControllerInterface, ConfigConstants
{
    /**
     * @var ConnectionRepository
     */
    private $connectionRepo;

    /**
     * @var string
     */
    protected $url;

    public function __construct()
    {
        $this->url            = admin_url('admin.php') . '?page=connectionoptions';
        $this->connectionRepo = ConnectionRepository::getInstance();
        parent::__construct();
    }

    /**
     * Calls a method based on the current url.
     *
     * @return void
     */
    public function init()
    {
        switch (true) {
            // Delete connection
            case isset($_REQUEST['delete']) && isset($_REQUEST['connection_id']):
                $this->connectionRepo->deleteConnection(intval($_GET['connection_id']));
                $this->messagesContainer->addNotice(TranslationHelper::translate('Connection deleted successfully.'));
                wp_redirect($this->url);
                exit();

            // Edit connection
            case isset($_REQUEST['edit']) && isset($_REQUEST['connection_id']):
                $this->showConnectionPage(intval($_REQUEST['connection_id']));
                break;

            // Add connection
            case isset($_REQUEST['add']):
                $this->showConnectionPage();
                break;

            // Other
            default:
                $this->showListViewPage();
        }
    }

    /**
     * Renders a table of added connections.
     *
     * @return void
     */
    public function showListViewPage()
    {
        $connections = $this->connectionRepo->getAllConnections();
        $this->render('connections/ec_connections_listview.html.twig', [
            'connections' => $connections,
            'url'         => $this->url,
        ]);
    }

    /**
     * Renders the connections detail page (edit/add connection).
     *
     * @param int $connectionId
     * @return void
     */
    protected function showConnectionPage(int $connectionId = 0)
    {
        if (isset($_POST['connection_submit'])) {
            // Note that empty/unchecked checkbox values are not included in the POST parameters.
            // So we have to explicitly set missing checkboxes values to empty values.
            $configurationKeys = array_keys((new ConnectionResource())->toArray());
            $configurationValues = array_fill_keys($configurationKeys, '');
            $formData = new ConnectionResource(array_merge($configurationValues, $_POST));
            if ($formData->validate()) {
                $this->connectionRepo->saveConnection($formData);
                $this->messagesContainer->addNotice(TranslationHelper::translate('Connection saved successfully.'));
                wp_redirect($this->url);
                exit();
            } else {
                foreach ($formData->getErrors() as $error) {
                    $this->messagesContainer->addError($error);
                }
            }
        } else {
            $formData = $this->connectionRepo->get($connectionId);
        }

        $this->render('connections/ec_connection.html.twig', [
            'activeWpmlLanguages' => WpmlHelper::getActiveLanguageCodes(),
            'defaultWpmlLanguage' => WpmlHelper::getDefaultLanguage(),
            'formData'            => $formData,
            'button'              => get_submit_button(TranslationHelper::translate('Save connection'), 'primary', 'connection_submit'),
        ]);
    }
}
