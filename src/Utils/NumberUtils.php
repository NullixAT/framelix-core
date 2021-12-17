<?php

namespace Framelix\Framelix\Utils;

use function is_float;
use function is_int;
use function is_string;
use function number_format;
use function preg_replace;
use function round;
use function str_replace;

/**
 * Number utilities for frequent tasks
 */
class NumberUtils
{

    /**
     * Clamp value between min and max
     * @param float|int $value
     * @param float|int|null $min
     * @param float|int|null $max
     * @return float|int
     */
    public static function clamp(float|int $value, float|int|null $min, float|int|null $max): float|int
    {
        if ($min !== null && $value < $min) {
            $value = is_float($value) ? (float)$min : (int)$min;
        }
        if ($max !== null && $value > $max) {
            $value = is_float($value) ? (float)$max : (int)$max;
        }
        return $value;
    }

    /**
     * Convert any value to float
     * @param mixed $value
     * @param int|null $round
     * @param string $commaSeparator
     * @return float
     */
    public static function toFloat(mixed $value, ?int $round = null, string $commaSeparator = ","): float
    {
        if ($value === null || $value === false) {
            return 0.0;
        }
        if ($value === true) {
            return 1.0;
        }
        if (is_float($value) || is_int($value)) {
            $value = (float)$value;
            return $round !== null ? round($value, $round) : $value;
        }
        if (!is_string($value)) {
            $value = (string)$value;
        }
        $value = preg_replace("~[^-0-9$commaSeparator]~", "", $value);
        $value = (float)str_replace($commaSeparator, ".", $value);
        return $round !== null ? round($value, $round) : $value;
    }

    /**
     * Convert any value to a formated number string
     * An ampty value return an empty string
     * @param mixed $value
     * @param int $decimals
     * @param string $commaSeparator
     * @param string $thousandSeparator
     * @param bool $plusSign Add + sign if value is > 0
     * @return string
     */
    public static function format(
        mixed $value,
        int $decimals = 0,
        string $commaSeparator = ",",
        string $thousandSeparator = ".",
        bool $plusSign = false
    ): string {
        if ($value === '' || $value === null) {
            return '';
        }
        $value = NumberUtils::toFloat($value, $decimals, $commaSeparator);
        $string = number_format($value, $decimals, $commaSeparator, $thousandSeparator);
        if ($plusSign && $value > 0) {
            $string = "+" . $string;
        }
        return $string;
    }
}