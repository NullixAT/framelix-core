<?php

namespace Framelix\Framelix\Network;

use Framelix\Framelix\Framelix;

use function session_destroy;

/**
 * Session utilities for frequent tasks
 */
class Session
{
    /**
     * Destroy the session
     */
    public static function destroy(): void
    {
        // @codeCoverageIgnoreStart
        if (!Framelix::isCli()) {
            if (!session_id()) {
                session_start();
            }
            session_destroy();
        }
        // @codeCoverageIgnoreEnd
        $_SESSION = [];
    }

    /**
     * Get session value
     * @param string $name
     * @return mixed
     */
    public static function get(string $name): mixed
    {
        // @codeCoverageIgnoreStart
        if (!Framelix::isCli()) {
            if (!session_id()) {
                session_start();
            }
        }
        // @codeCoverageIgnoreEnd
        return $_SESSION[$name] ?? null;
    }

    /**
     * Set session value
     * @param string $name
     * @param mixed $value Null will unset the session key
     */
    public static function set(string $name, mixed $value): void
    {
        // @codeCoverageIgnoreStart
        if (!Framelix::isCli()) {
            if (!session_id()) {
                session_start();
            }
        }
        // @codeCoverageIgnoreEnd
        if ($value === null) {
            unset($_SESSION[$name]);
        } else {
            $_SESSION[$name] = $value;
        }
    }
}