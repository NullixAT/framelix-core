<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Network\Session;

/**
 * Toast messages to display in the layout
 */
class Toast
{
    /**
     * Messages
     * @var array
     */
    private static array $messages = [];

    /**
     * Add an info message
     * @param string $message
     * @param float|string $delaySeconds
     */
    public static function info(string $message, float|string $delaySeconds = 'auto'): void
    {
        self::addMessage($message, $delaySeconds, 'info');
    }

    /**
     * Check if an info message has been added
     * @return bool
     */
    public static function hasInfo(): bool
    {
        return self::hasMessageType('info');
    }

    /**
     * Add an success message
     * @param string $message
     * @param float|string $delaySeconds
     */
    public static function success(string $message, float|string $delaySeconds = 'auto'): void
    {
        self::addMessage($message, $delaySeconds, 'success');
    }

    /**
     * Check if a success message has been added
     * @return bool
     */
    public static function hasSuccess(): bool
    {
        return self::hasMessageType('success');
    }

    /**
     * Add an warning message
     * @param string $message
     * @param float|string $delaySeconds
     */
    public static function warning(string $message, float|string $delaySeconds = 'auto'): void
    {
        self::addMessage($message, $delaySeconds, 'warning');
    }

    /**
     * Check if a warning message has been added
     * @return bool
     */
    public static function hasWarning(): bool
    {
        return self::hasMessageType('warning');
    }

    /**
     * Add an error message
     * @param string $message
     * @param float|string $delaySeconds
     */
    public static function error(string $message, float|string $delaySeconds = 'auto'): void
    {
        self::addMessage($message, $delaySeconds, 'error');
    }

    /**
     * Check if a error message has been added
     * @return bool
     */
    public static function hasError(): bool
    {
        return self::hasMessageType('error');
    }

    /**
     * Get all messages that are cached in the user session
     * @param bool $flushQueue If true, than delete the cached messages from the session after this call
     * @return array
     */
    public static function getQueueMessages(bool $flushQueue): array
    {
        $messages = Session::get('framelix-toast-messages');
        if ($flushQueue) {
            Session::set('framelix-toast-messages', null);
        }
        return $messages ?: [];
    }

    /**
     * Check if a specific message type has been added
     * @param string $messageType
     * @return bool
     */
    public static function hasMessageType(string $messageType): bool
    {
        foreach (self::$messages as $row) {
            if ($row['type'] === $messageType) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add a message
     * @param string $message
     * @param float|string $delaySeconds
     * @param string $type
     */
    private static function addMessage(string $message, float|string $delaySeconds, string $type): void
    {
        $messages = Session::get('framelix-toast-messages');
        if (!$messages) {
            $messages = [];
        }
        $row = ['message' => $message, 'type' => $type, 'delay' => $delaySeconds];
        $messages[] = $row;
        self::$messages[] = $row;
        Session::set('framelix-toast-messages', $messages);
    }
}
