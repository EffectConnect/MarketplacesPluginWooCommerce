<?php

namespace EffectConnect\Marketplaces\Controller;

use EffectConnect\Marketplaces\Helper\SessionMessageHelper;
use EffectConnect\Marketplaces\Helper\TranslationHelper;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BaseController
{
    /**
     * Instantiate Twig
     * @var Environment
     */
    protected $twig;

    /**
     * @var SessionMessageHelper
     */
    protected $messagesContainer;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../view/templates');
        $this->twig = new Environment($loader);
        $this->twig->addFunction(new TwigFunction('__', function (string $text, array $parameters = []) {
            return __(sprintf($text, ...$parameters), TranslationHelper::getTextDomain());
        }));

        $this->messagesContainer = new SessionMessageHelper();
    }

    /**
     * @param string $name
     * @param array $context
     * @return void
     */
    public function render(string $name, array $context = [])
    {
        try {
            $context['errors']  = $this->messagesContainer->getErrors();
            $context['notices'] = $this->messagesContainer->getNotices();
            echo $this->twig->render($name, $context);
        } catch (LoaderError | RuntimeError | SyntaxError $e) {}
    }
}
