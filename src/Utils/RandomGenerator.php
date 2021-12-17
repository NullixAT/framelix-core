<?php

namespace Framelix\Framelix\Utils;

use function is_int;
use function mb_strlen;
use function random_int;

/**
 * Random generator for passwords, ids, etc...
 */
class RandomGenerator
{
    /**
     * All alphanumeric chars
     */
    public const CHARSET_ALPHANUMERIC = 1;

    /**
     * A set of reduced alphanumeric characters that can easily be distinguished by humans
     * Optimal for OTP tokens or stuff like that
     */
    public const CHARSET_ALPHANUMERIC_READABILITY = 2;

    /**
     * List of charsets
     * @var string[]
     */
    public static $charsets = [
        self::CHARSET_ALPHANUMERIC => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
        self::CHARSET_ALPHANUMERIC_READABILITY => 'ABCDEFHKLMNPQRSTUWXYZ0123456789'
    ];

    /**
     * Get random html id
     * @return string
     */
    public static function getRandomHtmlId(): string
    {
        return "id-" . self::getRandomString(10, 13);
    }

    /**
     * Get random string based in given charset
     * @param int $minLength
     * @param int|null $maxLength
     * @param string|int $charset If int, than it must be a key from $charsets
     * @return string
     */
    public static function getRandomString(
        int $minLength,
        ?int $maxLength = null,
        string|int $charset = self::CHARSET_ALPHANUMERIC
    ): string {
        $charset = is_int($charset) ? self::$charsets[$charset] : $charset;
        $charsetLength = mb_strlen($charset);
        $maxLength = is_int($maxLength) ? $maxLength : $minLength;
        $useLength = self::getRandomInt($minLength, $maxLength);
        $str = "";
        for ($i = 1; $i <= $useLength; $i++) {
            $str .= $charset[self::getRandomInt(0, $charsetLength - 1)];
        }
        return $str;
    }

    /**
     * Get random int
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function getRandomInt(int $min, int $max): int
    {
        return random_int($min, $max);
    }
}