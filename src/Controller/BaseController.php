<?php

namespace EffectConnect\Marketplaces\Controller;

use EffectConnect\Marketplaces\Helper\SessionMessageHelper;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BaseController
{
    private const TEMPLATES = [
        'connections/ec_connection.php',
        'connections/ec_connections_listview.php',
        'logs/ec_logs.php',
        'options/ec_settings_list.php',
    ];

    /**
     * @var SessionMessageHelper
     */
    protected $messagesContainer;

    public function __construct()
    {
        $this->messagesContainer = new SessionMessageHelper();
    }

    /**
     * @param string $name
     * @param array $context
     * @return void
     */
    public function render(string $name, array $context = []): void
    {
        if (!in_array($name, self::TEMPLATES, true)) {
            throw new InvalidArgumentException(sprintf('Unknown admin template: %s', $name));
        }

        $context['errors']  = $this->messagesContainer->getErrors();
        $context['notices'] = $this->messagesContainer->getNotices();

        (static function (string $templatePath, array $context): void {
            extract($context, EXTR_SKIP);
            require $templatePath;
        })(__DIR__ . '/../../view/templates/' . $name, $context);
    }
}
