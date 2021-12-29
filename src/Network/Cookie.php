<?php

namespace Framelix\Framelix\Network;

use Framelix\Framelix\Config;
use Framelix\Framelix\Utils\CryptoUtils;

use function setcookie;
use function time;

/**
 * Cookie utilities for frequent tasks
 */
class Cookie
{
    /**
     * Get cookie value
     * @param string $name Cookie key name
     * @param bool $isSigned This cookie value need a valid signature that has been added by self::set() with $sign=true
     * @return string|null
     */
    public static function get(string $name, bool $isSigned = true): ?string
    {
        $value = $_COOKIE[$name] ?? null;
        if ($value !== null && $isSigned) {
            $hash = self::get($name . "__s", false);
            // check for salt separetely, as cyptoutils will throw error when no salt isset
            if (!Config::get("salts[general]") || $hash !== CryptoUtils::hash($name . $value)) {
                return null;
            }
        }
        return $value;
    }

    /**
     * Set cookie value
     * @param string $name Cookie key name
     * @param string|null $value Null will unset the cookie key
     * @param bool $sign This set also signed hash cookie value
     *  This make sure that a cookie value can't be faked by just changing it's value in the request
     *  The self::get() than check the given signature and return null if the signature hash does not match
     * @param int|null $lifetime Lifetime in seconds from now + given seconds, null then lifetime is a browser session
     */
    public static function set(string $name, ?string $value, bool $sign = true, ?int $lifetime = null): void
    {
        if ($value === null) {
            $value = "";
            $cookieLifetime = 1;
            unset($_COOKIE[$name]);
        } else {
            $cookieLifetime = $lifetime !== null ? time() + $lifetime : 0;
            $_COOKIE[$name] = $value;
        }
        // @codeCoverageIgnoreStart
        if (!Request::isCli()) {
            setcookie($name, (string)$value, [
                'expires' => $cookieLifetime,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true
            ]);
        }
        // @codeCoverageIgnoreEnd
        if ($sign && $value !== null) {
            self::set($name . "__s", CryptoUtils::hash($name . $value), false, $lifetime);
        }
    }
}