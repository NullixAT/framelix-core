<?php

namespace Framelix\Framelix\Network;

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
        if (!session_id()) {
            session_start();
        }
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Get session value
     * @param string $name
     * @return mixed
     */
    public static function get(string $name): mixed
    {
        if (!session_id()) {
            session_start();
        }
        return $_SESSION[$name] ?? null;
    }

    /**
     * Set session value
     * @param string $name
     * @param mixed $value Null will unset the session key
     */
    public static function set(string $name, mixed $value): void
    {
        if (!session_id()) {
            session_start();
        }
        if ($value === null) {
            unset($_SESSION[$name]);
        } else {
            $_SESSION[$name] = $value;
        }
    }
}