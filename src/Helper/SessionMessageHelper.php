<?php

namespace EffectConnect\Marketplaces\Helper;

class SessionMessageHelper
{
    const SESSION_ERROR_MESSAGES_KEY = 'ec_errors';
    const SESSION_NOTICE_MESSAGES_KEY = 'ec_notices';

    public function __construct()
    {
        if (!session_id()) {
            session_start();
        }
    }

    /**
     * @param string $error
     * @return void
     */
    public function addError(string $error)
    {
        $this->addMessage($error, self::SESSION_ERROR_MESSAGES_KEY);
    }

    /**
     * @param string $notice
     * @return void
     */
    public function addNotice(string $notice)
    {
        $this->addMessage($notice, self::SESSION_NOTICE_MESSAGES_KEY);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->getMessages(self::SESSION_ERROR_MESSAGES_KEY);
    }

    /**
     * @return array
     */
    public function getNotices(): array
    {
        return $this->getMessages(self::SESSION_NOTICE_MESSAGES_KEY);
    }

    /**
     * @param string $message
     * @param string $key
     * @return void
     */
    protected function addMessage(string $message, string $key)
    {
        if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) {
            $_SESSION[$key][] = $message;
        } else {
            $_SESSION[$key] = [$message];
        }
    }

    /**
     * @param string $key
     * @return array
     */
    protected function getMessages(string $key): array
    {
        if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) {
            $messages = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $messages;
        }
        return [];
    }
}